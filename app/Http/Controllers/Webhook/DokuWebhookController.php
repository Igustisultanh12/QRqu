<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\DokuWebhook;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Services\Webhook\CustomerWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DokuWebhookController extends Controller
{
    public function __construct(
        protected CustomerWebhookService $customerWebhookService
    ) {}

    /**
     * Menangani callback QRIS dari DOKU Payment Gateway
     * Mengikuti protokol romei 1 / DOKU Production Compliant
     */
    public function handleQrisCallback(Request $request): JsonResponse
    {
        $data = $request->all();

        // 1. Audit Trail: Rekam seluruh payload webhook masuk
        Log::info('DOKU Webhook Masuk (QRqu Gateway):', $data);

        $invoiceNumber = $data['order']['invoice_number'] ?? $data['invoice_number'] ?? null;
        $transactionStatus = strtoupper($data['transaction']['status'] ?? $data['transaction']['state'] ?? $data['status'] ?? '');
        $dokuReference = $data['transaction']['original_request_id'] ?? $data['emoney_payment']['approval_code'] ?? $data['transaction']['id'] ?? $data['reference_id'] ?? 'N/A';
        $eventId = $request->header('Request-Id') ?? ($data['transaction']['original_request_id'] ?? null);

        // Catat ke doku_webhooks
        $webhookLog = DokuWebhook::create([
            'event_id' => $eventId,
            'original_request_id' => $data['transaction']['original_request_id'] ?? null,
            'invoice_number' => $invoiceNumber ?? 'UNKNOWN',
            'payload' => $data,
            'signature' => $request->header('Signature'),
            'http_headers' => $request->headers->all(),
            'is_processed' => false,
        ]);

        if (!$invoiceNumber) {
            Log::error('DOKU Webhook Gagal: Invoice tidak ditemukan dalam payload.');
            return response()->json(['message' => 'Invalid Data Request: missing invoice_number'], 400);
        }

        // 2. Cari Invoice & Transaksi di Database QRqu
        $invoice = Invoice::where('id', $invoiceNumber)->first();
        if (!$invoice) {
            // Fallback: cari dari external_id jika dikirim sebagai invoice_number
            $invoice = Invoice::where('external_id', $invoiceNumber)->first();
        }

        if (!$invoice) {
            Log::error("DOKU Webhook Error: Invoice {$invoiceNumber} tidak terdaftar di QRqu.");
            return response()->json(['message' => 'Transaction/Invoice Not Found'], 404);
        }

        $transaction = $invoice->latestTransaction ?? Transaction::where('invoice_id', $invoice->id)->first();
        if (!$transaction) {
            return response()->json(['message' => 'Transaction record not found'], 404);
        }

        // 3. IDEMPOTENCY CHECK: Jika status di database sudah PAID/SUCCESS, langsung kembalikan OK untuk cegah double processing
        if (in_array(strtoupper($transaction->status), ['SUCCESS', 'PAID'])) {
            Log::info("DOKU Webhook Info: Invoice {$invoiceNumber} sudah lunas sebelumnya (Bypass Success).");
            $webhookLog->update(['is_processed' => true, 'processed_at' => now()]);
            return response()->json(['message' => 'Transaction already processed or finalized'], 200);
        }

        // 4. Update status jika Pembayaran DOKU Sukses
        if (in_array($transactionStatus, ['SUCCESS', 'PAID'])) {
            try {
                DB::transaction(function () use ($transaction, $invoice, $dokuReference, $invoiceNumber, $webhookLog) {
                    // Pessimistic Locking
                    $transaction->lockForUpdate();
                    $invoice->lockForUpdate();

                    $transaction->update([
                        'doku_reference' => $dokuReference,
                        'payment_gateway_ref' => $dokuReference,
                    ]);

                    $transaction->transitionTo('PAID', 'doku_webhook', "Pembayaran terverifikasi via DOKU QRIS (Ref: {$dokuReference})", [
                        'doku_reference' => $dokuReference,
                    ]);

                    $webhookLog->update(['is_processed' => true, 'processed_at' => now()]);

                    AuditLog::record(
                        'PAYMENT_SUCCESS',
                        $transaction,
                        ['status' => 'PENDING'],
                        ['status' => 'PAID', 'doku_reference' => $dokuReference],
                        $transaction->customer?->user_id
                    );
                });

                // Polling Cache untuk modal frontend
                Cache::put('payment_status_' . $invoice->id, 'PAID', 300);

                // Queue Customer Outgoing Webhook
                $this->customerWebhookService->dispatchPaymentEvent($transaction->fresh(), 'payment.paid');

                return response()->json(['message' => 'OK', 'status' => 'PAID'], 200);
            } catch (\Exception $e) {
                Log::critical("DOKU Webhook Runtime Exception QRqu: " . $e->getMessage(), [
                    'invoice' => $invoiceNumber,
                    'trace' => $e->getTraceAsString(),
                ]);

                return response()->json(['message' => 'Internal Server Error during processing'], 500);
            }
        }

        // 5. Penanganan jika Pembayaran Gagal / Expired / Dibatalkan
        if (in_array($transactionStatus, ['FAILED', 'EXPIRED', 'CANCEL', 'VOID'])) {
            DB::transaction(function () use ($transaction, $invoice, $transactionStatus, $webhookLog) {
                $targetStatus = $transactionStatus === 'EXPIRED' ? 'EXPIRED' : 'FAILED';
                $transaction->transitionTo($targetStatus, 'doku_webhook', "DOKU status reported: {$transactionStatus}");
                $webhookLog->update(['is_processed' => true, 'processed_at' => now()]);
            });

            Cache::put('payment_status_' . $invoice->id, $transactionStatus, 300);
            $this->customerWebhookService->dispatchPaymentEvent($transaction->fresh(), 'payment.failed');

            return response()->json(['message' => "Payment marked as {$transactionStatus}"], 200);
        }

        return response()->json(['message' => 'Unhandled payment status state: ' . $transactionStatus], 200);
    }
}
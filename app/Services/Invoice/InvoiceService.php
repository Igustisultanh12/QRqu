<?php

namespace App\Services\Invoice;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Services\Payment\Doku\DokuService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InvoiceService
{
    public function __construct(
        protected DokuService $dokuService
    ) {}

    /**
     * Create invoice with full concurrency and idempotency protections
     */
    public function createInvoice(Customer $customer, array $data): array
    {
        $externalId = trim($data['external_id']);
        $amount = (float) $data['amount'];

        // 1. Redis atomic lock to prevent race condition for same customer + external_id
        $lockKey = "invoice:create:{$customer->id}:{$externalId}";
        $lock = Cache::lock($lockKey, 10);

        try {
            $lock->block(5);

            // 2. Check if invoice with this external_id already exists for this customer
            $existingInvoice = Invoice::where('customer_id', $customer->id)
                ->where('external_id', $externalId)
                ->first();

            if ($existingInvoice) {
                return [
                    'success' => true,
                    'is_duplicate' => true,
                    'invoice' => $existingInvoice,
                ];
            }

            // 3. Determine Expiration Date
            $defaultMinutes = (int) env('QRQU_DEFAULT_EXPIRE_MINUTES', 60);
            $expiredAt = !empty($data['expired_at']) ? Carbon::parse($data['expired_at']) : now()->addMinutes($defaultMinutes);

            // 4. Create internal Invoice and Transaction in Database Transaction
            $invoiceId = Invoice::generateId();
            $transactionId = Transaction::generateId();

            $customerData = $data['customer'] ?? [];

            // Determine Store ID
            $storeId = $data['store_id'] ?? null;
            if (!$storeId && method_exists($customer, 'stores')) {
                $customer->ensureStores();
                $desc = $data['description'] ?? '';
                $matchingStore = $customer->stores()->where(function ($sq) use ($externalId, $desc) {
                    $sq->where(function ($sub) use ($externalId, $desc) {
                        if (str_contains(strtoupper($externalId), 'ROMEI') || str_contains(strtoupper($desc), 'ROMEI')) {
                            $sub->where('code', 'ROMEI')->orWhere('name', 'like', '%ROMEI%');
                        }
                    });
                })->first();

                $storeId = $matchingStore?->id ?? ($customer->defaultStore?->id ?? $customer->stores()->first()?->id);
            }

            $result = DB::transaction(function () use (
                $customer,
                $invoiceId,
                $transactionId,
                $externalId,
                $amount,
                $data,
                $customerData,
                $expiredAt,
                $storeId
            ) {
                $invoice = Invoice::create([
                    'id' => $invoiceId,
                    'customer_id' => $customer->id,
                    'store_id' => $storeId,
                    'external_id' => $externalId,
                    'amount' => $amount,
                    'description' => $data['description'] ?? "Pembayaran Order #{$externalId}",
                    'customer_name' => $customerData['name'] ?? null,
                    'customer_email' => $customerData['email'] ?? null,
                    'customer_phone' => $customerData['phone'] ?? null,
                    'callback_url' => $data['callback_url'] ?? null,
                    'webhook_url' => $data['webhook_url'] ?? null,
                    'status' => 'PENDING',
                    'payment_method' => 'QRIS',
                    'expired_at' => $expiredAt,
                ]);

                $transaction = Transaction::create([
                    'id' => $transactionId,
                    'invoice_id' => $invoiceId,
                    'customer_id' => $customer->id,
                    'store_id' => $storeId,
                    'external_id' => $externalId,
                    'amount' => $amount,
                    'status' => 'CREATED',
                    'previous_status' => null,
                    'status_changed_at' => now(),
                ]);

                $transaction->statusHistories()->create([
                    'from_status' => null,
                    'to_status' => 'CREATED',
                    'trigger' => 'api',
                    'reason' => 'Invoice created via Merchant API',
                    'metadata' => ['external_id' => $externalId],
                    'created_at' => now(),
                ]);

                return ['invoice' => $invoice, 'transaction' => $transaction];
            });

            /** @var Invoice $invoice */
            $invoice = $result['invoice'];
            /** @var Transaction $transaction */
            $transaction = $result['transaction'];

            // 5. Call DOKU Payment Gateway to generate QRIS
            $dokuResult = $this->dokuService->generateQris($transaction, $invoice);

            if ($dokuResult) {
                $paymentUrl = $dokuResult['payment_url'] ?? null;
                $qrString = $dokuResult['qr_string'] ?? null;

                $invoice->update([
                    'qr_url' => $paymentUrl,
                    'qr_string' => $qrString,
                ]);

                $transaction->transitionTo('PENDING', 'doku_service', 'QRIS generated from DOKU', [
                    'request_id' => $dokuResult['request_id'] ?? null,
                ]);

                $transaction->update([
                    'doku_request_id' => $dokuResult['request_id'] ?? null,
                    'doku_response' => $dokuResult['raw_response'] ?? null,
                ]);
            } else {
                Log::warning("QRqu: DOKU QRIS generation returned null for invoice {$invoiceId}");
            }

            return [
                'success' => true,
                'is_duplicate' => false,
                'invoice' => $invoice->fresh(),
            ];
        } catch (\Exception $e) {
            Log::error("QRqu Invoice Creation Error: " . $e->getMessage(), [
                'customer_id' => $customer->id,
                'external_id' => $externalId,
                'trace' => $e->getTraceAsString(),
            ]);

            return [
                'success' => false,
                'error' => [
                    'code' => 'INVOICE_CREATION_FAILED',
                    'message' => 'Failed to create invoice: ' . $e->getMessage(),
                ],
            ];
        } finally {
            optional($lock)->release();
        }
    }

    /**
     * Cancel an existing pending invoice
     */
    public function cancelInvoice(Customer $customer, Invoice $invoice, string $reason = 'Cancelled by merchant'): bool
    {
        if ($invoice->customer_id !== $customer->id) {
            return false;
        }

        if (!$invoice->canBeCancelled()) {
            return false;
        }

        return DB::transaction(function () use ($invoice, $reason) {
            $invoice->lockForUpdate();
            $invoice->update(['status' => 'CANCELLED']);

            foreach ($invoice->transactions as $trx) {
                $trx->transitionTo('CANCELLED', 'api', $reason);
            }

            return true;
        });
    }
}

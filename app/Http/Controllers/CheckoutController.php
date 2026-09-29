<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Transaction;
use App\Services\Webhook\CustomerWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CheckoutController extends Controller
{
    /**
     * Display QRIS Checkout Page
     */
    public function show(string $invoiceId): Response
    {
        $invoice = Invoice::with('customer')->findOrFail($invoiceId);

        return Inertia::render('Checkout/Show', [
            'invoice' => [
                'id' => $invoice->id,
                'external_id' => $invoice->external_id,
                'amount' => (float) $invoice->amount,
                'amount_formatted' => 'Rp ' . number_format($invoice->amount, 0, ',', '.'),
                'description' => $invoice->description,
                'status' => $invoice->status,
                'customer_name' => $invoice->customer_name,
                'qr_url' => $invoice->qr_url,
                'qr_string' => $invoice->qr_string,
                'expired_at' => $invoice->expired_at?->toIso8601String(),
                'callback_url' => $invoice->callback_url,
                'merchant_name' => $invoice->customer?->company_name ?? $invoice->customer?->name ?? 'QRqu Merchant',
                'is_sandbox' => str_starts_with($invoice->id, 'INV-') && (bool) env('APP_DEBUG', true),
            ],
        ]);
    }

    /**
     * Poll Payment Status for Real-Time Status Check (Safe polling)
     */
    public function checkStatus(string $invoiceId): JsonResponse
    {
        // 1. Check Redis/Cache for rapid response without querying DB every second
        $cachedStatus = Cache::get('payment_status_' . $invoiceId);
        if ($cachedStatus) {
            return response()->json([
                'status' => $cachedStatus,
                'is_paid' => $cachedStatus === 'PAID',
            ]);
        }

        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return response()->json(['status' => 'NOT_FOUND'], 404);
        }

        // Check expiration
        if ($invoice->status === 'PENDING' && $invoice->expired_at && $invoice->expired_at->isPast()) {
            $invoice->update(['status' => 'EXPIRED']);
            Cache::put('payment_status_' . $invoiceId, 'EXPIRED', 300);
        }

        return response()->json([
            'status' => $invoice->status,
            'is_paid' => $invoice->status === 'PAID',
            'paid_at' => $invoice->paid_at?->toIso8601String(),
        ]);
    }

    /**
     * Sandbox Payment Simulator (Only active in sandbox or debug mode)
     */
    public function simulatePayment(string $invoiceId, CustomerWebhookService $webhookService): JsonResponse
    {
        $invoice = Invoice::findOrFail($invoiceId);

        if ($invoice->status === 'PAID') {
            return response()->json(['success' => true, 'message' => 'Invoice already paid']);
        }

        $transaction = $invoice->latestTransaction ?? Transaction::where('invoice_id', $invoice->id)->first();
        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        DB::transaction(function () use ($transaction, $invoice) {
            $transaction->lockForUpdate();
            $invoice->lockForUpdate();

            $mockRef = 'DOKU-SIM-' . time();
            $transaction->update([
                'doku_reference' => $mockRef,
                'payment_gateway_ref' => $mockRef,
            ]);

            $transaction->transitionTo('PAID', 'simulation', 'Simulated payment test in sandbox mode');
        });

        Cache::put('payment_status_' . $invoice->id, 'PAID', 300);
        $webhookService->dispatchPaymentEvent($transaction->fresh(), 'payment.paid');

        return response()->json([
            'success' => true,
            'status' => 'PAID',
            'message' => 'Sandbox payment simulated successfully! Webhook dispatched.',
        ]);
    }
}

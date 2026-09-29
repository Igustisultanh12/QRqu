<?php

namespace App\Services\Webhook;

use App\Jobs\SendCustomerWebhookJob;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use Illuminate\Support\Str;

class CustomerWebhookService
{
    /**
     * Dispatch webhook notification to customer for payment events
     */
    public function dispatchPaymentEvent(Transaction $transaction, string $event = 'payment.paid'): ?WebhookDelivery
    {
        $customer = $transaction->customer;
        $invoice = $transaction->invoice;

        // Find customer webhook url (either specified in invoice or in customer's registered webhooks)
        $targetUrl = $invoice->webhook_url;
        $secret = 'whsec_default_fallback';

        if ($customer) {
            $configuredWebhook = $customer->webhooks()->where('is_active', true)->first();
            if ($configuredWebhook) {
                if (empty($targetUrl)) {
                    $targetUrl = $configuredWebhook->url;
                }
                $secret = $configuredWebhook->secret;
            }
        }

        if (empty($targetUrl)) {
            return null;
        }

        $eventId = 'EVT-' . strtoupper(Str::random(24));
        $now = now();

        $payload = [
            'event' => $event,
            'event_id' => $eventId,
            'invoice_id' => $invoice->id,
            'external_id' => $invoice->external_id,
            'transaction_id' => $transaction->id,
            'amount' => (float) $transaction->amount,
            'status' => $transaction->status,
            'payment_method' => $invoice->payment_method ?? 'QRIS',
            'paid_at' => $transaction->status === 'PAID' ? ($invoice->paid_at ? $invoice->paid_at->toIso8601String() : $now->toIso8601String()) : null,
            'timestamp' => $now->timestamp,
        ];

        $rawJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        $signature = hash_hmac('sha256', $rawJson, $secret);

        $delivery = WebhookDelivery::create([
            'customer_id' => $customer->id,
            'transaction_id' => $transaction->id,
            'invoice_id' => $invoice->id,
            'event_id' => $eventId,
            'event' => $event,
            'url' => $targetUrl,
            'payload' => $payload,
            'signature' => $signature,
            'attempt' => 0,
            'max_attempts' => 4,
            'status' => 'PENDING',
        ]);

        SendCustomerWebhookJob::dispatch($delivery->id);

        return $delivery;
    }

    /**
     * Manually trigger re-delivery of a failed webhook
     */
    public function retryDelivery(WebhookDelivery $delivery): bool
    {
        $delivery->update([
            'status' => 'PENDING',
            'attempt' => 0,
            'next_retry_at' => null,
        ]);

        SendCustomerWebhookJob::dispatch($delivery->id);

        return true;
    }
}

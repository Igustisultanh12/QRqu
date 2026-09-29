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

        // Attempt immediate synchronous delivery first for instant callback
        $delivery = $this->executeDelivery($delivery);

        // If initial sync attempt failed and retries remain, dispatch queue job for background retry
        if ($delivery->status !== 'DELIVERED' && $delivery->attempt < $delivery->max_attempts) {
            try {
                SendCustomerWebhookJob::dispatch($delivery->id)->delay(now()->addSeconds(30));
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning("QRqu: Could not queue webhook retry: " . $e->getMessage());
            }
        }

        return $delivery;
    }

    /**
     * Directly execute webhook delivery over HTTP (synchronously)
     */
    public function executeDelivery(WebhookDelivery $delivery): WebhookDelivery
    {
        $newAttempt = (int) $delivery->attempt + 1;
        $delivery->update([
            'attempt' => $newAttempt,
            'status' => 'RETRYING',
        ]);

        $startTime = microtime(true);
        $payloadJson = json_encode($delivery->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-QRQU-Signature' => $delivery->signature,
                'X-QRQU-Timestamp' => gmdate('Y-m-d\TH:i:s') . 'Z',
                'X-QRQU-Event' => $delivery->event,
                'X-QRQU-Event-ID' => $delivery->event_id,
            ])
                ->timeout(10)
                ->withBody($payloadJson, 'application/json')
                ->post($delivery->url);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            if ($response->successful()) {
                $delivery->update([
                    'status' => 'DELIVERED',
                    'http_status' => $response->status(),
                    'response_body' => substr($response->body(), 0, 5000),
                    'duration_ms' => $durationMs,
                    'next_retry_at' => null,
                ]);

                \Illuminate\Support\Facades\Log::info("QRqu: Customer webhook delivered successfully for event {$delivery->event_id} to {$delivery->url}");
            } else {
                $isFinal = $newAttempt >= $delivery->max_attempts;
                $delivery->update([
                    'status' => $isFinal ? 'FAILED' : 'RETRYING',
                    'http_status' => $response->status(),
                    'response_body' => substr($response->body(), 0, 5000),
                    'duration_ms' => $durationMs,
                    'next_retry_at' => !$isFinal ? now()->addSeconds(60) : null,
                ]);
            }
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $isFinal = $newAttempt >= $delivery->max_attempts;

            $delivery->update([
                'status' => $isFinal ? 'FAILED' : 'RETRYING',
                'http_status' => null,
                'response_body' => substr($e->getMessage(), 0, 5000),
                'duration_ms' => $durationMs,
                'next_retry_at' => !$isFinal ? now()->addSeconds(60) : null,
            ]);
        }

        return $delivery->fresh();
    }

    /**
     * Manually trigger re-delivery of a failed webhook
     */
    public function retryDelivery(WebhookDelivery $delivery): WebhookDelivery
    {
        $delivery->update([
            'status' => 'PENDING',
            'next_retry_at' => null,
        ]);

        return $this->executeDelivery($delivery);
    }
}

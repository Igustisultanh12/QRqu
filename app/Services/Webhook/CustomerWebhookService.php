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
        $invoice = $transaction->invoice;
        $customer = $transaction->customer ?? $invoice?->customer;
        if (!$customer && $transaction->customer_id) {
            $customer = Customer::find($transaction->customer_id);
        }
        if (!$customer && $invoice && $invoice->customer_id) {
            $customer = Customer::find($invoice->customer_id);
        }
        if (!$customer) {
            $customer = Customer::first();
        }

        $destinations = collect();

        if ($customer) {
            $configuredWebhooks = $customer->webhooks()
                ->where('is_active', true)
                ->where(function ($q) use ($transaction) {
                    $q->whereNull('store_id');
                    if ($transaction->store_id) {
                        $q->orWhere('store_id', $transaction->store_id);
                    }
                })
                ->get();

            foreach ($configuredWebhooks as $wh) {
                if (!empty($wh->url)) {
                    $destinations->push([
                        'url' => $wh->url,
                        'secret' => $wh->secret ?: 'whsec_default_fallback',
                    ]);
                }
            }
        }

        // Check if invoice has custom webhook_url not already in destinations
        if (!empty($invoice?->webhook_url) && !$destinations->contains('url', $invoice->webhook_url)) {
            $activeCredential = $customer?->apiCredentials()->where('status', 'ACTIVE')->first();
            $customSecret = $activeCredential ? ($activeCredential->getDecryptedSecret() ?: 'whsec_default_fallback') : 'whsec_default_fallback';
            $destinations->push([
                'url' => $invoice->webhook_url,
                'secret' => $customSecret,
            ]);
        }

        if ($destinations->isEmpty()) {
            \Illuminate\Support\Facades\Log::info("QRqu: Webhook tidak dikirim karena belum ada URL webhook yang aktif untuk transaksi #{$transaction->id}.");
            return null;
        }

        $now = now();
        $lastDelivery = null;

        foreach ($destinations as $dest) {
            $eventId = 'EVT-' . strtoupper(Str::random(24));
            $payload = [
                'event' => $event,
                'event_id' => $eventId,
                'invoice_id' => $invoice?->id,
                'external_id' => $invoice?->external_id,
                'transaction_id' => $transaction->id,
                'store_id' => $transaction->store_id,
                'amount' => (float) $transaction->amount,
                'status' => $transaction->status,
                'doku_reference' => $transaction->doku_reference,
                'payment_method' => $invoice?->payment_method ?? 'QRIS',
                'paid_at' => $transaction->status === 'PAID' ? ($invoice?->paid_at ? $invoice->paid_at->toIso8601String() : $now->toIso8601String()) : null,
                'timestamp' => $now->timestamp,
            ];

            $rawJson = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $signature = hash_hmac('sha256', $rawJson, $dest['secret']);

            $delivery = WebhookDelivery::create([
                'customer_id' => $customer?->id,
                'transaction_id' => $transaction->id,
                'invoice_id' => $invoice?->id,
                'event_id' => $eventId,
                'event' => $event,
                'url' => $dest['url'],
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

            $lastDelivery = $delivery;
        }

        return $lastDelivery;
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
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders([
                    'Content-Type' => 'application/json',
                    'X-QRQU-Signature' => $delivery->signature,
                    'X-Signature' => $delivery->signature,
                    'Signature' => $delivery->signature,
                    'X-QRQU-Timestamp' => gmdate('Y-m-d\TH:i:s') . 'Z',
                    'X-QRQU-Event' => $delivery->event,
                    'X-QRQU-Event-ID' => $delivery->event_id,
                ])
                ->timeout(12)
                ->withBody($payloadJson, 'application/json')
                ->post($delivery->url);

            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $rawBody = trim((string) $response->body());

            if ($response->successful()) {
                $delivery->update([
                    'status' => 'DELIVERED',
                    'http_status' => $response->status(),
                    'response_body' => $rawBody !== '' ? substr($rawBody, 0, 5000) : 'HTTP ' . $response->status() . ' OK',
                    'duration_ms' => $durationMs,
                    'next_retry_at' => null,
                ]);

                \Illuminate\Support\Facades\Log::info("QRqu: Customer webhook delivered successfully for event {$delivery->event_id} to {$delivery->url}");
            } else {
                $isFinal = $newAttempt >= $delivery->max_attempts;
                $delivery->update([
                    'status' => $isFinal ? 'FAILED' : 'RETRYING',
                    'http_status' => $response->status(),
                    'response_body' => $rawBody !== '' ? substr($rawBody, 0, 5000) : ('HTTP ' . $response->status() . ' ' . ($response->reason() ?: 'Error')),
                    'duration_ms' => $durationMs,
                    'next_retry_at' => !$isFinal ? now()->addSeconds(60) : null,
                ]);

                \Illuminate\Support\Facades\Log::warning("QRqu: Customer webhook delivery failed (HTTP {$response->status()}) for event {$delivery->event_id}: {$rawBody}");
            }
        } catch (\Throwable $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);
            $isFinal = $newAttempt >= $delivery->max_attempts;

            $delivery->update([
                'status' => $isFinal ? 'FAILED' : 'RETRYING',
                'http_status' => null,
                'response_body' => 'Gagal Terhubung: ' . substr($e->getMessage(), 0, 5000),
                'duration_ms' => $durationMs,
                'next_retry_at' => !$isFinal ? now()->addSeconds(60) : null,
            ]);

            \Illuminate\Support\Facades\Log::error("QRqu: Customer webhook connection exception for event {$delivery->event_id}: " . $e->getMessage());
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

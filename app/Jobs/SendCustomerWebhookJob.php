<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class SendCustomerWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 4;
    public $backoff = [30, 60, 300, 900]; // 30s, 1m, 5m, 15m

    public function __construct(
        public int $deliveryId
    ) {}

    public function handle(): void
    {
        $delivery = WebhookDelivery::find($this->deliveryId);
        if (!$delivery) {
            return;
        }

        // If already delivered, skip
        if ($delivery->status === 'DELIVERED') {
            return;
        }

        $delivery->update(['attempt' => $this->attempts(), 'status' => 'RETRYING']);

        $startTime = microtime(true);
        $payloadJson = json_encode($delivery->payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-QRQU-Signature' => $delivery->signature,
                'X-QRQU-Timestamp' => gmdate('Y-m-d\TH:i:s') . 'Z',
                'X-QRQU-Event' => $delivery->event,
                'X-QRQU-Event-ID' => $delivery->event_id,
            ])
                ->timeout(15)
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

                Log::info("QRqu: Customer webhook delivered successfully for event {$delivery->event_id} to {$delivery->url}");
            } else {
                $delivery->update([
                    'status' => $this->attempts() >= $this->tries ? 'FAILED' : 'RETRYING',
                    'http_status' => $response->status(),
                    'response_body' => substr($response->body(), 0, 5000),
                    'duration_ms' => $durationMs,
                    'next_retry_at' => $this->attempts() < $this->tries ? now()->addSeconds($this->backoff[$this->attempts() - 1] ?? 60) : null,
                ]);

                $this->fail(new \Exception("Webhook returned HTTP {$response->status()}"));
            }
        } catch (\Exception $e) {
            $durationMs = (int) round((microtime(true) - $startTime) * 1000);

            $delivery->update([
                'status' => $this->attempts() >= $this->tries ? 'FAILED' : 'RETRYING',
                'response_body' => substr($e->getMessage(), 0, 5000),
                'duration_ms' => $durationMs,
                'next_retry_at' => $this->attempts() < $this->tries ? now()->addSeconds($this->backoff[$this->attempts() - 1] ?? 60) : null,
            ]);

            throw $e;
        }
    }
}

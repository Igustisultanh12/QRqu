<?php

namespace App\Jobs;

use App\Models\WebhookDelivery;
use App\Services\Webhook\CustomerWebhookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendCustomerWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 4;
    public $backoff = [30, 60, 300, 900]; // 30s, 1m, 5m, 15m

    public function __construct(
        public int $deliveryId
    ) {}

    public function handle(CustomerWebhookService $service): void
    {
        $delivery = WebhookDelivery::find($this->deliveryId);
        if (!$delivery || $delivery->status === 'DELIVERED') {
            return;
        }

        $delivery = $service->executeDelivery($delivery);

        if ($delivery->status !== 'DELIVERED') {
            if ($this->attempts() < $this->tries) {
                $this->release($this->backoff[$this->attempts() - 1] ?? 60);
            }
        }
    }
}


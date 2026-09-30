<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\Webhook\CustomerWebhookService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ExpireInvoicesCommand extends Command
{
    protected $signature = 'qrqu:expire-invoices';
    protected $description = 'Expire all pending invoices that have exceeded their expiration timestamp';

    public function handle(CustomerWebhookService $webhookService): int
    {
        $now = now();
        $expiredInvoices = Invoice::where('status', 'PENDING')
            ->where(function ($q) use ($now) {
                $q->where('expired_at', '<=', $now)
                    ->orWhere('created_at', '<=', $now->copy()->subMinutes(60));
            })
            ->with(['latestTransaction', 'customer'])
            ->limit(100)
            ->get();

        $count = 0;

        foreach ($expiredInvoices as $invoice) {
            DB::transaction(function () use ($invoice, $webhookService, &$count) {
                $invoice->lockForUpdate();

                // Re-verify status under lock
                if ($invoice->status !== 'PENDING') {
                    return;
                }

                $invoice->update(['status' => 'EXPIRED']);

                if ($invoice->latestTransaction) {
                    $invoice->latestTransaction->transitionTo('EXPIRED', 'scheduler', 'Transaksi kadaluarsa / dibatalkan otomatis oleh sistem (melebihi batas waktu 1 jam)');
                    $webhookService->dispatchPaymentEvent($invoice->latestTransaction->fresh(), 'payment.expired');
                }

                \App\Models\Subscription::where('invoice_id', $invoice->id)
                    ->where('status', 'pending_payment')
                    ->update(['status' => 'cancelled']);

                Cache::put('payment_status_' . $invoice->id, 'EXPIRED', 300);
                $count++;
            });
        }

        if ($count > 0) {
            $this->info("Successfully expired {$count} overdue invoice(s).");
            Log::info("QRqu Scheduler: Expired {$count} invoice(s).");
        }

        return self::SUCCESS;
    }
}

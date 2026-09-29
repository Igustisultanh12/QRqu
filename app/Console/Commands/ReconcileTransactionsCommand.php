<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\Payment\Doku\DokuService;
use App\Services\Webhook\CustomerWebhookService;
use Illuminate\Console\Command;

class ReconcileTransactionsCommand extends Command
{
    protected $signature = 'qrqu:sync-pending-doku {--limit=50 : Jumlah transaksi pending yang diperiksa} {--date= : Tanggal transaksi yang direkonsiliasi}';
    protected $aliases = ['qrqu:reconcile'];
    protected $description = 'Proactively sync pending QRqu transactions with DOKU payment gateway';

    public function handle(DokuService $doku, CustomerWebhookService $webhookService): int
    {
        $limit = (int) ($this->option('limit') ?? 50);
        $this->info("Scanning pending transactions to sync with DOKU Live API (Limit: {$limit})...");

        // Ambil transaksi PENDING yang dibuat dalam 48 jam terakhir
        $pendingTransactions = Transaction::where('status', 'PENDING')
            ->where('created_at', '>=', now()->subHours(48))
            ->with(['invoice', 'customer'])
            ->latest()
            ->limit($limit)
            ->get();

        $synced = 0;
        $stillPending = 0;

        foreach ($pendingTransactions as $trx) {
            $result = $doku->syncTransactionWithDoku($trx, $webhookService);
            if ($result['is_paid']) {
                $this->info("[PAID] Transaksi #{$trx->id} (Invoice: {$trx->invoice_id}) telah diverifikasi DOKU: Ref {$result['doku_reference']}");
                $synced++;
            } else {
                $stillPending++;
            }
        }

        $this->info("DOKU Sync complete. Total diperiksa: " . count($pendingTransactions) . ", Berhasil Lunas: {$synced}, Masih Pending: {$stillPending}");
        return self::SUCCESS;
    }
}

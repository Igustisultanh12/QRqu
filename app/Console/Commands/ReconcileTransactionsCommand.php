<?php

namespace App\Console\Commands;

use App\Models\Transaction;
use App\Services\Payment\Doku\DokuService;
use Illuminate\Console\Command;

class ReconcileTransactionsCommand extends Command
{
    protected $signature = 'qrqu:reconcile {--date= : Specific date (YYYY-MM-DD) to reconcile}';
    protected $description = 'Reconcile QRqu transactions with DOKU payment gateway status';

    public function handle(DokuService $doku): int
    {
        $date = $this->option('date') ?? now()->toDateString();
        $this->info("Running transaction reconciliation for date: {$date}...");

        $transactions = Transaction::whereDate('created_at', $date)->get();
        $matched = 0;
        $mismatches = 0;

        foreach ($transactions as $trx) {
            if ($trx->status === 'PAID') {
                $matched++;
            } elseif ($trx->status === 'PENDING' && $trx->doku_reference) {
                $check = $doku->verifyPayment($trx->doku_reference);
                if (($check['status'] ?? null) === 'SUCCESS') {
                    $trx->transitionTo('PAID', 'reconciliation', 'Reconciled via automated check');
                    $mismatches++;
                } else {
                    $matched++;
                }
            } else {
                $matched++;
            }
        }

        $this->info("Reconciliation complete. Matched: {$matched}, Mismatches resolved: {$mismatches}");
        return self::SUCCESS;
    }
}

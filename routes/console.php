<?php

use App\Console\Commands\ApiCheckCommand;
use App\Console\Commands\ExpireInvoicesCommand;
use App\Console\Commands\ReconcileTransactionsCommand;
use App\Services\Payment\Doku\DokuService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/**
 * Command: Artisan qrqu:api-check (and alias romei:api-check)
 */
Artisan::command('romei:api-check', function (DokuService $doku) {
    $this->info('Checking DOKU Gateway Connection (romei compatibility mode)...');
    try {
        $dokuStatus = $doku->verifyPayment('PING-TEST');
        $this->info('[OK] API DOKU: Connected (' . ($dokuStatus['status'] ?? 'SUCCESS') . ')');
    } catch (\Exception $e) {
        $this->error('[FAIL] API DOKU: Disconnected (' . $e->getMessage() . ')');
    }
})->purpose('Check connection status for DOKU API');

/*
|--------------------------------------------------------------------------
| Task Scheduling (Laravel Scheduler)
|--------------------------------------------------------------------------
*/
Schedule::command(ExpireInvoicesCommand::class)->everyMinute()->withoutOverlapping();
Schedule::command(ReconcileTransactionsCommand::class)->everyMinute()->withoutOverlapping();
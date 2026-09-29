<?php

namespace App\Console\Commands;

use App\Services\Payment\Doku\DokuService;
use Illuminate\Console\Command;

class ApiCheckCommand extends Command
{
    protected $signature = 'qrqu:api-check';
    protected $description = 'Check connection status for DOKU QRIS Payment Gateway';

    public function handle(DokuService $doku): int
    {
        $this->info('Checking QRqu Payment Gateway Connections...');

        $config = $doku->getConfig();
        $this->line('DOKU Client ID: ' . (empty($config['client_id']) ? '<Not Configured (Simulation Mode)>' : $config['client_id']));
        $this->line('DOKU Base URL: ' . $config['base_url']);

        try {
            $result = $doku->verifyPayment('PING-TEST');
            $this->info('[OK] DOKU Gateway Status: ' . ($result['status'] ?? 'CONNECTED'));
            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error('[FAIL] DOKU Gateway: Disconnected (' . $e->getMessage() . ')');
            return self::FAILURE;
        }
    }
}

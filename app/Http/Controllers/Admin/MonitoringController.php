<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WebhookDelivery;
use App\Services\Payment\Doku\DokuService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class MonitoringController extends Controller
{
    public function index(DokuService $dokuService): Response
    {
        $dbConnected = false;
        try {
            DB::connection()->getPdo();
            $dbConnected = true;
        } catch (\Exception $e) {}

        $cacheOk = false;
        try {
            Cache::put('monitor_test', true, 10);
            $cacheOk = Cache::get('monitor_test') === true;
        } catch (\Exception $e) {}

        $dokuPing = $dokuService->verifyPayment('MONITOR-PING');

        $pendingWebhooks = WebhookDelivery::where('status', 'PENDING')->count();
        $retryingWebhooks = WebhookDelivery::where('status', 'RETRYING')->count();
        $failedWebhooks = WebhookDelivery::where('status', 'FAILED')->count();

        return Inertia::render('Admin/Monitoring/Index', [
            'health' => [
                'database' => $dbConnected,
                'cache' => $cacheOk,
                'doku_api' => ($dokuPing['status'] ?? null) !== 'ERROR',
                'php_version' => PHP_VERSION,
                'memory_usage' => round(memory_get_usage(true) / 1024 / 1024, 2) . ' MB',
                'queue_status' => [
                    'pending_webhooks' => $pendingWebhooks,
                    'retrying_webhooks' => $retryingWebhooks,
                    'failed_webhooks' => $failedWebhooks,
                ],
            ],
        ]);
    }
}

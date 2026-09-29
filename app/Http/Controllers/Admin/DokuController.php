<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\SystemSetting;
use App\Services\Payment\Doku\DokuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DokuController extends Controller
{
    public function index(DokuService $dokuService): Response
    {
        $config = $dokuService->getConfig();
        $maskedSecret = !empty($config['secret_key']) ? substr($config['secret_key'], 0, 4) . '****************' . substr($config['secret_key'], -4) : '';

        return Inertia::render('Admin/Doku/Index', [
            'config' => [
                'client_id' => $config['client_id'],
                'secret_key_masked' => $maskedSecret,
                'has_secret' => !empty($config['secret_key']),
                'base_url' => $config['base_url'],
                'environment' => env('DOKU_ENVIRONMENT', 'sandbox'),
                'webhook_url' => url('/api/v1/webhook/doku/qris'),
                'timeout_seconds' => 15,
                'retry_limit' => 3,
            ],
            'test_result' => session('doku_test_result'),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            'client_id' => 'required|string',
            'secret_key' => 'nullable|string',
            'base_url' => 'required|url',
        ]);

        SystemSetting::set('doku_client_id', trim($request->input('client_id')), 'doku', 'DOKU Merchant Client ID');
        SystemSetting::set('doku_api_base_url', rtrim(trim($request->input('base_url')), '/'), 'doku', 'DOKU API Base URL');

        if ($request->filled('secret_key')) {
            SystemSetting::set('doku_secret_key', trim($request->input('secret_key')), 'doku', 'DOKU Secret Key');
        }

        AuditLog::record('UPDATE_DOKU_CONFIG', null, null, [
            'client_id' => $request->input('client_id'),
            'base_url' => $request->input('base_url'),
        ]);

        return redirect()->back()->with('success', 'Konfigurasi DOKU berhasil disimpan!');
    }

    public function testConnection(DokuService $dokuService): RedirectResponse
    {
        $result = $dokuService->verifyPayment('PING-TEST');

        return redirect()->back()->with('doku_test_result', [
            'success' => ($result['status'] ?? null) !== 'ERROR',
            'message' => ($result['status'] ?? null) === 'ERROR' ? 'Koneksi gagal: ' . ($result['error'] ?? 'Unknown') : 'Koneksi DOKU berhasil diverifikasi!',
            'timestamp' => now()->format('Y-m-d H:i:s'),
        ]);
    }
}

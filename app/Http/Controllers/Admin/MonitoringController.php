<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Transaction;
use App\Models\User;
use App\Models\WebhookDelivery;
use App\Services\Payment\Doku\DokuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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

    /**
     * METHOD LIVE CHECKOUT (SINKRON 100% DENGAN PROTOKOL ROMEI 1)
     */
    public function testPayment(Request $request, DokuService $dokuService): JsonResponse
    {
        $request->validate([
            'amount' => 'required|numeric|min:1',
            'payment_method' => 'nullable|string'
        ]);

        try {
            $invoiceId = 'INV-QRQU-' . now()->format('YmdHis') . '-' . strtoupper(Str::random(4));
            $amount = (int) $request->amount;
            if ($amount < 1000) {
                $amount = 1000;
            }

            // Dapatkan user aktif dari session atau fallback ke admin user pertama
            $user = auth()->user() ?? User::where('role', 'admin')->first() ?? User::first();
            $userId = $user?->id;
            $userEmail = $user?->email ?? 'admin@qrqu.id';
            $userName = $user?->name ?? 'Master Administrator';

            // Dapatkan customer terdaftar atau buat profil customer internal
            $customer = null;
            if ($user && method_exists($user, 'customer')) {
                $customer = $user->customer;
            }
            if (!$customer && $userId) {
                $customer = Customer::where('user_id', $userId)->first();
            }
            if (!$customer) {
                $customer = Customer::where('email', $userEmail)->first() ?? Customer::first();
            }

            if (!$customer) {
                if (!$user) {
                    $user = User::firstOrCreate(
                        ['email' => 'admin@qrqu.id'],
                        [
                            'name' => 'Master Administrator',
                            'password' => bcrypt(Str::random(16)),
                            'role' => 'admin',
                            'status' => 'active',
                        ]
                    );
                    $userId = $user->id;
                    $userName = $user->name;
                    $userEmail = $user->email;
                }

                $customer = Customer::firstOrCreate(
                    ['email' => $userEmail],
                    [
                        'user_id' => $userId,
                        'name' => $userName,
                        'company_name' => 'QRqu Platform',
                        'phone' => '081234567890',
                        'status' => 'active',
                    ]
                );
            }

            $externalId = 'TEST-' . strtoupper(Str::random(8));

            $invoice = Invoice::create([
                'id' => $invoiceId,
                'customer_id' => $customer->id,
                'external_id' => $externalId,
                'amount' => $amount,
                'description' => 'Uji Coba Gate Transaksi DOKU (Production Page)',
                'customer_name' => $customer->name ?? $userName,
                'customer_email' => $customer->email ?? $userEmail,
                'customer_phone' => $customer->phone ?? '081234567890',
                'status' => 'PENDING',
                'expired_at' => now()->addMinutes(60),
                'callback_url' => url('/dashboard'),
            ]);

            $transaction = Transaction::create([
                'id' => Transaction::generateId(),
                'customer_id' => $customer->id,
                'invoice_id' => $invoice->id,
                'external_id' => $externalId,
                'amount' => $amount,
                'status' => 'PENDING',
            ]);

            $dokuResult = $dokuService->generateQris($transaction, $invoice);
            $paymentUrl = $dokuResult['payment_url'] ?? null;

            if ($paymentUrl) {
                $invoice->update(['qr_url' => $paymentUrl, 'qr_string' => $dokuResult['qr_string'] ?? null]);
                Cache::put('payment_status_' . $invoiceId, 'PENDING', 600);

                AuditLog::record(
                    'TEST_PAYMENT_CREATED',
                    $user,
                    null,
                    [
                        'invoice_id' => $invoiceId,
                        'amount' => $amount,
                        'payment_url' => $paymentUrl,
                    ],
                    $userId
                );

                return response()->json([
                    'status'      => 'success',
                    'success'     => true,
                    'message'     => 'Koneksi Sukses! Halaman Invoice Pembayaran Berhasil Diterbitkan Resmi Oleh DOKU.',
                    'invoice_id'  => $invoiceId,
                    'payment_url' => $paymentUrl,
                    'amount'      => $amount,
                    'qr_string'   => $invoice->qr_string,
                ]);
            }

            return response()->json([
                'status'  => 'error',
                'success' => false,
                'message' => 'DOKU Live Gateway menolak payload mas. Silakan periksa kredensial Merchant ID & Secret Key di Pengaturan.'
            ], 400);

        } catch (\Exception $e) {
            Log::error('QRqu Test Payment Exception: ' . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'success' => false,
                'message' => 'Sistem QRqu mendeteksi gangguan jembatan data internal: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * ENDPOINT POLLING STATUS TRANSAKSI (Sesuai Romei 1)
     */
    public function checkStatus(string $invoiceId, DokuService $dokuService): JsonResponse
    {
        $status = Cache::get('payment_status_' . $invoiceId, 'PENDING');

        if ($status === 'SUCCESS' || $status === 'PAID') {
            return response()->json([
                'status' => 'success',
                'payment_status' => 'SUCCESS',
                'is_paid' => true,
            ]);
        }

        $invoice = Invoice::find($invoiceId);
        if ($invoice) {
            if ($invoice->status === 'PAID') {
                Cache::put('payment_status_' . $invoiceId, 'SUCCESS', 300);
                return response()->json([
                    'status' => 'success',
                    'payment_status' => 'SUCCESS',
                    'is_paid' => true,
                ]);
            }

            // Verify with DOKU
            $dokuStatus = $dokuService->verifyPayment($invoice->id);
            if (isset($dokuStatus['transaction']['status']) && strtoupper($dokuStatus['transaction']['status']) === 'SUCCESS') {
                $invoice->update(['status' => 'PAID', 'paid_at' => now()]);
                $transaction = $invoice->latestTransaction;
                if ($transaction) {
                    $transaction->update(['status' => 'PAID']);
                }
                Cache::put('payment_status_' . $invoiceId, 'SUCCESS', 300);

                return response()->json([
                    'status' => 'success',
                    'payment_status' => 'SUCCESS',
                    'is_paid' => true,
                ]);
            }
        }

        return response()->json([
            'status' => 'pending',
            'payment_status' => 'PENDING',
            'is_paid' => false,
        ]);
    }

    /**
     * ENDPOINT SIMULASI PEMBAYARAN LUNAS (Untuk testing)
     */
    public function simulatePayment(string $invoiceId): JsonResponse
    {
        $invoice = Invoice::find($invoiceId);
        if ($invoice) {
            $invoice->update(['status' => 'PAID', 'paid_at' => now()]);
            $transaction = $invoice->latestTransaction;
            if ($transaction) {
                $transaction->update(['status' => 'PAID']);
            }
        }

        Cache::put('payment_status_' . $invoiceId, 'SUCCESS', 300);
        Cache::put('payment_status_' . $invoiceId, 'PAID', 300);

        return response()->json([
            'status' => 'success',
            'success' => true,
            'payment_status' => 'SUCCESS',
            'message' => 'Simulasi pembayaran berhasil.',
        ]);
    }
}

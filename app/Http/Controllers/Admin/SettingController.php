<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Services\Payment\Doku\DokuService;
use App\Services\Webhook\CustomerWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SettingController extends Controller
{
    public function index(): Response
    {
        $dokuSecret = SystemSetting::get('doku_secret_key', env('DOKU_SECRET_KEY', ''));
        $mailPassword = SystemSetting::get('mail_password', config('mail.mailers.smtp.password', ''));

        $settings = [
            // General Platform
            'app_name' => SystemSetting::get('app_name', config('app.name', 'QRqu')),
            'app_url' => SystemSetting::get('app_url', config('app.url', 'https://qrqu.id')),
            'timezone' => SystemSetting::get('timezone', config('app.timezone', 'Asia/Jakarta')),
            'currency' => SystemSetting::get('currency', 'IDR'),
            'maintenance_mode' => SystemSetting::get('maintenance_mode', 'false') === 'true',

            // API & Operations
            'default_expire_minutes' => (int) SystemSetting::get('default_expire_minutes', 60),
            'api_timestamp_tolerance' => (int) SystemSetting::get('api_timestamp_tolerance', 300),
            'webhook_max_retries' => (int) SystemSetting::get('webhook_max_retries', 4),
            'inbound_webhook_url' => url('/api/webhooks/doku'),

            // Pricing & Quota
            'monthly_price' => (float) SystemSetting::get('monthly_price', 150000),
            'monthly_quota' => (int) SystemSetting::get('monthly_quota', 1000),

            // DOKU Payment Gateway API
            'doku_client_id' => SystemSetting::get('doku_client_id', env('DOKU_CLIENT_ID', '')),
            'doku_secret_key' => $dokuSecret ? substr($dokuSecret, 0, 4) . '****************' . substr($dokuSecret, -4) : '',
            'doku_has_secret' => !empty($dokuSecret),
            'doku_base_url' => SystemSetting::get('doku_api_base_url', SystemSetting::get('doku_base_url', env('DOKU_BASE_URL', 'https://api-sandbox.doku.com'))),
            'doku_environment' => SystemSetting::get('doku_environment', env('DOKU_ENVIRONMENT', 'sandbox')),

            // Mail Gateway (SMTP)
            'mail_mailer' => SystemSetting::get('mail_mailer', config('mail.default', 'smtp')),
            'mail_host' => SystemSetting::get('mail_host', config('mail.mailers.smtp.host', 'smtp.mailtrap.io')),
            'mail_port' => (int) SystemSetting::get('mail_port', config('mail.mailers.smtp.port', 587)),
            'mail_username' => SystemSetting::get('mail_username', config('mail.mailers.smtp.username', '')),
            'mail_password' => $mailPassword ? '••••••••••••' : '',
            'mail_has_password' => !empty($mailPassword),
            'mail_encryption' => SystemSetting::get('mail_encryption', config('mail.mailers.smtp.encryption', 'tls')),
            'mail_from_address' => SystemSetting::get('mail_from_address', config('mail.from.address', 'no-reply@qrqu.id')),
            'mail_from_name' => SystemSetting::get('mail_from_name', config('mail.from.name', 'QRqu Payment Gateway')),
        ];

        return Inertia::render('Admin/Settings/Index', [
            'settings' => $settings,
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $request->validate([
            // General
            'app_name' => 'required|string|max:100',
            'app_url' => 'required|url|max:255',
            'timezone' => 'required|string|max:50',
            'currency' => 'required|string|max:10',
            'maintenance_mode' => 'required|boolean',

            // API & Operations
            'default_expire_minutes' => 'required|integer|min:5|max:1440',
            'api_timestamp_tolerance' => 'required|integer|min:30|max:1800',
            'webhook_max_retries' => 'required|integer|min:1|max:10',

            // Pricing & Quota
            'monthly_price' => 'required|numeric|min:0',
            'monthly_quota' => 'required|integer|min:1',

            // DOKU API
            'doku_client_id' => 'nullable|string|max:100',
            'doku_secret_key' => 'nullable|string|max:255',
            'doku_base_url' => 'nullable|url|max:255',
            'doku_environment' => 'nullable|in:sandbox,production',

            // Mail Gateway
            'mail_mailer' => 'required|string|in:smtp,sendmail,log',
            'mail_host' => 'nullable|string|max:150',
            'mail_port' => 'nullable|integer|min:1|max:65535',
            'mail_username' => 'nullable|string|max:150',
            'mail_password' => 'nullable|string|max:255',
            'mail_encryption' => 'nullable|string|in:tls,ssl,none',
            'mail_from_address' => 'required|email|max:150',
            'mail_from_name' => 'required|string|max:150',
        ]);

        // 1. Simpan General & API Settings
        foreach ($request->only([
            'app_name', 'app_url', 'timezone', 'currency', 'default_expire_minutes',
            'api_timestamp_tolerance', 'webhook_max_retries',
            'monthly_price', 'monthly_quota'
        ]) as $key => $val) {
            SystemSetting::set($key, (string) $val, 'general');
        }

        SystemSetting::set('maintenance_mode', $request->boolean('maintenance_mode') ? 'true' : 'false', 'general');

        // 2. Sinkronisasi ke starter plan bulanan
        $starterPlan = Plan::where('slug', 'monthly-30d')
            ->orWhere('slug', 'starter-30d')
            ->orWhere('duration_days', 30)
            ->first();

        if ($starterPlan) {
            $starterPlan->update([
                'price' => (float) $request->input('monthly_price'),
                'transaction_limit' => (int) $request->input('monthly_quota'),
            ]);
        }

        // 3. Simpan DOKU Gateway API Configuration
        if ($request->filled('doku_client_id')) {
            SystemSetting::set('doku_client_id', trim($request->input('doku_client_id')), 'doku');
        }

        if ($request->filled('doku_base_url')) {
            $cleanBaseUrl = rtrim(trim($request->input('doku_base_url')), '/');
            SystemSetting::set('doku_base_url', $cleanBaseUrl, 'doku');
            SystemSetting::set('doku_api_base_url', $cleanBaseUrl, 'doku');
        }

        if ($request->filled('doku_environment')) {
            SystemSetting::set('doku_environment', $request->input('doku_environment'), 'doku');
        }

        $dokuSecret = $request->input('doku_secret_key');
        if (!empty($dokuSecret) && !str_contains($dokuSecret, '***')) {
            SystemSetting::set('doku_secret_key', trim($dokuSecret), 'doku');
        }

        // 4. Simpan Mail Gateway (SMTP) Configuration
        foreach ($request->only([
            'mail_mailer', 'mail_host', 'mail_port', 'mail_username',
            'mail_encryption', 'mail_from_address', 'mail_from_name'
        ]) as $key => $val) {
            SystemSetting::set($key, (string) ($val ?? ''), 'mail');
        }

        $mailPassword = $request->input('mail_password');
        if (!empty($mailPassword) && $mailPassword !== '••••••••••••') {
            SystemSetting::set('mail_password', $mailPassword, 'mail');
        }

        AuditLog::record('UPDATE_SYSTEM_SETTINGS', null, null, $request->except(['doku_secret_key', 'mail_password']));

        return redirect()->back()->with('success', 'Semua pengaturan platform, DOKU API, dan Mail Gateway berhasil disimpan!');
    }

    public function testMail(Request $request): RedirectResponse
    {
        $request->validate([
            'test_email' => 'required|email',
        ]);

        $recipient = $request->input('test_email');

        try {
            // Terapkan konfigurasi database secara real-time
            $mailer = SystemSetting::get('mail_mailer', config('mail.default', 'smtp'));
            $host = SystemSetting::get('mail_host', config('mail.mailers.smtp.host'));
            $port = (int) SystemSetting::get('mail_port', config('mail.mailers.smtp.port', 587));
            $encryption = SystemSetting::get('mail_encryption', config('mail.mailers.smtp.encryption', 'tls'));
            $username = SystemSetting::get('mail_username', config('mail.mailers.smtp.username'));
            $password = SystemSetting::get('mail_password', config('mail.mailers.smtp.password'));
            $fromAddress = SystemSetting::get('mail_from_address', config('mail.from.address', 'no-reply@qrqu.id'));
            $fromName = SystemSetting::get('mail_from_name', config('mail.from.name', 'QRqu Payment Gateway'));

            config([
                'mail.default'                 => $mailer,
                'mail.mailers.smtp.host'       => $host,
                'mail.mailers.smtp.port'       => $port,
                'mail.mailers.smtp.encryption' => $encryption === 'none' ? null : $encryption,
                'mail.mailers.smtp.username'   => $username,
                'mail.mailers.smtp.password'   => $password,
                'mail.from.address'            => $fromAddress,
                'mail.from.name'               => $fromName,
            ]);

            Mail::raw("Halo Administrator!\n\nIni adalah pesan konfirmasi bahwa Mail Gateway (SMTP) QRqu Payment Gateway telah terhubung dan berfungsi dengan sangat baik.\n\nDetail Gateway:\n- Host: {$host}:{$port}\n- Pengirim: {$fromName} <{$fromAddress}>\n- Waktu Uji Coba: " . now()->toDateTimeString(), function ($message) use ($recipient, $fromAddress, $fromName) {
                $message->to($recipient)
                    ->from($fromAddress, $fromName)
                    ->subject('[QRqu] Uji Coba Konfigurasi Mail Gateway Berhasil');
            });

            AuditLog::record('TEST_MAIL_GATEWAY', null, null, ['recipient' => $recipient]);

            return redirect()->back()->with('success', "Email uji coba berhasil dikirim ke {$recipient}!");
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', "Gagal mengirim email uji coba: " . $e->getMessage());
        }
    }

    public function testDoku(DokuService $dokuService): RedirectResponse
    {
        $result = $dokuService->verifyPayment('PING-TEST-' . time());

        if (($result['status'] ?? '') === 'ERROR') {
            return redirect()->back()->with('error', 'Koneksi DOKU Gagal: ' . ($result['error'] ?? 'Unreachable'));
        }

        return redirect()->back()->with('success', 'Koneksi API DOKU Berhasil! Status: ' . ($result['status'] ?? 'CONNECTED'));
    }

    public function createTestPayment(Request $request, DokuService $dokuService): JsonResponse
    {
        $user = $request->user();
        $customer = $user->customer;

        if (!$customer) {
            $customer = Customer::firstOrCreate(
                ['email' => $user->email],
                [
                    'user_id' => $user->id,
                    'name' => $user->name ?: 'Master Administrator',
                    'company_name' => 'QRqu Gateway Internal Test',
                    'phone' => '081234567890',
                    'whatsapp' => '081234567890',
                    'status' => 'active',
                ]
            );
        }

        $amount = (float) $request->input('amount', 1000);
        if ($amount < 1000) {
            $amount = 1000;
        }

        $invoiceId = Invoice::generateId();
        $externalId = 'TEST-' . strtoupper(Str::random(8));

        $invoice = Invoice::create([
            'id' => $invoiceId,
            'customer_id' => $customer->id,
            'external_id' => $externalId,
            'amount' => $amount,
            'description' => 'Uji Coba Pembayaran QRIS DOKU (Romei Protocol Test)',
            'customer_name' => $request->input('customer_name', 'Admin Tester'),
            'customer_email' => $request->input('customer_email', $user->email),
            'customer_phone' => '081234567890',
            'status' => 'PENDING',
            'expired_at' => now()->addMinutes(60),
            'callback_url' => url('/admin/settings?tab=doku&test_paid=' . $invoiceId),
        ]);

        $transaction = Transaction::create([
            'id' => Transaction::generateId(),
            'customer_id' => $customer->id,
            'invoice_id' => $invoice->id,
            'external_id' => $externalId,
            'amount' => $amount,
            'status' => 'PENDING',
        ]);

        // Generate QRIS via DOKU
        $dokuResult = $dokuService->generateQris($transaction, $invoice);

        if ($dokuResult && (!empty($dokuResult['qr_string']) || !empty($dokuResult['payment_url']))) {
            $invoice->update([
                'qr_string' => $dokuResult['qr_string'] ?? null,
                'qr_url' => $dokuResult['payment_url'] ?? null,
            ]);

            AuditLog::record('TEST_PAYMENT_CREATED', $user, null, [
                'invoice_id' => $invoice->id,
                'amount' => $amount,
            ]);

            return response()->json([
                'success' => true,
                'invoice_id' => $invoice->id,
                'external_id' => $invoice->external_id,
                'amount' => $amount,
                'amount_formatted' => 'Rp ' . number_format($amount, 0, ',', '.'),
                'qr_string' => $invoice->qr_string,
                'qr_url' => $invoice->qr_url,
                'checkout_url' => url('/checkout/' . $invoice->id),
                'expired_at' => $invoice->expired_at->toIso8601String(),
                'status' => $invoice->status,
                'raw_response' => $dokuResult['raw_response'] ?? null,
            ]);
        }

        $errorMsg = 'Gagal membuat QRIS di DOKU. Periksa kredensial Merchant ID, Secret Key, dan status akun merchant Anda.';
        if (isset($dokuResult['raw_response'])) {
            $errorMsg .= ' DOKU Response: ' . json_encode($dokuResult['raw_response']);
        }

        return response()->json([
            'success' => false,
            'message' => $errorMsg,
            'raw_response' => $dokuResult['raw_response'] ?? null,
        ], 422);
    }

    public function checkTestPaymentStatus(string $invoiceId, DokuService $dokuService): JsonResponse
    {
        $invoice = Invoice::find($invoiceId);
        if (!$invoice) {
            return response()->json(['success' => false, 'message' => 'Invoice tidak ditemukan'], 404);
        }

        if ($invoice->status === 'PENDING') {
            $dokuStatus = $dokuService->verifyPayment($invoice->id);
            if (isset($dokuStatus['transaction']['status']) && strtoupper($dokuStatus['transaction']['status']) === 'SUCCESS') {
                $transaction = $invoice->latestTransaction;
                if ($transaction) {
                    $transaction->transitionTo('PAID', 'doku_status_check', 'Payment verified via DOKU API');
                } else {
                    $invoice->update(['status' => 'PAID', 'paid_at' => now()]);
                }
                $invoice->refresh();
            }
        }

        return response()->json([
            'success' => true,
            'status' => $invoice->status,
            'is_paid' => $invoice->status === 'PAID',
            'paid_at' => $invoice->paid_at?->format('d M Y H:i:s'),
        ]);
    }

    public function simulateTestPayment(string $invoiceId, CustomerWebhookService $webhookService): JsonResponse
    {
        $invoice = Invoice::findOrFail($invoiceId);
        $transaction = $invoice->latestTransaction ?? Transaction::where('invoice_id', $invoice->id)->first();

        if ($transaction && $transaction->status !== 'PAID') {
            $transaction->transitionTo('PAID', 'admin_test_simulator', 'Simulasi pembayaran test dari Admin Settings');
            $webhookService->dispatchPaymentEvent($transaction->fresh(), 'payment.paid');
        } else {
            $invoice->update(['status' => 'PAID', 'paid_at' => now()]);
        }

        return response()->json([
            'success' => true,
            'status' => 'PAID',
            'message' => 'Pembayaran uji coba berhasil disimulasikan sebagai LUNAS!',
        ]);
    }
}
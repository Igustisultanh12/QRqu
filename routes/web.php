<?php

use App\Http\Controllers\Admin;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\Customer;
use App\Http\Controllers\ProfileController;
use App\Http\Middleware\EnsureAdmin;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

// Public QRIS Checkout Interface
Route::get('/checkout/{invoice}', [CheckoutController::class, 'show'])->name('checkout.show');
Route::get('/checkout/{invoice}/status', [CheckoutController::class, 'checkStatus'])->name('checkout.status');
Route::post('/checkout/{invoice}/simulate', [CheckoutController::class, 'simulatePayment'])->name('checkout.simulate');

/*
|--------------------------------------------------------------------------
| Authentication Routes (Breeze/Fortify scaffolding)
|--------------------------------------------------------------------------
*/
require __DIR__ . '/auth.php';

/*
|--------------------------------------------------------------------------
| Customer Portal (Authenticated)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth'])->group(function () {
    // Customer Dashboard (Bisa diakses, menampilkan notifikasi verifikasi jika belum verifikasi email)
    Route::get('/dashboard', [Customer\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/customer/dashboard', [Customer\DashboardController::class, 'index'])->name('customer.dashboard');

    // Subscription & Plans
    Route::get('/subscription', [Customer\SubscriptionController::class, 'index'])->name('customer.subscription.index');
    Route::post('/subscription/{plan}/subscribe', [Customer\SubscriptionController::class, 'subscribe'])->name('customer.subscription.subscribe');

    // Transactions & Invoices
    Route::get('/transactions', [Customer\TransactionController::class, 'index'])->name('customer.transactions.index');
    Route::get('/transactions/export', [Customer\TransactionController::class, 'export'])->name('customer.transactions.export');
    Route::get('/transactions/{transaction}', [Customer\TransactionController::class, 'show'])->name('customer.transactions.show');
    Route::match(['get', 'post'], '/transactions/{transaction}/sync', [Customer\TransactionController::class, 'syncStatus'])->name('customer.transactions.sync');
    Route::post('/transactions/{transaction}/simulate', [Customer\TransactionController::class, 'simulate'])->name('customer.transactions.simulate');

    // Monthly Reports (Laporan Bulanan Transaksi)
    Route::get('/reports/monthly', [Customer\MonthlyReportController::class, 'index'])->name('customer.reports.monthly');
    Route::get('/reports/monthly/export', [Customer\MonthlyReportController::class, 'exportCsv'])->name('customer.reports.monthly.export');

    // Saldo & Penarikan Dana (Settlements)
    Route::get('/settlements', [Customer\SettlementController::class, 'index'])->name('customer.settlements.index');
    Route::post('/settlements', [Customer\SettlementController::class, 'store'])->name('customer.settlements.store');

    // Tiket Bantuan & Pengaduan
    Route::get('/tickets', [Customer\TicketController::class, 'index'])->name('customer.tickets.index');
    Route::post('/tickets', [Customer\TicketController::class, 'store'])->name('customer.tickets.store');
    Route::get('/tickets/{ticket}', [Customer\TicketController::class, 'show'])->name('customer.tickets.show');
    Route::post('/tickets/{ticket}/reply', [Customer\TicketController::class, 'reply'])->name('customer.tickets.reply');
    Route::post('/tickets/{ticket}/close', [Customer\TicketController::class, 'close'])->name('customer.tickets.close');

    // Documentation
    Route::get('/docs', [Customer\DocumentationController::class, 'index'])->name('customer.docs.index');

    // Profile & Security Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::match(['patch', 'post'], '/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');

    // Fitur API & Webhook Terkunci (Wajib Berlangganan)
    Route::middleware(['customer.subscribed'])->group(function () {
        // API Credentials
        Route::get('/credentials', [Customer\ApiCredentialController::class, 'index'])->name('customer.credentials.index');
        Route::post('/credentials', [Customer\ApiCredentialController::class, 'store'])->name('customer.credentials.store');
        Route::post('/credentials/{credential}/revoke', [Customer\ApiCredentialController::class, 'revoke'])->name('customer.credentials.revoke');
        Route::post('/credentials/{credential}/ip-whitelist', [Customer\ApiCredentialController::class, 'updateIpWhitelist'])->name('customer.credentials.ip-whitelist');

        // Webhooks
        Route::get('/webhooks', [Customer\WebhookController::class, 'index'])->name('customer.webhooks.index');
        Route::post('/webhooks', [Customer\WebhookController::class, 'store'])->name('customer.webhooks.store');
        Route::post('/webhooks/test-ping', [Customer\WebhookController::class, 'testPing'])->name('customer.webhooks.test-ping');
        Route::post('/webhooks/{delivery}/retry', [Customer\WebhookController::class, 'retry'])->name('customer.webhooks.retry');

        // API Usage & Logs
        Route::get('/api-usage', [Customer\ApiUsageController::class, 'index'])->name('customer.api-usage.index');
    });
});

/*
|--------------------------------------------------------------------------
| Admin Portal (Authenticated & EnsureAdmin)
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', EnsureAdmin::class])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [Admin\DashboardController::class, 'index'])->name('dashboard');

    // Customer Management
    Route::get('/customers', [Admin\CustomerController::class, 'index'])->name('customers.index');
    Route::get('/customers/{customer}', [Admin\CustomerController::class, 'show'])->name('customers.show');
    Route::post('/customers/{customer}/status', [Admin\CustomerController::class, 'updateStatus'])->name('customers.status');
    Route::post('/customers/{customer}/reset-password', [Admin\CustomerController::class, 'resetPassword'])->name('customers.reset-password');

    // Plan Management
    Route::get('/plans', [Admin\PlanController::class, 'index'])->name('plans.index');
    Route::post('/plans', [Admin\PlanController::class, 'store'])->name('plans.store');
    Route::put('/plans/{plan}', [Admin\PlanController::class, 'update'])->name('plans.update');
    Route::delete('/plans/{plan}', [Admin\PlanController::class, 'destroy'])->name('plans.destroy');

    // Transaction Management
    Route::get('/transactions', [Admin\TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [Admin\TransactionController::class, 'show'])->name('transactions.show');
    Route::match(['get', 'post'], '/transactions/{transaction}/sync', [Admin\TransactionController::class, 'syncStatus'])->name('transactions.sync');
    Route::post('/transactions/{transaction}/cancel', [Admin\TransactionController::class, 'cancel'])->name('transactions.cancel');
    Route::post('/transactions/{transaction}/simulate', [Admin\TransactionController::class, 'simulate'])->name('transactions.simulate');

    // Monthly Reports (Laporan Bulanan Platform)
    Route::get('/reports/monthly', [Admin\MonthlyReportController::class, 'index'])->name('reports.monthly');
    Route::get('/reports/monthly/export', [Admin\MonthlyReportController::class, 'exportCsv'])->name('reports.monthly.export');


    // Webhook Deliveries Management
    Route::get('/webhooks', [Admin\WebhookController::class, 'index'])->name('webhooks.index');
    Route::post('/webhooks/{delivery}/retry', [Admin\WebhookController::class, 'retry'])->name('webhooks.retry');

    // System Settings (Semua API & Mail Gateway)
    Route::get('/settings', [Admin\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-mail', [Admin\SettingController::class, 'testMail'])->name('settings.test-mail');
    Route::post('/settings/test-doku', [Admin\SettingController::class, 'testDoku'])->name('settings.test-doku');
    Route::post('/settings/test-payment', [Admin\SettingController::class, 'createTestPayment'])->name('settings.test-payment');
    Route::get('/settings/test-payment/{invoice}/status', [Admin\SettingController::class, 'checkTestPaymentStatus'])->name('settings.test-payment.status');
    Route::post('/settings/test-payment/{invoice}/simulate', [Admin\SettingController::class, 'simulateTestPayment'])->name('settings.test-payment.simulate');

    // DOKU Gateway Management
    Route::get('/doku', [Admin\DokuController::class, 'index'])->name('doku.index');
    Route::post('/doku', [Admin\DokuController::class, 'update'])->name('doku.update');
    Route::post('/doku/test-connection', [Admin\DokuController::class, 'testConnection'])->name('doku.test-connection');
    Route::post('/doku/test-payment', [Admin\SettingController::class, 'createTestPayment'])->name('doku.test-payment');
    Route::get('/doku/test-payment/{invoice}/status', [Admin\SettingController::class, 'checkTestPaymentStatus'])->name('doku.test-payment.status');
    Route::post('/doku/test-payment/{invoice}/simulate', [Admin\SettingController::class, 'simulateTestPayment'])->name('doku.test-payment.simulate');

    // Penarikan Saldo Pelanggan (Settlements)
    Route::get('/settlements', [Admin\SettlementController::class, 'index'])->name('settlements.index');
    Route::post('/settlements/{settlement}/status', [Admin\SettlementController::class, 'updateStatus'])->name('settlements.status');

    // Tiket Pengaduan & Layanan Bantuan
    Route::get('/tickets', [Admin\TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets/{ticket}/reply', [Admin\TicketController::class, 'reply'])->name('tickets.reply');

    // Audit & Security Logs
    Route::get('/audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit-logs.index');

    // Monitoring & Health
    Route::get('/monitoring', [Admin\MonitoringController::class, 'index'])->name('monitoring.index');
    Route::post('/monitoring/test-payment', [Admin\MonitoringController::class, 'testPayment'])->name('monitoring.test-payment');
    Route::get('/monitoring/check-status/{invoice_id}', [Admin\MonitoringController::class, 'checkStatus'])->name('monitoring.check-status');
    Route::post('/monitoring/simulate/{invoice_id}', [Admin\MonitoringController::class, 'simulatePayment'])->name('monitoring.simulate');
});
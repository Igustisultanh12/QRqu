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
    // Customer Dashboard
    Route::get('/dashboard', [Customer\DashboardController::class, 'index'])->name('dashboard');
    Route::get('/customer/dashboard', [Customer\DashboardController::class, 'index'])->name('customer.dashboard');

    // Subscription & Plans
    Route::get('/subscription', [Customer\SubscriptionController::class, 'index'])->name('customer.subscription.index');
    Route::post('/subscription/{plan}/subscribe', [Customer\SubscriptionController::class, 'subscribe'])->name('customer.subscription.subscribe');

    // API Credentials
    Route::get('/credentials', [Customer\ApiCredentialController::class, 'index'])->name('customer.credentials.index');
    Route::post('/credentials', [Customer\ApiCredentialController::class, 'store'])->name('customer.credentials.store');
    Route::post('/credentials/{credential}/revoke', [Customer\ApiCredentialController::class, 'revoke'])->name('customer.credentials.revoke');
    Route::post('/credentials/{credential}/ip-whitelist', [Customer\ApiCredentialController::class, 'updateIpWhitelist'])->name('customer.credentials.ip-whitelist');

    // Transactions & Invoices
    Route::get('/transactions', [Customer\TransactionController::class, 'index'])->name('customer.transactions.index');
    Route::get('/transactions/export', [Customer\TransactionController::class, 'export'])->name('customer.transactions.export');
    Route::get('/transactions/{transaction}', [Customer\TransactionController::class, 'show'])->name('customer.transactions.show');

    // Webhooks
    Route::get('/webhooks', [Customer\WebhookController::class, 'index'])->name('customer.webhooks.index');
    Route::post('/webhooks', [Customer\WebhookController::class, 'store'])->name('customer.webhooks.store');
    Route::post('/webhooks/test-ping', [Customer\WebhookController::class, 'testPing'])->name('customer.webhooks.test-ping');
    Route::post('/webhooks/{delivery}/retry', [Customer\WebhookController::class, 'retry'])->name('customer.webhooks.retry');

    // API Usage & Logs
    Route::get('/api-usage', [Customer\ApiUsageController::class, 'index'])->name('customer.api-usage.index');

    // Documentation
    Route::get('/docs', [Customer\DocumentationController::class, 'index'])->name('customer.docs.index');

    // Profile & Security Settings
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::put('/password', [ProfileController::class, 'updatePassword'])->name('password.update');
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
    Route::post('/transactions/{transaction}/cancel', [Admin\TransactionController::class, 'cancel'])->name('transactions.cancel');

    // DOKU Gateway Management
    Route::get('/doku', [Admin\DokuController::class, 'index'])->name('doku.index');
    Route::post('/doku', [Admin\DokuController::class, 'update'])->name('doku.update');
    Route::post('/doku/test-connection', [Admin\DokuController::class, 'testConnection'])->name('doku.test-connection');

    // Webhook Deliveries Management
    Route::get('/webhooks', [Admin\WebhookController::class, 'index'])->name('webhooks.index');
    Route::post('/webhooks/{delivery}/retry', [Admin\WebhookController::class, 'retry'])->name('webhooks.retry');

    // System Settings
    Route::get('/settings', [Admin\SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [Admin\SettingController::class, 'update'])->name('settings.update');

    // Audit & Security Logs
    Route::get('/audit-logs', [Admin\AuditLogController::class, 'index'])->name('audit-logs.index');

    // Monitoring & Health
    Route::get('/monitoring', [Admin\MonitoringController::class, 'index'])->name('monitoring.index');
});
<?php

use App\Http\Controllers\Api\V1\AccountApiController;
use App\Http\Controllers\Api\V1\InvoiceApiController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\HealthController;
use App\Http\Controllers\Webhook\DokuWebhookController;
use App\Http\Middleware\CheckApiRateLimit;
use App\Http\Middleware\VerifyApiAuthentication;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Internal Health & Monitoring Check Endpoints
|--------------------------------------------------------------------------
*/
Route::get('/health', [HealthController::class, 'health'])->name('api.health');
Route::get('/ready', [HealthController::class, 'ready'])->name('api.ready');

/*
|--------------------------------------------------------------------------
| Inbound DOKU Payment Webhook Handlers (romei 1 compliant)
|--------------------------------------------------------------------------
*/
Route::post('/webhooks/doku', [DokuWebhookController::class, 'handleQrisCallback'])->name('api.webhook.doku');
Route::post('/v1/webhook/doku/qris', [DokuWebhookController::class, 'handleQrisCallback'])->name('api.webhook.doku.qris');
Route::post('/webhook/doku/qris', [DokuWebhookController::class, 'handleQrisCallback'])->name('api.webhook.doku.qris.direct');
Route::post('/v1/callback/doku', [DokuWebhookController::class, 'handleQrisCallback'])->name('api.webhook.doku.callback');

/*
|--------------------------------------------------------------------------
| ADMIN MONITORING & GATEWAY LIVE TEST (Protokol Romei 1)
|--------------------------------------------------------------------------
*/
Route::prefix('admin/monitoring')->group(function () {
    Route::post('/test-payment', [\App\Http\Controllers\Admin\MonitoringController::class, 'testPayment'])->name('api.admin.monitoring.test-payment');
    Route::get('/check-status/{invoice_id}', [\App\Http\Controllers\Admin\MonitoringController::class, 'checkStatus'])->name('api.admin.monitoring.check-status');
    Route::post('/simulate/{invoice_id}', [\App\Http\Controllers\Admin\MonitoringController::class, 'simulatePayment'])->name('api.admin.monitoring.simulate');
});

/*
|--------------------------------------------------------------------------
| Public Checkout Polling
|--------------------------------------------------------------------------
*/
Route::get('/checkout/{invoice}/status', [CheckoutController::class, 'checkStatus'])->name('api.checkout.status');

/*
|--------------------------------------------------------------------------
| Merchant Protected API v1 Endpoints
|--------------------------------------------------------------------------
| Uses HMAC-SHA256 Request Signature, Nonce, Timestamp, Idempotency and Rate Limiting
*/
Route::prefix('v1')->middleware([VerifyApiAuthentication::class, CheckApiRateLimit::class])->group(function () {
    // Invoices
    Route::post('/invoices', [InvoiceApiController::class, 'store'])->name('api.v1.invoices.store');
    Route::get('/invoices/{invoice}', [InvoiceApiController::class, 'show'])->name('api.v1.invoices.show');
    Route::post('/invoices/{invoice}/cancel', [InvoiceApiController::class, 'cancel'])->name('api.v1.invoices.cancel');

    // Transactions
    Route::get('/transactions', [InvoiceApiController::class, 'transactions'])->name('api.v1.transactions.index');
    Route::get('/transactions/{transaction}', [InvoiceApiController::class, 'showTransaction'])->name('api.v1.transactions.show');

    // Account & Usage
    Route::get('/account', [AccountApiController::class, 'profile'])->name('api.v1.account.profile');
    Route::get('/account/usage', [AccountApiController::class, 'usage'])->name('api.v1.account.usage');
    Route::get('/account/balance', [AccountApiController::class, 'balance'])->name('api.v1.account.balance');
});
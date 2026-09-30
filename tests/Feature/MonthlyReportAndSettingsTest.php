<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Settlement;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MonthlyReportAndSettingsTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;
    protected User $merchantUser;
    protected Customer $customer;
    protected Plan $plan;
    protected Subscription $subscription;

    protected function setUp(): void
    {
        parent::setUp();

        // Roles
        $adminRole = Role::create(['name' => 'admin']);
        $customerRole = Role::create(['name' => 'customer']);

        // Admin User
        $this->adminUser = User::factory()->create([
            'role' => 'admin',
            'status' => 'active',
        ]);
        $this->adminUser->roles()->syncWithoutDetaching([$adminRole->id => ['model_type' => User::class]]);

        // Starter 30d Plan
        $this->plan = Plan::create([
            'name' => 'Paket 1 Bulan (Starter)',
            'slug' => 'monthly-30d',
            'duration_days' => 30,
            'price' => 150000,
            'transaction_limit' => 1000,
            'api_limit' => 10000,
            'rate_limit_rpm' => 60,
            'features' => ['QRIS Dinamis'],
            'status' => 'active',
        ]);

        // Merchant Customer
        $this->merchantUser = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);
        $this->merchantUser->roles()->syncWithoutDetaching([$customerRole->id => ['model_type' => User::class]]);

        $this->customer = Customer::create([
            'user_id' => $this->merchantUser->id,
            'name' => 'Toko Kelontong Sukses',
            'company_name' => 'PT Kelontong Sukses',
            'email' => $this->merchantUser->email,
            'status' => 'active',
        ]);

        $this->subscription = Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $this->plan->id,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'status' => 'active',
        ]);
    }

    public function test_admin_can_update_monthly_price_and_quota_in_settings(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/settings', [
                'app_name' => 'QRqu Gateway Production',
                'app_url' => 'https://qrqu.id',
                'timezone' => 'Asia/Jakarta',
                'currency' => 'IDR',
                'maintenance_mode' => false,
                'default_expire_minutes' => 60,
                'api_timestamp_tolerance' => 300,
                'webhook_max_retries' => 4,
                'monthly_price' => 250000,
                'monthly_quota' => 2000,
                'doku_client_id' => 'MCH-DEMO-999',
                'doku_secret_key' => 'sec_test_doku_key_123',
                'doku_base_url' => 'https://api-sandbox.doku.com',
                'doku_environment' => 'sandbox',
                'mail_mailer' => 'log',
                'mail_host' => 'smtp.mailtrap.io',
                'mail_port' => 587,
                'mail_username' => 'testuser',
                'mail_password' => 'secret123',
                'mail_encryption' => 'tls',
                'mail_from_address' => 'no-reply@qrqu.id',
                'mail_from_name' => 'QRqu Gateway',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verifikasi SystemSetting
        $this->assertEquals(250000, (float) SystemSetting::get('monthly_price'));
        $this->assertEquals(2000, (int) SystemSetting::get('monthly_quota'));
        $this->assertEquals('MCH-DEMO-999', SystemSetting::get('doku_client_id'));
        $this->assertEquals('smtp.mailtrap.io', SystemSetting::get('mail_host'));

        // Verifikasi Sinkronisasi ke Plan Bulanan
        $this->assertEquals(250000, (float) $this->plan->fresh()->price);
        $this->assertEquals(2000, (int) $this->plan->fresh()->transaction_limit);
    }

    public function test_admin_can_send_test_mail(): void
    {
        SystemSetting::set('mail_mailer', 'log');
        SystemSetting::set('mail_from_address', 'no-reply@qrqu.id');
        SystemSetting::set('mail_from_name', 'QRqu');

        $response = $this->actingAs($this->adminUser)
            ->post('/admin/settings/test-mail', [
                'test_email' => 'admin@qrqu.id',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_admin_can_test_doku_connection(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/settings/test-doku');

        $response->assertRedirect();
        $response->assertSessionHas('success');
    }

    public function test_customer_can_view_monthly_report_with_quota_and_breakdown(): void
    {
        // 1. Transaksi Berhasil (PAID)
        $invPaid = Invoice::create([
            'id' => 'INV-MONTHLY-001',
            'customer_id' => $this->customer->id,
            'external_id' => 'order-paid-001',
            'amount' => 50000,
            'currency' => 'IDR',
            'status' => 'PAID',
            'payment_method' => 'QRIS',
            'description' => 'Pembayaran Belanja Sembako #001',
            'expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);
        Transaction::create([
            'id' => 'TRX-MONTHLY-001',
            'customer_id' => $this->customer->id,
            'invoice_id' => $invPaid->id,
            'external_id' => 'order-paid-001',
            'amount' => 50000,
            'fee' => 350,
            'net_amount' => 49650,
            'payment_method' => 'QRIS',
            'status' => 'PAID',
        ]);

        // 2. Transaksi Gagal (FAILED)
        $invFailed = Invoice::create([
            'id' => 'INV-MONTHLY-002',
            'customer_id' => $this->customer->id,
            'external_id' => 'order-failed-002',
            'amount' => 75000,
            'currency' => 'IDR',
            'status' => 'FAILED',
            'payment_method' => 'QRIS',
            'description' => 'Pembelian Pulsa Gagal',
            'expired_at' => now()->addHour(),
        ]);
        Transaction::create([
            'id' => 'TRX-MONTHLY-002',
            'customer_id' => $this->customer->id,
            'invoice_id' => $invFailed->id,
            'external_id' => 'order-failed-002',
            'amount' => 75000,
            'fee' => 0,
            'net_amount' => 0,
            'payment_method' => 'QRIS',
            'status' => 'FAILED',
        ]);

        // 3. Transaksi Kedaluwarsa (EXPIRED)
        $invExpired = Invoice::create([
            'id' => 'INV-MONTHLY-003',
            'customer_id' => $this->customer->id,
            'external_id' => 'order-expired-003',
            'amount' => 25000,
            'currency' => 'IDR',
            'status' => 'EXPIRED',
            'payment_method' => 'QRIS',
            'description' => 'Tiket Parkir Kedaluwarsa',
            'expired_at' => now()->subHour(),
        ]);
        Transaction::create([
            'id' => 'TRX-MONTHLY-003',
            'customer_id' => $this->customer->id,
            'invoice_id' => $invExpired->id,
            'external_id' => 'order-expired-003',
            'amount' => 25000,
            'fee' => 0,
            'net_amount' => 0,
            'payment_method' => 'QRIS',
            'status' => 'EXPIRED',
        ]);

        $response = $this->actingAs($this->merchantUser)
            ->get('/reports/monthly');

        $response->assertStatus(200);

        // Verifikasi data props Inertia
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Reports/Monthly')
            ->has('quota', fn ($quota) => $quota
                ->where('used', 3)
                ->where('limit', 1000)
                ->where('remaining', 997)
                ->where('percentage', 0.3)
                ->etc()
            )
            ->has('summary', fn ($summary) => $summary
                ->where('total_count', 3)
                ->where('successful_count', 1)
                ->where('failed_count', 1)
                ->where('expired_count', 1)
                ->where('successful_amount', 50000)
                ->etc()
            )
            ->has('transactions.data', 3)
            ->where('transactions.data', fn ($data) => collect($data)->pluck('nama_transaksi')->contains('Pembayaran Belanja Sembako #001'))
        );
    }

    public function test_customer_can_export_monthly_report_csv(): void
    {
        $inv = Invoice::create([
            'id' => 'INV-CSV-001',
            'customer_id' => $this->customer->id,
            'external_id' => 'order-csv-001',
            'amount' => 120000,
            'currency' => 'IDR',
            'status' => 'PAID',
            'payment_method' => 'QRIS',
            'description' => 'Order Ekspor CSV Spesial',
            'expired_at' => now()->addHour(),
            'paid_at' => now(),
        ]);
        Transaction::create([
            'id' => 'TRX-CSV-001',
            'customer_id' => $this->customer->id,
            'invoice_id' => $inv->id,
            'external_id' => 'order-csv-001',
            'amount' => 120000,
            'fee' => 840,
            'net_amount' => 119160,
            'payment_method' => 'QRIS',
            'status' => 'PAID',
        ]);

        $response = $this->actingAs($this->merchantUser)
            ->get('/reports/monthly/export');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('Order Ekspor CSV Spesial', $response->streamedContent());
        $this->assertStringContainsString('PAID', $response->streamedContent());
    }

    public function test_admin_can_view_monthly_report_and_export_csv(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/monthly');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Reports/Monthly')
            ->has('quota')
            ->has('summary')
            ->has('merchants')
            ->has('transactions')
        );

        // Ekspor CSV admin
        $csvResponse = $this->actingAs($this->adminUser)
            ->get('/admin/reports/monthly/export');

        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }

    public function test_admin_can_create_and_simulate_doku_test_payment(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/admin/settings/test-payment', [
                'amount' => 1000,
                'customer_name' => 'Tester Sultan',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'amount' => 1000,
            'status' => 'PENDING',
        ]);

        $invoiceId = $response->json('invoice_id');
        $this->assertNotNull($invoiceId);

        // Check Status
        $statusResponse = $this->actingAs($this->adminUser)
            ->getJson("/admin/settings/test-payment/{$invoiceId}/status");

        $statusResponse->assertStatus(200);
        $statusResponse->assertJson([
            'success' => true,
            'status' => 'PENDING',
            'is_paid' => false,
        ]);

        // Simulate Payment
        $simulateResponse = $this->actingAs($this->adminUser)
            ->postJson("/admin/settings/test-payment/{$invoiceId}/simulate");

        $simulateResponse->assertStatus(200);
        $simulateResponse->assertJson([
            'success' => true,
            'status' => 'PAID',
        ]);

        // Re-check Status
        $finalStatus = $this->actingAs($this->adminUser)
            ->getJson("/admin/settings/test-payment/{$invoiceId}/status");

        $finalStatus->assertJson([
            'success' => true,
            'status' => 'PAID',
            'is_paid' => true,
        ]);
    }

    public function test_admin_monitoring_romei_test_payment_and_polling(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/admin/monitoring/test-payment', [
                'amount' => 10000,
                'payment_method' => 'qris',
            ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'success' => true,
        ]);

        $invoiceId = $response->json('invoice_id');
        $paymentUrl = $response->json('payment_url');
        $this->assertNotEmpty($invoiceId);
        $this->assertNotEmpty($paymentUrl);

        // Check Status (Romei format)
        $statusRes = $this->actingAs($this->adminUser)
            ->getJson("/api/admin/monitoring/check-status/{$invoiceId}");

        $statusRes->assertStatus(200);
        $statusRes->assertJson([
            'status' => 'pending',
            'payment_status' => 'PENDING',
            'is_paid' => false,
        ]);

        // Simulate
        $simRes = $this->actingAs($this->adminUser)
            ->postJson("/api/admin/monitoring/simulate/{$invoiceId}");

        $simRes->assertStatus(200);
        $simRes->assertJson([
            'status' => 'success',
            'payment_status' => 'SUCCESS',
        ]);

        // Recheck
        $finalRes = $this->actingAs($this->adminUser)
            ->getJson("/api/admin/monitoring/check-status/{$invoiceId}");

        $finalRes->assertStatus(200);
        $finalRes->assertJson([
            'status' => 'success',
            'payment_status' => 'SUCCESS',
            'is_paid' => true,
        ]);
    }

    public function test_admin_monitoring_test_payment_succeeds_even_when_unauthenticated(): void
    {
        $response = $this->postJson('/api/admin/monitoring/test-payment', [
            'amount' => 5000,
            'payment_method' => 'qris',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'status' => 'success',
            'success' => true,
        ]);

        $this->assertNotEmpty($response->json('invoice_id'));
        $this->assertNotEmpty($response->json('payment_url'));
    }

    public function test_customer_can_export_passbook_pdf_statement(): void
    {
        $invoice = Invoice::create([
            'id' => 'INV-API-TEST-001',
            'customer_id' => $this->customer->id,
            'external_id' => 'ORDER-ORD-1234',
            'amount' => 50000,
            'currency' => 'IDR',
            'description' => 'Pembayaran Pesanan Kopi Susu',
            'customer_name' => 'Budi Santoso',
            'status' => 'PAID',
            'expired_at' => now()->addHour(),
        ]);

        Transaction::create([
            'id' => 'TRX-API-TEST-001',
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice->id,
            'external_id' => 'ORDER-ORD-1234',
            'amount' => 50000,
            'fee' => 350,
            'net_amount' => 49650,
            'status' => 'PAID',
        ]);

        $response = $this->actingAs($this->merchantUser)
            ->get('/reports/monthly/pdf?month=' . now()->month . '&year=' . now()->year);

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment;', $response->headers->get('Content-Disposition'));
    }

    public function test_admin_can_export_passbook_pdf_statement(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get('/admin/reports/monthly/pdf?month=' . now()->month . '&year=' . now()->year);

        $response->assertStatus(200);
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type'));
    }

    public function test_admin_can_view_subscription_transactions_menu(): void
    {
        // 1. Regular API Transaction
        $ordInvoice = Invoice::create([
            'id' => 'INV-ORD-101',
            'customer_id' => $this->customer->id,
            'external_id' => 'ORDER-REGULAR-101',
            'amount' => 20000,
            'status' => 'PAID',
            'expired_at' => now()->addHour(),
        ]);

        Transaction::create([
            'id' => 'TRX-ORD-101',
            'customer_id' => $this->customer->id,
            'invoice_id' => $ordInvoice->id,
            'external_id' => 'ORDER-REGULAR-101',
            'amount' => 20000,
            'status' => 'PAID',
        ]);

        // 2. Subscription Transaction
        $subInvoice = Invoice::create([
            'id' => 'INV-SUB-TEST-99',
            'customer_id' => $this->customer->id,
            'external_id' => 'SUB-' . $this->customer->id . '-' . $this->plan->id . '-12345',
            'amount' => 150000,
            'description' => 'Langganan Starter 30 Hari',
            'status' => 'PAID',
            'expired_at' => now()->addHour(),
        ]);

        $subTrx = Transaction::create([
            'id' => 'TRX-SUB-99',
            'customer_id' => $this->customer->id,
            'invoice_id' => $subInvoice->id,
            'external_id' => 'SUB-' . $this->customer->id . '-' . $this->plan->id . '-12345',
            'amount' => 150000,
            'status' => 'PAID',
        ]);

        Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $this->plan->id,
            'invoice_id' => $subInvoice->id,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get('/admin/subscriptions/transactions');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Admin/Subscriptions/Transactions')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.external_id', 'SUB-' . $this->customer->id . '-' . $this->plan->id . '-12345')
        );
    }

    public function test_passbook_pdf_and_monthly_report_excludes_subscription_transactions(): void
    {
        // Subscription Transaction
        $invSub = Invoice::create([
            'id' => 'INV-SUB-ISOLATE',
            'customer_id' => $this->customer->id,
            'external_id' => 'SUB-999-1-99999',
            'amount' => 500000,
            'status' => 'PAID',
            'expired_at' => now()->addHour(),
        ]);

        Transaction::create([
            'id' => 'TRX-SUB-ISOLATE',
            'customer_id' => $this->customer->id,
            'invoice_id' => $invSub->id,
            'external_id' => 'SUB-999-1-99999',
            'amount' => 500000,
            'status' => 'PAID',
        ]);

        // Regular API Transaction
        $invApi = Invoice::create([
            'id' => 'INV-API-ISOLATE',
            'customer_id' => $this->customer->id,
            'external_id' => 'API-MERCHANT-INV-01',
            'amount' => 75000,
            'status' => 'PAID',
            'expired_at' => now()->addHour(),
        ]);

        Transaction::create([
            'id' => 'TRX-API-ISOLATE',
            'customer_id' => $this->customer->id,
            'invoice_id' => $invApi->id,
            'external_id' => 'API-MERCHANT-INV-01',
            'amount' => 75000,
            'status' => 'PAID',
        ]);

        $response = $this->actingAs($this->merchantUser)
            ->get('/reports/monthly?month=' . now()->month . '&year=' . now()->year);

        $response->assertStatus(200);
        // Only the API transaction should be counted
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Reports/Monthly')
            ->has('transactions.data', 1)
            ->where('transactions.data.0.external_id', 'API-MERCHANT-INV-01')
        );
    }

    public function test_transaction_older_than_1_hour_auto_expires(): void
    {
        $invoice = Invoice::create([
            'id' => 'INV-EXPIRE-TEST',
            'customer_id' => $this->customer->id,
            'external_id' => 'EXT-EXPIRE-01',
            'amount' => 50000,
            'status' => 'PENDING',
            'expired_at' => now()->subMinutes(5),
        ]);

        $trx = Transaction::create([
            'id' => 'TRX-EXPIRE-TEST',
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice->id,
            'external_id' => 'EXT-EXPIRE-01',
            'amount' => 50000,
            'status' => 'PENDING',
        ]);

        \Illuminate\Support\Facades\DB::table('invoices')->where('id', $invoice->id)->update(['created_at' => now()->subMinutes(70)]);
        \Illuminate\Support\Facades\DB::table('transactions')->where('id', $trx->id)->update(['created_at' => now()->subMinutes(70)]);
        $trx->refresh();

        $expired = $trx->checkAndExpire();
        $this->assertTrue($expired);
        $this->assertEquals('EXPIRED', $trx->fresh()->status);
        $this->assertEquals('EXPIRED', $invoice->fresh()->status);

        // Also test customer transaction show endpoint triggers checkAndExpire
        $invoice2 = Invoice::create([
            'id' => 'INV-EXPIRE-TEST-2',
            'customer_id' => $this->customer->id,
            'external_id' => 'EXT-EXPIRE-02',
            'amount' => 75000,
            'status' => 'PENDING',
            'expired_at' => now()->addMinutes(10), // even if expired_at is in future, created_at > 60m
        ]);

        $trx2 = Transaction::create([
            'id' => 'TRX-EXPIRE-TEST-2',
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice2->id,
            'external_id' => 'EXT-EXPIRE-02',
            'amount' => 75000,
            'status' => 'PENDING',
        ]);

        \Illuminate\Support\Facades\DB::table('invoices')->where('id', $invoice2->id)->update(['created_at' => now()->subMinutes(65)]);
        \Illuminate\Support\Facades\DB::table('transactions')->where('id', $trx2->id)->update(['created_at' => now()->subMinutes(65)]);
        $trx2->refresh();

        $response = $this->actingAs($this->merchantUser)
            ->get("/transactions/{$trx2->id}");
        $response->assertStatus(200);
        $this->assertEquals('EXPIRED', $trx2->fresh()->status);
    }

    public function test_subscription_duration_stacks_when_subscribing_with_existing_active_subscription(): void
    {
        // Existing active subscription expiring in 10 days
        $initialExpiresAt = now()->addDays(10);
        $this->subscription->update([
            'status' => 'active',
            'starts_at' => now()->subDays(20),
            'expires_at' => $initialExpiresAt,
        ]);

        // Customer subscribes to another 30-day plan
        $newPlan = Plan::create([
            'name' => 'Paket Pro (30 Hari)',
            'slug' => 'pro-30d',
            'duration_days' => 30,
            'price' => 300000,
            'transaction_limit' => 5000,
            'api_limit' => 50000,
            'rate_limit_rpm' => 120,
            'features' => ['QRIS Dinamis', 'Webhooks Priority'],
            'status' => 'active',
        ]);

        $newSubInvoice = Invoice::create([
            'id' => 'INV-STACK-TEST',
            'customer_id' => $this->customer->id,
            'external_id' => 'SUB-' . $this->customer->id . '-' . $newPlan->id . '-999',
            'amount' => 300000,
            'status' => 'PENDING',
            'expired_at' => now()->addHour(),
        ]);

        $newSub = Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $newPlan->id,
            'invoice_id' => $newSubInvoice->id,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'grace_period_days' => 3,
            'status' => 'pending_payment',
            'auto_renew' => true,
        ]);

        // Activate new subscription via activateWithExtension
        $activated = $newSub->activateWithExtension();
        $this->assertTrue($activated);

        // Previous subscription should be completed
        $this->assertEquals('completed', $this->subscription->fresh()->status);

        // New subscription should be active and stacked by 30 days from initialExpiresAt
        $freshNewSub = $newSub->fresh();
        $this->assertEquals('active', $freshNewSub->status);
        $expectedExpiry = $initialExpiresAt->copy()->addDays(30);

        // Diff between expected and actual expires_at should be less than 5 seconds
        $this->assertLessThan(5, abs($expectedExpiry->diffInSeconds($freshNewSub->expires_at)));
        // Total remaining days should now be ~40 days (10 remaining + 30 new)
        $this->assertGreaterThanOrEqual(39, $freshNewSub->remainingDays());
    }

    public function test_pending_subscription_cancelled_when_expired_via_command(): void
    {
        $invoice = Invoice::create([
            'id' => 'INV-CMD-EXP',
            'customer_id' => $this->customer->id,
            'external_id' => 'SUB-CMD-01',
            'amount' => 150000,
            'status' => 'PENDING',
            'expired_at' => now()->subMinute(),
        ]);

        $trx = Transaction::create([
            'id' => 'TRX-CMD-EXP',
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice->id,
            'external_id' => 'SUB-CMD-01',
            'amount' => 150000,
            'status' => 'PENDING',
        ]);

        $sub = Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $this->plan->id,
            'invoice_id' => $invoice->id,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'status' => 'pending_payment',
        ]);

        $this->artisan('qrqu:expire-invoices')->assertSuccessful();

        $this->assertEquals('EXPIRED', $invoice->fresh()->status);
        $this->assertEquals('EXPIRED', $trx->fresh()->status);
        $this->assertEquals('cancelled', $sub->fresh()->status);
    }

    public function test_customer_can_create_store_and_filter_monthly_report_by_store(): void
    {
        // 1. Create a store via POST /stores
        $resStore = $this->actingAs($this->merchantUser)
            ->post('/stores', [
                'name' => 'Toko Cabang Bandung',
                'code' => 'BDG-01',
                'description' => 'Outlet cabang Jawa Barat',
            ]);
        $resStore->assertRedirect();
        $resStore->assertSessionHas('success');

        $this->assertDatabaseHas('stores', [
            'customer_id' => $this->customer->id,
            'name' => 'Toko Cabang Bandung',
            'code' => 'BDG-01',
        ]);

        $storeBdg = Store::where('code', 'BDG-01')->first();
        $this->customer->ensureStores();
        $defaultStore = $this->customer->defaultStore;

        // 2. Buat transaksi untuk storeBdg dan defaultStore
        $invBdg = Invoice::create([
            'id' => 'INV-BDG-001',
            'customer_id' => $this->customer->id,
            'store_id' => $storeBdg->id,
            'external_id' => 'order-bdg-001',
            'amount' => 50000,
            'status' => 'PAID',
            'expired_at' => now()->addHour(),
        ]);
        Transaction::create([
            'id' => 'TRX-BDG-001',
            'customer_id' => $this->customer->id,
            'store_id' => $storeBdg->id,
            'invoice_id' => $invBdg->id,
            'external_id' => 'order-bdg-001',
            'amount' => 50000,
            'status' => 'PAID',
        ]);

        $invDef = Invoice::create([
            'id' => 'INV-DEF-001',
            'customer_id' => $this->customer->id,
            'store_id' => $defaultStore->id,
            'external_id' => 'order-def-001',
            'amount' => 100000,
            'status' => 'PAID',
            'expired_at' => now()->addHour(),
        ]);
        Transaction::create([
            'id' => 'TRX-DEF-001',
            'customer_id' => $this->customer->id,
            'store_id' => $defaultStore->id,
            'invoice_id' => $invDef->id,
            'external_id' => 'order-def-001',
            'amount' => 100000,
            'status' => 'PAID',
        ]);

        // 3. Filter Monthly Report by storeBdg
        $resFilter = $this->actingAs($this->merchantUser)
            ->get(route('customer.reports.monthly', ['store_id' => $storeBdg->id]));
        $resFilter->assertOk();
        $resFilter->assertInertia(fn ($page) =>
            $page->component('Customer/Reports/Monthly')
                ->where('selected_store_id', $storeBdg->id)
                ->where('summary.total_count', 1)
                ->where('summary.total_amount', 50000)
        );

        // 4. Filter Monthly Report with all stores
        $resAll = $this->actingAs($this->merchantUser)
            ->get(route('customer.reports.monthly', ['store_id' => 'all']));
        $resAll->assertOk();
        $resAll->assertInertia(fn ($page) =>
            $page->component('Customer/Reports/Monthly')
                ->where('selected_store_id', 'all')
                ->where('summary.total_count', 2)
                ->where('summary.total_amount', 150000)
        );
    }

    public function test_customer_can_export_monthly_report_pdf_with_store_filter(): void
    {
        $this->customer->ensureStores();
        $defaultStore = $this->customer->defaultStore;

        $inv = Invoice::create([
            'id' => 'INV-PDF-001',
            'customer_id' => $this->customer->id,
            'store_id' => $defaultStore->id,
            'external_id' => 'order-pdf-001',
            'amount' => 75000,
            'status' => 'PAID',
            'expired_at' => now()->addHour(),
        ]);
        Transaction::create([
            'id' => 'TRX-PDF-001',
            'customer_id' => $this->customer->id,
            'store_id' => $defaultStore->id,
            'invoice_id' => $inv->id,
            'external_id' => 'order-pdf-001',
            'amount' => 75000,
            'status' => 'PAID',
        ]);

        $response = $this->actingAs($this->merchantUser)
            ->get(route('customer.reports.monthly.pdf', ['store_id' => $defaultStore->id]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_customer_settlement_page_shows_store_breakdown_and_doku_fee(): void
    {
        SystemSetting::set('doku_settlement_fee_enabled', 'true');
        SystemSetting::set('doku_settlement_fee_percent', '0.7');

        $this->customer->ensureStores();
        $defaultStore = $this->customer->defaultStore;

        // Income 1.000.000 -> Fee 0.7% = 7.000 -> Net = 993.000
        $inv = Invoice::create([
            'id' => 'INV-SETTLE-001',
            'customer_id' => $this->customer->id,
            'store_id' => $defaultStore->id,
            'external_id' => 'order-set-001',
            'amount' => 1000000,
            'status' => 'PAID',
            'expired_at' => now()->addHour(),
        ]);
        Transaction::create([
            'id' => 'TRX-SETTLE-001',
            'customer_id' => $this->customer->id,
            'store_id' => $defaultStore->id,
            'invoice_id' => $inv->id,
            'external_id' => 'order-set-001',
            'amount' => 1000000,
            'status' => 'PAID',
        ]);

        $response = $this->actingAs($this->merchantUser)
            ->get(route('customer.settlements.index', ['store_id' => $defaultStore->id]));

        $response->assertOk();
        $response->assertInertia(fn ($page) =>
            $page->component('Customer/Settlements/Index')
                ->where('doku_fee_enabled', true)
                ->where('doku_fee_percent', 0.7)
                ->where('doku_fee_amount', 7000)
                ->where('balance', 993000)
                ->where('total_income', 1000000)
                ->where('total_net', 993000)
        );

        // Tarik saldo
        $resWithdraw = $this->actingAs($this->merchantUser)
            ->post(route('customer.settlements.store'), [
                'amount' => 500000,
                'bank_name' => 'BCA',
                'account_number' => '1234567890',
                'account_name' => 'PT Kelontong Sukses',
                'store_id' => $defaultStore->id,
            ]);
        $resWithdraw->assertRedirect();
        $resWithdraw->assertSessionHas('success');

        $this->assertDatabaseHas('settlements', [
            'customer_id' => $this->customer->id,
            'store_id' => $defaultStore->id,
            'amount' => 500000,
            'status' => 'verifikasi',
        ]);
    }

    public function test_customer_can_export_settlement_pdf_report(): void
    {
        $this->customer->ensureStores();
        $defaultStore = $this->customer->defaultStore;

        $response = $this->actingAs($this->merchantUser)
            ->get(route('customer.settlements.pdf', ['store_id' => $defaultStore->id]));

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }

    public function test_admin_can_toggle_doku_settlement_fee_in_settings(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post('/admin/settings', [
                'app_name' => 'QRqu Gateway Production',
                'app_url' => 'https://qrqu.id',
                'timezone' => 'Asia/Jakarta',
                'currency' => 'IDR',
                'maintenance_mode' => false,
                'default_expire_minutes' => 60,
                'api_timestamp_tolerance' => 300,
                'webhook_max_retries' => 4,
                'monthly_price' => 200000,
                'monthly_quota' => 1500,
                'doku_settlement_fee_enabled' => false,
                'doku_settlement_fee_percent' => 0.5,
                'doku_client_id' => 'MCH-DEMO-999',
                'doku_secret_key' => 'sec_test_doku_key_123',
                'doku_base_url' => 'https://api-sandbox.doku.com',
                'doku_environment' => 'sandbox',
                'mail_mailer' => 'log',
                'mail_host' => 'smtp.mailtrap.io',
                'mail_port' => 587,
                'mail_username' => 'testuser',
                'mail_password' => 'secret123',
                'mail_encryption' => 'tls',
                'mail_from_address' => 'no-reply@qrqu.id',
                'mail_from_name' => 'QRqu Gateway',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals('false', SystemSetting::get('doku_settlement_fee_enabled'));
        $this->assertEquals('0.5', SystemSetting::get('doku_settlement_fee_percent'));
    }
}



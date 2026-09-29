<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Role;
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
                'timezone' => 'Asia/Jakarta',
                'currency' => 'IDR',
                'maintenance_mode' => false,
                'default_expire_minutes' => 60,
                'api_timestamp_tolerance' => 300,
                'webhook_max_retries' => 4,
                'monthly_price' => 250000,
                'monthly_quota' => 2000,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verifikasi SystemSetting
        $this->assertEquals(250000, (float) SystemSetting::get('monthly_price'));
        $this->assertEquals(20000, (int) SystemSetting::get('monthly_quota') * 10);

        // Verifikasi Sinkronisasi ke Plan Bulanan
        $this->assertEquals(250000, (float) $this->plan->fresh()->price);
        $this->assertEquals(2000, (int) $this->plan->fresh()->transaction_limit);
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
}

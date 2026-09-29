<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SchedulerExpirationTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $this->customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Scheduler Test Merchant',
            'email' => $user->email,
            'status' => 'active',
        ]);

        $plan = Plan::create([
            'name' => 'Starter Plan',
            'slug' => 'starter',
            'duration_days' => 30,
            'price' => 150000,
            'transaction_limit' => 1000,
            'api_limit' => 10000,
            'rate_limit_rpm' => 60,
            'features' => ['qris' => true],
            'is_active' => true,
        ]);

        Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'status' => 'active',
        ]);
    }

    public function test_expire_invoices_command_marks_overdue_invoices_as_expired(): void
    {
        // 1. Overdue Invoice (sudah lewat waktu kedaluwarsa)
        $overdueInvoice = Invoice::create([
            'id' => 'INV-EXPIRED-001',
            'customer_id' => $this->customer->id,
            'external_id' => 'order-overdue-1',
            'amount' => 50000,
            'currency' => 'IDR',
            'status' => 'PENDING',
            'payment_method' => 'QRIS',
            'expired_at' => now()->subMinutes(15),
        ]);

        $overdueTransaction = Transaction::create([
            'id' => 'TXN-EXPIRED-001',
            'customer_id' => $this->customer->id,
            'invoice_id' => $overdueInvoice->id,
            'reference_id' => 'ref-overdue-1',
            'external_id' => 'order-overdue-1',
            'amount' => 50000,
            'fee' => 350,
            'net_amount' => 49650,
            'payment_method' => 'QRIS',
            'status' => 'PENDING',
        ]);

        // 2. Active Invoice (masih berlaku 1 jam ke depan)
        $activeInvoice = Invoice::create([
            'id' => 'INV-ACTIVE-001',
            'customer_id' => $this->customer->id,
            'external_id' => 'order-active-1',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => 'PENDING',
            'payment_method' => 'QRIS',
            'expired_at' => now()->addHour(),
        ]);

        $activeTransaction = Transaction::create([
            'id' => 'TXN-ACTIVE-001',
            'customer_id' => $this->customer->id,
            'invoice_id' => $activeInvoice->id,
            'reference_id' => 'ref-active-1',
            'external_id' => 'order-active-1',
            'amount' => 100000,
            'fee' => 700,
            'net_amount' => 99300,
            'payment_method' => 'QRIS',
            'status' => 'PENDING',
        ]);

        // Jalankan Artisan command
        $this->artisan('qrqu:expire-invoices')
            ->assertSuccessful();

        // Invoice overdue harus berubah status jadi EXPIRED
        $this->assertEquals('EXPIRED', $overdueInvoice->fresh()->status);
        $this->assertEquals('EXPIRED', $overdueTransaction->fresh()->status);

        // Invoice aktif harus tetap PENDING
        $this->assertEquals('PENDING', $activeInvoice->fresh()->status);
        $this->assertEquals('PENDING', $activeTransaction->fresh()->status);
    }

    public function test_reconcile_transactions_command_runs_successfully(): void
    {
        $this->artisan('qrqu:reconcile')
            ->assertSuccessful();
    }
}

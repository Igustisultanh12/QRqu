<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Settlement;
use App\Models\Ticket;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SettlementAndTicketTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $customerUser;
    private Customer $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@qrqu.id',
            'role' => 'admin',
        ]);

        $this->customerUser = User::factory()->create([
            'email' => 'merchant@test.id',
            'role' => 'customer',
        ]);

        $this->customer = Customer::create([
            'user_id' => $this->customerUser->id,
            'name' => 'Merchant Test',
            'company_name' => 'Toko Sukses Makmur',
            'email' => 'merchant@test.id',
            'phone' => '081234567890',
            'whatsapp' => '081234567890',
            'status' => 'active',
        ]);
    }

    public function test_customer_can_view_settlements_page_with_balance(): void
    {
        // Add a PAID transaction
        $invoice = Invoice::create([
            'id' => Invoice::generateId(),
            'customer_id' => $this->customer->id,
            'external_id' => 'INV-2026-0001',
            'amount' => 500000,
            'status' => 'PAID',
            'payment_method' => 'QRIS',
            'expired_at' => now()->addMinutes(30),
        ]);

        Transaction::create([
            'id' => Transaction::generateId(),
            'external_id' => $invoice->external_id,
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'amount' => $invoice->amount,
            'status' => 'PAID',
        ]);

        $response = $this->actingAs($this->customerUser)->get(route('customer.settlements.index'));
        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Customer/Settlements/Index')
            ->where('balance', 496500)
            ->where('total_income', 500000)
            ->where('pending_withdrawn', 0)
        );
    }

    public function test_customer_can_request_withdrawal(): void
    {
        // Give customer 200.000 balance
        $invoice = Invoice::create([
            'id' => Invoice::generateId(),
            'customer_id' => $this->customer->id,
            'external_id' => 'INV-2026-0002',
            'amount' => 200000,
            'status' => 'PAID',
            'payment_method' => 'QRIS',
            'expired_at' => now()->addMinutes(30),
        ]);

        Transaction::create([
            'id' => Transaction::generateId(),
            'external_id' => $invoice->external_id,
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'amount' => 200000,
            'status' => 'PAID',
        ]);

        $response = $this->actingAs($this->customerUser)->post(route('customer.settlements.store'), [
            'amount' => 150000,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Merchant Test',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('settlements', [
            'customer_id' => $this->customer->id,
            'amount' => 150000,
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Merchant Test',
            'status' => 'verifikasi',
        ]);
    }

    public function test_customer_cannot_withdraw_more_than_available_balance(): void
    {
        // Only 50.000 balance
        $invoice = Invoice::create([
            'id' => Invoice::generateId(),
            'customer_id' => $this->customer->id,
            'external_id' => 'INV-2026-0003',
            'amount' => 50000,
            'status' => 'PAID',
            'payment_method' => 'QRIS',
            'expired_at' => now()->addMinutes(30),
        ]);

        Transaction::create([
            'id' => Transaction::generateId(),
            'external_id' => $invoice->external_id,
            'invoice_id' => $invoice->id,
            'customer_id' => $this->customer->id,
            'amount' => 50000,
            'status' => 'PAID',
        ]);

        $response = $this->actingAs($this->customerUser)->post(route('customer.settlements.store'), [
            'amount' => 100000, // Exceeds balance
            'bank_name' => 'BCA',
            'account_number' => '1234567890',
            'account_name' => 'Merchant Test',
        ]);

        $response->assertSessionHasErrors(['amount']);
        $this->assertDatabaseCount('settlements', 0);
    }

    public function test_admin_can_update_settlement_status(): void
    {
        $settlement = Settlement::create([
            'settlement_number' => Settlement::generateNumber(),
            'customer_id' => $this->customer->id,
            'user_id' => $this->customerUser->id,
            'amount' => 100000,
            'bank_name' => 'Mandiri',
            'account_number' => '9876543210',
            'account_name' => 'Merchant Test',
            'status' => 'verifikasi',
        ]);

        // 1. Update to proses
        $response = $this->actingAs($this->admin)->post(route('admin.settlements.status', $settlement->id), [
            'status' => 'proses',
            'admin_notes' => 'Sedang diproses transfer via Mandiri Internet Banking',
        ]);

        $response->assertRedirect();
        $settlement->refresh();
        $this->assertEquals('proses', $settlement->status);
        $this->assertNotNull($settlement->processed_at);

        // 2. Update to selesai
        $response = $this->actingAs($this->admin)->post(route('admin.settlements.status', $settlement->id), [
            'status' => 'selesai',
            'admin_notes' => 'Transfer sukses, ref: MDR-882912',
        ]);

        $response->assertRedirect();
        $settlement->refresh();
        $this->assertEquals('selesai', $settlement->status);
        $this->assertNotNull($settlement->completed_at);
    }

    public function test_customer_can_create_ticket_and_reply(): void
    {
        // 1. Create ticket
        $response = $this->actingAs($this->customerUser)->post(route('customer.tickets.store'), [
            'category' => 'Kendala Transaksi QRIS',
            'subject' => 'Pelanggan scan tapi status masih pending',
            'description' => 'Tolong dicek untuk transaksi atas nama Budi nomor invoice INV-001.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tickets', [
            'user_id' => $this->customerUser->id,
            'category' => 'Kendala Transaksi QRIS',
            'status' => 'OPEN',
        ]);

        $ticket = Ticket::first();

        // 2. Reply to ticket
        $replyResponse = $this->actingAs($this->customerUser)->post(route('customer.tickets.reply', $ticket->id), [
            'message' => 'Tambahan informasi, pembayarannya tadi pukul 13:45 WIB.',
        ]);

        $replyResponse->assertRedirect();
        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'message' => 'Tambahan informasi, pembayarannya tadi pukul 13:45 WIB.',
            'is_admin' => false,
        ]);
    }

    public function test_admin_can_reply_to_ticket(): void
    {
        $ticket = Ticket::create([
            'ticket_number' => Ticket::generateTicketNumber(),
            'user_id' => $this->customerUser->id,
            'customer_id' => $this->customer->id,
            'category' => 'Penarikan Saldo / Settlement',
            'subject' => 'Kapan saldo saya masuk rekening?',
            'priority' => 'HIGH',
            'status' => 'OPEN',
            'description' => 'Saya sudah tarik saldo dari pagi belum ada notifikasi.',
        ]);

        $response = $this->actingAs($this->admin)->post(route('admin.tickets.reply', $ticket->id), [
            'status' => 'RESOLVED',
            'admin_reply' => 'Halo merchant, dana telah berhasil kami transfer via BCA. Silakan cek mutasi rekening.',
        ]);

        $response->assertRedirect();
        $ticket->refresh();
        $this->assertEquals('RESOLVED', $ticket->status);
        $this->assertEquals('Halo merchant, dana telah berhasil kami transfer via BCA. Silakan cek mutasi rekening.', $ticket->admin_reply);

        $this->assertDatabaseHas('ticket_replies', [
            'ticket_id' => $ticket->id,
            'is_admin' => true,
        ]);
    }
}

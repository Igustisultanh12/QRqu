<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Webhook;
use App\Models\WebhookDelivery;
use App\Services\Webhook\CustomerWebhookService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class CustomerMultiWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Store $store1;
    protected Store $store2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $this->customer = Customer::create([
            'user_id' => $this->user->id,
            'name' => 'Multi Webhook Merchant',
            'email' => $this->user->email,
            'status' => 'active',
        ]);

        $plan = Plan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro',
            'duration_days' => 30,
            'price' => 300000,
            'transaction_limit' => 5000,
            'api_limit' => 50000,
            'rate_limit_rpm' => 120,
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

        $this->store1 = Store::create([
            'customer_id' => $this->customer->id,
            'name' => 'Toko Cabang Jakarta',
            'status' => 'active',
        ]);

        $this->store2 = Store::create([
            'customer_id' => $this->customer->id,
            'name' => 'Toko Cabang Surabaya',
            'status' => 'active',
        ]);
    }

    public function test_customer_can_create_multiple_webhooks(): void
    {
        $this->actingAs($this->user);

        // 1. Create global webhook
        $response1 = $this->post('/webhooks', [
            'name' => 'Global ERP Webhook',
            'url' => 'https://erp.merchant.com/webhook',
            'secret' => 'erp_secret_1234567890123456',
            'store_id' => null,
            'is_active' => true,
        ]);
        $response1->assertRedirect();

        // 2. Create store-specific webhook
        $response2 = $this->post('/webhooks', [
            'name' => 'Jakarta POS Webhook',
            'url' => 'https://pos-jkt.merchant.com/webhook',
            'secret' => 'pos_secret_1234567890123456',
            'store_id' => $this->store1->id,
            'is_active' => true,
        ]);
        $response2->assertRedirect();

        // 3. Create another store-specific webhook (e.g. Surabaya)
        $response3 = $this->post('/webhooks', [
            'name' => 'Surabaya Bot Notification',
            'url' => 'https://bot-sby.merchant.com/webhook',
            'secret' => 'sby_secret_1234567890123456',
            'store_id' => $this->store2->id,
            'is_active' => true,
        ]);
        $response3->assertRedirect();

        $this->assertDatabaseCount('webhooks', 3);
        $this->assertDatabaseHas('webhooks', [
            'customer_id' => $this->customer->id,
            'name' => 'Global ERP Webhook',
            'store_id' => null,
        ]);
        $this->assertDatabaseHas('webhooks', [
            'customer_id' => $this->customer->id,
            'name' => 'Jakarta POS Webhook',
            'store_id' => $this->store1->id,
        ]);
        $this->assertDatabaseHas('webhooks', [
            'customer_id' => $this->customer->id,
            'name' => 'Surabaya Bot Notification',
            'store_id' => $this->store2->id,
        ]);
    }

    public function test_customer_can_update_and_toggle_and_delete_webhook(): void
    {
        $this->actingAs($this->user);

        $webhook = Webhook::create([
            'customer_id' => $this->customer->id,
            'name' => 'Initial Name',
            'url' => 'https://initial.example.com/webhook',
            'secret' => 'old_secret_1234567890123456',
            'is_active' => true,
        ]);

        // Update
        $updateResp = $this->put("/webhooks/{$webhook->id}", [
            'name' => 'Updated Name',
            'url' => 'https://updated.example.com/webhook',
            'secret' => 'new_secret_1234567890123456',
            'store_id' => $this->store1->id,
            'is_active' => true,
        ]);
        $updateResp->assertRedirect();

        $this->assertDatabaseHas('webhooks', [
            'id' => $webhook->id,
            'name' => 'Updated Name',
            'url' => 'https://updated.example.com/webhook',
            'store_id' => $this->store1->id,
        ]);

        // Toggle
        $toggleResp = $this->post("/webhooks/{$webhook->id}/toggle");
        $toggleResp->assertRedirect();
        $this->assertFalse($webhook->fresh()->is_active);

        // Delete
        $deleteResp = $this->delete("/webhooks/{$webhook->id}");
        $deleteResp->assertRedirect();
        $this->assertDatabaseMissing('webhooks', ['id' => $webhook->id]);
    }

    public function test_customer_can_test_ping_specific_webhook(): void
    {
        Http::fake([
            'https://ping.example.com/*' => Http::response(['status' => 'received'], 200),
        ]);

        $this->actingAs($this->user);

        $webhook = Webhook::create([
            'customer_id' => $this->customer->id,
            'name' => 'Ping Target',
            'url' => 'https://ping.example.com/callback',
            'secret' => 'ping_secret_1234567890123456',
            'is_active' => true,
        ]);

        $response = $this->post('/webhooks/test-ping', [
            'webhook_id' => $webhook->id,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('webhook_deliveries', [
            'customer_id' => $this->customer->id,
            'event' => 'ping.test',
            'status' => 'DELIVERED',
            'http_status' => 200,
        ]);
    }

    public function test_dispatch_payment_event_reaches_global_and_matching_store_webhooks_only(): void
    {
        Http::fake([
            'https://erp.example.com/*' => Http::response(['ok' => true], 200),
            'https://jkt.example.com/*' => Http::response(['ok' => true], 200),
            'https://sby.example.com/*' => Http::response(['ok' => true], 200),
        ]);

        // Webhook 1: Global
        $globalWebhook = Webhook::create([
            'customer_id' => $this->customer->id,
            'name' => 'Global Webhook',
            'url' => 'https://erp.example.com/webhook',
            'secret' => 'global_secret_key_123456',
            'store_id' => null,
            'is_active' => true,
        ]);

        // Webhook 2: Toko Jakarta
        $jktWebhook = Webhook::create([
            'customer_id' => $this->customer->id,
            'name' => 'Jakarta POS Webhook',
            'url' => 'https://jkt.example.com/webhook',
            'secret' => 'jkt_secret_key_123456',
            'store_id' => $this->store1->id,
            'is_active' => true,
        ]);

        // Webhook 3: Toko Surabaya
        $sbyWebhook = Webhook::create([
            'customer_id' => $this->customer->id,
            'name' => 'Surabaya POS Webhook',
            'url' => 'https://sby.example.com/webhook',
            'secret' => 'sby_secret_key_123456',
            'store_id' => $this->store2->id,
            'is_active' => true,
        ]);

        // Create transaction for Toko Jakarta ($store1)
        $invoice = Invoice::create([
            'id' => 'INV-MULTI-001',
            'customer_id' => $this->customer->id,
            'external_id' => 'ext-multi-001',
            'amount' => 75000,
            'currency' => 'IDR',
            'status' => 'PAID',
            'payment_method' => 'QRIS',
            'expired_at' => now()->addHour(),
        ]);

        $transaction = Transaction::create([
            'id' => 'TXN-MULTI-001',
            'customer_id' => $this->customer->id,
            'store_id' => $this->store1->id,
            'invoice_id' => $invoice->id,
            'reference_id' => 'ref-multi-001',
            'external_id' => 'ext-multi-001',
            'amount' => 75000,
            'payment_method' => 'QRIS',
            'status' => 'PAID',
            'paid_at' => now(),
        ]);

        $service = app(CustomerWebhookService::class);
        $service->dispatchPaymentEvent($transaction, 'payment.paid');

        // Should deliver to Global and Jakarta, but NOT Surabaya
        $deliveries = WebhookDelivery::where('customer_id', $this->customer->id)
            ->where('transaction_id', $transaction->id)
            ->get();

        $deliveredUrls = $deliveries->pluck('url')->all();
        $this->assertContains('https://erp.example.com/webhook', $deliveredUrls);
        $this->assertContains('https://jkt.example.com/webhook', $deliveredUrls);
        $this->assertNotContains('https://sby.example.com/webhook', $deliveredUrls);
        $this->assertCount(2, $deliveries);

        // Check HTTP requests
        Http::assertSent(fn ($req) => $req->url() === 'https://erp.example.com/webhook');
        Http::assertSent(fn ($req) => $req->url() === 'https://jkt.example.com/webhook');
        Http::assertNotSent(fn ($req) => $req->url() === 'https://sby.example.com/webhook');
    }
}

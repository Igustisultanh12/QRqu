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
            'webhook_limit' => 3,
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

    public function test_customer_cannot_exceed_plan_webhook_quota(): void
    {
        $this->actingAs($this->user);

        // Limit plan to 1 webhook
        $this->customer->activeSubscription->plan->update(['webhook_limit' => 1]);

        // 1. Create first webhook -> should succeed
        $response1 = $this->post('/webhooks', [
            'name' => 'First Allowed Webhook',
            'url' => 'https://allowed1.example.com/webhook',
        ]);
        $response1->assertRedirect();
        $response1->assertSessionHas('success');
        $this->assertDatabaseCount('webhooks', 1);

        // 2. Attempt to create second webhook -> should fail with error
        $response2 = $this->post('/webhooks', [
            'name' => 'Second Exceeded Webhook',
            'url' => 'https://exceeded2.example.com/webhook',
        ]);
        $response2->assertRedirect();
        $response2->assertSessionHas('error');
        $this->assertDatabaseCount('webhooks', 1);
        $this->assertDatabaseMissing('webhooks', ['name' => 'Second Exceeded Webhook']);
    }

    public function test_customer_can_purchase_webhook_addon_and_increase_quota(): void
    {
        $this->actingAs($this->user);

        // Limit plan to 1 webhook
        $this->customer->activeSubscription->plan->update(['webhook_limit' => 1]);

        // Create 1 webhook
        Webhook::create([
            'customer_id' => $this->customer->id,
            'name' => 'Existing Webhook',
            'url' => 'https://existing.example.com/webhook',
            'secret' => 'whsec_existing12345678',
        ]);

        $this->assertFalse($this->customer->fresh()->canAddWebhook());

        // Purchase +2 Add-on Webhook slots
        $addonResponse = $this->post('/webhooks/addon/purchase', [
            'quantity' => 2,
        ]);

        $addonResponse->assertRedirect();
        $this->assertDatabaseHas('customer_addons', [
            'customer_id' => $this->customer->id,
            'type' => 'webhook_slot',
            'quantity' => 2,
            'status' => 'pending_payment',
        ]);

        $addon = \App\Models\CustomerAddon::where('customer_id', $this->customer->id)->first();
        $invoice = $addon->invoice;
        $this->assertNotNull($invoice);
        $this->assertEquals(50000, (float) $invoice->amount);

        // Simulate invoice payment via transaction PAID transition
        $transaction = Transaction::create([
            'id' => 'TXN-ADDON-001',
            'customer_id' => $this->customer->id,
            'invoice_id' => $invoice->id,
            'external_id' => $invoice->external_id,
            'amount' => 50000,
            'payment_method' => 'QRIS',
            'status' => 'PENDING',
        ]);

        $transaction->transitionTo('PAID', 'doku_notification', 'Paid via QRIS');

        // Verify addon is now active
        $this->assertEquals('active', $addon->fresh()->status);
        $this->assertNotNull($addon->fresh()->paid_at);

        // Customer now has max 3 allowed (1 from plan + 2 from addon)
        $this->assertEquals(3, $this->customer->fresh()->getMaxWebhooksAllowed());
        $this->assertTrue($this->customer->fresh()->canAddWebhook());

        // Now creating second webhook succeeds
        $respSuccess = $this->post('/webhooks', [
            'name' => 'Second Webhook via Addon',
            'url' => 'https://addon-wh.example.com/webhook',
        ]);
        $respSuccess->assertRedirect();
        $respSuccess->assertSessionHas('success');
        $this->assertDatabaseCount('webhooks', 2);
    }

    public function test_admin_can_update_plan_webhook_limit_and_addon_price(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $this->actingAs($admin);

        $plan = Plan::first();

        // 1. Update plan with new webhook_limit
        $updatePlanResp = $this->put("/admin/plans/{$plan->id}", [
            'name' => $plan->name,
            'duration_days' => $plan->duration_days,
            'price' => $plan->price,
            'transaction_limit' => $plan->transaction_limit,
            'api_limit' => $plan->api_limit,
            'rate_limit_rpm' => $plan->rate_limit_rpm,
            'webhook_limit' => 5,
            'status' => 'active',
        ]);
        $updatePlanResp->assertRedirect();
        $this->assertEquals(5, $plan->fresh()->webhook_limit);

        // 2. Update setting webhook_addon_price
        $updateSettingResp = $this->post('/admin/settings', [
            'app_name' => 'QRqu',
            'app_url' => 'https://qrqu.id',
            'timezone' => 'Asia/Jakarta',
            'currency' => 'IDR',
            'maintenance_mode' => false,
            'default_expire_minutes' => 60,
            'api_timestamp_tolerance' => 300,
            'webhook_max_retries' => 4,
            'monthly_price' => 150000,
            'monthly_quota' => 1000,
            'webhook_addon_price' => 30000,
            'mail_mailer' => 'smtp',
            'mail_from_address' => 'no-reply@qrqu.id',
            'mail_from_name' => 'QRqu',
        ]);
        $updateSettingResp->assertRedirect();
        $this->assertEquals('30000', (string) \App\Models\SystemSetting::get('webhook_addon_price'));
    }
}

<?php

namespace Tests\Feature;

use App\Models\ApiCredential;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Store;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class CustomerApiCredentialStoreTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected Store $store1;
    protected Store $store2;
    protected User $otherUser;
    protected Customer $otherCustomer;
    protected Store $otherStore;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $this->customer = Customer::create([
            'user_id' => $this->user->id,
            'name' => 'Store Merchant',
            'email' => $this->user->email,
            'status' => 'active',
        ]);

        $this->store1 = Store::create([
            'customer_id' => $this->customer->id,
            'name' => 'Toko Utama',
            'code' => 'STORE-MAIN',
            'is_default' => true,
            'is_active' => true,
        ]);

        $this->store2 = Store::create([
            'customer_id' => $this->customer->id,
            'name' => 'JAKORLAP Store',
            'code' => 'STORE-JAKORLAP',
            'is_default' => false,
            'is_active' => true,
        ]);

        $this->otherUser = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $this->otherCustomer = Customer::create([
            'user_id' => $this->otherUser->id,
            'name' => 'Other Merchant',
            'email' => $this->otherUser->email,
            'status' => 'active',
        ]);
        $this->otherStore = Store::create([
            'customer_id' => $this->otherCustomer->id,
            'name' => 'Other Store',
            'code' => 'STORE-OTHER',
            'is_default' => true,
            'is_active' => true,
        ]);

        $plan = Plan::create([
            'name' => 'Pro Plan',
            'slug' => 'pro',
            'duration_days' => 30,
            'price' => 300000,
            'transaction_limit' => 5000,
            'api_limit' => 50000,
            'rate_limit_rpm' => 120,
            'webhook_limit' => 5,
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

    public function test_merchant_can_create_api_credential_with_specific_store(): void
    {
        $response = $this->actingAs($this->user)->post(route('customer.credentials.store'), [
            'name' => 'JAKORLAP Key',
            'environment' => 'production',
            'store_id' => $this->store2->id,
        ]);

        $response->assertRedirect(route('customer.credentials.index'));
        $this->assertDatabaseHas('api_credentials', [
            'customer_id' => $this->customer->id,
            'name' => 'JAKORLAP Key',
            'store_id' => $this->store2->id,
        ]);
    }

    public function test_merchant_cannot_create_credential_with_another_merchants_store(): void
    {
        $response = $this->actingAs($this->user)->post(route('customer.credentials.store'), [
            'name' => 'Invalid Store Key',
            'environment' => 'production',
            'store_id' => $this->otherStore->id,
        ]);

        $response->assertSessionHasErrors(['store_id']);
        $this->assertDatabaseMissing('api_credentials', [
            'name' => 'Invalid Store Key',
        ]);
    }

    public function test_merchant_can_update_existing_credential_store(): void
    {
        $plainSecret = 'sec_test_secret_1234567890';
        $credential = ApiCredential::create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store1->id,
            'name' => 'Old Key Name',
            'environment' => 'production',
            'api_key' => 'qrqu_live_' . Str::random(32),
            'api_secret_hash' => Hash::make($plainSecret),
            'api_secret_encrypted' => Crypt::encryptString($plainSecret),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->put(route('customer.credentials.update', $credential), [
            'name' => 'Renamed to JAKORLAP',
            'store_id' => $this->store2->id,
        ]);

        $response->assertRedirect(route('customer.credentials.index'));
        $this->assertDatabaseHas('api_credentials', [
            'id' => $credential->id,
            'name' => 'Renamed to JAKORLAP',
            'store_id' => $this->store2->id,
        ]);
    }

    public function test_merchant_cannot_update_another_merchants_credential(): void
    {
        $plainSecret = 'sec_test_secret_1234567890';
        $otherCredential = ApiCredential::create([
            'customer_id' => $this->otherCustomer->id,
            'store_id' => $this->otherStore->id,
            'name' => 'Other Merchant Key',
            'environment' => 'production',
            'api_key' => 'qrqu_live_' . Str::random(32),
            'api_secret_hash' => Hash::make($plainSecret),
            'api_secret_encrypted' => Crypt::encryptString($plainSecret),
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->user)->put(route('customer.credentials.update', $otherCredential), [
            'name' => 'Hacked Key',
            'store_id' => $this->store1->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_api_invoice_creation_inherits_store_from_credential(): void
    {
        $plainSecret = 'sec_jakorlap_secret_9999999999';
        $credential = ApiCredential::create([
            'customer_id' => $this->customer->id,
            'store_id' => $this->store2->id,
            'name' => 'JAKORLAP Dedicated Key',
            'environment' => 'production',
            'api_key' => 'qrqu_live_' . Str::random(32),
            'api_secret_hash' => Hash::make($plainSecret),
            'api_secret_encrypted' => Crypt::encryptString($plainSecret),
            'status' => 'active',
        ]);

        $timestamp = (string) time();
        $nonce = (string) Str::uuid();
        $payload = json_encode([
            'external_id' => 'ORDER-JAKORLAP-001',
            'amount' => 125000,
            'description' => 'JAKORLAP transaction via API',
        ]);

        $signature = hash_hmac('sha256', $credential->api_key . $timestamp . $nonce . $payload, $plainSecret);

        $response = $this->call('POST', '/api/v1/invoices', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_QRQU_KEY' => $credential->api_key,
            'HTTP_X_QRQU_TIMESTAMP' => $timestamp,
            'HTTP_X_QRQU_NONCE' => $nonce,
            'HTTP_X_QRQU_SIGNATURE' => $signature,
        ], $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'external_id' => 'ORDER-JAKORLAP-001',
                    'amount' => 125000,
                ],
            ]);

        $invoice = Invoice::where('external_id', 'ORDER-JAKORLAP-001')->first();
        $this->assertNotNull($invoice);
        $this->assertEquals($this->store2->id, $invoice->store_id);

        $this->assertDatabaseHas('transactions', [
            'invoice_id' => $invoice->id,
            'store_id' => $this->store2->id,
            'customer_id' => $this->customer->id,
        ]);
    }
}

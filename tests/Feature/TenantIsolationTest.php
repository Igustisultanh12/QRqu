<?php

namespace Tests\Feature;

use App\Models\ApiCredential;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class TenantIsolationTest extends TestCase
{
    use RefreshDatabase;

    protected User $userA;
    protected Customer $customerA;
    protected ApiCredential $credentialA;
    protected string $secretA = 'sec_tenant_a_secret_key_123456789';

    protected User $userB;
    protected Customer $customerB;
    protected ApiCredential $credentialB;
    protected string $secretB = 'sec_tenant_b_secret_key_987654321';

    protected Invoice $invoiceA;
    protected Transaction $transactionA;

    protected function setUp(): void
    {
        parent::setUp();

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

        // Customer A Setup
        $this->userA = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $this->customerA = Customer::create([
            'user_id' => $this->userA->id,
            'name' => 'Merchant Alpha',
            'email' => $this->userA->email,
            'status' => 'active',
        ]);
        Subscription::create([
            'customer_id' => $this->customerA->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'status' => 'active',
        ]);
        $this->credentialA = ApiCredential::create([
            'customer_id' => $this->customerA->id,
            'environment' => 'production',
            'name' => 'Live Key Alpha',
            'api_key' => 'qrqu_live_alpha_' . Str::random(24),
            'api_secret_hash' => Hash::make($this->secretA),
            'api_secret_encrypted' => Crypt::encryptString($this->secretA),
            'status' => 'active',
        ]);

        // Customer B Setup
        $this->userB = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $this->customerB = Customer::create([
            'user_id' => $this->userB->id,
            'name' => 'Merchant Beta',
            'email' => $this->userB->email,
            'status' => 'active',
        ]);
        Subscription::create([
            'customer_id' => $this->customerB->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'status' => 'active',
        ]);
        $this->credentialB = ApiCredential::create([
            'customer_id' => $this->customerB->id,
            'environment' => 'production',
            'name' => 'Live Key Beta',
            'api_key' => 'qrqu_live_beta_' . Str::random(24),
            'api_secret_hash' => Hash::make($this->secretB),
            'api_secret_encrypted' => Crypt::encryptString($this->secretB),
            'status' => 'active',
        ]);

        // Invoice milik Customer A
        $this->invoiceA = Invoice::create([
            'id' => 'INV-ALPHA-001',
            'customer_id' => $this->customerA->id,
            'external_id' => 'order-alpha-001',
            'amount' => 100000,
            'currency' => 'IDR',
            'status' => 'PENDING',
            'payment_method' => 'QRIS',
            'description' => 'Invoice Tenant A',
            'expired_at' => now()->addHour(),
        ]);

        $this->transactionA = Transaction::create([
            'id' => 'TXN-ALPHA-001',
            'customer_id' => $this->customerA->id,
            'invoice_id' => $this->invoiceA->id,
            'reference_id' => 'ref-alpha-001',
            'external_id' => 'order-alpha-001',
            'amount' => 100000,
            'fee' => 700,
            'net_amount' => 99300,
            'payment_method' => 'QRIS',
            'status' => 'PENDING',
        ]);
    }

    protected function sendSignedApiRequest(string $method, string $uri, array $data, ApiCredential $credential, string $secret)
    {
        $timestamp = (string) time();
        $nonce = (string) Str::uuid();
        $body = !empty($data) ? json_encode($data) : '';

        $signature = hash_hmac('sha256', $credential->api_key . $timestamp . $nonce . $body, $secret);

        return $this->call($method, $uri, [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_QRQU_KEY' => $credential->api_key,
            'HTTP_X_QRQU_TIMESTAMP' => $timestamp,
            'HTTP_X_QRQU_NONCE' => $nonce,
            'HTTP_X_QRQU_SIGNATURE' => $signature,
        ], $body);
    }

    public function test_customer_b_cannot_get_invoice_of_customer_a_via_api(): void
    {
        $path = '/api/v1/invoices/' . $this->invoiceA->id;
        $response = $this->sendSignedApiRequest('GET', $path, [], $this->credentialB, $this->secretB);

        // Harus 404 (Not Found) untuk isolasi multi-tenant data
        $response->assertStatus(404);
        $response->assertJson([
            'success' => false,
            'error' => [
                'code' => 'INVOICE_NOT_FOUND',
            ],
        ]);
    }

    public function test_customer_b_cannot_cancel_invoice_of_customer_a_via_api(): void
    {
        $path = '/api/v1/invoices/' . $this->invoiceA->id . '/cancel';
        $response = $this->sendSignedApiRequest('POST', $path, [], $this->credentialB, $this->secretB);

        $response->assertStatus(404);
        $this->assertEquals('PENDING', $this->invoiceA->fresh()->status);
    }

    public function test_customer_b_cannot_view_customer_a_transaction_in_web_portal(): void
    {
        $response = $this->actingAs($this->userB)
            ->get('/transactions/' . $this->transactionA->id);

        $response->assertStatus(403);
    }

    public function test_customer_b_cannot_revoke_customer_a_api_credential(): void
    {
        $response = $this->actingAs($this->userB)
            ->post('/credentials/' . $this->credentialA->id . '/revoke');

        $response->assertStatus(403);
        $this->assertEquals('active', $this->credentialA->fresh()->status);
    }
}

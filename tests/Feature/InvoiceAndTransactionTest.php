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

class InvoiceAndTransactionTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected ApiCredential $credential;
    protected string $plainSecret = 'sec_invoice_test_secret_1234567890';

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $this->customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Invoice Test Merchant',
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
            'status' => 'active',
        ]);

        Subscription::create([
            'customer_id' => $this->customer->id,
            'plan_id' => $plan->id,
            'starts_at' => now(),
            'expires_at' => now()->addDays(30),
            'status' => 'active',
        ]);

        $this->credential = ApiCredential::create([
            'customer_id' => $this->customer->id,
            'name' => 'Invoice Key',
            'environment' => 'production',
            'api_key' => 'qrqu_live_' . Str::random(32),
            'api_secret_hash' => Hash::make($this->plainSecret),
            'api_secret_encrypted' => Crypt::encryptString($this->plainSecret),
            'status' => 'active',
        ]);
    }

    protected function sendSignedApiRequest(string $method, string $uri, array $body = [], array $extraHeaders = []): \Illuminate\Testing\TestResponse
    {
        $timestamp = (string) time();
        $nonce = (string) Str::uuid();
        $payload = !empty($body) ? json_encode($body) : '';
        $signature = hash_hmac('sha256', $this->credential->api_key . $timestamp . $nonce . $payload, $this->plainSecret);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_QRQU_KEY' => $this->credential->api_key,
            'HTTP_X_QRQU_TIMESTAMP' => $timestamp,
            'HTTP_X_QRQU_NONCE' => $nonce,
            'HTTP_X_QRQU_SIGNATURE' => $signature,
        ];

        foreach ($extraHeaders as $k => $v) {
            $headers['HTTP_' . strtoupper(str_replace('-', '_', $k))] = $v;
        }

        return $this->call($method, $uri, [], [], [], $headers, $payload);
    }

    public function test_create_invoice_successfully(): void
    {
        $response = $this->sendSignedApiRequest('POST', '/api/v1/invoices', [
            'external_id' => 'ORDER-1001',
            'amount' => 150000,
            'description' => 'Pembayaran Order #1001',
            'customer' => [
                'name' => 'Andi Wijaya',
                'email' => 'andi@example.com',
                'phone' => '08123456789',
            ],
            'callback_url' => 'https://merchant.test/callback',
            'webhook_url' => 'https://merchant.test/webhook',
        ]);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'external_id' => 'ORDER-1001',
                    'amount' => 150000,
                    'status' => 'PENDING',
                    'payment_method' => 'QRIS',
                ],
            ]);

        $this->assertDatabaseHas('invoices', [
            'customer_id' => $this->customer->id,
            'external_id' => 'ORDER-1001',
            'amount' => 150000,
            'status' => 'PENDING',
        ]);

        $this->assertDatabaseHas('transactions', [
            'customer_id' => $this->customer->id,
            'external_id' => 'ORDER-1001',
            'amount' => 150000,
            'status' => 'PENDING',
        ]);
    }

    public function test_idempotency_key_returns_identical_cached_response(): void
    {
        $idempotencyKey = (string) Str::uuid();

        $body = [
            'external_id' => 'ORDER-IDEMP-01',
            'amount' => 200000,
        ];

        // First call
        $res1 = $this->sendSignedApiRequest('POST', '/api/v1/invoices', $body, ['Idempotency-Key' => $idempotencyKey]);
        $res1->assertStatus(201);
        $invoiceId1 = $res1->json('data.invoice_id');

        // Second call with same Idempotency-Key
        $res2 = $this->sendSignedApiRequest('POST', '/api/v1/invoices', $body, ['Idempotency-Key' => $idempotencyKey]);
        $res2->assertStatus(201);
        $res2->assertHeader('X-Idempotent-Replay', 'true');
        $invoiceId2 = $res2->json('data.invoice_id');

        $this->assertEquals($invoiceId1, $invoiceId2);
        $this->assertEquals(1, Invoice::where('external_id', 'ORDER-IDEMP-01')->count());
    }

    public function test_duplicate_external_id_returns_existing_invoice_without_duplication(): void
    {
        $body = [
            'external_id' => 'ORDER-DUP-01',
            'amount' => 50000,
        ];

        // Call without Idempotency-Key twice
        $res1 = $this->sendSignedApiRequest('POST', '/api/v1/invoices', $body);
        $res1->assertStatus(201);

        $res2 = $this->sendSignedApiRequest('POST', '/api/v1/invoices', $body);
        $res2->assertStatus(201);

        $this->assertEquals(
            $res1->json('data.invoice_id'),
            $res2->json('data.invoice_id')
        );

        $this->assertEquals(1, Invoice::where('customer_id', $this->customer->id)->where('external_id', 'ORDER-DUP-01')->count());
    }

    public function test_get_invoice_by_id_or_external_id(): void
    {
        $createRes = $this->sendSignedApiRequest('POST', '/api/v1/invoices', [
            'external_id' => 'ORDER-GET-01',
            'amount' => 120000,
        ]);
        $invoiceId = $createRes->json('data.invoice_id');

        // Query by invoice ID
        $getRes = $this->sendSignedApiRequest('GET', "/api/v1/invoices/{$invoiceId}");
        $getRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'invoice_id' => $invoiceId,
                    'external_id' => 'ORDER-GET-01',
                    'amount' => 120000,
                ],
            ]);

        // Query by external ID
        $getExtRes = $this->sendSignedApiRequest('GET', "/api/v1/invoices/ORDER-GET-01");
        $getExtRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'invoice_id' => $invoiceId,
                ],
            ]);
    }

    public function test_cancel_pending_invoice(): void
    {
        $createRes = $this->sendSignedApiRequest('POST', '/api/v1/invoices', [
            'external_id' => 'ORDER-CANCEL-01',
            'amount' => 100000,
        ]);
        $invoiceId = $createRes->json('data.invoice_id');

        $cancelRes = $this->sendSignedApiRequest('POST', "/api/v1/invoices/{$invoiceId}/cancel");
        $cancelRes->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'invoice_id' => $invoiceId,
                    'status' => 'CANCELLED',
                ],
            ]);

        $this->assertDatabaseHas('invoices', [
            'id' => $invoiceId,
            'status' => 'CANCELLED',
        ]);
    }
}

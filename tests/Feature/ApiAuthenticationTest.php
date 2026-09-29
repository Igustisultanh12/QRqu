<?php

namespace Tests\Feature;

use App\Models\ApiCredential;
use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class ApiAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Customer $customer;
    protected ApiCredential $credential;
    protected string $plainSecret = 'sec_test_secret_key_1234567890abcdef';

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'role' => 'customer',
            'status' => 'active',
        ]);

        $this->customer = Customer::create([
            'user_id' => $this->user->id,
            'name' => 'Test Merchant',
            'company_name' => 'PT Test Merchant',
            'email' => $this->user->email,
            'status' => 'active',
        ]);

        $plan = Plan::create([
            'name' => 'Starter Test',
            'slug' => 'starter-test',
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
            'name' => 'Test Key',
            'environment' => 'production',
            'api_key' => 'qrqu_live_' . Str::random(32),
            'api_secret_hash' => Hash::make($this->plainSecret),
            'api_secret_encrypted' => Crypt::encryptString($this->plainSecret),
            'status' => 'active',
        ]);
    }

    public function test_missing_auth_headers_returns_401(): void
    {
        $response = $this->postJson('/api/v1/invoices', [
            'external_id' => 'ORDER-001',
            'amount' => 50000,
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'MISSING_AUTH_HEADERS',
                ],
            ]);
    }

    public function test_invalid_signature_returns_401(): void
    {
        $timestamp = (string) time();
        $nonce = (string) Str::uuid();

        $response = $this->postJson('/api/v1/invoices', [
            'external_id' => 'ORDER-001',
            'amount' => 50000,
        ], [
            'X-QRQU-Key' => $this->credential->api_key,
            'X-QRQU-Timestamp' => $timestamp,
            'X-QRQU-Nonce' => $nonce,
            'X-QRQU-Signature' => 'invalid_signature_hash',
        ]);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'INVALID_SIGNATURE',
                ],
            ]);
    }

    public function test_expired_timestamp_is_rejected(): void
    {
        $expiredTimestamp = (string) (time() - 400); // 400 seconds ago > 300s tolerance
        $nonce = (string) Str::uuid();
        $payload = json_encode(['external_id' => 'ORDER-001', 'amount' => 50000]);
        $signature = hash_hmac('sha256', $this->credential->api_key . $expiredTimestamp . $nonce . $payload, $this->plainSecret);

        $response = $this->call('POST', '/api/v1/invoices', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_QRQU_KEY' => $this->credential->api_key,
            'HTTP_X_QRQU_TIMESTAMP' => $expiredTimestamp,
            'HTTP_X_QRQU_NONCE' => $nonce,
            'HTTP_X_QRQU_SIGNATURE' => $signature,
        ], $payload);

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'TIMESTAMP_EXPIRED',
                ],
            ]);
    }

    public function test_replay_attack_with_same_nonce_is_rejected(): void
    {
        $timestamp = (string) time();
        $nonce = 'test-nonce-123456';
        $payload = json_encode(['external_id' => 'ORDER-001', 'amount' => 50000]);
        $signature = hash_hmac('sha256', $this->credential->api_key . $timestamp . $nonce . $payload, $this->plainSecret);

        $headers = [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_QRQU_KEY' => $this->credential->api_key,
            'HTTP_X_QRQU_TIMESTAMP' => $timestamp,
            'HTTP_X_QRQU_NONCE' => $nonce,
            'HTTP_X_QRQU_SIGNATURE' => $signature,
        ];

        // First attempt succeeds
        $res1 = $this->call('POST', '/api/v1/invoices', [], [], [], $headers, $payload);
        $res1->assertStatus(201);

        // Second attempt with exact same nonce is rejected as replay attack
        $res2 = $this->call('POST', '/api/v1/invoices', [], [], [], $headers, $payload);
        $res2->assertStatus(401)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'REPLAY_ATTACK_DETECTED',
                ],
            ]);
    }

    public function test_valid_hmac_request_authenticates_successfully(): void
    {
        $timestamp = (string) time();
        $nonce = (string) Str::uuid();
        $payload = json_encode([
            'external_id' => 'ORDER-VALID-001',
            'amount' => 75000,
            'description' => 'Test Valid HMAC Payment',
        ]);

        $signature = hash_hmac('sha256', $this->credential->api_key . $timestamp . $nonce . $payload, $this->plainSecret);

        $response = $this->call('POST', '/api/v1/invoices', [], [], [], [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X_QRQU_KEY' => $this->credential->api_key,
            'HTTP_X_QRQU_TIMESTAMP' => $timestamp,
            'HTTP_X_QRQU_NONCE' => $nonce,
            'HTTP_X_QRQU_SIGNATURE' => $signature,
        ], $payload);

        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
                'data' => [
                    'external_id' => 'ORDER-VALID-001',
                    'amount' => 75000,
                    'status' => 'PENDING',
                    'payment_method' => 'QRIS',
                ],
            ]);
    }
}

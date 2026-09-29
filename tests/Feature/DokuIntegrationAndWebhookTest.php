<?php

namespace Tests\Feature;

use App\Jobs\SendCustomerWebhookJob;
use App\Models\Customer;
use App\Models\DokuWebhook;
use App\Models\Invoice;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Payment\Doku\DokuService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class DokuIntegrationAndWebhookTest extends TestCase
{
    use RefreshDatabase;

    protected Customer $customer;
    protected Invoice $invoice;
    protected Transaction $transaction;

    protected function setUp(): void
    {
        parent::setUp();

        $user = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $this->customer = Customer::create([
            'user_id' => $user->id,
            'name' => 'Doku Test Merchant',
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

        $this->invoice = Invoice::create([
            'id' => 'INV-TEST-001',
            'customer_id' => $this->customer->id,
            'external_id' => 'ext-doku-001',
            'amount' => 50000,
            'currency' => 'IDR',
            'status' => 'PENDING',
            'payment_method' => 'QRIS',
            'qr_string' => '00020101021226670016ID.DOKU.WWW01189360000100000000015204581253033605802ID5914DOKU MERCHANT6007JAKARTA61051293062070703A0163040000',
            'description' => 'Test Inbound DOKU Webhook',
            'webhook_url' => 'https://merchant.example.com/webhook',
            'expired_at' => now()->addHour(),
        ]);

        $this->transaction = Transaction::create([
            'id' => 'TXN-TEST-001',
            'customer_id' => $this->customer->id,
            'invoice_id' => $this->invoice->id,
            'reference_id' => 'ref-doku-001',
            'external_id' => 'ext-doku-001',
            'amount' => 50000,
            'fee' => 350,
            'net_amount' => 49650,
            'payment_method' => 'QRIS',
            'status' => 'PENDING',
        ]);
    }

    public function test_doku_webhook_marks_invoice_and_transaction_as_paid(): void
    {
        Queue::fake();

        $payload = [
            'order' => [
                'invoice_number' => $this->invoice->id,
                'amount' => 50000,
            ],
            'transaction' => [
                'status' => 'SUCCESS',
                'original_request_id' => 'DOKU-REQ-UUID-12345',
                'id' => 'DOKU-TX-99999',
            ],
            'emoney_payment' => [
                'approval_code' => 'APPR123456',
            ],
        ];

        $response = $this->postJson('/api/webhooks/doku', $payload, [
            'Request-Id' => 'DOKU-REQ-UUID-12345',
            'Signature' => 'test-signature-header',
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'OK',
            'status' => 'PAID',
        ]);

        // Verifikasi database QRqu
        $this->assertEquals('PAID', $this->transaction->fresh()->status);
        $this->assertEquals('PAID', $this->invoice->fresh()->status);
        $this->assertNotNull($this->transaction->fresh()->status_changed_at);
        $this->assertNotNull($this->invoice->fresh()->paid_at);

        // Verifikasi DokuWebhook audit log
        $this->assertDatabaseHas('doku_webhooks', [
            'invoice_number' => $this->invoice->id,
            'is_processed' => true,
        ]);

        // Verifikasi Outgoing customer webhook dispatched
        Queue::assertPushed(SendCustomerWebhookJob::class);
    }

    public function test_doku_webhook_is_idempotent(): void
    {
        Queue::fake();

        // Tandai transaksi sudah lunas
        $this->transaction->update(['status' => 'PAID', 'paid_at' => now()]);
        $this->invoice->update(['status' => 'PAID', 'paid_at' => now()]);

        $payload = [
            'order' => [
                'invoice_number' => $this->invoice->id,
            ],
            'transaction' => [
                'status' => 'SUCCESS',
                'original_request_id' => 'DOKU-REQ-UUID-12345',
            ],
        ];

        $response = $this->postJson('/api/webhooks/doku', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Transaction already processed or finalized',
        ]);

        // Tidak boleh kirim ulang webhook ke merchant
        Queue::assertNotPushed(SendCustomerWebhookJob::class);
    }

    public function test_doku_webhook_handles_failed_status(): void
    {
        Queue::fake();

        $payload = [
            'order' => [
                'invoice_number' => $this->invoice->id,
            ],
            'transaction' => [
                'status' => 'FAILED',
                'original_request_id' => 'DOKU-REQ-FAILED-1',
            ],
        ];

        $response = $this->postJson('/api/webhooks/doku', $payload);

        $response->assertStatus(200);
        $response->assertJson([
            'message' => 'Payment marked as FAILED',
        ]);

        $this->assertEquals('FAILED', $this->transaction->fresh()->status);
        $this->assertEquals('FAILED', $this->invoice->fresh()->status);
    }

    public function test_doku_webhook_returns_404_for_unknown_invoice(): void
    {
        $payload = [
            'order' => [
                'invoice_number' => 'INV-DOES-NOT-EXIST-999',
            ],
            'transaction' => [
                'status' => 'SUCCESS',
            ],
        ];

        $response = $this->postJson('/api/webhooks/doku', $payload);

        $response->assertStatus(404);
        $response->assertJson([
            'message' => 'Transaction/Invoice Not Found',
        ]);
    }

    public function test_doku_service_generate_qris_simulation(): void
    {
        Http::fake([
            '*/checkout/v1/payment' => Http::response([
                'payment' => [
                    'url' => 'https://sandbox.doku.com/checkout/pay/12345',
                    'qr_string' => '00020101021226670016ID.DOKU.WWW01189360000100000000015204581253033605802ID5914DOKU MERCHANT6007JAKARTA61051293062070703A0163040000',
                ],
            ], 200),
        ]);

        $dokuService = app(DokuService::class);

        $result = $dokuService->generateQris($this->transaction, $this->invoice);

        $this->assertNotNull($result);
        $this->assertNotEmpty($result['qr_string']);
        $this->assertNotEmpty($result['payment_url']);
        $this->assertNotEmpty($result['request_id']);
    }
}

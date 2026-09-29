<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Plan;
use App\Models\Subscription;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class RegistrationVerificationAndSubscriptionLockTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_registration_does_not_auto_subscribe_and_requires_email_verification(): void
    {
        Event::fake([Registered::class]);

        $response = $this->post('/register', [
            'name' => 'Merchant Baru',
            'company_name' => 'PT Toko Baru',
            'email' => 'toko@baru.com',
            'phone' => '081298765432',
            'whatsapp' => '081298765432',
            'password' => 'Password123#',
            'password_confirmation' => 'Password123#',
        ]);

        $this->assertAuthenticated();
        Event::assertDispatched(Registered::class);

        $user = User::where('email', 'toko@baru.com')->first();
        $this->assertNotNull($user);
        $this->assertNull($user->email_verified_at);

        $customer = Customer::where('user_id', $user->id)->first();
        $this->assertNotNull($customer);
        // Customer MUST NOT have an active subscription upon registration
        $this->assertFalse($customer->hasActiveSubscription());
        $this->assertNull($customer->activeSubscription);

        // Unverified user can access dashboard and see unverified status notification
        $dashRes = $this->actingAs($user)->get('/dashboard');
        $dashRes->assertStatus(200);
        $dashRes->assertInertia(fn ($page) => $page
            ->component('Customer/Dashboard')
            ->where('auth.user.has_verified_email', false)
            ->where('auth.user.customer.has_active_subscription', false)
        );
    }

    public function test_unsubscribed_customer_cannot_access_locked_api_menus(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);

        $this->assertFalse($customer->hasActiveSubscription());

        // API Credentials locked
        $credRes = $this->actingAs($user)->get('/credentials');
        $credRes->assertRedirect(route('customer.subscription.index'));
        $credRes->assertSessionHas('error');

        // Webhooks locked
        $webhookRes = $this->actingAs($user)->get('/webhooks');
        $webhookRes->assertRedirect(route('customer.subscription.index'));
        $webhookRes->assertSessionHas('error');

        // API Usage locked
        $usageRes = $this->actingAs($user)->get('/api-usage');
        $usageRes->assertRedirect(route('customer.subscription.index'));
        $usageRes->assertSessionHas('error');
    }

    public function test_subscribing_to_plan_unlocks_api_menus(): void
    {
        $user = User::factory()->create(['role' => 'customer', 'status' => 'active']);
        $customer = Customer::create([
            'user_id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'status' => 'active',
        ]);

        $plan = Plan::create([
            'name' => 'Paket Hemat Rp 1',
            'slug' => 'paket-hemat-1',
            'duration_days' => 30,
            'price' => 1,
            'transaction_limit' => 500,
            'api_limit' => 5000,
            'rate_limit_rpm' => 60,
            'status' => 'active',
        ]);

        $subRes = $this->actingAs($user)->post("/subscription/{$plan->id}/subscribe");
        $subRes->assertRedirect();

        $subscription = Subscription::where('customer_id', $customer->id)->latest()->first();
        $this->assertNotNull($subscription);
        $this->assertEquals('pending_payment', $subscription->status);

        // Simulasi pembayaran QRIS untuk mengaktifkan paket langganan
        $simRes = $this->postJson("/checkout/{$subscription->invoice_id}/simulate");
        $simRes->assertStatus(200);

        $this->assertTrue($customer->fresh()->hasActiveSubscription());

        // Now credentials and webhooks can be accessed without redirection
        $credRes = $this->actingAs($user->fresh())->get('/credentials');
        $credRes->assertStatus(200);

        $webhookRes = $this->actingAs($user->fresh())->get('/webhooks');
        $webhookRes->assertStatus(200);

        $usageRes = $this->actingAs($user->fresh())->get('/api-usage');
        $usageRes->assertStatus(200);
    }

    public function test_admin_can_set_plan_price_starting_from_one_rupiah(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        // Create plan with price Rp 1
        $createRes = $this->actingAs($admin)->post('/admin/plans', [
            'name' => 'Paket Uji Coba Rp 1',
            'duration_days' => 7,
            'price' => 1,
            'transaction_limit' => 100,
            'api_limit' => 1000,
            'rate_limit_rpm' => 30,
            'status' => 'active',
        ]);

        $createRes->assertRedirect();
        $this->assertDatabaseHas('plans', [
            'name' => 'Paket Uji Coba Rp 1',
            'price' => 1.00,
        ]);

        $plan = Plan::where('name', 'Paket Uji Coba Rp 1')->first();

        // Update plan price to Rp 5000
        $updateRes = $this->actingAs($admin)->put("/admin/plans/{$plan->id}", [
            'name' => 'Paket Uji Coba Rp 1 Updated',
            'duration_days' => 7,
            'price' => 5000,
            'transaction_limit' => 200,
            'api_limit' => 2000,
            'rate_limit_rpm' => 60,
            'status' => 'active',
        ]);

        $updateRes->assertRedirect();
        $this->assertDatabaseHas('plans', [
            'id' => $plan->id,
            'price' => 5000.00,
        ]);
    }
}

<?php

namespace Tests\Feature;

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_welcome_page_displays_active_plans_set_by_admin(): void
    {
        Plan::create([
            'name' => 'Custom Pro Plan',
            'slug' => 'custom-pro-plan',
            'duration_days' => 45,
            'price' => 250000,
            'transaction_limit' => 3000,
            'api_limit' => 20000,
            'rate_limit_rpm' => 120,
            'webhook_limit' => 20000,
            'features' => ['QRIS Dinamis', 'Rate Limit 120 RPM'],
            'status' => 'active',
        ]);

        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('plans', 1)
            ->where('plans.0.name', 'Custom Pro Plan')
            ->where('plans.0.price', '250000.00')
        );
    }
}

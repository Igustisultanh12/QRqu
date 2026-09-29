<?php

namespace Database\Seeders;

use App\Models\ApiCredential;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\PaymentMethod;
use App\Models\Permission;
use App\Models\Plan;
use App\Models\Role;
use App\Models\Subscription;
use App\Models\SubscriptionHistory;
use App\Models\SystemSetting;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Webhook;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Roles & Permissions
        $adminRole = Role::firstOrCreate(['name' => 'admin'], ['description' => 'Administrator with full system privileges']);
        $customerRole = Role::firstOrCreate(['name' => 'customer'], ['description' => 'Merchant/developer account']);

        $permissions = [
            'manage_customers', 'manage_subscriptions', 'manage_plans',
            'manage_transactions', 'view_transactions', 'manage_api_keys',
            'view_api_keys', 'manage_webhooks', 'view_audit_logs',
            'manage_settings', 'manage_doku', 'reconcile_transactions'
        ];

        foreach ($permissions as $p) {
            $perm = Permission::firstOrCreate(['name' => $p]);
            $adminRole->permissions()->syncWithoutDetaching([$perm->id]);
        }

        // 2. Subscription Plans
        $planMonthly = Plan::updateOrCreate(
            ['slug' => 'monthly-30d'],
            [
                'name' => 'Paket 1 Bulan (Starter)',
                'duration_days' => 30,
                'price' => 150000,
                'transaction_limit' => 1000,
                'api_limit' => 10000,
                'rate_limit_rpm' => 60,
                'webhook_limit' => 10000,
                'features' => ['QRIS Dinamis', 'DOKU Direct Integration', 'Sandbox & Live Environment', 'Rate Limit 60 RPM'],
                'status' => 'active',
            ]
        );

        $planQuarterly = Plan::updateOrCreate(
            ['slug' => 'quarterly-90d'],
            [
                'name' => 'Paket 3 Bulan (Business)',
                'duration_days' => 90,
                'price' => 400000,
                'transaction_limit' => 5000,
                'api_limit' => 50000,
                'rate_limit_rpm' => 300,
                'webhook_limit' => 50000,
                'features' => ['Semua fitur Starter', 'Prioritas Antrean Webhook', 'Rate Limit 300 RPM', 'Multi IP Whitelist'],
                'status' => 'active',
            ]
        );

        $planSemiannual = Plan::updateOrCreate(
            ['slug' => 'semiannual-180d'],
            [
                'name' => 'Paket 6 Bulan (Enterprise)',
                'duration_days' => 180,
                'price' => 750000,
                'transaction_limit' => 25000,
                'api_limit' => 200000,
                'rate_limit_rpm' => 1000,
                'webhook_limit' => 200000,
                'features' => ['Semua fitur Business', 'Dedicated Rate Limit 1000 RPM', 'Custom Webhook Retry Policy', 'Dukungan Teknis Prioritas 24/7'],
                'status' => 'active',
            ]
        );

        // 3. Payment Methods
        PaymentMethod::updateOrCreate(
            ['code' => 'QRIS'],
            [
                'name' => 'QRIS Realtime (DOKU Gateway)',
                'provider' => 'doku',
                'is_active' => true,
                'fee_flat' => 0,
                'fee_percentage' => 0.70, // 0.7% MDR standar QRIS
            ]
        );

        // 4. Default Settings
        SystemSetting::set('app_name', 'QRqu Payment Gateway');
        SystemSetting::set('doku_base_url', env('DOKU_BASE_URL', 'https://api-sandbox.doku.com'), 'doku');
        SystemSetting::set('doku_client_id', env('DOKU_CLIENT_ID', ''), 'doku');
        SystemSetting::set('doku_secret_key', env('DOKU_SECRET_KEY', ''), 'doku');
        SystemSetting::set('api_timestamp_tolerance', '300', 'api');
        SystemSetting::set('default_expire_minutes', '60', 'transaction');
        SystemSetting::set('webhook_max_retries', '4', 'webhook');

        // 5. Admin User
        $admin = User::updateOrCreate(
            ['email' => 'admin@qrqu.id'],
            [
                'name' => 'Master Administrator',
                'role' => 'admin',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $admin->roles()->syncWithoutDetaching([$adminRole->id => ['model_type' => User::class]]);

        // 6. Demo Merchant Customer
        $merchantUser = User::updateOrCreate(
            ['email' => 'merchant@tokoku.com'],
            [
                'name' => 'Budi Santoso',
                'role' => 'customer',
                'status' => 'active',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $merchantUser->roles()->syncWithoutDetaching([$customerRole->id => ['model_type' => User::class]]);

        $merchantCustomer = Customer::updateOrCreate(
            ['user_id' => $merchantUser->id],
            [
                'name' => 'Budi Santoso',
                'company_name' => 'Tokoku Pratama Indonesia',
                'email' => 'merchant@tokoku.com',
                'phone' => '081234567890',
                'whatsapp' => '081234567890',
                'address' => 'Jl. Sudirman No. 45, Jakarta Selatan',
                'country' => 'ID',
                'status' => 'active',
            ]
        );

        // Demo Subscription
        $subscription = Subscription::updateOrCreate(
            ['customer_id' => $merchantCustomer->id],
            [
                'plan_id' => $planMonthly->id,
                'starts_at' => now()->subDays(5),
                'expires_at' => now()->addDays(25),
                'grace_period_days' => 3,
                'status' => 'active',
                'auto_renew' => true,
            ]
        );

        SubscriptionHistory::firstOrCreate(
            ['subscription_id' => $subscription->id],
            [
                'customer_id' => $merchantCustomer->id,
                'plan_id' => $planMonthly->id,
                'event' => 'created',
                'note' => 'Aktivasi Paket Bulanan Perdana',
                'amount_paid' => $planMonthly->price,
            ]
        );

        // Demo API Credentials
        $liveSecret = 'sec_live_demo_1234567890abcdefghijklmnop';
        ApiCredential::updateOrCreate(
            ['api_key' => 'qrqu_live_tokoku_demo_key_12345'],
            [
                'customer_id' => $merchantCustomer->id,
                'name' => 'Production Key Tokoku',
                'environment' => 'production',
                'api_secret_hash' => Hash::make($liveSecret),
                'api_secret_encrypted' => Crypt::encryptString($liveSecret),
                'status' => 'active',
            ]
        );

        $sandboxSecret = 'sec_sand_demo_1234567890abcdefghijklmnop';
        ApiCredential::updateOrCreate(
            ['api_key' => 'qrqu_sand_tokoku_test_key_98765'],
            [
                'customer_id' => $merchantCustomer->id,
                'name' => 'Sandbox Testing Key',
                'environment' => 'sandbox',
                'api_secret_hash' => Hash::make($sandboxSecret),
                'api_secret_encrypted' => Crypt::encryptString($sandboxSecret),
                'status' => 'active',
            ]
        );

        // Demo Webhook configuration
        Webhook::updateOrCreate(
            ['customer_id' => $merchantCustomer->id],
            [
                'url' => 'https://tokoku.example.com/api/payment/webhook',
                'secret' => 'whsec_tokoku_demo_secret_key',
                'events' => ['payment.paid', 'payment.failed', 'payment.expired'],
                'is_active' => true,
            ]
        );

        // Demo Sample Invoice and Transaction
        $invoice = Invoice::updateOrCreate(
            ['id' => 'INV-20260929-DEMO001'],
            [
                'customer_id' => $merchantCustomer->id,
                'external_id' => 'ORDER-20260929-001',
                'amount' => 150000,
                'description' => 'Pembayaran Order Tokoku #001',
                'customer_name' => 'John Doe',
                'customer_email' => 'customer@example.com',
                'customer_phone' => '08123456789',
                'callback_url' => 'https://tokoku.example.com/payment/callback',
                'webhook_url' => 'https://tokoku.example.com/api/payment/webhook',
                'status' => 'PAID',
                'payment_method' => 'QRIS',
                'expired_at' => now()->addMinutes(60),
                'paid_at' => now()->subMinutes(10),
            ]
        );

        $trx = Transaction::updateOrCreate(
            ['id' => 'TRX-20260929-DEMO001'],
            [
                'invoice_id' => $invoice->id,
                'customer_id' => $merchantCustomer->id,
                'external_id' => 'ORDER-20260929-001',
                'amount' => 150000,
                'status' => 'PAID',
                'previous_status' => 'PENDING',
                'status_changed_at' => now()->subMinutes(10),
                'doku_reference' => 'DOKU-REF-202609290001',
                'payment_gateway_ref' => 'DOKU-REF-202609290001',
            ]
        );
    }
}

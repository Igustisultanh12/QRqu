<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Roles and Permissions
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('guard_name')->default('web');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('guard_name')->default('web');
            $table->string('group')->default('general');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->primary(['role_id', 'permission_id']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->index(['model_id', 'model_type']);
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        // 2. Customers (Tenants)
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->string('name');
            $table->string('company_name')->nullable();
            $table->string('email');
            $table->string('phone')->nullable();
            $table->string('whatsapp')->nullable();
            $table->text('address')->nullable();
            $table->string('country')->default('ID');
            $table->string('status', 30)->default('active')->index(); // 'pending', 'active', 'suspended', 'expired', 'blocked'
            $table->timestamps();
        });

        // 3. Plans & Subscriptions
        Schema::create('plans', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->integer('duration_days')->default(30);
            $table->decimal('price', 16, 2)->default(0);
            $table->integer('transaction_limit')->default(1000);
            $table->integer('api_limit')->default(10000);
            $table->integer('rate_limit_rpm')->default(60);
            $table->integer('webhook_limit')->default(10000);
            $table->json('features')->nullable();
            $table->string('status', 20)->default('active')->index();
            $table->timestamps();
        });

        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->timestamp('starts_at');
            $table->timestamp('expires_at')->index();
            $table->integer('grace_period_days')->default(3);
            $table->string('status', 30)->default('active')->index(); // 'pending', 'active', 'expired', 'cancelled', 'suspended'
            $table->boolean('auto_renew')->default(false);
            $table->timestamps();
        });

        Schema::create('subscription_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('subscription_id')->nullable()->index();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('event', 50); // 'created', 'renewed', 'upgraded', 'expired', 'cancelled'
            $table->text('note')->nullable();
            $table->decimal('amount_paid', 16, 2)->default(0);
            $table->timestamps();
        });

        // 4. API Credentials & Usage
        Schema::create('api_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('name')->default('Production Key');
            $table->string('environment', 20)->default('production')->index(); // 'production', 'sandbox'
            $table->string('api_key', 80)->unique();
            $table->string('api_secret_hash');
            $table->text('api_secret_encrypted');
            $table->json('ip_whitelist')->nullable();
            $table->string('status', 20)->default('active')->index(); // 'active', 'revoked'
            $table->timestamp('expires_at')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        Schema::create('api_usages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('api_credential_id')->nullable()->constrained('api_credentials')->nullOnDelete();
            $table->date('date')->index();
            $table->integer('request_count')->default(0);
            $table->json('endpoint_counts')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'date', 'api_credential_id'], 'customer_date_cred_unique');
        });

        // 5. Invoices & Transactions
        Schema::create('invoices', function (Blueprint $table) {
            $table->string('id', 50)->primary(); // e.g. INV-20260929-ABC12345
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('external_id')->index();
            $table->decimal('amount', 16, 2);
            $table->text('description')->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_email')->nullable();
            $table->string('customer_phone')->nullable();
            $table->text('callback_url')->nullable();
            $table->text('webhook_url')->nullable();
            $table->string('status', 30)->default('PENDING')->index(); // 'CREATED', 'PENDING', 'PAID', 'FAILED', 'EXPIRED', 'CANCELLED', 'REFUNDED'
            $table->string('payment_method', 30)->default('QRIS');
            $table->longText('qr_string')->nullable();
            $table->text('qr_url')->nullable();
            $table->timestamp('expired_at')->index();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['customer_id', 'external_id'], 'customer_external_unique');
        });

        Schema::create('transactions', function (Blueprint $table) {
            $table->string('id', 50)->primary(); // e.g. TRX-20260929-ABC12345
            $table->string('invoice_id', 50)->index();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('external_id')->index();
            $table->decimal('amount', 16, 2);
            $table->string('status', 30)->default('CREATED')->index();
            $table->string('previous_status', 30)->nullable();
            $table->timestamp('status_changed_at')->nullable();
            $table->string('doku_reference')->nullable()->index();
            $table->string('payment_gateway_ref')->nullable();
            $table->string('doku_request_id')->nullable();
            $table->json('doku_response')->nullable();
            $table->timestamps();

            $table->foreign('invoice_id')->references('id')->on('invoices')->cascadeOnDelete();
        });

        Schema::create('transaction_status_histories', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 50)->index();
            $table->string('from_status', 30)->nullable();
            $table->string('to_status', 30);
            $table->string('trigger', 50); // 'doku_webhook', 'scheduler', 'manual_admin', 'api'
            $table->text('reason')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('transaction_id')->references('id')->on('transactions')->cascadeOnDelete();
        });

        // 6. Payment Methods
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name');
            $table->string('provider', 50)->default('doku');
            $table->boolean('is_active')->default(true);
            $table->decimal('fee_flat', 16, 2)->default(0);
            $table->decimal('fee_percentage', 5, 2)->default(0);
            $table->timestamps();
        });

        // 7. DOKU Integration Logs
        Schema::create('doku_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_id', 50)->index();
            $table->string('invoice_number', 50)->index();
            $table->string('request_id')->nullable()->index();
            $table->string('client_id')->nullable();
            $table->decimal('amount', 16, 2);
            $table->text('doku_url')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->string('status', 30)->default('PENDING');
            $table->timestamps();
        });

        Schema::create('doku_webhooks', function (Blueprint $table) {
            $table->id();
            $table->string('event_id')->nullable()->index();
            $table->string('original_request_id')->nullable()->index();
            $table->string('invoice_number', 50)->index();
            $table->json('payload');
            $table->string('signature')->nullable();
            $table->json('http_headers')->nullable();
            $table->boolean('is_processed')->default(false);
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });

        // 8. Outgoing Customer Webhooks
        Schema::create('webhooks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->text('url');
            $table->string('secret');
            $table->json('events')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('webhook_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('transaction_id', 50)->nullable()->index();
            $table->string('invoice_id', 50)->nullable()->index();
            $table->string('event_id', 64)->index();
            $table->string('event', 50);
            $table->text('url');
            $table->json('payload');
            $table->string('signature', 128);
            $table->integer('attempt')->default(1);
            $table->integer('max_attempts')->default(4);
            $table->integer('http_status')->nullable();
            $table->text('response_body')->nullable();
            $table->integer('duration_ms')->nullable();
            $table->string('status', 30)->default('PENDING')->index(); // 'PENDING', 'DELIVERED', 'FAILED', 'RETRYING'
            $table->timestamp('next_retry_at')->nullable()->index();
            $table->timestamps();
        });

        // 9. Idempotency Keys
        Schema::create('idempotency_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('key', 128)->index();
            $table->string('request_path');
            $table->string('request_params_hash', 64);
            $table->integer('response_code')->default(200);
            $table->json('response_body');
            $table->timestamp('expires_at')->index();
            $table->timestamps();

            $table->unique(['customer_id', 'key'], 'customer_idempotency_unique');
        });

        // 10. Logs & Security
        Schema::create('api_logs', function (Blueprint $table) {
            $table->id();
            $table->string('request_id', 64)->index();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('api_credential_id')->nullable()->constrained('api_credentials')->nullOnDelete();
            $table->string('method', 10);
            $table->string('path');
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->integer('status_code');
            $table->integer('duration_ms');
            $table->json('request_payload')->nullable();
            $table->json('response_body')->nullable();
            $table->string('error_code', 50)->nullable();
            $table->timestamps();
        });

        Schema::create('security_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('ip_address', 45);
            $table->text('user_agent')->nullable();
            $table->string('event_type', 60)->index();
            $table->string('severity', 20)->default('medium'); // 'low', 'medium', 'high', 'critical'
            $table->json('details')->nullable();
            $table->timestamps();
        });

        Schema::create('system_settings', function (Blueprint $table) {
            $table->id();
            $table->string('key', 100)->unique();
            $table->text('value')->nullable();
            $table->string('group', 50)->default('general');
            $table->string('description')->nullable();
            $table->timestamps();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('action', 100)->index();
            $table->string('target_type', 100)->nullable();
            $table->string('target_id', 100)->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('before')->nullable();
            $table->json('after')->nullable();
            $table->timestamps();
        });

        // 11. Notifications
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->morphs('notifiable');
            $table->text('data');
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('security_logs');
        Schema::dropIfExists('api_logs');
        Schema::dropIfExists('idempotency_keys');
        Schema::dropIfExists('webhook_deliveries');
        Schema::dropIfExists('webhooks');
        Schema::dropIfExists('doku_webhooks');
        Schema::dropIfExists('doku_transactions');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('transaction_status_histories');
        Schema::dropIfExists('transactions');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('api_usages');
        Schema::dropIfExists('api_credentials');
        Schema::dropIfExists('subscription_histories');
        Schema::dropIfExists('subscriptions');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('customers');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};

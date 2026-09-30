<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('customer_addons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_id')->nullable()->index();
            $table->string('type')->default('webhook_slot');
            $table->integer('quantity')->default(1);
            $table->decimal('price_paid', 12, 2)->default(0);
            $table->string('status')->default('pending_payment'); // pending_payment, active, cancelled
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();
        });

        Schema::table('plans', function (Blueprint $table) {
            $table->integer('webhook_limit')->default(1)->change();
        });

        // Set realistic webhook_limit on initial seed plans:
        // Starter (1000 trx) => 1 webhook
        // Business (5000 trx) => 3 webhooks
        // Enterprise (25000 trx) => 10 webhooks
        DB::table('plans')->where('slug', 'like', '%starter%')->orWhere('id', 1)->update(['webhook_limit' => 1]);
        DB::table('plans')->where('slug', 'like', '%business%')->orWhere('id', 2)->update(['webhook_limit' => 3]);
        DB::table('plans')->where('slug', 'like', '%enterprise%')->orWhere('id', 3)->update(['webhook_limit' => 10]);

        // Default setting for webhook addon price if not already present
        if (!DB::table('system_settings')->where('key', 'webhook_addon_price')->exists()) {
            DB::table('system_settings')->insert([
                'key' => 'webhook_addon_price',
                'value' => '25000',
                'group' => 'pricing',
                'description' => 'Harga Add-on per Slot Webhook (IDR)',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_addons');
        DB::table('system_settings')->where('key', 'webhook_addon_price')->delete();
    }
};

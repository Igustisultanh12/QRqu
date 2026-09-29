<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->string('invoice_id', 100)->nullable()->index()->after('plan_id');
        });

        // Sanitize any existing DOKU mentions in plans table
        try {
            \App\Models\Plan::all()->each(function ($plan) {
                if (is_array($plan->features)) {
                    $cleaned = array_map(function ($feat) {
                        return str_ireplace('DOKU Direct Integration', 'Direct Gateway Integration', str_ireplace('DOKU', 'Gateway', $feat));
                    }, $plan->features);
                    $plan->update(['features' => $cleaned]);
                }
            });
        } catch (\Throwable $e) {
            // Ignore during fresh testing if table not yet populated
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->dropColumn('invoice_id');
        });
    }
};

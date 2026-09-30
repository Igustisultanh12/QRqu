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
        Schema::table('api_credentials', function (Blueprint $table) {
            $table->foreignId('store_id')->nullable()->after('customer_id')->constrained('stores')->nullOnDelete();
        });

        // Auto-link existing api_credentials to matching stores if possible, or customer default store
        $credentials = DB::table('api_credentials')->get();
        foreach ($credentials as $cred) {
            $matchingStore = DB::table('stores')
                ->where('customer_id', $cred->customer_id)
                ->where(function ($q) use ($cred) {
                    $q->where('name', 'like', '%' . $cred->name . '%')
                      ->orWhere('code', 'like', '%' . $cred->name . '%');
                })
                ->first();

            $storeId = $matchingStore?->id;
            if (!$storeId) {
                $defaultStore = DB::table('stores')
                    ->where('customer_id', $cred->customer_id)
                    ->where('is_default', 1)
                    ->first();
                $storeId = $defaultStore?->id;
            }

            if ($storeId) {
                DB::table('api_credentials')->where('id', $cred->id)->update(['store_id' => $storeId]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('api_credentials', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn('store_id');
        });
    }
};

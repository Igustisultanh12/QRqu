<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->string('name')->nullable()->after('customer_id');
            $table->foreignId('store_id')->nullable()->after('name')->constrained('stores')->nullOnDelete();
            $table->string('description')->nullable()->after('url');
        });
    }

    public function down(): void
    {
        Schema::table('webhooks', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->dropColumn(['name', 'store_id', 'description']);
        });
    }
};

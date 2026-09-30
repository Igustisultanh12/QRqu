<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('stores')) {
            Schema::create('stores', function (Blueprint $table) {
                $table->id();
                $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
                $table->string('name', 100);
                $table->string('code', 50)->nullable()->index();
                $table->text('description')->nullable();
                $table->text('address')->nullable();
                $table->string('phone', 30)->nullable();
                $table->boolean('is_default')->default(false);
                $table->string('status', 20)->default('active')->index();
                $table->timestamps();
            });
        }

        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'store_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->foreignId('store_id')->nullable()->after('customer_id')->constrained('stores')->nullOnDelete();
            });
        }

        if (Schema::hasTable('transactions') && !Schema::hasColumn('transactions', 'store_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->foreignId('store_id')->nullable()->after('customer_id')->constrained('stores')->nullOnDelete();
            });
        }

        if (Schema::hasTable('settlements') && !Schema::hasColumn('settlements', 'store_id')) {
            Schema::table('settlements', function (Blueprint $table) {
                $table->foreignId('store_id')->nullable()->after('customer_id')->constrained('stores')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('settlements') && Schema::hasColumn('settlements', 'store_id')) {
            Schema::table('settlements', function (Blueprint $table) {
                $table->dropForeign(['store_id']);
                $table->dropColumn('store_id');
            });
        }

        if (Schema::hasTable('transactions') && Schema::hasColumn('transactions', 'store_id')) {
            Schema::table('transactions', function (Blueprint $table) {
                $table->dropForeign(['store_id']);
                $table->dropColumn('store_id');
            });
        }

        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'store_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropForeign(['store_id']);
                $table->dropColumn('store_id');
            });
        }

        Schema::dropIfExists('stores');
    }
};

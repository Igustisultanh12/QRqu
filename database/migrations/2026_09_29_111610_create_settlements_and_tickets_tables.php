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
        // 1. Settlements (Penarikan Saldo Pelanggan / Merchant)
        Schema::create('settlements', function (Blueprint $table) {
            $table->id();
            $table->string('settlement_number', 64)->unique();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->decimal('amount', 15, 2);
            $table->string('bank_name', 50);
            $table->string('account_number', 100);
            $table->string('account_name', 100);
            $table->string('status', 30)->default('verifikasi')->index(); // 'verifikasi', 'proses', 'selesai', 'ditolak'
            $table->text('admin_notes')->nullable();
            $table->string('proof_file')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        // 2. Support Tickets (Pengaduan & Bantuan)
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('ticket_number', 64)->unique();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();
            $table->string('subject', 255);
            $table->string('category', 100);
            $table->string('priority', 20)->default('MEDIUM');
            $table->string('status', 30)->default('OPEN')->index(); // 'OPEN', 'IN_PROGRESS', 'RESOLVED', 'CLOSED'
            $table->text('description');
            $table->text('admin_reply')->nullable();
            $table->timestamps();
        });

        // 3. Ticket Replies (Thread Chat Tiket)
        Schema::create('ticket_replies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ticket_id')->constrained('tickets')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('message');
            $table->boolean('is_admin')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ticket_replies');
        Schema::dropIfExists('tickets');
        Schema::dropIfExists('settlements');
    }
};

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
        Schema::create('cash_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Nama akun: "Kas Toko", "Bank BCA"
            $table->string('code')->unique(); // KAS-TOKO, BANK-BCA
            $table->enum('type', ['cash', 'bank']); // Jenis: kas tunai atau bank
            $table->boolean('is_active')->default(true); // Status aktif
            $table->decimal('opening_balance', 15, 2)->default(0); // Saldo awal
            $table->decimal('current_balance', 15, 2)->default(0); // Saldo saat ini
            $table->text('notes')->nullable(); // Catatan
            $table->foreignId('created_by')->constrained('users'); // User yang buat
            $table->timestamps();
            
            // Indexes
            $table->index('type');
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_accounts');
    }
};

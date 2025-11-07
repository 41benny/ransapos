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
        Schema::create('coa_accounts', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // 4-100, 5-200, dst
            $table->string('name'); // Penjualan Resto, Biaya Listrik, dst
            $table->enum('type', ['income', 'expense', 'asset', 'liability', 'equity']); // Tipe akun
            $table->string('group'); // PENDAPATAN, HPP, BIAYA OPERASIONAL, dst
            $table->boolean('is_active')->default(true); // Status aktif
            $table->text('notes')->nullable(); // Catatan
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
        Schema::dropIfExists('coa_accounts');
    }
};

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
        Schema::create('cash_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('transaction_number')->unique(); // CT-001-20251107-001
            $table->foreignId('cash_account_id')->constrained('cash_accounts')->cascadeOnDelete();
            $table->enum('type', ['in', 'out']); // Jenis: kas masuk atau kas keluar
            $table->date('transaction_date'); // Tanggal transaksi
            $table->decimal('amount', 15, 2); // Jumlah
            $table->decimal('balance_before', 15, 2); // Saldo sebelum
            $table->decimal('balance_after', 15, 2); // Saldo sesudah
            $table->string('description'); // Deskripsi transaksi
            
            // Referensi opsional (untuk link ke transaksi lain)
            $table->string('reference_type')->nullable(); // purchase, sale, transfer, dll
            $table->unsignedBigInteger('reference_id')->nullable(); // ID referensi
            
            $table->text('notes')->nullable(); // Catatan tambahan
            $table->foreignId('created_by')->constrained('users'); // User yang catat
            $table->timestamps();
            
            // Indexes
            $table->index('cash_account_id');
            $table->index('type');
            $table->index('transaction_date');
            $table->index(['reference_type', 'reference_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cash_transactions');
    }
};

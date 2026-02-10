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
        Schema::create('bank_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('transfer_number', 50)->unique()->comment('Nomor transfer (auto-generated)');
            $table->foreignId('from_cash_account_id')->constrained('cash_accounts')->onDelete('restrict');
            $table->foreignId('to_cash_account_id')->constrained('cash_accounts')->onDelete('restrict');
            $table->date('transfer_date')->comment('Tanggal transfer');
            $table->decimal('amount', 15, 2)->comment('Jumlah transfer');
            $table->text('description')->comment('Deskripsi transfer');
            $table->text('notes')->nullable()->comment('Catatan tambahan');
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            // Indexes
            $table->index('transfer_date');
            $table->index('from_cash_account_id');
            $table->index('to_cash_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bank_transfers');
    }
};

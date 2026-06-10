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
        Schema::table('sales_types', function (Blueprint $table) {
            $table->foreignId('default_payment_method_id')
                ->nullable()
                ->after('channel_type')
                ->comment('Metode bayar yang otomatis dipakai untuk tipe penjualan ini (mis. kanal online)')
                ->constrained('payment_methods')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_types', function (Blueprint $table) {
            $table->dropConstrainedForeignId('default_payment_method_id');
        });
    }
};

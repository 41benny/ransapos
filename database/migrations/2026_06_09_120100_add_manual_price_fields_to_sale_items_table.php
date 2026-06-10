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
        Schema::table('sale_items', function (Blueprint $table) {
            $table->decimal('normal_price', 15, 2)
                ->nullable()
                ->after('unit_price')
                ->comment('Harga preset sesuai sales_type saat transaksi, sebagai pembanding harga manual');
            $table->boolean('is_manual_price')
                ->default(false)
                ->after('normal_price')
                ->comment('True jika unit_price hasil input manual (berbeda dari normal_price), khusus kanal online');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sale_items', function (Blueprint $table) {
            $table->dropColumn(['normal_price', 'is_manual_price']);
        });
    }
};

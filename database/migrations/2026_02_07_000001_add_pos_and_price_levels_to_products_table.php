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
        Schema::table('products', function (Blueprint $table) {
            $table->boolean('is_sellable')
                ->default(true)
                ->after('product_type')
                ->comment('Apakah produk dijual langsung');
            $table->boolean('is_pos_available')
                ->default(true)
                ->after('is_sellable')
                ->comment('Tersedia di POS');
            $table->boolean('is_online_order_available')
                ->default(false)
                ->after('is_pos_available')
                ->comment('Tersedia di online order');
            $table->boolean('is_available_all_outlets')
                ->default(true)
                ->after('is_online_order_available')
                ->comment('Ketersediaan untuk semua outlet');
            $table->boolean('is_available_all_users')
                ->default(true)
                ->after('is_available_all_outlets')
                ->comment('Ketersediaan untuk semua pengguna POS');
            $table->json('pos_outlet_ids')
                ->nullable()
                ->after('is_available_all_users')
                ->comment('Daftar outlet yang diizinkan jika tidak semua outlet');
            $table->json('price_levels')
                ->nullable()
                ->after('selling_price')
                ->comment('Harga berdasarkan level, contoh: regular, gofood, grabfood');
        });

        DB::table('products')
            ->where('product_type', 'raw_material')
            ->update([
                'is_sellable' => false,
                'is_pos_available' => false,
                'is_online_order_available' => false,
                'is_available_all_outlets' => true,
                'pos_outlet_ids' => null,
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'is_sellable',
                'is_pos_available',
                'is_online_order_available',
                'is_available_all_outlets',
                'is_available_all_users',
                'pos_outlet_ids',
                'price_levels',
            ]);
        });
    }
};

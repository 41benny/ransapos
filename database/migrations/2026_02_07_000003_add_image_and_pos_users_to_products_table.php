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
        Schema::table('products', function (Blueprint $table) {
            $table->string('image_path')
                ->nullable()
                ->after('description')
                ->comment('Path gambar produk untuk POS');
            $table->json('pos_user_ids')
                ->nullable()
                ->after('pos_outlet_ids')
                ->comment('Daftar user POS yang diizinkan jika tidak semua pengguna');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['image_path', 'pos_user_ids']);
        });
    }
};

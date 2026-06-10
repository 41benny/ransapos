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
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->boolean('is_online_only')
                ->default(false)
                ->after('is_active')
                ->comment('True jika metode bayar khusus kanal online (disembunyikan saat penjualan offline)');
        });

        // Tandai metode bayar food-delivery sebagai khusus online.
        DB::table('payment_methods')
            ->whereIn('code', ['GO_FOOD', 'GRAB_FOOD', 'SHOPEE_FOOD'])
            ->update(['is_online_only' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('payment_methods', function (Blueprint $table) {
            $table->dropColumn('is_online_only');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if Hutang Usaha already exists
        $exists = DB::table('coa_accounts')->where('code', '2-100')->exists();
        if (!$exists) {
            DB::table('coa_accounts')->insert([
                'code' => '2-100',
                'name' => 'Hutang Usaha / Supplier',
                'type' => 'liability',
                'group' => 'HUTANG',
                'is_active' => true,
                'notes' => 'Hutang kepada supplier atas pembelian barang secara kredit',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('coa_accounts')->where('code', '2-100')->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const COA_CODE = 'EXP-OUTLET-LAINNYA';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $exists = DB::table('coa_accounts')
            ->where('code', self::COA_CODE)
            ->exists();

        if ($exists) {
            return;
        }

        DB::table('coa_accounts')->insert([
            'code' => self::COA_CODE,
            'name' => 'Keperluan Outlet Lainnya',
            'type' => 'expense',
            'group' => 'BIAYA OPERASIONAL',
            'is_active' => true,
            'notes' => 'Akun default petty cash POS untuk kebutuhan outlet lainnya',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $coaId = DB::table('coa_accounts')
            ->where('code', self::COA_CODE)
            ->value('id');

        if (!$coaId) {
            return;
        }

        $hasTransactions = DB::table('cash_transactions')
            ->where('coa_account_id', $coaId)
            ->exists();

        if ($hasTransactions) {
            return;
        }

        DB::table('coa_accounts')
            ->where('id', $coaId)
            ->delete();
    }
};

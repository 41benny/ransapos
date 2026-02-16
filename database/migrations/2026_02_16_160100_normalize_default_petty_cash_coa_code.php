<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const LEGACY_CODE = 'EXP-OUTLET-LAINNYA';
    private const PREVIOUS_TARGET_CODE = '6-190';
    private const TARGET_CODE = '6-135';
    private const TARGET_NAME = 'Biaya Keperluan Outlet Lainnya';
    private const TARGET_GROUP = 'BIAYA OPERASIONAL';
    private const TARGET_NOTES = 'Akun default petty cash POS untuk kebutuhan outlet lainnya';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $target = DB::table('coa_accounts')
            ->where('code', self::TARGET_CODE)
            ->first();

        // Prioritas sumber saat target belum ada: 6-190 -> EXP-OUTLET-LAINNYA.
        $legacyCandidate = DB::table('coa_accounts')
            ->whereIn('code', [self::PREVIOUS_TARGET_CODE, self::LEGACY_CODE])
            ->orderByRaw(
                'CASE code WHEN ? THEN 0 WHEN ? THEN 1 ELSE 2 END',
                [self::PREVIOUS_TARGET_CODE, self::LEGACY_CODE]
            )
            ->first();

        if (! $target && $legacyCandidate) {
            DB::table('coa_accounts')
                ->where('id', $legacyCandidate->id)
                ->update([
                    'code' => self::TARGET_CODE,
                    'name' => self::TARGET_NAME,
                    'type' => 'expense',
                    'group' => self::TARGET_GROUP,
                    'is_active' => true,
                    'notes' => self::TARGET_NOTES,
                    'updated_at' => now(),
                ]);
        }

        $target = DB::table('coa_accounts')
            ->where('code', self::TARGET_CODE)
            ->first();

        if (! $target) {
            DB::table('coa_accounts')
                ->insert([
                    'code' => self::TARGET_CODE,
                    'name' => self::TARGET_NAME,
                    'type' => 'expense',
                    'group' => self::TARGET_GROUP,
                    'is_active' => true,
                    'notes' => self::TARGET_NOTES,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

            $target = DB::table('coa_accounts')
                ->where('code', self::TARGET_CODE)
                ->first();
        }

        if (! $target) {
            return;
        }

        DB::table('coa_accounts')
            ->where('id', $target->id)
            ->update([
                'name' => self::TARGET_NAME,
                'type' => 'expense',
                'group' => self::TARGET_GROUP,
                'is_active' => true,
                'notes' => self::TARGET_NOTES,
                'updated_at' => now(),
            ]);

        $legacyIds = DB::table('coa_accounts')
            ->whereIn('code', [self::PREVIOUS_TARGET_CODE, self::LEGACY_CODE])
            ->where('id', '!=', $target->id)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        foreach ($legacyIds as $legacyId) {
            if (Schema::hasTable('cash_transactions') && Schema::hasColumn('cash_transactions', 'coa_account_id')) {
                DB::table('cash_transactions')
                    ->where('coa_account_id', $legacyId)
                    ->update(['coa_account_id' => $target->id]);
            }

            if (Schema::hasTable('expense_categories') && Schema::hasColumn('expense_categories', 'coa_account_id')) {
                DB::table('expense_categories')
                    ->where('coa_account_id', $legacyId)
                    ->update(['coa_account_id' => $target->id]);
            }

            if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'coa_account_id')) {
                DB::table('expenses')
                    ->where('coa_account_id', $legacyId)
                    ->update(['coa_account_id' => $target->id]);
            }

            DB::table('coa_accounts')
                ->where('id', $legacyId)
                ->delete();
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $target = DB::table('coa_accounts')
            ->where('code', self::TARGET_CODE)
            ->first();

        if (! $target) {
            return;
        }

        $previous = DB::table('coa_accounts')
            ->where('code', self::PREVIOUS_TARGET_CODE)
            ->first();

        if ($previous && (int) $previous->id !== (int) $target->id) {
            if (Schema::hasTable('cash_transactions') && Schema::hasColumn('cash_transactions', 'coa_account_id')) {
                DB::table('cash_transactions')
                    ->where('coa_account_id', $target->id)
                    ->update(['coa_account_id' => $previous->id]);
            }

            if (Schema::hasTable('expense_categories') && Schema::hasColumn('expense_categories', 'coa_account_id')) {
                DB::table('expense_categories')
                    ->where('coa_account_id', $target->id)
                    ->update(['coa_account_id' => $previous->id]);
            }

            if (Schema::hasTable('expenses') && Schema::hasColumn('expenses', 'coa_account_id')) {
                DB::table('expenses')
                    ->where('coa_account_id', $target->id)
                    ->update(['coa_account_id' => $previous->id]);
            }

            DB::table('coa_accounts')
                ->where('id', $target->id)
                ->delete();

            return;
        }

        DB::table('coa_accounts')
            ->where('id', $target->id)
            ->update([
                'code' => self::PREVIOUS_TARGET_CODE,
                'name' => self::TARGET_NAME,
                'type' => 'expense',
                'group' => self::TARGET_GROUP,
                'is_active' => true,
                'notes' => self::TARGET_NOTES,
                'updated_at' => now(),
            ]);
    }
};

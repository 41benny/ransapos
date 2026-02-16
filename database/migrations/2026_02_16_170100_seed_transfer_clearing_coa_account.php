<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const COA_CODE = '1-190';
    private const COA_NAME = 'Ayat Silang Kas/Bank (Pindah Buku)';
    private const COA_GROUP = 'ASET LANCAR';
    private const COA_NOTES = 'Akun ayat silang/pos sementara untuk transaksi pindah buku antar kas & bank';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $coa = DB::table('coa_accounts')
            ->where('code', self::COA_CODE)
            ->first();

        if (! $coa) {
            DB::table('coa_accounts')->insert([
                'code' => self::COA_CODE,
                'name' => self::COA_NAME,
                'type' => 'asset',
                'group' => self::COA_GROUP,
                'is_active' => true,
                'notes' => self::COA_NOTES,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } else {
            if (($coa->type ?? null) !== 'asset') {
                throw new \RuntimeException(
                    'COA dengan kode ' . self::COA_CODE .
                    ' sudah ada namun tipe bukan asset. Ubah tipe COA tersebut ke asset lalu jalankan migrate ulang.'
                );
            }

            DB::table('coa_accounts')
                ->where('id', $coa->id)
                ->update([
                    'name' => self::COA_NAME,
                    'group' => self::COA_GROUP,
                    'is_active' => true,
                    'notes' => self::COA_NOTES,
                    'updated_at' => now(),
                ]);
        }

        $coaId = DB::table('coa_accounts')
            ->where('code', self::COA_CODE)
            ->value('id');

        if (! $coaId) {
            if (DB::connection()->pretending()) {
                return;
            }

            throw new \RuntimeException('Gagal menentukan ID COA ayat silang untuk transaksi pindah buku.');
        }

        if (! Schema::hasTable('cash_transactions') || ! Schema::hasColumn('cash_transactions', 'coa_account_id')) {
            return;
        }

        $query = DB::table('cash_transactions')
            ->where('reference_type', 'bank_transfer')
            ->whereNull('coa_account_id');

        if (Schema::hasColumn('cash_transactions', 'updated_at')) {
            $query->update([
                'coa_account_id' => $coaId,
                'updated_at' => now(),
            ]);

            return;
        }

        $query->update([
            'coa_account_id' => $coaId,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $coa = DB::table('coa_accounts')
            ->where('code', self::COA_CODE)
            ->first();

        if (! $coa) {
            return;
        }

        if (Schema::hasTable('cash_transactions') && Schema::hasColumn('cash_transactions', 'coa_account_id')) {
            $query = DB::table('cash_transactions')
                ->where('reference_type', 'bank_transfer')
                ->where('coa_account_id', $coa->id);

            if (Schema::hasColumn('cash_transactions', 'updated_at')) {
                $query->update([
                    'coa_account_id' => null,
                    'updated_at' => now(),
                ]);
            } else {
                $query->update(['coa_account_id' => null]);
            }
        }

        $isStillUsed = Schema::hasTable('cash_transactions') && Schema::hasColumn('cash_transactions', 'coa_account_id')
            ? DB::table('cash_transactions')->where('coa_account_id', $coa->id)->exists()
            : false;

        if (! $isStillUsed) {
            DB::table('coa_accounts')
                ->where('id', $coa->id)
                ->delete();
        }
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Kategori Pengeluaran Kios untuk belanja stok/barang dagangan yang SENGAJA
 * tidak mengurangi laba dan TIDAK terintegrasi ke modul persediaan/stok.
 *
 * Akuntansi: beli stok memindahkan aset dari Kas ke Barang — bukan beban —
 * jadi COA-nya bertipe `asset`, bukan `expense`. Baik DashboardController
 * (kartu Laba Owner) maupun ProfitLossReportService cuma menjumlah COA
 * `type = expense`, jadi kategori bertipe asset otomatis tidak masuk hitungan
 * biaya operasional tanpa perlu kode khusus di kedua tempat itu.
 *
 * PENTING: nama & kode SENGAJA menghindari kata "persedia"/"inventory" dan
 * prefix kode "1-13x". CatalogReportController::inventoryReconciliationReport()
 * mengumpulkan SEMUA akun asset yang nama/grup-nya mengandung kata itu (atau
 * kode 1-13x) sebagai "Nilai Persediaan Neraca" lalu membandingkannya dengan
 * nilai stok riil hasil hitung sistem. Karena kategori ini tidak pernah
 * menaikkan stok riil, memakai nama "Persediaan" akan membuat laporan
 * rekonsiliasi bulanan itu selalu menunjukkan selisih palsu.
 */
return new class extends Migration
{
    private const COA_CODE = '1-145';

    public function up(): void
    {
        $now = now();

        $existing = DB::table('coa_accounts')->where('code', self::COA_CODE)->first();

        if ($existing) {
            DB::table('coa_accounts')->where('id', $existing->id)->update([
                'is_pos_selectable' => true,
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('coa_accounts')->insert([
            'code' => self::COA_CODE,
            'name' => 'Belanja Stok/Barang Dagangan (Petty Cash)',
            'type' => 'asset',
            'group' => 'ASET LANCAR',
            'is_active' => true,
            'is_pos_selectable' => true,
            'notes' => 'Uang laci yang dipakai beli stok/barang dagangan, dicatat sebagai catatan '
                . 'owner saja. TIDAK menaikkan stok sistem dan TIDAK dihitung sebagai biaya '
                . 'operasional (bukan pengurang laba) — karena membeli stok memindahkan aset dari '
                . 'kas ke barang, bukan beban. Kalau ke depan perlu stok benar-benar naik & HPP '
                . 'tercatat rapi, pakai menu Pembelian (Purchase), bukan kategori ini.',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $coaId = DB::table('coa_accounts')->where('code', self::COA_CODE)->value('id');
        if (! $coaId) {
            return;
        }

        $hasTransactions = DB::table('cash_transactions')->where('coa_account_id', $coaId)->exists()
            || DB::table('cash_session_expenses')->where('coa_account_id', $coaId)->exists();

        if ($hasTransactions) {
            // Jangan hapus akun yang sudah dipakai — matikan saja dari dropdown POS.
            DB::table('coa_accounts')->where('id', $coaId)->update([
                'is_pos_selectable' => false,
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('coa_accounts')->where('id', $coaId)->delete();
    }
};

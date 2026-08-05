<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Akun "Kas Besar" per outlet — penerima setoran kasir saat closing.
 *
 * Tanpa akun ini, closing memposting setoran KELUAR dari laci tanpa sisi lawan:
 * saldonya menggantung selamanya di ayat silang 1-190 dan buku kas benar-benar
 * tidak seimbang. Hal yang sama terjadi pada modal awal pagi — uangnya masuk ke
 * laci tanpa pernah keluar dari mana pun.
 *
 * PosDrawerExpenseService::ensureMainCashAccount() juga membuat akun ini
 * on-demand untuk outlet yang dibuat setelah migrasi berjalan; migrasi ini
 * hanya menyiapkan outlet yang sudah ada supaya langsung terlihat di Kas & Bank.
 */
return new class extends Migration
{
    public function up(): void
    {
        $creatorId = DB::table('users')->orderBy('id')->value('id');
        if (! $creatorId) {
            return;
        }

        $now = now();

        $outlets = DB::table('outlets')->get(['id', 'code', 'name']);

        foreach ($outlets as $outlet) {
            $hasOperational = DB::table('cash_accounts')
                ->where('outlet_id', $outlet->id)
                ->where('usage_type', 'operational')
                ->where('is_active', true)
                ->exists();

            // Hormati akun operasional yang sudah dibuat admin — jangan
            // menambah akun kedua yang bersaing jadi penerima setoran.
            if ($hasOperational) {
                continue;
            }

            DB::table('cash_accounts')->insert([
                'outlet_id' => $outlet->id,
                'name' => 'Kas Besar - ' . ($outlet->name ?: $outlet->code),
                'code' => 'KAS-BESAR',
                'type' => 'cash',
                'usage_type' => 'operational',
                'is_active' => true,
                'opening_balance' => 0,
                'current_balance' => 0,
                'notes' => 'Penerima setoran kasir dari laci saat closing shift. '
                    . 'Dibuat otomatis; nama boleh diubah lewat menu Kas & Bank.',
                'created_by' => $creatorId,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        // Hanya hapus akun yang belum pernah dipakai. Akun yang sudah punya
        // transaksi memuat riwayat kas nyata dan tidak boleh hilang.
        $ids = DB::table('cash_accounts')
            ->where('code', 'KAS-BESAR')
            ->where('usage_type', 'operational')
            ->pluck('id');

        foreach ($ids as $id) {
            $used = DB::table('cash_transactions')->where('cash_account_id', $id)->exists();
            if (! $used) {
                DB::table('cash_accounts')->where('id', $id)->delete();
            }
        }
    }
};

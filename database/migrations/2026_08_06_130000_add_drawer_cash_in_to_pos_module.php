<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Kas Masuk Laci — uang yang masuk ke laci di luar penjualan.
 *
 * Sebelum ini, rumus laci hanya `modal awal + penjualan tunai - pengeluaran`.
 * Tidak ada suku untuk uang yang ditambahkan owner di pagi hari, sehingga
 * satu-satunya cara mencatatnya adalah mengubah modal awal — yang justru
 * memutus rantai modal antar-shift dan membuat selisih kas kemarin bisa
 * disembunyikan. Jalur daruratnya (override modal awal + jurnal manual admin)
 * butuh dua orang dan dua langkah; kalau langkah keduanya lupa, kas besar
 * langsung minus dan uang seolah tercipta sendiri.
 *
 * Dipakai ulang tabel `cash_session_expenses` dengan kolom arah, bukan tabel
 * baru: seluruh pengaman yang sudah matang di sana (kunci idempotensi anti
 * klik ganda, void beralasan, lock baris shift, larangan di shift backdate)
 * langsung ikut terpakai. Tabel baru berarti menulis ulang semua itu, dan
 * pasti ada yang terlewat.
 */
return new class extends Migration
{
    private const COA_OWNER_CAPITAL = '3-100';

    public function up(): void
    {
        // ── 1. Arah baris buku pembantu ───────────────────────────────────
        // Default 'out' supaya seluruh baris lama tetap terbaca sebagai
        // pengeluaran tanpa perlu backfill.
        Schema::table('cash_session_expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('cash_session_expenses', 'direction')) {
                $table->string('direction', 10)->default('out')->after('payment_source')
                    ->comment('out = pengeluaran kios | in = kas masuk laci');
                $table->index(['cash_session_id', 'direction']);
            }
        });

        // ── 2. Agregat kas masuk per shift ────────────────────────────────
        // NOT NULL DEFAULT 0 (TEK-07: MySQL strict mode).
        Schema::table('cash_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('cash_sessions', 'total_cash_in')) {
                $table->decimal('total_cash_in', 15, 2)->default(0)->after('total_noncash_expense')
                    ->comment('Kas masuk laci di luar penjualan (modal tambahan)');
            }
        });

        // ── 3. COA modal disetor ──────────────────────────────────────────
        // Dibutuhkan supaya uang pribadi owner yang masuk ke laci punya lawan
        // yang sah. Tanpa akun ini, satu-satunya jalur adalah menarik dari kas
        // besar — yang saldonya jadi minus kalau uangnya memang tidak berasal
        // dari sana. Kas besar minus bukan sekadar laporan jelek: itu tanda
        // uang masuk buku tanpa asal-usul.
        //
        // `is_pos_selectable` sengaja FALSE: akun ini dipilih otomatis oleh
        // sistem untuk kas masuk, bukan muncul di dropdown kategori biaya
        // kasir. Akun ekuitas yang bisa dipilih sebagai kategori pengeluaran
        // akan membuat setoran modal tercatat sebagai beban.
        if (! DB::table('coa_accounts')->where('code', self::COA_OWNER_CAPITAL)->exists()) {
            DB::table('coa_accounts')->insert([
                'code' => self::COA_OWNER_CAPITAL,
                'name' => 'Modal Disetor Pemilik',
                'type' => 'equity',
                'group' => 'EKUITAS',
                'is_active' => true,
                'is_pos_selectable' => false,
                'notes' => 'Uang pemilik yang disetorkan ke outlet, termasuk modal tambahan yang '
                    . 'ditaruh langsung ke laci kasir. Juga dipakai untuk membukukan saldo kas '
                    . 'yang sudah ada sebelum sistem dipakai.',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // ── 4. Permission ─────────────────────────────────────────────────
        DB::table('permissions')->updateOrInsert(
            ['key' => 'pos.session.cash_in'],
            [
                'module' => 'pos',
                'action' => 'session_cash_in',
                'label' => 'Input Kas Masuk Laci',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Kasir ikut diberi izin karena owner menyerahkan uangnya ke kasir yang
        // sedang jaga, sering sebelum supervisor datang. Risikonya rendah:
        // kas masuk palsu menaikkan saldo seharusnya, sehingga justru muncul
        // sebagai selisih KURANG saat hitung fisik — merugikan pemalsunya
        // sendiri, bukan menyembunyikan apa pun.
        $roleIds = DB::table('roles')
            ->whereIn('name', ['kasir', 'admin', 'manager', 'superadmin'])
            ->pluck('id');
        $permissionId = DB::table('permissions')->where('key', 'pos.session.cash_in')->value('id');

        if ($permissionId && $roleIds->isNotEmpty()) {
            foreach ($roleIds as $roleId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        Schema::table('cash_session_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('cash_session_expenses', 'direction')) {
                $table->dropIndex(['cash_session_id', 'direction']);
                $table->dropColumn('direction');
            }
        });

        Schema::table('cash_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('cash_sessions', 'total_cash_in')) {
                $table->dropColumn('total_cash_in');
            }
        });

        $permissionId = DB::table('permissions')->where('key', 'pos.session.cash_in')->value('id');
        if ($permissionId) {
            DB::table('role_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('user_permissions')->where('permission_id', $permissionId)->delete();
            DB::table('permissions')->where('id', $permissionId)->delete();
        }

        // Akun modal tidak dihapus kalau sudah dipakai — menghapusnya akan
        // memutus jurnal yang sudah terlanjur menunjuk ke sana.
        $coaId = DB::table('coa_accounts')->where('code', self::COA_OWNER_CAPITAL)->value('id');
        if ($coaId && ! DB::table('cash_transactions')->where('coa_account_id', $coaId)->exists()) {
            DB::table('coa_accounts')->where('id', $coaId)->delete();
        }
    }
};

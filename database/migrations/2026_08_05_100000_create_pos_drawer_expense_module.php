<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Modul Pengeluaran Kios (Petty Cash Kasir) — lihat docs/PRD-POS-PENGELUARAN-KIOS-PETTY-CASH.md
 *
 * Migrasi ini HANYA menyiapkan struktur (Tahap 0). Perilaku sistem belum berubah
 * sampai setting `pos_drawer_expense_enabled` dinyalakan.
 */
return new class extends Migration
{
    public function up(): void
    {
        // ── 1. Kolom baru di cash_sessions ────────────────────────────────
        // Semua numerik NOT NULL DEFAULT 0 (TEK-07: MySQL strict mode).
        Schema::table('cash_sessions', function (Blueprint $table) {
            if (! Schema::hasColumn('cash_sessions', 'total_cash_expense')) {
                $table->decimal('total_cash_expense', 15, 2)->default(0)->after('total_non_cash')
                    ->comment('Pengeluaran tunai dari laci pada shift ini');
            }
            if (! Schema::hasColumn('cash_sessions', 'total_noncash_expense')) {
                $table->decimal('total_noncash_expense', 15, 2)->default(0)->after('total_cash_expense')
                    ->comment('Pengeluaran kios non-tunai, tidak memotong laci');
            }
            if (! Schema::hasColumn('cash_sessions', 'retained_balance')) {
                $table->decimal('retained_balance', 15, 2)->default(0)->after('total_noncash_expense')
                    ->comment('Modal disisakan untuk shift berikutnya');
            }
            if (! Schema::hasColumn('cash_sessions', 'deposit_amount')) {
                $table->decimal('deposit_amount', 15, 2)->default(0)->after('retained_balance')
                    ->comment('Setoran = hitung fisik - modal disisakan (turunan, bukan input)');
            }
            if (! Schema::hasColumn('cash_sessions', 'deposit_status')) {
                $table->string('deposit_status', 20)->default('pending')->after('deposit_amount')
                    ->comment('pending | handed_over | verified');
            }
            if (! Schema::hasColumn('cash_sessions', 'drawer_cash_account_id')) {
                $table->unsignedBigInteger('drawer_cash_account_id')->nullable()->after('deposit_status');
                $table->index('drawer_cash_account_id');
            }
        });

        // ── 2. Kolom baru di cash_transactions ────────────────────────────
        Schema::table('cash_transactions', function (Blueprint $table) {
            if (! Schema::hasColumn('cash_transactions', 'cash_session_id')) {
                $table->unsignedBigInteger('cash_session_id')->nullable()->after('cash_account_id')
                    ->comment('Shift asal; null untuk transaksi admin');
                $table->index('cash_session_id');
            }
            if (! Schema::hasColumn('cash_transactions', 'payment_source')) {
                $table->string('payment_source', 20)->default('drawer')->after('reference_id')
                    ->comment('drawer | noncash | personal_advance');
            }
        });

        // ── 3. Tabel buku pembantu pengeluaran per shift ──────────────────
        if (! Schema::hasTable('cash_session_expenses')) {
            Schema::create('cash_session_expenses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('cash_session_id')->constrained('cash_sessions')->cascadeOnDelete();
                $table->foreignId('outlet_id')->constrained('outlets')->restrictOnDelete();
                $table->foreignId('coa_account_id')->constrained('coa_accounts')->restrictOnDelete();

                $table->string('recipient_name', 60);
                $table->string('description', 150);
                $table->decimal('amount', 15, 2);
                $table->string('payment_source', 20)->default('drawer');
                $table->string('attachment_path')->nullable();
                $table->string('status', 20)->default('posted')->comment('posted | void');

                $table->unsignedBigInteger('cash_transaction_id')->nullable();
                $table->string('idempotency_key', 64)->nullable()->unique();

                $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
                $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
                $table->string('void_reason', 200)->nullable();
                $table->timestamp('voided_at')->nullable();
                $table->timestamps();

                $table->index(['cash_session_id', 'status']);
                $table->index(['outlet_id', 'created_at']);
                $table->index('cash_transaction_id');
            });
        }

        // ── 4. payment_methods.is_cash (JUAL-04: satu sumber kebenaran) ───
        //
        // Sengaja NULLABLE tanpa default. NULL berarti "belum ditentukan", dan
        // untuk baris seperti itu model jatuh ke heuristik nama seperti dulu.
        //
        // Kalau kolomnya NOT NULL DEFAULT false, setiap metode pembayaran baru
        // yang dibuat admin akan lahir sebagai "bukan tunai" tanpa ada yang
        // memutuskan begitu — metode bernama "Tunai Kasir 2" akan membuat kas
        // penjualan tidak terhitung dan memunculkan selisih palsu saat closing.
        Schema::table('payment_methods', function (Blueprint $table) {
            if (! Schema::hasColumn('payment_methods', 'is_cash')) {
                $table->boolean('is_cash')->nullable()->default(null)->after('name');
                $table->index('is_cash');
            }
        });

        // Backfill hanya menandai yang jelas tunai memakai aturan lama, supaya
        // hasilnya identik dengan perilaku sekarang. Sisanya dibiarkan NULL
        // agar tetap diputuskan heuristik yang sama persis seperti sebelumnya.
        DB::table('payment_methods')
            ->where(function ($q) {
                $q->whereRaw('UPPER(code) = ?', ['CASH'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%cash%'])
                    ->orWhereRaw('LOWER(name) LIKE ?', ['%tunai%']);
            })
            ->update(['is_cash' => true]);

        // ── 5. coa_accounts.is_pos_selectable (KLR-06 whitelist) ──────────
        Schema::table('coa_accounts', function (Blueprint $table) {
            if (! Schema::hasColumn('coa_accounts', 'is_pos_selectable')) {
                $table->boolean('is_pos_selectable')->default(false)->after('is_active')
                    ->comment('Boleh dipilih kasir di layar Pengeluaran Kios');
            }
        });

        // ── 6. Seed COA yang dibutuhkan modul ─────────────────────────────
        $now = now();
        $coaSeeds = [
            // Whitelist default untuk kasir.
            ['code' => '6-135', 'name' => 'Biaya Keperluan Outlet Lainnya', 'type' => 'expense', 'group' => 'BIAYA OPERASIONAL', 'is_pos_selectable' => true],
            ['code' => '6-136', 'name' => 'Biaya Galon & Air Minum',        'type' => 'expense', 'group' => 'BIAYA OPERASIONAL', 'is_pos_selectable' => true],
            ['code' => '6-137', 'name' => 'Biaya Gas LPG',                  'type' => 'expense', 'group' => 'BIAYA OPERASIONAL', 'is_pos_selectable' => true],
            ['code' => '6-138', 'name' => 'Biaya Transport & Parkir',       'type' => 'expense', 'group' => 'BIAYA OPERASIONAL', 'is_pos_selectable' => true],
            ['code' => '6-139', 'name' => 'Biaya Kebersihan & Habis Pakai', 'type' => 'expense', 'group' => 'BIAYA OPERASIONAL', 'is_pos_selectable' => true],
            // Akun mesin — TIDAK boleh dipilih kasir.
            ['code' => '6-190', 'name' => 'Selisih Kas',                    'type' => 'expense', 'group' => 'BIAYA OPERASIONAL', 'is_pos_selectable' => false],
            ['code' => '4-900', 'name' => 'Pendapatan Lain-lain',           'type' => 'income',  'group' => 'PENDAPATAN LAIN',    'is_pos_selectable' => false],
            ['code' => '4-100', 'name' => 'Pendapatan Penjualan',           'type' => 'income',  'group' => 'PENDAPATAN',         'is_pos_selectable' => false],
            ['code' => '2-150', 'name' => 'Utang ke Karyawan (Talangan)',   'type' => 'liability', 'group' => 'KEWAJIBAN LANCAR', 'is_pos_selectable' => false],
        ];

        foreach ($coaSeeds as $seed) {
            $existing = DB::table('coa_accounts')->where('code', $seed['code'])->first();

            if ($existing) {
                // Jangan timpa nama/tipe akun yang sudah dipakai user — cukup
                // tandai apakah boleh muncul di POS.
                DB::table('coa_accounts')
                    ->where('id', $existing->id)
                    ->update(['is_pos_selectable' => $seed['is_pos_selectable'], 'updated_at' => $now]);
                continue;
            }

            DB::table('coa_accounts')->insert([
                'code' => $seed['code'],
                'name' => $seed['name'],
                'type' => $seed['type'],
                'group' => $seed['group'],
                'is_active' => true,
                'is_pos_selectable' => $seed['is_pos_selectable'],
                'notes' => 'Dibuat oleh modul Pengeluaran Kios POS',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // ── 7. Akun laci (pos_drawer) per outlet ──────────────────────────
        $creatorId = DB::table('users')->orderBy('id')->value('id');

        if ($creatorId) {
            $outlets = DB::table('outlets')->get(['id', 'code', 'name']);

            foreach ($outlets as $outlet) {
                $exists = DB::table('cash_accounts')
                    ->where('outlet_id', $outlet->id)
                    ->where('usage_type', 'pos_drawer')
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('cash_accounts')->insert([
                    'outlet_id' => $outlet->id,
                    'name' => 'Laci Kasir - ' . ($outlet->name ?: $outlet->code),
                    'code' => 'KAS-LACI',
                    'type' => 'cash',
                    'usage_type' => 'pos_drawer',
                    'is_active' => true,
                    'opening_balance' => 0,
                    'current_balance' => 0,
                    'notes' => 'Laci kasir fisik. Saldo dikelola otomatis oleh shift POS.',
                    'created_by' => $creatorId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }

        // ── 8. Permission granular (TEK-05) ───────────────────────────────
        $permissions = [
            ['key' => 'pos.expense.view',             'action' => 'expense_view',            'label' => 'Lihat Pengeluaran Kios'],
            ['key' => 'pos.expense.create',           'action' => 'expense_create',          'label' => 'Input Pengeluaran Kios'],
            ['key' => 'pos.expense.void',             'action' => 'expense_void',            'label' => 'Batalkan Pengeluaran Kios'],
            ['key' => 'pos.expense.override_limit',   'action' => 'expense_override_limit',  'label' => 'Lewati Batas Pengeluaran Kios'],
            ['key' => 'pos.session.override_opening', 'action' => 'session_override_opening','label' => 'Ubah Modal Awal Shift'],
            ['key' => 'pos.deposit.verify',           'action' => 'deposit_verify',          'label' => 'Verifikasi Setoran Shift'],
        ];

        foreach ($permissions as $permission) {
            DB::table('permissions')->updateOrInsert(
                ['key' => $permission['key']],
                [
                    'module' => 'pos',
                    'action' => $permission['action'],
                    'label' => $permission['label'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]
            );
        }

        // Kasir dapat view + create. Void & override hanya admin/manager.
        $this->grant(['kasir', 'admin', 'manager', 'superadmin'], ['pos.expense.view', 'pos.expense.create']);
        $this->grant(['admin', 'manager', 'superadmin'], [
            'pos.expense.void',
            'pos.expense.override_limit',
            'pos.session.override_opening',
            'pos.deposit.verify',
        ]);

        // ── 9. Feature flag — default MATI ────────────────────────────────
        // Perilaku kas tidak berubah sampai ini dinyalakan (Tahap 2 / cutover).
        DB::table('settings')->updateOrInsert(
            ['key' => 'pos_drawer_expense_enabled'],
            ['value' => '0']
        );

        // Ambang wajib foto nota (KLR-07) dan ambang otorisasi (KLR-05).
        DB::table('settings')->updateOrInsert(['key' => 'pos_expense_receipt_threshold'], ['value' => '100000']);
        DB::table('settings')->updateOrInsert(['key' => 'pos_expense_max_per_transaction'], ['value' => '500000']);
        DB::table('settings')->updateOrInsert(['key' => 'pos_cash_difference_tolerance'], ['value' => '20000']);
    }

    /**
     * Beri permission ke sekumpulan role.
     *
     * @param  array<int, string>  $roleNames
     * @param  array<int, string>  $permissionKeys
     */
    private function grant(array $roleNames, array $permissionKeys): void
    {
        $roleIds = DB::table('roles')->whereIn('name', $roleNames)->pluck('id');
        if ($roleIds->isEmpty()) {
            return;
        }

        $permissionIds = DB::table('permissions')->whereIn('key', $permissionKeys)->pluck('id');

        foreach ($roleIds as $roleId) {
            foreach ($permissionIds as $permissionId) {
                DB::table('role_permissions')->updateOrInsert(
                    ['role_id' => $roleId, 'permission_id' => $permissionId],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    public function down(): void
    {
        $permissionKeys = [
            'pos.expense.view',
            'pos.expense.create',
            'pos.expense.void',
            'pos.expense.override_limit',
            'pos.session.override_opening',
            'pos.deposit.verify',
        ];

        $permissionIds = DB::table('permissions')->whereIn('key', $permissionKeys)->pluck('id');
        if ($permissionIds->isNotEmpty()) {
            DB::table('role_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('user_permissions')->whereIn('permission_id', $permissionIds)->delete();
            DB::table('permissions')->whereIn('id', $permissionIds)->delete();
        }

        DB::table('settings')->whereIn('key', [
            'pos_drawer_expense_enabled',
            'pos_expense_receipt_threshold',
            'pos_expense_max_per_transaction',
            'pos_cash_difference_tolerance',
        ])->delete();

        Schema::dropIfExists('cash_session_expenses');

        Schema::table('coa_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('coa_accounts', 'is_pos_selectable')) {
                $table->dropColumn('is_pos_selectable');
            }
        });

        Schema::table('payment_methods', function (Blueprint $table) {
            if (Schema::hasColumn('payment_methods', 'is_cash')) {
                $table->dropIndex(['is_cash']);
                $table->dropColumn('is_cash');
            }
        });

        Schema::table('cash_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('cash_transactions', 'cash_session_id')) {
                $table->dropIndex(['cash_session_id']);
                $table->dropColumn('cash_session_id');
            }
            if (Schema::hasColumn('cash_transactions', 'payment_source')) {
                $table->dropColumn('payment_source');
            }
        });

        Schema::table('cash_sessions', function (Blueprint $table) {
            if (Schema::hasColumn('cash_sessions', 'drawer_cash_account_id')) {
                $table->dropIndex(['drawer_cash_account_id']);
            }
            $table->dropColumn([
                'total_cash_expense',
                'total_noncash_expense',
                'retained_balance',
                'deposit_amount',
                'deposit_status',
                'drawer_cash_account_id',
            ]);
        });

        // Akun laci sengaja TIDAK dihapus — bisa sudah punya transaksi.
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Rekening sumber untuk pengeluaran kios NON-TUNAI.
 *
 * Sebelumnya pengeluaran non-tunai hanya tersimpan di buku pembantu dan tidak
 * pernah diposting ke buku besar, karena sistem tidak tahu uangnya keluar dari
 * rekening mana. Akibatnya uang benar-benar keluar tapi biayanya tidak pernah
 * diakui — laba terlihat lebih besar dari kenyataan.
 *
 * Nullable karena pengeluaran tunai dari laci tidak memakainya; akun lacinya
 * sudah tercatat di cash_sessions.drawer_cash_account_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cash_session_expenses', function (Blueprint $table) {
            if (! Schema::hasColumn('cash_session_expenses', 'source_cash_account_id')) {
                $table->unsignedBigInteger('source_cash_account_id')
                    ->nullable()
                    ->after('payment_source')
                    ->comment('Rekening asal dana untuk pengeluaran non-tunai');

                $table->index('source_cash_account_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('cash_session_expenses', function (Blueprint $table) {
            if (Schema::hasColumn('cash_session_expenses', 'source_cash_account_id')) {
                $table->dropIndex(['source_cash_account_id']);
                $table->dropColumn('source_cash_account_id');
            }
        });
    }
};

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
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->string('voucher_number')->nullable()->after('transaction_number');
            $table->index('voucher_number');
        });

        // Default: voucher_number mengikuti nomor baris transaksi.
        DB::statement('UPDATE cash_transactions SET voucher_number = transaction_number WHERE voucher_number IS NULL');

        // Backfill batch transaksi agar satu voucher_number untuk satu batch.
        $groupedReferenceTypes = ['general_batch', 'petty_cash_pos'];

        $groups = DB::table('cash_transactions')
            ->select('reference_type', 'reference_id', DB::raw('MIN(transaction_number) as voucher_number'))
            ->whereIn('reference_type', $groupedReferenceTypes)
            ->whereNotNull('reference_id')
            ->groupBy('reference_type', 'reference_id')
            ->get();

        foreach ($groups as $group) {
            DB::table('cash_transactions')
                ->where('reference_type', $group->reference_type)
                ->where('reference_id', $group->reference_id)
                ->update(['voucher_number' => $group->voucher_number]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropIndex(['voucher_number']);
            $table->dropColumn('voucher_number');
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('purchases') || !Schema::hasColumn('purchases', 'payment_status')) {
            return;
        }

        // Legacy data used "unpaid". Standardize to "pending".
        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE purchases MODIFY payment_status ENUM('unpaid','pending','partial','paid') NOT NULL DEFAULT 'pending'"
            );
        }

        DB::table('purchases')
            ->where('payment_status', 'unpaid')
            ->update(['payment_status' => 'pending']);

        if (DB::getDriverName() === 'mysql') {
            DB::statement(
                "ALTER TABLE purchases MODIFY payment_status ENUM('pending','partial','paid') NOT NULL DEFAULT 'pending'"
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: data normalization migration.
    }
};


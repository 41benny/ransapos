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
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        if (!Schema::hasTable('cash_sessions')) {
            return;
        }

        // Use DATETIME to avoid MySQL implicit ON UPDATE behavior on first TIMESTAMP column.
        DB::statement("
            ALTER TABLE `cash_sessions`
            MODIFY `opened_at` DATETIME NOT NULL,
            MODIFY `closed_at` DATETIME NULL
        ");

        // Repair historical opened_at drift: the record insert time is created_at.
        DB::statement("
            UPDATE `cash_sessions`
            SET `opened_at` = `created_at`
            WHERE `created_at` IS NOT NULL
              AND `opened_at` > `created_at`
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!in_array(DB::getDriverName(), ['mysql', 'mariadb'], true)) {
            return;
        }

        if (!Schema::hasTable('cash_sessions')) {
            return;
        }

        DB::statement("
            ALTER TABLE `cash_sessions`
            MODIFY `opened_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            MODIFY `closed_at` TIMESTAMP NULL DEFAULT NULL
        ");
    }
};

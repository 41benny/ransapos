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
        if (!Schema::hasTable('cash_sessions')) {
            return;
        }

        $column = DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'cash_sessions')
            ->where('column_name', 'opened_at')
            ->first(['data_type']);

        if (($column->data_type ?? null) === 'timestamp') {
            // Best effort for timestamp environments: remove explicit ON UPDATE.
            DB::statement("
                ALTER TABLE `cash_sessions`
                MODIFY `opened_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            ");
        }

        // Best-effort data repair:
        // created_at records session insert time (open shift time),
        // while opened_at was previously drifting due to ON UPDATE.
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
        if (!Schema::hasTable('cash_sessions')) {
            return;
        }

        $column = DB::table('information_schema.columns')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', 'cash_sessions')
            ->where('column_name', 'opened_at')
            ->first(['data_type']);

        if (($column->data_type ?? null) === 'timestamp') {
            DB::statement("
                ALTER TABLE `cash_sessions`
                MODIFY `opened_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ");
        }
    }
};

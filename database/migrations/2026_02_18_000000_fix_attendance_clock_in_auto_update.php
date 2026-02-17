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
        $driver = Schema::getConnection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE attendances MODIFY clock_in TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE attendances MODIFY clock_out TIMESTAMP NULL DEFAULT NULL');

        // Recover historical rows where clock_in was overwritten by ON UPDATE during clock_out.
        DB::statement("
            UPDATE attendances
            SET clock_in = created_at
            WHERE clock_out IS NOT NULL
              AND created_at IS NOT NULL
              AND updated_at IS NOT NULL
              AND clock_in = updated_at
              AND created_at <= clock_out
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();

        if (!in_array($driver, ['mysql', 'mariadb'], true)) {
            return;
        }

        DB::statement('ALTER TABLE attendances MODIFY clock_in TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP');
        DB::statement('ALTER TABLE attendances MODIFY clock_out TIMESTAMP NULL DEFAULT NULL');
    }
};

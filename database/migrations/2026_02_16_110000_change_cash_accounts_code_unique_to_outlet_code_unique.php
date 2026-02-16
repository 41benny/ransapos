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
        $hasCodeUnique = $this->indexExists('cash_accounts', 'cash_accounts_code_unique');
        $hasOutletCodeUnique = $this->indexExists('cash_accounts', 'cash_accounts_outlet_id_code_unique');

        Schema::table('cash_accounts', function (Blueprint $table) use ($hasCodeUnique, $hasOutletCodeUnique) {
            if ($hasCodeUnique) {
                $table->dropUnique('cash_accounts_code_unique');
            }

            if (! $hasOutletCodeUnique) {
                $table->unique(['outlet_id', 'code'], 'cash_accounts_outlet_id_code_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $hasOutletCodeUnique = $this->indexExists('cash_accounts', 'cash_accounts_outlet_id_code_unique');

        Schema::table('cash_accounts', function (Blueprint $table) use ($hasOutletCodeUnique) {
            if ($hasOutletCodeUnique) {
                $table->dropUnique('cash_accounts_outlet_id_code_unique');
            }
        });

        $hasDuplicateCode = DB::table('cash_accounts')
            ->select('code')
            ->groupBy('code')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if (! $hasDuplicateCode && ! $this->indexExists('cash_accounts', 'cash_accounts_code_unique')) {
            Schema::table('cash_accounts', function (Blueprint $table) {
                $table->unique('code', 'cash_accounts_code_unique');
            });
        }
    }

    private function indexExists(string $table, string $indexName): bool
    {
        $databaseName = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $databaseName)
            ->where('table_name', $table)
            ->where('index_name', $indexName)
            ->exists();
    }
};

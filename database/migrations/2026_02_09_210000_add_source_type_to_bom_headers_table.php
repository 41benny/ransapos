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
        Schema::table('bom_headers', function (Blueprint $table) {
            $table->string('source_type', 20)
                ->default('bundle')
                ->after('name')
                ->comment('Sumber resep: production atau bundle');
        });

        DB::table('bom_headers')
            ->whereNull('source_type')
            ->update(['source_type' => 'bundle']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('bom_headers', function (Blueprint $table) {
            $table->dropColumn('source_type');
        });
    }
};

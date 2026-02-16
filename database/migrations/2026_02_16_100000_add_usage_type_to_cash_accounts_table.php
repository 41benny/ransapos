<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('cash_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('cash_accounts', 'usage_type')) {
                $table->string('usage_type', 30)
                    ->default('operational')
                    ->after('type')
                    ->comment('operational atau petty_cash');
                $table->index('usage_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('cash_accounts', 'usage_type')) {
                $table->dropIndex(['usage_type']);
                $table->dropColumn('usage_type');
            }
        });
    }
};

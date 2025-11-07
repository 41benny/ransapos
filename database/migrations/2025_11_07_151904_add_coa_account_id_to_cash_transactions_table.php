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
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->foreignId('coa_account_id')->nullable()->after('cash_account_id')->constrained('coa_accounts')->nullOnDelete();
            $table->index('coa_account_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_transactions', function (Blueprint $table) {
            $table->dropForeign(['coa_account_id']);
            $table->dropIndex(['coa_account_id']);
            $table->dropColumn('coa_account_id');
        });
    }
};

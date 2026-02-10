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
            // Outlet assignment (nullable untuk backward compatibility)
            $table->foreignId('outlet_id')->nullable()->after('id')->constrained('outlets')->onDelete('restrict');

            // Bank details (opsional, hanya untuk type = 'bank')
            $table->string('bank_name', 100)->nullable()->after('type')->comment('Nama bank (BCA, Mandiri, dll)');
            $table->string('account_number', 50)->nullable()->after('bank_name')->comment('Nomor rekening');
            $table->string('account_holder', 200)->nullable()->after('account_number')->comment('Nama pemegang rekening');
            $table->string('branch', 200)->nullable()->after('account_holder')->comment('Cabang bank');

            // Index
            $table->index('outlet_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_accounts', function (Blueprint $table) {
            $table->dropForeign(['outlet_id']);
            $table->dropColumn([
                'outlet_id',
                'bank_name',
                'account_number',
                'account_holder',
                'branch',
            ]);
        });
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->string('idempotency_key', 100)->nullable()->after('invoice_number');
            $table->unique('idempotency_key', 'sales_idempotency_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_idempotency_key_unique');
            $table->dropColumn('idempotency_key');
        });
    }
};

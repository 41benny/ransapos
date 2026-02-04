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
        // 1. Add settings to outlets table
        Schema::table('outlets', function (Blueprint $table) {
            $table->decimal('tax_rate', 5, 2)->default(10.00)->after('email')
                ->comment('Persentase pajak (PB1), default 10%');
            $table->decimal('service_charge_rate', 5, 2)->default(0.00)->after('tax_rate')
                ->comment('Persentase service charge (opsional), default 0%');
        });

        // 2. Add calculated fields to sales table
        Schema::table('sales', function (Blueprint $table) {
            $table->decimal('service_charge_amount', 15, 2)->default(0)->after('discount_amount')
                ->comment('Nominal service charge');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('outlets', function (Blueprint $table) {
            $table->dropColumn(['tax_rate', 'service_charge_rate']);
        });

        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn('service_charge_amount');
        });
    }
};

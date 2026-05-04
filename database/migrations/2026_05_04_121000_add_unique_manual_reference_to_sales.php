<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex('sales_is_backdated_manual_reference_index');
            $table->unique('manual_reference', 'sales_manual_reference_unique');
            $table->index('is_backdated');
        });
    }

    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropUnique('sales_manual_reference_unique');
            $table->dropIndex(['is_backdated']);
            $table->index(['is_backdated', 'manual_reference']);
        });
    }
};

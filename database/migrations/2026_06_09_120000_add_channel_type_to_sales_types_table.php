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
        Schema::table('sales_types', function (Blueprint $table) {
            $table->enum('channel_type', ['offline', 'online'])
                ->default('offline')
                ->after('name')
                ->comment('Kelompok kanal: offline (dine-in/biasa) atau online (ojek online)');
        });

        // Tandai kanal online bawaan
        DB::table('sales_types')
            ->whereIn('code', ['gofood', 'grabfood', 'shopeefood'])
            ->update(['channel_type' => 'online']);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales_types', function (Blueprint $table) {
            $table->dropColumn('channel_type');
        });
    }
};

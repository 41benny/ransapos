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
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->foreignId('opened_pos_device_id')
                ->nullable()
                ->after('user_id')
                ->constrained('pos_devices')
                ->nullOnDelete();
            $table->foreignId('closed_pos_device_id')
                ->nullable()
                ->after('opened_pos_device_id')
                ->constrained('pos_devices')
                ->nullOnDelete();
            $table->string('opened_ip', 45)->nullable()->after('closed_pos_device_id');
            $table->string('closed_ip', 45)->nullable()->after('opened_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cash_sessions', function (Blueprint $table) {
            $table->dropConstrainedForeignId('closed_pos_device_id');
            $table->dropConstrainedForeignId('opened_pos_device_id');
            $table->dropColumn(['opened_ip', 'closed_ip']);
        });
    }
};

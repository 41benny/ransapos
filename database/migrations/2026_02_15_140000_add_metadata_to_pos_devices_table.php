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
        Schema::table('pos_devices', function (Blueprint $table) {
            $table->json('device_meta')->nullable()->after('device_type');
            $table->string('fingerprint_hash', 64)->nullable()->after('device_meta');
            $table->index('fingerprint_hash');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pos_devices', function (Blueprint $table) {
            $table->dropIndex(['fingerprint_hash']);
            $table->dropColumn(['device_meta', 'fingerprint_hash']);
        });
    }
};

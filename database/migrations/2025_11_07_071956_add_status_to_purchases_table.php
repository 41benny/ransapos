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
        Schema::table('purchases', function (Blueprint $table) {
            $table->enum('status', ['draft', 'received', 'cancelled'])->default('draft')->after('purchase_date');
            $table->timestamp('received_at')->nullable()->after('status');
            $table->foreignId('received_by')->nullable()->constrained('users')->onDelete('set null')->after('received_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchases', function (Blueprint $table) {
            $table->dropForeign(['received_by']);
            $table->dropColumn(['status', 'received_at', 'received_by']);
        });
    }
};

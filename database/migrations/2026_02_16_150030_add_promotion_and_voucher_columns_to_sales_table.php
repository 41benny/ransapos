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
        Schema::table('sales', function (Blueprint $table) {
            $table->foreignId('promotion_id')->nullable()->after('customer_id')
                ->constrained('promotions')->nullOnDelete();
            $table->foreignId('voucher_id')->nullable()->after('promotion_id')
                ->constrained('vouchers')->nullOnDelete();
            $table->string('voucher_code', 60)->nullable()->after('voucher_id');

            $table->index('promotion_id');
            $table->index('voucher_id');
            $table->index('voucher_code');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropIndex(['promotion_id']);
            $table->dropIndex(['voucher_id']);
            $table->dropIndex(['voucher_code']);

            $table->dropConstrainedForeignId('promotion_id');
            $table->dropConstrainedForeignId('voucher_id');
            $table->dropColumn('voucher_code');
        });
    }
};


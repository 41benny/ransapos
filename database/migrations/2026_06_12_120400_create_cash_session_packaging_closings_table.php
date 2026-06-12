<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_session_packaging_closings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_session_id')->constrained('cash_sessions')->cascadeOnDelete();
            $table->foreignId('packaging_item_id')->constrained('packaging_items')->cascadeOnDelete();
            $table->decimal('opening_qty', 12, 2)->default(0);
            $table->decimal('approved_adjustment_in_qty', 12, 2)->default(0);
            $table->decimal('approved_adjustment_out_qty', 12, 2)->default(0);
            $table->decimal('pending_adjustment_in_qty', 12, 2)->default(0);
            $table->decimal('pending_adjustment_out_qty', 12, 2)->default(0);
            $table->decimal('closing_physical_qty', 12, 2)->default(0);
            $table->decimal('actual_used_qty', 12, 2)->default(0);
            $table->decimal('estimated_sales_used_qty', 12, 2)->default(0);
            $table->decimal('difference_qty', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['cash_session_id', 'packaging_item_id'], 'csp_closing_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_session_packaging_closings');
    }
};

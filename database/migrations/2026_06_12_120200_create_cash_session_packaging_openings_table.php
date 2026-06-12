<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_session_packaging_openings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cash_session_id')->constrained('cash_sessions')->cascadeOnDelete();
            $table->foreignId('packaging_item_id')->constrained('packaging_items')->cascadeOnDelete();
            $table->decimal('opening_qty', 12, 2)->default(0);
            $table->decimal('source_last_closing_qty', 12, 2)->nullable();
            $table->boolean('is_manual_corrected')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['cash_session_id', 'packaging_item_id'], 'csp_opening_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_session_packaging_openings');
    }
};

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
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('outlet_id')->constrained('outlets')->onDelete('restrict');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->timestamp('last_mutation_at')->nullable();
            $table->timestamps();

            // Unique constraint: satu produk hanya punya 1 record stok per outlet
            $table->unique(['product_id', 'outlet_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
    }
};

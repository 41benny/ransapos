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
        Schema::create('stock_mutations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('outlet_id')->constrained('outlets')->onDelete('restrict');
            $table->enum('mutation_type', ['in', 'out', 'adjustment', 'transfer_in', 'transfer_out']);
            $table->decimal('quantity', 10, 2)->comment('+ untuk in, - untuk out');
            $table->decimal('stock_before', 10, 2);
            $table->decimal('stock_after', 10, 2);
            $table->string('reference_type', 50)->nullable()->comment('purchase, sale, stock_opname, transfer');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->date('mutation_date');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index('mutation_date');
            $table->index(['product_id', 'outlet_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_mutations');
    }
};

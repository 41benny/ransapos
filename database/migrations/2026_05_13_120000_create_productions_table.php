<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('productions', function (Blueprint $table) {
            $table->id();
            $table->string('production_number', 60)->unique();
            $table->foreignId('bom_id')->constrained('bom_headers')->onDelete('restrict');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->foreignId('outlet_id')->constrained('outlets')->onDelete('restrict');
            $table->date('production_date');
            $table->decimal('quantity', 15, 4);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->string('status', 20)->default('completed');
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();

            $table->index(['outlet_id', 'production_date']);
            $table->index(['product_id', 'production_date']);
            $table->index('status');
        });

        Schema::create('production_materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('production_id')->constrained('productions')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products')->onDelete('restrict');
            $table->decimal('quantity', 15, 4);
            $table->decimal('unit_cost', 15, 4)->default(0);
            $table->decimal('total_cost', 15, 2)->default(0);
            $table->string('uom', 50)->nullable();
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_materials');
        Schema::dropIfExists('productions');
    }
};

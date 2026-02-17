<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('outlet_id')->constrained()->onDelete('cascade');
            $table->decimal('avg_cost', 18, 4)->default(0);
            $table->timestamp('last_calculated_at')->nullable();
            $table->timestamps();

            $table->unique(['product_id', 'outlet_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_costs');
    }
};

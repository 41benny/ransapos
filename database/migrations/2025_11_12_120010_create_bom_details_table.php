<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('bom_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bom_id')->constrained('bom_headers')->onDelete('cascade');
            $table->foreignId('component_product_id')->constrained('products')->onDelete('restrict');
            $table->decimal('quantity', 15, 4); // presisi tinggi utk recipe
            $table->string('uom', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bom_details');
    }
};

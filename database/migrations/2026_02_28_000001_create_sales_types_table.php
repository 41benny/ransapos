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
        Schema::create('sales_types', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique()->comment('Key teknis, e.g. regular, gofood');
            $table->string('name', 100)->comment('Label tampilan, e.g. Reguler, GoFood');
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales_types');
    }
};

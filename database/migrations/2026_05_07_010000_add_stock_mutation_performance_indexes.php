<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_mutations', function (Blueprint $table) {
            $table->index(
                ['product_id', 'outlet_id', 'mutation_date', 'created_at', 'id'],
                'stock_mutations_product_outlet_date_created_id_index'
            );
            $table->index(
                ['product_id', 'outlet_id', 'reference_type', 'reference_id', 'mutation_type'],
                'stock_mutations_product_outlet_reference_type_index'
            );
        });
    }

    public function down(): void
    {
        Schema::table('stock_mutations', function (Blueprint $table) {
            $table->dropIndex('stock_mutations_product_outlet_date_created_id_index');
            $table->dropIndex('stock_mutations_product_outlet_reference_type_index');
        });
    }
};

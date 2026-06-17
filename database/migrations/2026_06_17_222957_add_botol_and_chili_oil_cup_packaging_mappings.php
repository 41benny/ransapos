<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Tambah 2 mapping packaging baru:
 *  1. Botol (unit: botol) -> Cleo 220 ml  — menggantikan mapping Cup
 *  2. Cup Chili Oil (unit: pcs) -> Chilli Oil (TOPPING)
 */
return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        // --- 1. Packaging item "Botol" ---
        $botolId = DB::table('packaging_items')->where('name', 'Botol')->value('id');
        if (! $botolId) {
            $maxSort = DB::table('packaging_items')->max('sort_order') ?? 0;
            $botolId = DB::table('packaging_items')->insertGetId([
                'name'       => 'Botol',
                'unit'       => 'botol',
                'is_active'  => true,
                'sort_order' => $maxSort + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Hapus mapping lama Cleo 220 ml (dari Cup), ganti ke Botol
        $cleoId = DB::table('products')->where('name', 'Cleo 220 ml')->value('id');
        if ($cleoId) {
            DB::table('product_packaging_mappings')
                ->where('product_id', $cleoId)
                ->delete();

            DB::table('product_packaging_mappings')->insertOrIgnore([
                'product_id'         => $cleoId,
                'packaging_item_id'  => $botolId,
                'qty_per_product'    => 1,
                'is_active'          => true,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
        }

        // --- 2. Packaging item "Cup Chili Oil" ---
        $cupChiliId = DB::table('packaging_items')->where('name', 'Cup Chili Oil')->value('id');
        if (! $cupChiliId) {
            $maxSort = DB::table('packaging_items')->max('sort_order') ?? 0;
            $cupChiliId = DB::table('packaging_items')->insertGetId([
                'name'       => 'Cup Chili Oil',
                'unit'       => 'pcs',
                'is_active'  => true,
                'sort_order' => $maxSort + 1,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        // Map Chilli Oil (TOPPING) -> Cup Chili Oil
        $chilliOilId = DB::table('products')->where('name', 'Chilli Oil')->value('id');
        if ($chilliOilId) {
            DB::table('product_packaging_mappings')->insertOrIgnore([
                'product_id'         => $chilliOilId,
                'packaging_item_id'  => $cupChiliId,
                'qty_per_product'    => 1,
                'is_active'          => true,
                'created_at'         => $now,
                'updated_at'         => $now,
            ]);
        }
    }

    public function down(): void
    {
        $cleoId      = DB::table('products')->where('name', 'Cleo 220 ml')->value('id');
        $chilliOilId = DB::table('products')->where('name', 'Chilli Oil')->value('id');
        $cupId       = DB::table('packaging_items')->where('name', 'Cup')->value('id');

        // Kembalikan Cleo -> Cup
        if ($cleoId) {
            DB::table('product_packaging_mappings')->where('product_id', $cleoId)->delete();
            if ($cupId) {
                DB::table('product_packaging_mappings')->insert([
                    'product_id'         => $cleoId,
                    'packaging_item_id'  => $cupId,
                    'qty_per_product'    => 1,
                    'is_active'          => true,
                    'created_at'         => now(),
                    'updated_at'         => now(),
                ]);
            }
        }

        // Hapus mapping Chilli Oil -> Cup Chili Oil
        $cupChiliId = DB::table('packaging_items')->where('name', 'Cup Chili Oil')->value('id');
        if ($chilliOilId && $cupChiliId) {
            DB::table('product_packaging_mappings')
                ->where('product_id', $chilliOilId)
                ->where('packaging_item_id', $cupChiliId)
                ->delete();
        }

        // Hapus item yang ditambahkan migration ini
        DB::table('packaging_items')->where('name', 'Botol')->delete();
        DB::table('packaging_items')->where('name', 'Cup Chili Oil')->delete();
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Seed item packaging awal dan mapping produk -> packaging secara otomatis.
 *
 * Aturan auto-mapping (MVP):
 *  - Kategori DIMSUM  : nama "... Isi N" -> "Box N"            (qty 1)
 *  - Kategori minuman (COFFEE / NON COFFEE / Minuman) -> "Cup" (qty 1)
 *
 * Catatan: Takoyaki TIDAK ikut kontrol packaging (sesuai keputusan owner) —
 * hanya box dimsum dan cup minuman yang dihitung.
 *
 * Produk lain (Takoyaki, Makanan, Snack, Topping) sengaja tidak dipetakan dan
 * akan muncul sebagai "Produk Belum Mapping Packaging" jika terjual.
 */
return new class extends Migration
{
    private array $drinkCategories = ['COFFEE', 'NON COFFEE', 'MINUMAN'];

    public function up(): void
    {
        $now = now();

        $products = DB::table('products')
            ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
            ->select('products.id', 'products.name', 'product_categories.name as category')
            ->get();

        // 1. Tentukan item packaging yang dibutuhkan berdasarkan data produk.
        $neededItems = []; // name => sort weight
        $resolved = [];    // product_id => item name

        foreach ($products as $product) {
            $itemName = $this->resolvePackagingName($product->name, (string) $product->category);
            if ($itemName === null) {
                continue;
            }

            $neededItems[$itemName] = $this->sortWeight($itemName);
            $resolved[$product->id] = $itemName;
        }

        if (empty($neededItems)) {
            return;
        }

        // Urutkan berdasarkan bobot agar sort_order rapi (box kecil -> besar -> cup).
        asort($neededItems);

        // 2. Insert item packaging.
        $sortOrder = 1;
        $itemRows = [];
        foreach (array_keys($neededItems) as $name) {
            $itemRows[] = [
                'name' => $name,
                'unit' => 'pcs',
                'is_active' => true,
                'sort_order' => $sortOrder++,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        DB::table('packaging_items')->insertOrIgnore($itemRows);

        $itemIdByName = DB::table('packaging_items')
            ->whereIn('name', array_keys($neededItems))
            ->pluck('id', 'name');

        // 3. Insert mapping produk -> packaging.
        $mappingRows = [];
        foreach ($resolved as $productId => $itemName) {
            if (! isset($itemIdByName[$itemName])) {
                continue;
            }

            $mappingRows[] = [
                'product_id' => $productId,
                'packaging_item_id' => $itemIdByName[$itemName],
                'qty_per_product' => 1,
                'is_active' => true,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        foreach (array_chunk($mappingRows, 500) as $chunk) {
            DB::table('product_packaging_mappings')->insertOrIgnore($chunk);
        }
    }

    private function resolvePackagingName(string $productName, string $category): ?string
    {
        $cat = strtoupper(trim($category));

        if (in_array($cat, $this->drinkCategories, true)) {
            return 'Cup';
        }

        $isi = $this->parseIsi($productName);

        if ($cat === 'DIMSUM' && $isi !== null) {
            return 'Box ' . $isi;
        }

        return null;
    }

    private function parseIsi(string $name): ?int
    {
        if (preg_match('/isi\s*(\d+)/i', $name, $m)) {
            return (int) $m[1];
        }

        return null;
    }

    /**
     * Bobot urutan: Box dimsum (1xxx) < Box Takoyaki (2xxx) < Cup (9999).
     */
    private function sortWeight(string $name): int
    {
        if ($name === 'Cup') {
            return 9999;
        }

        if (str_starts_with($name, 'Box Takoyaki ')) {
            return 2000 + (int) filter_var($name, FILTER_SANITIZE_NUMBER_INT);
        }

        if (str_starts_with($name, 'Box ')) {
            return 1000 + (int) filter_var($name, FILTER_SANITIZE_NUMBER_INT);
        }

        return 5000;
    }

    public function down(): void
    {
        DB::table('product_packaging_mappings')->delete();
        DB::table('packaging_items')->delete();
    }
};

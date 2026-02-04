<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\ProductCategory;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class ProductImport implements ToModel, WithHeadingRow
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        // Skip if name is empty
        if (!isset($row['nama_produk']) || empty($row['nama_produk'])) {
            return null;
        }

        // Handle Category
        $categoryName = $row['kategori'] ?? 'Uncategorized';
        $category = ProductCategory::firstOrCreate(
            ['name' => $categoryName],
            ['slug' => Str::slug($categoryName)]
        );

        // Handle Unit (default to pcs)
        $unit = $row['satuan'] ?? 'pcs';

        // Clean prices
        $purchasePrice = isset($row['harga_beli']) ? (float) preg_replace('/[^0-9.]/', '', $row['harga_beli']) : 0;
        $sellingPrice = isset($row['harga_jual']) ? (float) preg_replace('/[^0-9.]/', '', $row['harga_jual']) : 0;
        $stock = isset($row['stok']) ? (int) $row['stok'] : 0;

        return new Product([
            'name'           => $row['nama_produk'],
            'sku'            => $row['sku'] ?? $this->generateSku($row['nama_produk']),
            'category_id'    => $category->id,
            'purchase_price' => $purchasePrice,
            'selling_price'  => $sellingPrice,
            'unit'           => $unit,
            'description'    => $row['deskripsi'] ?? null,
            'min_stock'      => 5,
            'is_active'      => true,
            'created_by'     => auth()->id() ?? 1, // Fallback to admin if not auth
            // Assuming this is initial stock, we might need to handle stock mutation separately 
            // but for simple import we often just ignore stock or set it if the model allows.
            // However, Product model usually doesn't hold stock directly if using Stock table.
            // Based on earlier file view, Product has 'stocks' relation.
            // For now, let's keep it simple. If product table has no stock column, this might fail or be ignored.
            // Checking Product model... it doesn't have 'stock' field in fillable.
            // It has 'min_stock'.
            // Real stock should be in Stock model. 
            // I will skip saving stock here to avoid errors, or handle it via AfterImport event if needed.
            // For now, let's just save the product master data.
        ]);
    }

    private function generateSku($name)
    {
        return strtoupper(substr(Str::slug($name), 0, 3) . '-' . rand(1000, 9999));
    }
}

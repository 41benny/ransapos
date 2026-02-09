<?php

namespace App\Imports;

use App\Models\BomDetail;
use App\Models\BomHeader;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\OnEachRow;
use Maatwebsite\Excel\Row;
use Illuminate\Support\Str;

class ProductImport implements OnEachRow, WithHeadingRow
{
    /**
     * Track product IDs whose BOM details were reset during this import run.
     *
     * @var array<int, bool>
     */
    private array $resetBomDetailsFor = [];
    private int $processedRows = 0;
    private int $masterOnlyRows = 0;
    private int $bomRows = 0;

    /**
     * @var array<string, bool>
     */
    private array $touchedProducts = [];

    /**
     * @var array<string, bool>
     */
    private array $bundleProducts = [];

    public function onRow(Row $excelRow): void
    {
        $row = $excelRow->toArray();
        $rowNumber = $excelRow->getIndex();

        // Skip if name is empty
        if (!$this->hasValue($row['nama_produk'] ?? null)) {
            return;
        }

        DB::transaction(function () use ($row, $rowNumber) {
            $productName = trim((string) ($row['nama_produk'] ?? ''));
            $categoryName = trim((string) ($row['kategori'] ?? 'Uncategorized'));
            if ($categoryName === '') {
                $categoryName = 'Uncategorized';
            }

            $category = ProductCategory::firstOrCreate(
                ['name' => $categoryName],
                [
                    'code' => $this->generateCategoryCode($categoryName),
                    'is_active' => true,
                ]
            );

            $unit = $this->normalizeText($row['satuan'] ?? null) ?? 'pcs';
            $purchasePrice = $this->parseDecimal($row['harga_beli'] ?? null);
            $sellingPrice = $this->parseDecimal($row['harga_jual'] ?? null);
            $productType = $this->guessProductType($row);
            $isRawMaterial = $productType === 'raw_material';
            $hasBomComponent = $this->hasBomComponent($row);
            $skuInput = $this->normalizeText($row['sku'] ?? null);
            if ($hasBomComponent && !$skuInput) {
                throw new \InvalidArgumentException("Baris {$rowNumber}: SKU wajib diisi untuk baris yang memiliki BOM.");
            }

            // Fix Duplication: If SKU is empty, check if product with same name exists
            if ($skuInput) {
                $sku = $skuInput;
            } else {
                $existingProduct = Product::where('name', $productName)->first();
                if ($existingProduct) {
                    $sku = $existingProduct->sku;
                } else {
                    $sku = $this->generateSku($productName);
                }
            }

            $defaultCreatedBy = auth()->id() ?: User::query()->value('id');

            $product = Product::updateOrCreate(
                ['sku' => $sku],
                [
                    'name' => $productName,
                    'category_id' => $category->id,
                    'product_type' => $productType,
                    'is_sellable' => !$isRawMaterial,
                    'is_pos_available' => !$isRawMaterial,
                    'is_online_order_available' => false,
                    'is_available_all_outlets' => true,
                    'is_available_all_users' => true,
                    'pos_outlet_ids' => null,
                    'pos_user_ids' => null,
                    'price_levels' => [
                        'regular' => $sellingPrice,
                    ],
                    'purchase_price' => $purchasePrice,
                    'selling_price' => $sellingPrice,
                    'unit' => $unit,
                    'description' => $row['deskripsi'] ?? null,
                    'min_stock' => 5,
                    'is_active' => true,
                    'created_by' => $defaultCreatedBy,
                ]
            );

            $this->processedRows++;
            $this->touchedProducts[$sku] = true;

            if (!$hasBomComponent) {
                $this->masterOnlyRows++;
                return;
            }

            $this->bomRows++;
            $this->bundleProducts[$sku] = true;

            if ($productType !== 'finished_good') {
                throw new \InvalidArgumentException("Baris {$rowNumber}: BOM hanya boleh untuk produk/bundle.");
            }

            $quantity = $this->parseDecimal(
                $row['bom_qty']
                    ?? $row['qty_bom']
                    ?? $row['qty_komponen']
                    ?? null
            );

            if ($quantity <= 0) {
                throw new \InvalidArgumentException("Baris {$rowNumber}: Qty BOM harus lebih dari 0.");
            }

            $component = $this->resolveBomComponent($row, $rowNumber);
            if ((int) $component->id === (int) $product->id) {
                throw new \InvalidArgumentException("Baris {$rowNumber}: Komponen tidak boleh sama dengan produk bundle.");
            }

            $bomHeader = BomHeader::firstOrCreate(
                ['product_id' => $product->id],
                [
                    'name' => 'Resep ' . $product->name,
                    'is_active' => true,
                    'notes' => $product->description,
                ]
            );

            if (!isset($this->resetBomDetailsFor[$product->id])) {
                // First BOM row for this product in current file: rebuild BOM from import rows.
                $bomHeader->details()->delete();
                $this->resetBomDetailsFor[$product->id] = true;
            }

            BomDetail::updateOrCreate(
                [
                    'bom_id' => $bomHeader->id,
                    'component_product_id' => $component->id,
                ],
                [
                    'quantity' => $quantity,
                    'uom' => $this->normalizeText($row['bom_uom'] ?? $row['uom_bom'] ?? null) ?? $component->unit,
                ]
            );

            $this->syncBundlePurchasePrice($product, $bomHeader->id);
        });
    }

    /**
     * @return array{
     *     mode: string,
     *     processed_rows: int,
     *     master_only_rows: int,
     *     bom_rows: int,
     *     unique_products: int,
     *     unique_bundle_products: int
     * }
     */
    public function getSummary(): array
    {
        $mode = 'master_only';
        if ($this->bomRows > 0 && $this->masterOnlyRows === 0) {
            $mode = 'bundle_only';
        } elseif ($this->bomRows > 0 && $this->masterOnlyRows > 0) {
            $mode = 'mixed';
        }

        return [
            'mode' => $mode,
            'processed_rows' => $this->processedRows,
            'master_only_rows' => $this->masterOnlyRows,
            'bom_rows' => $this->bomRows,
            'unique_products' => count($this->touchedProducts),
            'unique_bundle_products' => count($this->bundleProducts),
        ];
    }

    private function guessProductType(array $row): string
    {
        $raw = $row['product_type']
            ?? $row['jenis_produk']
            ?? $row['jenis']
            ?? $row['tipe']
            ?? null;

        if (!$raw) {
            return 'finished_good';
        }

        $value = strtolower(trim((string) $raw));
        $value = str_replace(['-', ' '], '_', $value);

        if (in_array($value, ['raw_material', 'bahan', 'bahan_baku', 'material', 'ingredient'], true)) {
            return 'raw_material';
        }
        if (in_array($value, ['service', 'jasa'], true)) {
            return 'service';
        }
        if (in_array($value, ['finished_good', 'produk', 'produk_jadi', 'menu', 'bundle', 'bundel'], true)) {
            return 'finished_good';
        }

        return 'finished_good';
    }

    private function hasBomComponent(array $row): bool
    {
        return $this->hasValue($row['bom_komponen_sku'] ?? null)
            || $this->hasValue($row['komponen_sku'] ?? null)
            || $this->hasValue($row['bom_komponen_nama'] ?? null)
            || $this->hasValue($row['komponen_nama'] ?? null)
            || $this->parseDecimal($row['bom_qty'] ?? $row['qty_bom'] ?? $row['qty_komponen'] ?? null) > 0;
    }

    private function resolveBomComponent(array $row, int $rowNumber): Product
    {
        $componentSku = $this->normalizeText($row['bom_komponen_sku'] ?? $row['komponen_sku'] ?? null);
        $componentName = $this->normalizeText($row['bom_komponen_nama'] ?? $row['komponen_nama'] ?? null);

        if ($componentSku) {
            $bySku = Product::where('sku', $componentSku)->first();
            if ($bySku) {
                return $bySku;
            }

            throw new \InvalidArgumentException("Baris {$rowNumber}: Komponen SKU '{$componentSku}' tidak ditemukan.");
        }

        if ($componentName) {
            $matches = Product::where('name', $componentName)->get(['id', 'name']);
            if ($matches->count() === 1) {
                return Product::findOrFail($matches->first()->id);
            }

            if ($matches->count() > 1) {
                throw new \InvalidArgumentException("Baris {$rowNumber}: Nama komponen '{$componentName}' duplikat, gunakan SKU komponen.");
            }
        }

        throw new \InvalidArgumentException("Baris {$rowNumber}: Komponen BOM harus isi SKU atau nama komponen.");
    }

    private function syncBundlePurchasePrice(Product $product, int $bomId): void
    {
        $details = BomDetail::with('component')
            ->where('bom_id', $bomId)
            ->get();

        $total = (float) $details->sum(function (BomDetail $detail) {
            return ((float) ($detail->component?->purchase_price ?? 0)) * ((float) $detail->quantity);
        });

        $product->update(['purchase_price' => $total]);
    }

    private function hasValue(mixed $value): bool
    {
        return $this->normalizeText($value) !== null;
    }

    private function normalizeText(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $text = trim((string) $value);
        return $text === '' ? null : $text;
    }

    private function parseDecimal(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $raw = preg_replace('/[^0-9,.\-]/', '', (string) $value) ?? '';
        if ($raw === '' || $raw === '-' || $raw === '.' || $raw === ',') {
            return 0.0;
        }

        $lastComma = strrpos($raw, ',');
        $lastDot = strrpos($raw, '.');

        if ($lastComma !== false && $lastDot !== false) {
            if ($lastComma > $lastDot) {
                $raw = str_replace('.', '', $raw);
                $raw = str_replace(',', '.', $raw);
            } else {
                $raw = str_replace(',', '', $raw);
            }
        } elseif ($lastComma !== false) {
            $raw = str_replace(',', '.', $raw);
        } else {
            $raw = str_replace(',', '', $raw);
        }

        return is_numeric($raw) ? (float) $raw : 0.0;
    }

    private function generateSku(string $name): string
    {
        $prefix = strtoupper(substr(Str::slug($name), 0, 3));
        if ($prefix === '') {
            $prefix = 'PRD';
        }

        do {
            $candidate = $prefix . '-' . rand(1000, 9999);
        } while (Product::where('sku', $candidate)->exists());

        return $candidate;
    }

    private function generateCategoryCode(string $name): string
    {
        $base = strtoupper(substr(Str::slug($name), 0, 8));
        if ($base === '') {
            $base = 'CAT';
        }

        $base = 'CAT-' . $base;
        $code = $base . '-' . rand(1000, 9999);

        while (ProductCategory::where('code', $code)->exists()) {
            $code = $base . '-' . rand(1000, 9999);
        }

        return $code;
    }
}

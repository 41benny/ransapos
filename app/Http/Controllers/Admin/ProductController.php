<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BomDetail;
use App\Models\BomHeader;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\SalesType;
use App\Models\ProductCategory;
use App\Models\User;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Support\ProductSkuGenerator;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class ProductController extends Controller
{
    /**
     * Import products from Excel
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xls,xlsx'
        ]);

        try {
            $importer = new \App\Imports\ProductImport();
            Excel::import($importer, $request->file('file'));
            $summary = $importer->getSummary();

            // Redirect back to index with preserved filters
            return redirect(session('product_index_url', route('admin.products.index')))
                ->with('success', $this->buildImportSuccessMessage($summary));
        } catch (\Throwable $e) {
            return redirect(session('product_index_url', route('admin.products.index')))
                ->with('error', 'Gagal import produk: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan daftar produk
     */
    public function index(Request $request)
    {
        // Save current URL for redirection after actions
        session(['product_index_url' => $request->fullUrl()]);

        $query = Product::with(['category', 'bomHeader']);

        // Filter SKU
        if ($request->filled('sku')) {
            $query->where('sku', 'like', '%' . $request->sku . '%');
        }

        // Filter Name
        if ($request->filled('name')) {
            $query->where('name', 'like', '%' . $request->name . '%');
        }

        // Filter Category
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->category . '%');
            });
        }

        // Filter Price (flexible search)
        if ($request->filled('price')) {
            $query->whereRaw("CAST(selling_price AS CHAR) LIKE ?", ['%' . $request->price . '%']);
        }

        // Filter Unit
        if ($request->filled('unit')) {
            $query->where('unit', 'like', '%' . $request->unit . '%');
        }

        // Filter Status
        if ($request->filled('status')) {
            $isActive = $request->status === 'aktif';
            $query->where('is_active', $isActive);
        }

        $products = $query->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.products.index', compact('products'));
    }

    /**
     * @param array{
     *     mode: string,
     *     processed_rows: int,
     *     master_only_rows: int,
     *     bom_rows: int,
     *     unique_products: int,
     *     unique_bundle_products: int
     * } $summary
     */
    private function buildImportSuccessMessage(array $summary): string
    {
        $modeLabel = match ($summary['mode']) {
            'bundle_only' => 'Import bundle-only terdeteksi',
            'mixed' => 'Import campuran (master + bundle) terdeteksi',
            default => 'Import master-only terdeteksi',
        };

        return sprintf(
            '%s. Total baris: %d, baris master: %d, baris BOM: %d, produk unik: %d, bundle unik: %d.',
            $modeLabel,
            (int) $summary['processed_rows'],
            (int) $summary['master_only_rows'],
            (int) $summary['bom_rows'],
            (int) $summary['unique_products'],
            (int) $summary['unique_bundle_products']
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return $this->buildCreateView('product');
    }

    /**
     * Show form tambah bundle.
     */
    public function createBundle()
    {
        return $this->buildCreateView('bundle');
    }

    public function generateSku(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable|string|max:255',
            'bundle_mode' => 'nullable|boolean',
            'ignore_product_id' => 'nullable|integer|exists:products,id',
        ]);

        return response()->json([
            'sku' => ProductSkuGenerator::generate(
                $validated['name'] ?? null,
                (bool) ($validated['bundle_mode'] ?? false),
                isset($validated['ignore_product_id']) ? (int) $validated['ignore_product_id'] : null
            ),
        ]);
    }

    /**
     * Data bersama untuk form create produk/bundle.
     */
    private function buildCreateView(string $formMode)
    {
        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get();

        $outlets = Outlet::active()
            ->orderBy('name')
            ->get(['id', 'name']);
        $posUsers = User::with(['role:id,name', 'outlet:id,name'])
            ->where('is_active', true)
            ->whereHas('role', function ($query) {
                $query->whereIn('name', ['kasir', 'admin', 'manager']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'role_id', 'outlet_id']);
        $priceLevels = SalesType::priceLevels();
        $defaults = [
            'product_type' => $formMode === 'bundle' ? 'finished_good' : 'finished_good',
            'is_sellable' => true,
            'is_pos_available' => true,
            'is_online_order_available' => false,
            'is_available_all_outlets' => true,
            'is_available_all_users' => true,
        ];

        if ($formMode === 'bundle') {
            $rawMaterials = $this->loadRawMaterialsForBundle();

            return view('admin.products.create_bundle', compact('categories', 'outlets', 'posUsers', 'priceLevels', 'formMode', 'defaults', 'rawMaterials'));
        }

        return view('admin.products.create', compact('categories', 'outlets', 'posUsers', 'priceLevels', 'formMode', 'defaults'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        unset($data['image'], $data['bundle_components'], $data['bundle_mode']);

        $data['created_by'] = Auth::id();
        $isBundleMode = $request->boolean('bundle_mode');
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['is_sellable'] = $request->has('is_sellable') ? 1 : 0;
        $data['is_pos_available'] = $request->has('is_pos_available') ? 1 : 0;
        $data['is_online_order_available'] = $request->has('is_online_order_available') ? 1 : 0;
        $data['is_available_all_outlets'] = $request->has('is_available_all_outlets') ? 1 : 0;
        $data['is_available_all_users'] = $request->has('is_available_all_users') ? 1 : 0;
        $data['pos_outlet_ids'] = $data['is_available_all_outlets']
            ? null
            : $this->sanitizeOutletIds($request->input('pos_outlet_ids', []));
        $data['pos_user_ids'] = $data['is_available_all_users']
            ? null
            : $this->sanitizeUserIds($request->input('pos_user_ids', []));
        $data['price_levels'] = $this->normalizePriceLevels(
            $request->input('price_levels', []),
            (float) ($data['selling_price'] ?? 0)
        );

        if (($data['product_type'] ?? null) === 'raw_material') {
            $data['is_sellable'] = 0;
            $data['is_pos_available'] = 0;
            $data['is_online_order_available'] = 0;
            $data['is_available_all_outlets'] = 1;
            $data['is_available_all_users'] = 1;
            $data['pos_outlet_ids'] = null;
            $data['pos_user_ids'] = null;
        }

        // Extract selling_price from regular level (could be number or array with 'default')
        $regularPrice = $data['price_levels']['regular'];
        $data['selling_price'] = is_array($regularPrice) ? $regularPrice['default'] : $regularPrice;
        $components = collect();

        if ($isBundleMode) {
            $components = $this->sanitizeBundleComponents($request->input('bundle_components', []));

            if ($components->isEmpty()) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'bundle_components' => 'Bundle harus memiliki minimal 1 komponen bahan.',
                    ]);
            }

            $data['purchase_price'] = $this->calculateBundlePurchasePrice($components);
        }

        DB::beginTransaction();
        $uploadedImagePath = null;
        $uploadedThumbnailPath = null;

        try {
            if ($request->hasFile('image')) {
                $uploadedImagePath = $request->file('image')->store('products', 'public');
                $data['image_path'] = $uploadedImagePath;
                $uploadedThumbnailPath = $this->generateThumbnail($uploadedImagePath);
                $data['thumbnail_path'] = $uploadedThumbnailPath;
            }

            $product = Product::create($data);

            if ($isBundleMode) {
                $bomHeader = BomHeader::create([
                    'product_id' => $product->id,
                    'name' => $request->filled('name') ? ('Resep ' . $request->input('name')) : null,
                    'source_type' => 'bundle',
                    'is_active' => true,
                    'notes' => $request->input('description'),
                ]);

                foreach ($components as $component) {
                    if ((int) $component['component_product_id'] === (int) $product->id) {
                        throw new \InvalidArgumentException('Komponen tidak boleh sama dengan produk bundle.');
                    }

                    BomDetail::create([
                        'bom_id' => $bomHeader->id,
                        'component_product_id' => (int) $component['component_product_id'],
                        'quantity' => (float) $component['quantity'],
                        'uom' => $component['uom'] ?? null,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($uploadedImagePath) {
                Storage::disk('public')->delete($uploadedImagePath);
            }
            if ($uploadedThumbnailPath) {
                Storage::disk('public')->delete($uploadedThumbnailPath);
            }

            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }

        // Redirect back to index with preserved filters
        return redirect(session('product_index_url', route('admin.products.index')))
            ->with('success', $isBundleMode ? 'Bundle dan BOM berhasil ditambahkan!' : 'Produk berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load(
            'category',
            'creator',
            'stocks.outlet',
            'bomHeader.details.component'
        );

        $isBundleProduct = $product->bomHeader !== null;
        $bundleComponents = collect();
        $bundleTotalHpp = 0.0;

        if ($isBundleProduct) {
            $bundleComponents = $product->bomHeader->details
                ->map(function (BomDetail $detail): array {
                    $quantity = (float) $detail->quantity;
                    $unitCost = (float) ($detail->component?->purchase_price ?? 0);

                    return [
                        'component_name' => $detail->component?->name ?? '-',
                        'component_sku' => $detail->component?->sku ?? '-',
                        'quantity' => $quantity,
                        'uom' => $detail->uom ?: ($detail->component?->unit ?? '-'),
                        'unit_cost' => $unitCost,
                        'subtotal' => $quantity * $unitCost,
                    ];
                })
                ->values();

            $bundleTotalHpp = (float) $bundleComponents->sum('subtotal');
        }

        return view('admin.products.show', [
            'product' => $product,
            'isBundleProduct' => $isBundleProduct,
            'bundleComponents' => $bundleComponents,
            'bundleTotalHpp' => $bundleTotalHpp,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
    {
        // Load dependencies standard
        $categories = ProductCategory::where('is_active', true)
            ->orderBy('name')
            ->get();
        $outlets = Outlet::active()
            ->orderBy('name')
            ->get(['id', 'name']);
        $posUsers = User::with(['role:id,name', 'outlet:id,name'])
            ->where('is_active', true)
            ->whereHas('role', function ($query) {
                $query->whereIn('name', ['kasir', 'admin', 'manager']);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'role_id', 'outlet_id']);
        $priceLevels = SalesType::priceLevels();

        // Bundle tetap memakai halaman edit_bundle agar form komponen BOM tersedia.
        $isBundle = $product->bomHeader()->where('source_type', 'bundle')->exists();
        if ($isBundle) {
            $rawMaterials = $this->loadRawMaterialsForBundle();

            return view('admin.products.edit_bundle', compact('product', 'categories', 'outlets', 'posUsers', 'priceLevels', 'rawMaterials'));
        }

        return view('admin.products.edit', compact('product', 'categories', 'outlets', 'posUsers', 'priceLevels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();
        unset($data['image'], $data['bundle_components'], $data['bundle_mode']);

        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['is_sellable'] = $request->has('is_sellable') ? 1 : 0;
        $data['is_pos_available'] = $request->has('is_pos_available') ? 1 : 0;
        $data['is_online_order_available'] = $request->has('is_online_order_available') ? 1 : 0;
        $data['is_available_all_outlets'] = $request->has('is_available_all_outlets') ? 1 : 0;
        $data['is_available_all_users'] = $request->has('is_available_all_users') ? 1 : 0;
        $data['pos_outlet_ids'] = $data['is_available_all_outlets']
            ? null
            : $this->sanitizeOutletIds($request->input('pos_outlet_ids', []));
        $data['pos_user_ids'] = $data['is_available_all_users']
            ? null
            : $this->sanitizeUserIds($request->input('pos_user_ids', []));
        $data['price_levels'] = $this->normalizePriceLevels(
            $request->input('price_levels', []),
            (float) ($data['selling_price'] ?? $product->selling_price)
        );

        if (($data['product_type'] ?? null) === 'raw_material') {
            $data['is_sellable'] = 0;
            $data['is_pos_available'] = 0;
            $data['is_online_order_available'] = 0;
            $data['is_available_all_outlets'] = 1;
            $data['is_available_all_users'] = 1;
            $data['pos_outlet_ids'] = null;
            $data['pos_user_ids'] = null;
        }

        // Extract selling_price from regular level (could be number or array with 'default')
        $regularPrice = $data['price_levels']['regular'];
        $data['selling_price'] = is_array($regularPrice) ? $regularPrice['default'] : $regularPrice;

        $isBundleMode = $request->boolean('bundle_mode')
            || $product->bomHeader()->where('source_type', 'bundle')->exists();
        $components = collect();

        if ($isBundleMode) {
            $components = $this->sanitizeBundleComponents($request->input('bundle_components', []));

            if ($components->isEmpty()) {
                return back()
                    ->withInput()
                    ->withErrors([
                        'bundle_components' => 'Bundle harus memiliki minimal 1 komponen bahan.',
                    ]);
            }

            $data['purchase_price'] = $this->calculateBundlePurchasePrice($components);
        }

        $newImagePath = null;
        $newThumbnailPath = null;
        $oldImagePath = $product->image_path;
        $oldThumbnailPath = $product->thumbnail_path;

        DB::beginTransaction();

        try {
            if ($request->hasFile('image')) {
                $newImagePath = $request->file('image')->store('products', 'public');
                $newThumbnailPath = $this->generateThumbnail($newImagePath);
                $data['image_path'] = $newImagePath;
                $data['thumbnail_path'] = $newThumbnailPath;
            }

            $product->update($data);

            if ($isBundleMode) {
                $bomHeader = $product->bomHeader()->first();
                if (!$bomHeader) {
                    $bomHeader = BomHeader::create([
                        'product_id' => $product->id,
                        'name' => $request->filled('name') ? ('Resep ' . $request->input('name')) : null,
                        'source_type' => 'bundle',
                        'is_active' => (bool) $data['is_active'],
                        'notes' => $request->input('description'),
                    ]);
                } else {
                    $bomHeader->update([
                        'name' => $request->filled('name') ? ('Resep ' . $request->input('name')) : $bomHeader->name,
                        'is_active' => (bool) $data['is_active'],
                        'notes' => $request->input('description'),
                    ]);
                }

                $bomHeader->details()->delete();

                foreach ($components as $component) {
                    if ((int) $component['component_product_id'] === (int) $product->id) {
                        throw new \InvalidArgumentException('Komponen tidak boleh sama dengan produk bundle.');
                    }

                    BomDetail::create([
                        'bom_id' => $bomHeader->id,
                        'component_product_id' => (int) $component['component_product_id'],
                        'quantity' => (float) $component['quantity'],
                        'uom' => $component['uom'] ?? null,
                    ]);
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            if ($newImagePath) {
                Storage::disk('public')->delete($newImagePath);
            }
            if ($newThumbnailPath) {
                Storage::disk('public')->delete($newThumbnailPath);
            }

            return back()
                ->withInput()
                ->with('error', 'Gagal memperbarui data: ' . $e->getMessage());
        }

        if ($newImagePath && !empty($oldImagePath)) {
            Storage::disk('public')->delete($oldImagePath);
        }
        if ($newThumbnailPath && !empty($oldThumbnailPath)) {
            Storage::disk('public')->delete($oldThumbnailPath);
        }

        // Redirect back to index with preserved filters
        return redirect(session('product_index_url', route('admin.products.index')))
            ->with('success', $isBundleMode ? 'Bundle dan BOM berhasil diperbarui!' : 'Produk berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        try {
            $product->delete();

            // Redirect back to index with preserved filters
            return redirect(session('product_index_url', route('admin.products.index')))
                ->with('success', 'Produk berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect(session('product_index_url', route('admin.products.index')))
                ->with('error', 'Gagal menghapus produk. Produk mungkin masih digunakan dalam transaksi.');
        }
    }

    /**
     * Bersihkan daftar outlet dari request.
     */
    private function sanitizeOutletIds(mixed $outletIds): array
    {
        if (is_string($outletIds)) {
            $outletIds = array_filter(array_map('trim', explode(',', $outletIds)));
        }

        if (!is_array($outletIds)) {
            return [];
        }

        return collect($outletIds)
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Bersihkan daftar user POS dari request.
     */
    private function sanitizeUserIds(mixed $userIds): array
    {
        if (is_string($userIds)) {
            $userIds = array_filter(array_map('trim', explode(',', $userIds)));
        }

        if (!is_array($userIds)) {
            return [];
        }

        return collect($userIds)
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function generateThumbnail(string $imagePath): ?string
    {
        if (!extension_loaded('gd')) {
            return null;
        }

        $disk = Storage::disk('public');
        $sourceAbsolutePath = $disk->path($imagePath);

        if (!is_file($sourceAbsolutePath)) {
            return null;
        }

        $imageData = @file_get_contents($sourceAbsolutePath);
        if ($imageData === false) {
            return null;
        }

        $sourceImage = @imagecreatefromstring($imageData);
        if ($sourceImage === false) {
            return null;
        }

        $sourceWidth = imagesx($sourceImage);
        $sourceHeight = imagesy($sourceImage);
        if ($sourceWidth <= 0 || $sourceHeight <= 0) {
            imagedestroy($sourceImage);
            return null;
        }

        $thumbSize = 360;
        $cropSize = min($sourceWidth, $sourceHeight);
        $cropX = (int) floor(($sourceWidth - $cropSize) / 2);
        $cropY = (int) floor(($sourceHeight - $cropSize) / 2);

        $thumbnailImage = imagecreatetruecolor($thumbSize, $thumbSize);
        $background = imagecolorallocate($thumbnailImage, 255, 255, 255);
        imagefill($thumbnailImage, 0, 0, $background);

        imagecopyresampled(
            $thumbnailImage,
            $sourceImage,
            0,
            0,
            $cropX,
            $cropY,
            $thumbSize,
            $thumbSize,
            $cropSize,
            $cropSize
        );

        $filename = pathinfo($imagePath, PATHINFO_FILENAME);
        $thumbnailPath = 'products/thumbnails/' . $filename . '_thumb.jpg';
        $thumbnailAbsolutePath = $disk->path($thumbnailPath);
        $thumbnailDir = dirname($thumbnailAbsolutePath);

        if (!is_dir($thumbnailDir)) {
            mkdir($thumbnailDir, 0755, true);
        }

        $written = @imagejpeg($thumbnailImage, $thumbnailAbsolutePath, 82);

        imagedestroy($thumbnailImage);
        imagedestroy($sourceImage);

        if (!$written) {
            Log::warning('Failed to write product thumbnail', [
                'image_path' => $imagePath,
                'thumbnail_path' => $thumbnailPath,
            ]);
            return null;
        }

        return $thumbnailPath;
    }

    /**
     * Bentuk map harga berdasarkan level.
     * Mendukung format lama (numeric) dan format baru (dengan default + outlets)
     *
     * @param array<string, mixed> $priceLevels
     * @param float $fallbackRegularPrice
     * @return array<string, float|array>
     */
    private function normalizePriceLevels(array $priceLevels, float $fallbackRegularPrice): array
    {
        $definedLevels = array_keys(SalesType::priceLevels());
        $normalized = [];

        foreach ($definedLevels as $level) {
            $rawValue = $priceLevels[$level] ?? null;

            if ($rawValue === '' || $rawValue === null) {
                continue;
            }

            // Case 1: Simple numeric input (backward compatible - dari form lama)
            if (is_numeric($rawValue)) {
                $normalized[$level] = (float) $rawValue;
                continue;
            }

            // Case 2: Array dengan 'default' dan possibly 'outlets'
            if (is_array($rawValue)) {
                $defaultPrice = isset($rawValue['default']) && is_numeric($rawValue['default'])
                    ? (float) $rawValue['default']
                    : 0;

                // Kumpulkan outlet-specific prices
                $outletPrices = [];
                if (isset($rawValue['outlets']) && is_array($rawValue['outlets'])) {
                    foreach ($rawValue['outlets'] as $outletId => $price) {
                        // Hanya simpan jika price ada dan > 0
                        if (is_numeric($price) && (float) $price > 0) {
                            $outletPrices[(string) $outletId] = (float) $price;
                        }
                    }
                }

                // Jika tidak ada outlet-specific prices, simpan sebagai number saja (backward compatible)
                if (empty($outletPrices)) {
                    $normalized[$level] = $defaultPrice;
                } else {
                    // Simpan dengan struktur lengkap
                    $normalized[$level] = [
                        'default' => $defaultPrice,
                        'outlets' => $outletPrices,
                    ];
                }
                continue;
            }
        }

        // Pastikan 'regular' selalu ada
        if (!isset($normalized['regular'])) {
            $normalized['regular'] = $fallbackRegularPrice;
        } elseif (is_array($normalized['regular'])) {
            // Jika regular berbentuk array, pastikan default tidak 0
            if (!isset($normalized['regular']['default']) || $normalized['regular']['default'] <= 0) {
                $normalized['regular']['default'] = $fallbackRegularPrice;
            }
        } elseif ((float) $normalized['regular'] <= 0) {
            $normalized['regular'] = $fallbackRegularPrice;
        }

        return $normalized;
    }

    private function loadRawMaterialsForBundle()
    {
        $rawCategoryName = config('bom.raw_material_category_name', env('BOM_RAW_MATERIAL_CATEGORY_NAME', 'Bahan Baku'));
        $rawCategory = ProductCategory::where('name', $rawCategoryName)->first();

        if ($rawCategory) {
            return Product::where('category_id', $rawCategory->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'sku', 'unit', 'purchase_price']);
        }

        return Product::where('product_type', 'raw_material')
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'purchase_price']);
    }

    private function sanitizeBundleComponents(mixed $components): Collection
    {
        if (!is_array($components)) {
            return collect();
        }

        return collect($components)
            ->map(function ($component) {
                if (!is_array($component)) {
                    return null;
                }

                $componentProductId = $component['component_product_id'] ?? null;
                $quantity = $component['quantity'] ?? null;
                $uom = $component['uom'] ?? null;

                if (!is_numeric($componentProductId) || !is_numeric($quantity)) {
                    return null;
                }

                $quantityValue = (float) $quantity;
                if ($quantityValue <= 0) {
                    return null;
                }

                return [
                    'component_product_id' => (int) $componentProductId,
                    'quantity' => $quantityValue,
                    'uom' => !empty($uom) ? (string) $uom : null,
                ];
            })
            ->filter()
            ->values();
    }

    private function calculateBundlePurchasePrice(Collection $components): float
    {
        $componentIds = $components->pluck('component_product_id')
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values();

        $purchasePriceById = Product::whereIn('id', $componentIds)
            ->pluck('purchase_price', 'id');

        if ($purchasePriceById->count() !== $componentIds->count()) {
            throw new \InvalidArgumentException('Ada komponen bundle yang tidak ditemukan.');
        }

        return (float) $components->sum(function (array $component) use ($purchasePriceById) {
            $componentId = (int) $component['component_product_id'];
            $quantity = (float) $component['quantity'];
            $purchasePrice = (float) ($purchasePriceById[$componentId] ?? 0);

            return $quantity * $purchasePrice;
        });
    }
}

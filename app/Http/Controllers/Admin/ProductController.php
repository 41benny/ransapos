<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BomDetail;
use App\Models\BomHeader;
use App\Models\Outlet;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\User;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
            Excel::import(new \App\Imports\ProductImport, $request->file('file'));

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Produk berhasil diimport!');
        } catch (\Throwable $e) {
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Gagal import produk: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan daftar produk
     */
    public function index()
    {
        $products = Product::with('category')
            ->orderBy('name')
            ->paginate(15);

        return view('admin.products.index', compact('products'));
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
        $priceLevels = config('sales.price_levels', ['regular' => 'Reguler']);
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

        $data['selling_price'] = $data['price_levels']['regular'];
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

        try {
            if ($request->hasFile('image')) {
                $uploadedImagePath = $request->file('image')->store('products', 'public');
                $data['image_path'] = $uploadedImagePath;
            }

            $product = Product::create($data);

            if ($isBundleMode) {
                $bomHeader = BomHeader::create([
                    'product_id' => $product->id,
                    'name' => $request->filled('name') ? ('Resep ' . $request->input('name')) : null,
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

            return back()
                ->withInput()
                ->with('error', 'Gagal menyimpan data: ' . $e->getMessage());
        }

        return redirect()
            ->route('admin.products.index')
            ->with('success', $isBundleMode ? 'Bundle dan BOM berhasil ditambahkan!' : 'Produk berhasil ditambahkan!');
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        $product->load('category', 'creator', 'stocks.outlet');

        return view('admin.products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Product $product)
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
        $priceLevels = config('sales.price_levels', ['regular' => 'Reguler']);

        return view('admin.products.edit', compact('product', 'categories', 'outlets', 'posUsers', 'priceLevels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();
        unset($data['image']);

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

        $data['selling_price'] = $data['price_levels']['regular'];

        if ($request->hasFile('image')) {
            $newImagePath = $request->file('image')->store('products', 'public');
            if (!empty($product->image_path)) {
                Storage::disk('public')->delete($product->image_path);
            }
            $data['image_path'] = $newImagePath;
        }

        $product->update($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil diperbarui!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        try {
            $product->delete();

            return redirect()
                ->route('admin.products.index')
                ->with('success', 'Produk berhasil dihapus!');
        } catch (\Exception $e) {
            return redirect()
                ->route('admin.products.index')
                ->with('error', 'Gagal menghapus produk. Produk mungkin masih digunakan dalam transaksi.');
        }
    }

    /**
     * Bersihkan daftar outlet dari request.
     */
    private function sanitizeOutletIds(mixed $outletIds): array
    {
        if (!is_array($outletIds)) {
            return [];
        }

        return collect($outletIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Bersihkan daftar user POS dari request.
     */
    private function sanitizeUserIds(mixed $userIds): array
    {
        if (!is_array($userIds)) {
            return [];
        }

        return collect($userIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Bentuk map harga berdasarkan level.
     *
     * @param array<string, mixed> $priceLevels
     * @return array<string, float>
     */
    private function normalizePriceLevels(array $priceLevels, float $fallbackRegularPrice): array
    {
        $definedLevels = array_keys(config('sales.price_levels', ['regular' => 'Reguler']));
        $normalized = [];

        foreach ($definedLevels as $level) {
            $rawValue = $priceLevels[$level] ?? null;
            if ($rawValue === '' || $rawValue === null) {
                continue;
            }

            $normalized[$level] = (float) $rawValue;
        }

        $regularPrice = $normalized['regular'] ?? $fallbackRegularPrice;
        $normalized['regular'] = max(0, (float) $regularPrice);

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
            ->map(fn ($id) => (int) $id)
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

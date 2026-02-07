<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\Outlet;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
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
        $priceLevels = config('sales.price_levels', ['regular' => 'Reguler']);
        $defaults = [
            'product_type' => $formMode === 'bundle' ? 'finished_good' : 'finished_good',
            'is_sellable' => true,
            'is_pos_available' => true,
            'is_online_order_available' => false,
            'is_available_all_outlets' => true,
            'is_available_all_users' => true,
        ];

        return view('admin.products.create', compact('categories', 'outlets', 'priceLevels', 'formMode', 'defaults'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $data = $request->validated();
        $data['created_by'] = Auth::id();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['is_sellable'] = $request->has('is_sellable') ? 1 : 0;
        $data['is_pos_available'] = $request->has('is_pos_available') ? 1 : 0;
        $data['is_online_order_available'] = $request->has('is_online_order_available') ? 1 : 0;
        $data['is_available_all_outlets'] = $request->has('is_available_all_outlets') ? 1 : 0;
        $data['is_available_all_users'] = $request->has('is_available_all_users') ? 1 : 0;
        $data['pos_outlet_ids'] = $data['is_available_all_outlets']
            ? null
            : $this->sanitizeOutletIds($request->input('pos_outlet_ids', []));
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
        }

        $data['selling_price'] = $data['price_levels']['regular'];

        Product::create($data);

        return redirect()
            ->route('admin.products.index')
            ->with('success', 'Produk berhasil ditambahkan!');
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
        $priceLevels = config('sales.price_levels', ['regular' => 'Reguler']);

        return view('admin.products.edit', compact('product', 'categories', 'outlets', 'priceLevels'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, Product $product)
    {
        $data = $request->validated();
        $data['is_active'] = $request->has('is_active') ? 1 : 0;
        $data['is_sellable'] = $request->has('is_sellable') ? 1 : 0;
        $data['is_pos_available'] = $request->has('is_pos_available') ? 1 : 0;
        $data['is_online_order_available'] = $request->has('is_online_order_available') ? 1 : 0;
        $data['is_available_all_outlets'] = $request->has('is_available_all_outlets') ? 1 : 0;
        $data['is_available_all_users'] = $request->has('is_available_all_users') ? 1 : 0;
        $data['pos_outlet_ids'] = $data['is_available_all_outlets']
            ? null
            : $this->sanitizeOutletIds($request->input('pos_outlet_ids', []));
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
        }

        $data['selling_price'] = $data['price_levels']['regular'];

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
}

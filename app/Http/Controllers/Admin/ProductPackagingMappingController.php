<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PackagingItem;
use App\Models\Product;
use App\Models\ProductPackagingMapping;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProductPackagingMappingController extends Controller
{
    public function index(Request $request)
    {
        $query = Product::query()
            ->with(['category', 'packagingMappings' => fn ($q) => $q->where('is_active', true)->with('packagingItem')]);

        if ($search = trim((string) $request->get('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        if ($request->get('filter') === 'unmapped') {
            $query->whereDoesntHave('packagingMappings', fn ($q) => $q->where('is_active', true));
        }

        $products = $query->orderBy('name')->paginate(30)->withQueryString();
        $packagingItems = PackagingItem::active()->ordered()->get();

        $unmappedCount = Product::whereDoesntHave('packagingMappings', fn ($q) => $q->where('is_active', true))->count();

        return view('admin.packaging-mappings.index', compact('products', 'packagingItems', 'unmappedCount'));
    }

    /**
     * Set/update mapping packaging untuk satu produk (MVP: satu packaging utama per produk).
     */
    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'packaging_item_id' => 'nullable|exists:packaging_items,id',
            'qty_per_product' => 'nullable|numeric|min:0.01',
        ]);

        DB::transaction(function () use ($validated, $product) {
            // Nonaktifkan mapping lain milik produk ini.
            ProductPackagingMapping::where('product_id', $product->id)->update(['is_active' => false]);

            if (! empty($validated['packaging_item_id'])) {
                ProductPackagingMapping::updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'packaging_item_id' => $validated['packaging_item_id'],
                    ],
                    [
                        'qty_per_product' => $validated['qty_per_product'] ?? 1,
                        'is_active' => true,
                    ]
                );
            }
        });

        return back()->with('success', "Mapping untuk \"{$product->name}\" disimpan.");
    }
}

<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductCategoryController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->input('q', ''));

        $query = ProductCategory::query()
            ->withCount('products')
            ->orderByDesc('is_active')
            ->orderBy('name');

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhere('description', 'like', '%' . $search . '%');
            });
        }

        $categories = $query->paginate(20)->withQueryString();

        return view('admin.product-categories.index', [
            'categories' => $categories,
            'search' => $search,
        ]);
    }

    public function create(): View
    {
        return view('admin.product-categories.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $request->merge([
            'code' => $this->normalizeCode((string) $request->input('code', '')),
        ]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/', 'unique:product_categories,code'],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'Kode hanya boleh huruf kapital, angka, dan underscore (_).',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        ProductCategory::create($validated);

        return redirect()
            ->route('admin.product-categories.index')
            ->with('success', 'Kategori produk berhasil ditambahkan.');
    }

    public function edit(ProductCategory $productCategory): View
    {
        return view('admin.product-categories.edit', compact('productCategory'));
    }

    public function update(Request $request, ProductCategory $productCategory): RedirectResponse
    {
        $request->merge([
            'code' => $this->normalizeCode((string) $request->input('code', '')),
        ]);

        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9_]+$/', 'unique:product_categories,code,' . $productCategory->id],
            'name' => ['required', 'string', 'max:200'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
        ], [
            'code.regex' => 'Kode hanya boleh huruf kapital, angka, dan underscore (_).',
        ]);

        $validated['is_active'] = $request->boolean('is_active');

        $productCategory->update($validated);

        return redirect()
            ->route('admin.product-categories.index')
            ->with('success', 'Kategori produk berhasil diperbarui.');
    }

    public function destroy(ProductCategory $productCategory): RedirectResponse
    {
        $productCount = $productCategory->products()->count();
        if ($productCount > 0) {
            return back()->with('error', "Kategori produk tidak bisa dihapus karena sudah dipakai oleh {$productCount} produk.");
        }

        $promotionRuleCount = $productCategory->promotionRules()->count();
        if ($promotionRuleCount > 0) {
            return back()->with('error', 'Kategori produk tidak bisa dihapus karena masih dipakai di aturan promo.');
        }

        $productCategory->delete();

        return redirect()
            ->route('admin.product-categories.index')
            ->with('success', 'Kategori produk berhasil dihapus.');
    }

    private function normalizeCode(string $code): string
    {
        $normalized = strtoupper(trim($code));
        $normalized = preg_replace('/[^A-Z0-9]+/', '_', $normalized) ?? '';

        return trim($normalized, '_');
    }
}

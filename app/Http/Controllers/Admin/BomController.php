<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\BomHeader;
use App\Models\BomDetail;
use App\Models\Product;
use App\Models\ProductCategory;
use Illuminate\Support\Facades\DB;
use Exception;

class BomController extends Controller
{
    private function rawMaterialCategory(): ?ProductCategory
    {
        $name = config('bom.raw_material_category_name', env('BOM_RAW_MATERIAL_CATEGORY_NAME', 'Bahan Baku'));
        return ProductCategory::where('name', $name)->first();
    }

    public function index()
    {
        $sourceType = request()->query('source_type', 'production');
        if (!in_array($sourceType, ['production', 'bundle', 'all'], true)) {
            $sourceType = 'production';
        }

        $bomsQuery = BomHeader::with(['product'])->withCount('details');
        if ($sourceType !== 'all') {
            $bomsQuery->where('source_type', $sourceType);
        }

        $boms = $bomsQuery
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
        
        if (request()->expectsJson()) {
            return response()->json($boms);
        }
        
        return view('admin.boms.index', compact('boms', 'sourceType'));
    }

    public function create()
    {
        $rawCategory = $this->rawMaterialCategory();

        if ($rawCategory) {
            // Prefer category-based selection: Komponen = kategori bahan baku; Produk Utama = selain itu.
            $rawMaterials = Product::where('category_id', $rawCategory->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $finishedProducts = Product::where('category_id', '!=', $rawCategory->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            // Fallback for older data setups that still use product_type.
            $finishedProducts = Product::where('product_type', 'finished_good')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $rawMaterials = Product::where('product_type', 'raw_material')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        $prefillProductId = null;
        if (request()->filled('product_id')) {
            $candidateId = (int) request()->query('product_id');
            if ($candidateId > 0 && $finishedProducts->contains('id', $candidateId)) {
                $prefillProductId = $candidateId;
            }
        }

        // gunakan tampilan versi baru yang lebih bersih
        return view('admin.boms.create_clean', compact('finishedProducts', 'rawMaterials', 'prefillProductId'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'nullable|string|max:200',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
            'components' => 'required|array|min:1',
            'components.*.component_product_id' => 'required|exists:products,id',
            'components.*.quantity' => 'required|numeric|min:0.0001',
            'components.*.uom' => 'nullable|string|max:50',
        ]);

        // Validasi produk utama bukan bahan baku (kategori) atau service
        $product = Product::findOrFail($data['product_id']);
        $rawCategory = $this->rawMaterialCategory();
        if (($rawCategory && $product->category_id == $rawCategory->id) || $product->product_type === 'service') {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Produk utama harus finished_good'], 422);
            }
            return back()->withErrors(['product_id' => 'Produk utama harus finished_good'])->withInput();
        }

        DB::beginTransaction();
        try {
            $bom = BomHeader::create([
                'product_id' => $product->id,
                'name' => $data['name'] ?? null,
                'source_type' => 'production',
                'is_active' => $data['is_active'] ?? true,
                'notes' => $data['notes'] ?? null,
            ]);

            foreach ($data['components'] as $component) {
                if ($component['component_product_id'] == $product->id) {
                    throw new Exception('Komponen tidak boleh produk itu sendiri');
                }
                BomDetail::create([
                    'bom_id' => $bom->id,
                    'component_product_id' => $component['component_product_id'],
                    'quantity' => $component['quantity'],
                    'uom' => $component['uom'] ?? null,
                ]);
            }

            DB::commit();
            
            if ($request->expectsJson()) {
                return response()->json($bom->load('details.component'), 201);
            }
            
            return redirect()->route('admin.boms.index')->with('success', 'BOM berhasil dibuat');
        } catch (Exception $e) {
            DB::rollBack();
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }
            return back()->withErrors(['general' => $e->getMessage()])->withInput();
        }
    }

    public function show(BomHeader $bom)
    {
        $bom->load('product','details.component');
        
        if (request()->expectsJson()) {
            return response()->json($bom);
        }
        
        return view('admin.boms.show', compact('bom'));
    }

    public function edit(BomHeader $bom)
    {
        $bom->load('details.component');

        $rawCategory = $this->rawMaterialCategory();

        if ($rawCategory) {
            $rawMaterials = Product::where('category_id', $rawCategory->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $finishedProducts = Product::where('category_id', '!=', $rawCategory->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        } else {
            $finishedProducts = Product::where('product_type', 'finished_good')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();

            $rawMaterials = Product::where('product_type', 'raw_material')
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }
            
        return view('admin.boms.edit', compact('bom', 'finishedProducts', 'rawMaterials'));
    }

    public function update(Request $request, BomHeader $bom)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:200',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
            'components' => 'nullable|array',
            'components.*.id' => 'nullable|exists:bom_details,id',
            'components.*.component_product_id' => 'required_with:components|exists:products,id',
            'components.*.quantity' => 'required_with:components|numeric|min:0.0001',
            'components.*.uom' => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();
        try {
            $bom->update([
                'name' => $data['name'] ?? $bom->name,
                'is_active' => $data['is_active'] ?? $bom->is_active,
                'notes' => $data['notes'] ?? $bom->notes,
            ]);

            if (isset($data['components'])) {
                // Hapus semua detail lama dan buat baru (sederhana)
                $bom->details()->delete();
                foreach ($data['components'] as $component) {
                    if ($component['component_product_id'] == $bom->product_id) {
                        throw new Exception('Komponen tidak boleh produk itu sendiri');
                    }
                    BomDetail::create([
                        'bom_id' => $bom->id,
                        'component_product_id' => $component['component_product_id'],
                        'quantity' => $component['quantity'],
                        'uom' => $component['uom'] ?? null,
                    ]);
                }
            }

            DB::commit();

            if ($request->expectsJson()) {
                return response()->json($bom->load('details.component'));
            }

            return redirect()
                ->route('admin.boms.index')
                ->with('success', 'BOM berhasil diperbarui');
        } catch (Exception $e) {
            DB::rollBack();

            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], 422);
            }

            return back()
                ->withInput()
                ->withErrors(['general' => $e->getMessage()]);
        }
    }

    public function destroy(BomHeader $bom)
    {
        $bom->delete();
        
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Deleted']);
        }
        
        return redirect()->route('admin.boms.index')->with('success', 'BOM berhasil dihapus');
    }
}

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
use App\Support\ReportExport;

class BomController extends Controller
{
    private const SOURCE_TYPE_PRODUCTION = 'production';
    private const SOURCE_TYPE_BUNDLE = 'bundle';
    private const SOURCE_TYPE_ALL = 'all';

    private function rawMaterialCategory(): ?ProductCategory
    {
        $name = config('bom.raw_material_category_name', env('BOM_RAW_MATERIAL_CATEGORY_NAME', 'Bahan Baku'));
        return ProductCategory::where('name', $name)->first();
    }

    private function normalizeSourceType(?string $sourceType, string $default = self::SOURCE_TYPE_PRODUCTION): string
    {
        $normalized = strtolower(trim((string) $sourceType));
        $allowed = [self::SOURCE_TYPE_PRODUCTION, self::SOURCE_TYPE_BUNDLE, self::SOURCE_TYPE_ALL];

        return in_array($normalized, $allowed, true) ? $normalized : $default;
    }

    private function resolveSafeReturnTo(?string $returnTo, string $fallback): string
    {
        if (!$returnTo) {
            return $fallback;
        }

        $parsed = parse_url($returnTo);
        if ($parsed === false) {
            return $fallback;
        }

        // Allow relative internal URLs only.
        if (!isset($parsed['host'])) {
            return str_starts_with($returnTo, '/') ? $returnTo : $fallback;
        }

        $currentHost = parse_url(url('/'), PHP_URL_HOST);
        $currentScheme = parse_url(url('/'), PHP_URL_SCHEME);
        $hostMatch = strtolower((string) $parsed['host']) === strtolower((string) $currentHost);
        $schemeMatch = !isset($parsed['scheme']) || strtolower((string) $parsed['scheme']) === strtolower((string) $currentScheme);

        return ($hostMatch && $schemeMatch) ? $returnTo : $fallback;
    }

    public function index()
    {
        $sourceType = $this->normalizeSourceType(request()->query('source_type'), self::SOURCE_TYPE_PRODUCTION);

        $bomsQuery = BomHeader::with(['product', 'details.component'])->withCount('details');
        if ($sourceType !== self::SOURCE_TYPE_ALL) {
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
        $sourceType = $this->normalizeSourceType(request()->query('source_type'), self::SOURCE_TYPE_PRODUCTION);
        if ($sourceType === self::SOURCE_TYPE_ALL) {
            $sourceType = self::SOURCE_TYPE_PRODUCTION;
        }
        $defaultBackUrl = $sourceType === self::SOURCE_TYPE_BUNDLE
            ? route('admin.products.index')
            : route('admin.boms.index', ['source_type' => self::SOURCE_TYPE_PRODUCTION]);
        $returnTo = $this->resolveSafeReturnTo(request()->query('return_to'), $defaultBackUrl);

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
        return view('admin.boms.create_clean', compact('finishedProducts', 'rawMaterials', 'prefillProductId', 'sourceType', 'returnTo'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'name' => 'nullable|string|max:200',
            'source_type' => 'nullable|in:production,bundle',
            'return_to' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
            'components' => 'required|array|min:1',
            'components.*.component_product_id' => 'required|exists:products,id',
            'components.*.quantity' => 'required|numeric|min:0.0001',
            'components.*.uom' => 'nullable|string|max:50',
        ]);

        $sourceType = $this->normalizeSourceType($data['source_type'] ?? null, self::SOURCE_TYPE_PRODUCTION);
        if ($sourceType === self::SOURCE_TYPE_ALL) {
            $sourceType = self::SOURCE_TYPE_PRODUCTION;
        }
        $defaultRedirect = $sourceType === self::SOURCE_TYPE_BUNDLE
            ? route('admin.products.index')
            : route('admin.boms.index', ['source_type' => self::SOURCE_TYPE_PRODUCTION]);
        $returnTo = $this->resolveSafeReturnTo($data['return_to'] ?? null, $defaultRedirect);

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
                'source_type' => $sourceType,
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
            
            $successMessage = $sourceType === self::SOURCE_TYPE_BUNDLE
                ? 'Resep bundle berhasil dibuat'
                : 'Resep produksi berhasil dibuat';

            return redirect($returnTo)->with('success', $successMessage);
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
        $sourceType = $this->normalizeSourceType($bom->source_type, self::SOURCE_TYPE_BUNDLE);
        $defaultBackUrl = $sourceType === self::SOURCE_TYPE_BUNDLE
            ? route('admin.products.index')
            : route('admin.boms.index', ['source_type' => self::SOURCE_TYPE_PRODUCTION]);
        $returnTo = $this->resolveSafeReturnTo(request()->query('return_to'), $defaultBackUrl);
        
        if (request()->expectsJson()) {
            return response()->json($bom);
        }
        
        return view('admin.boms.show', compact('bom', 'sourceType', 'returnTo'));
    }

    public function edit(BomHeader $bom)
    {
        $bom->load('details.component');
        $sourceType = $this->normalizeSourceType($bom->source_type, self::SOURCE_TYPE_BUNDLE);
        $defaultBackUrl = $sourceType === self::SOURCE_TYPE_BUNDLE
            ? route('admin.products.index')
            : route('admin.boms.index', ['source_type' => self::SOURCE_TYPE_PRODUCTION]);
        $returnTo = $this->resolveSafeReturnTo(request()->query('return_to'), $defaultBackUrl);

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
            
        return view('admin.boms.edit', compact('bom', 'finishedProducts', 'rawMaterials', 'sourceType', 'returnTo'));
    }

    public function update(Request $request, BomHeader $bom)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:200',
            'source_type' => 'nullable|in:production,bundle',
            'return_to' => 'nullable|string|max:2000',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
            'components' => 'nullable|array',
            'components.*.id' => 'nullable|exists:bom_details,id',
            'components.*.component_product_id' => 'required_with:components|exists:products,id',
            'components.*.quantity' => 'required_with:components|numeric|min:0.0001',
            'components.*.uom' => 'nullable|string|max:50',
        ]);

        $currentSourceType = $this->normalizeSourceType($bom->source_type, self::SOURCE_TYPE_BUNDLE);
        $sourceType = $this->normalizeSourceType($data['source_type'] ?? null, $currentSourceType);
        if ($sourceType === self::SOURCE_TYPE_ALL) {
            $sourceType = $currentSourceType;
        }
        $defaultRedirect = $sourceType === self::SOURCE_TYPE_BUNDLE
            ? route('admin.products.index')
            : route('admin.boms.index', ['source_type' => self::SOURCE_TYPE_PRODUCTION]);
        $returnTo = $this->resolveSafeReturnTo($data['return_to'] ?? null, $defaultRedirect);

        DB::beginTransaction();
        try {
            $bom->update([
                'name' => $data['name'] ?? $bom->name,
                'source_type' => $sourceType,
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

            $successMessage = $sourceType === self::SOURCE_TYPE_BUNDLE
                ? 'Resep bundle berhasil diperbarui'
                : 'Resep produksi berhasil diperbarui';

            return redirect()
                ->to($returnTo)
                ->with('success', $successMessage);
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
        $sourceType = $this->normalizeSourceType(request()->query('source_type'), self::SOURCE_TYPE_PRODUCTION);
        if ($sourceType === self::SOURCE_TYPE_ALL) {
            $sourceType = self::SOURCE_TYPE_PRODUCTION;
        }
        $defaultRedirect = $sourceType === self::SOURCE_TYPE_BUNDLE
            ? route('admin.products.index')
            : route('admin.boms.index', ['source_type' => $sourceType]);
        $returnTo = $this->resolveSafeReturnTo(request()->query('return_to'), $defaultRedirect);

        $bom->delete();
        
        if (request()->expectsJson()) {
            return response()->json(['message' => 'Deleted']);
        }
        
        return redirect($returnTo)->with('success', 'Resep berhasil dihapus');

    public function exportExcel(Request $request)
    {
        $sourceType = $this->normalizeSourceType($request->query('source_type'), self::SOURCE_TYPE_PRODUCTION);
        
        $boms = BomHeader::with(['product', 'details.component'])
            ->when($sourceType !== self::SOURCE_TYPE_ALL, function($q) use ($sourceType) {
                return $q->where('source_type', $sourceType);
            })
            ->get();

        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'product_name', 'label' => 'Produk'],
            ['key' => 'sku', 'label' => 'SKU'],
            ['key' => 'bom_name', 'label' => 'Nama BOM'],
            ['key' => 'components', 'label' => 'Bahan / Komponen'],
            ['key' => 'status', 'label' => 'Status'],
        ];

        $rows = $boms->flatMap(function ($bom) {
            $details = $bom->details;
            $compStrings = $details->map(function($d) {
                return ($d->component->name ?? '-') . ': ' . (float)$d->quantity . ' ' . ($d->uom ?? '');
            });

            return [[
                'id' => $bom->id,
                'product_name' => $bom->product->name ?? '-',
                'sku' => $bom->product->sku ?? '-',
                'bom_name' => $bom->name ?? '-',
                'components' => $compStrings->implode("\n"),
                'status' => $bom->is_active ? 'Aktif' : 'Non-Aktif',
            ]];
        });

        ReportExport::xlsx('boms_' . $sourceType . '_' . now()->format('Ymd_His') . '.xlsx', 'BOMs ' . ucfirst($sourceType), $columns, $rows);
    }

    public function exportPdf(Request $request)
    {
        $sourceType = $this->normalizeSourceType($request->query('source_type'), self::SOURCE_TYPE_PRODUCTION);
        
        $boms = BomHeader::with(['product', 'details.component'])
            ->when($sourceType !== self::SOURCE_TYPE_ALL, function($q) use ($sourceType) {
                return $q->where('source_type', $sourceType);
            })
            ->get();

        $columns = [
            ['key' => 'id', 'label' => 'ID'],
            ['key' => 'product_name', 'label' => 'Produk'],
            ['key' => 'sku', 'label' => 'SKU'],
            ['key' => 'components', 'label' => 'Bahan / Komponen'],
            ['key' => 'status', 'label' => 'Status'],
        ];

        $rows = $boms->map(function ($bom) {
            $compStrings = $bom->details->map(function($d) {
                return ($d->component->name ?? '-') . ' (' . (float)$d->quantity . ' ' . ($d->uom ?? '') . ')';
            });

            return [
                'id' => $bom->id,
                'product_name' => $bom->product->name ?? '-',
                'sku' => $bom->product->sku ?? '-',
                'components' => $compStrings->implode(", "),
                'status' => $bom->is_active ? 'Aktif' : 'Non-Aktif',
            ];
        });

        ReportExport::pdf('boms_' . $sourceType . '_' . now()->format('Ymd_His') . '.pdf', 'Daftar Resep Produk (' . ucfirst($sourceType) . ')', $columns, $rows);
    }
}

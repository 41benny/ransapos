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

    private function componentProductsForSourceType(string $sourceType)
    {
        $rawCategory = $this->rawMaterialCategory();

        return Product::query()
            ->where('is_active', true)
            ->where(function ($query) use ($rawCategory, $sourceType) {
                if ($rawCategory) {
                    $query->where('category_id', $rawCategory->id);
                } else {
                    $query->where('product_type', 'raw_material');
                }

                if ($sourceType === self::SOURCE_TYPE_BUNDLE) {
                    $query->orWhereHas('productionBom', function ($bomQuery) {
                        $bomQuery->where('is_active', true);
                    });
                }
            })
            ->orderBy('name')
            ->get();
    }

    private function finishedProductsForSourceType()
    {
        $rawCategory = $this->rawMaterialCategory();

        if ($rawCategory) {
            return Product::where('category_id', '!=', $rawCategory->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get();
        }

        return Product::where('product_type', 'finished_good')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    public function index()
    {
        $sourceType = $this->normalizeSourceType(request()->query('source_type'), self::SOURCE_TYPE_PRODUCTION);
        $search = trim((string) request()->query('search'));

        $bomsQuery = BomHeader::with(['product', 'details.component'])->withCount('details');
        if ($sourceType !== self::SOURCE_TYPE_ALL) {
            $bomsQuery->where('source_type', $sourceType);
        }

        if ($search !== '') {
            $bomsQuery->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhereHas('product', function ($pq) use ($search) {
                      $pq->where('name', 'like', "%{$search}%")
                         ->orWhere('sku', 'like', "%{$search}%");
                  });
            });
        }

        $boms = $bomsQuery
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();
        
        if (request()->expectsJson()) {
            return response()->json($boms);
        }
        
        return view('admin.boms.index', compact('boms', 'sourceType', 'search'));
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

        $rawMaterials = $this->componentProductsForSourceType($sourceType);
        $finishedProducts = $this->finishedProductsForSourceType();

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

        $rawMaterials = $this->componentProductsForSourceType($sourceType);
        $finishedProducts = $this->finishedProductsForSourceType();
            
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
    }

    public function exportExcel(Request $request)
    {
        $sourceType = $this->normalizeSourceType($request->query('source_type'), self::SOURCE_TYPE_PRODUCTION);
        
        $boms = BomHeader::with(['product', 'details.component'])
            ->when($sourceType !== self::SOURCE_TYPE_ALL, function($q) use ($sourceType) {
                return $q->where('source_type', $sourceType);
            })
            ->orderBy('id')
            ->get();

        $columns = [
            ['key' => 'no', 'label' => 'No'],
            ['key' => 'product_name', 'label' => 'Produk Utama'],
            ['key' => 'sku', 'label' => 'SKU'],
            ['key' => 'component_name', 'label' => 'Nama Bahan'],
            ['key' => 'component_sku', 'label' => 'SKU Bahan'],
            ['key' => 'quantity', 'label' => 'Jumlah', 'type' => 'number', 'decimals' => 4],
            ['key' => 'uom', 'label' => 'Satuan'],
            ['key' => 'status', 'label' => 'Status'],
        ];

        $rows = collect();
        $no = 0;
        foreach ($boms as $bom) {
            $no++;
            $isFirst = true;
            foreach ($bom->details as $detail) {
                $rows->push([
                    'no' => $isFirst ? $no : '',
                    'product_name' => $isFirst ? ($bom->product->name ?? '-') : '',
                    'sku' => $isFirst ? ($bom->product->sku ?? '-') : '',
                    'component_name' => $detail->component->name ?? '-',
                    'component_sku' => $detail->component->sku ?? '-',
                    'quantity' => (float) $detail->quantity,
                    'uom' => $detail->uom ?? '-',
                    'status' => $isFirst ? ($bom->is_active ? 'Aktif' : 'Non-Aktif') : '',
                ]);
                $isFirst = false;
            }
            if ($bom->details->isEmpty()) {
                $rows->push([
                    'no' => $no,
                    'product_name' => $bom->product->name ?? '-',
                    'sku' => $bom->product->sku ?? '-',
                    'component_name' => '(tidak ada komponen)',
                    'component_sku' => '-',
                    'quantity' => 0,
                    'uom' => '-',
                    'status' => $bom->is_active ? 'Aktif' : 'Non-Aktif',
                ]);
            }
        }

        ReportExport::xlsx('boms_' . $sourceType . '_' . now()->format('Ymd_His') . '.xlsx', 'Resep ' . ucfirst($sourceType), $columns, $rows);
    }

    public function exportPdf(Request $request)
    {
        $sourceType = $this->normalizeSourceType($request->query('source_type'), self::SOURCE_TYPE_PRODUCTION);
        
        $boms = BomHeader::with(['product', 'details.component'])
            ->when($sourceType !== self::SOURCE_TYPE_ALL, function($q) use ($sourceType) {
                return $q->where('source_type', $sourceType);
            })
            ->orderBy('id')
            ->get();

        $sourceLabels = ['production' => 'Produksi', 'bundle' => 'Bundle', 'all' => 'Semua'];
        $title = 'Daftar Resep Produk — ' . ($sourceLabels[$sourceType] ?? ucfirst($sourceType));
        $generatedAt = now()->format('d M Y H:i');

        $html = '<!doctype html><html><head><meta charset="utf-8"><style>';
        $html .= 'body{font-family:DejaVu Sans,sans-serif;font-size:11px;color:#111827;margin:20px;}';
        $html .= 'h1{font-size:16px;margin:0 0 2px;}';
        $html .= '.meta{font-size:9px;color:#6b7280;margin-bottom:14px;}';
        $html .= '.bom-card{border:1px solid #d1d5db;margin-bottom:12px;page-break-inside:avoid;}';
        $html .= '.bom-header{background:#f3f4f6;padding:6px 10px;border-bottom:1px solid #d1d5db;}';
        $html .= '.bom-header .product{font-weight:bold;font-size:12px;}';
        $html .= '.bom-header .sku{font-size:9px;color:#6b7280;}';
        $html .= '.bom-header .badge{font-size:8px;padding:1px 6px;border-radius:3px;float:right;margin-top:2px;}';
        $html .= '.badge-aktif{background:#d1fae5;color:#065f46;}';
        $html .= '.badge-nonaktif{background:#fee2e2;color:#991b1b;}';
        $html .= 'table{width:100%;border-collapse:collapse;}';
        $html .= 'th{background:#f9fafb;text-align:left;padding:4px 8px;border-bottom:1px solid #e5e7eb;font-size:9px;text-transform:uppercase;color:#6b7280;}';
        $html .= 'td{padding:4px 8px;border-bottom:1px solid #f3f4f6;font-size:10px;}';
        $html .= 'td.qty{text-align:right;font-weight:bold;}';
        $html .= '.empty{color:#9ca3af;font-style:italic;padding:8px 10px;font-size:10px;}';
        $html .= '</style></head><body>';
        $html .= '<h1>' . e($title) . '</h1>';
        $html .= '<div class="meta">Dicetak: ' . $generatedAt . ' &bull; Total: ' . $boms->count() . ' resep</div>';

        $no = 0;
        foreach ($boms as $bom) {
            $no++;
            $badgeClass = $bom->is_active ? 'badge-aktif' : 'badge-nonaktif';
            $badgeText = $bom->is_active ? 'Aktif' : 'Non-Aktif';
            $productName = e($bom->product->name ?? '-');
            $productSku = e($bom->product->sku ?? '-');
            $bomName = $bom->name ? ' — ' . e($bom->name) : '';

            $html .= '<div class="bom-card">';
            $html .= '<div class="bom-header">';
            $html .= '<span class="badge ' . $badgeClass . '">' . $badgeText . '</span>';
            $html .= '<span class="product">' . $no . '. ' . $productName . $bomName . '</span><br>';
            $html .= '<span class="sku">SKU: ' . $productSku . '</span>';
            $html .= '</div>';

            if ($bom->details->isNotEmpty()) {
                $html .= '<table><thead><tr><th>Nama Bahan</th><th>SKU Bahan</th><th style="text-align:right">Jumlah</th><th>Satuan</th></tr></thead><tbody>';
                foreach ($bom->details as $detail) {
                    $html .= '<tr>';
                    $html .= '<td>' . e($detail->component->name ?? '-') . '</td>';
                    $html .= '<td>' . e($detail->component->sku ?? '-') . '</td>';
                    $html .= '<td class="qty">' . number_format((float) $detail->quantity, 4, ',', '.') . '</td>';
                    $html .= '<td>' . e($detail->uom ?? '-') . '</td>';
                    $html .= '</tr>';
                }
                $html .= '</tbody></table>';
            } else {
                $html .= '<div class="empty">Tidak ada komponen terdaftar.</div>';
            }

            $html .= '</div>';
        }

        if ($boms->isEmpty()) {
            $html .= '<div class="empty">Tidak ada resep produk.</div>';
        }

        $html .= '</body></html>';

        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $tempFile = tempnam(sys_get_temp_dir(), 'pdf_') . '.pdf';
        file_put_contents($tempFile, $dompdf->output());

        // Send using raw headers
        while (ob_get_level()) { ob_end_clean(); }
        $filename = 'boms_' . $sourceType . '_' . now()->format('Ymd_His') . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($tempFile));
        readfile($tempFile);
        @unlink($tempFile);
        exit;
    }
}

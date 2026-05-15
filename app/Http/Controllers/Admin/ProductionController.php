<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BomHeader;
use App\Models\Outlet;
use App\Models\Production;
use App\Services\ProductionService;
use Illuminate\Http\Request;

class ProductionController extends Controller
{
    public function __construct(private readonly ProductionService $productionService)
    {
    }

    public function index(Request $request)
    {
        $query = Production::query()
            ->with(['product', 'outlet', 'creator', 'materials']);

        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('start_date')) {
            $query->whereDate('production_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('production_date', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $search = trim((string) $request->search);
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('production_number', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($productQuery) use ($search) {
                        $productQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('sku', 'like', "%{$search}%");
                    });
            });
        }

        $productions = $query->orderByDesc('production_date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $outlets = Outlet::query()->where('is_active', true)->orderBy('name')->get();
        $products = BomHeader::query()
            ->where('source_type', 'production')
            ->where('is_active', true)
            ->with('product')
            ->get()
            ->pluck('product')
            ->filter()
            ->unique('id')
            ->sortBy('name')
            ->values();

        return view('admin.productions.index', compact('productions', 'outlets', 'products'));
    }

    public function create()
    {
        $outlets = Outlet::query()->where('is_active', true)->orderBy('name')->get();
        $boms = BomHeader::query()
            ->where('source_type', 'production')
            ->where('is_active', true)
            ->with(['product', 'details.component'])
            ->orderByDesc('id')
            ->get();

        return view('admin.productions.create', compact('outlets', 'boms'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bom_id' => 'required|exists:bom_headers,id',
            'outlet_id' => 'required|exists:outlets,id',
            'production_date' => 'required|date',
            'quantity' => 'required|numeric|min:0.0001',
            'notes' => 'nullable|string|max:1000',
        ]);

        try {
            $production = $this->productionService->createProduction($data);

            return redirect()
                ->route('admin.productions.show', $production)
                ->with('success', 'Produksi berhasil diproses. Stok bahan berkurang dan stok hasil produksi bertambah.');
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal memproses produksi: ' . $e->getMessage());
        }
    }

    public function show(Production $production)
    {
        $production->load(['bom.details.component', 'product', 'outlet', 'materials.product', 'creator']);

        return view('admin.productions.show', compact('production'));
    }
}

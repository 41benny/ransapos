<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Stock;
use App\Models\StockMutation;
use App\Models\Product;
use App\Models\Outlet;
use App\Services\StockService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockController extends Controller
{
    protected $stockService;

    public function __construct(StockService $stockService)
    {
        $this->stockService = $stockService;
    }

    /**
     * Display stock overview
     */
    public function index(Request $request)
    {
        $query = Stock::with(['product.category', 'outlet']);

        // Filter by outlet
        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        // Filter by product name
        if ($request->filled('search')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('sku', 'like', '%' . $request->search . '%');
            });
        }

        // Filter low stock (kurang dari min_stock)
        if ($request->filled('low_stock') && $request->low_stock == '1') {
            $query->whereHas('product', function ($q) {
                $q->whereColumn('stocks.quantity', '<', 'products.min_stock');
            });
        }

        // Order by
        $query->orderBy('updated_at', 'desc');

        $stocks = $query->paginate(20);

        // Load data for filters
        $outlets = Outlet::where('is_active', true)->get();
        $categories = \App\Models\ProductCategory::orderBy('name')->get();

        // Statistics
        $stats = [
            'total_products' => Stock::distinct('product_id')->count(),
            'total_value' => Stock::join('products', 'stocks.product_id', '=', 'products.id')
                ->sum(DB::raw('stocks.quantity * products.purchase_price')),
            'low_stock_count' => Stock::whereHas('product', function ($q) {
                $q->whereColumn('stocks.quantity', '<', 'products.min_stock');
            })->count(),
            'out_of_stock' => Stock::where('quantity', '<=', 0)->count(),
        ];

        return view('admin.stocks.index', compact('stocks', 'outlets', 'categories', 'stats'));
    }

    /**
     * Display stock mutations history
     */
    public function mutations(Request $request)
    {
        $tab = $request->get('tab', 'all');

        $query = StockMutation::with(['product', 'outlet', 'creator']);

        if ($tab === 'usage') {
            // For the new "Pemakaian Bahan Baku" tab
            $query->whereIn('reference_type', ['sale', 'sale_cancellation']);

            // Harus pilih bahan baku
            if ($request->filled('product_id')) {
                $query->where('product_id', $request->product_id);
            } else {
                // If no product is selected, we might want to show nothing or require it.
                // For now, let's just show an empty result set if no product is selected to force the user.
                $query->whereRaw('1 = 0'); 
            }
            
            // Filter by date range
            if ($request->filled('start_date')) {
                $query->where('mutation_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->where('mutation_date', '<=', $request->end_date);
            }

            // Filter by reference id (sale id)
            if ($request->filled('reference_id')) {
                $query->where('stock_mutations.reference_id', (int) $request->reference_id);
            }
            
            // It's safer to join sales to get invoice number and sort by it or just fetch them later since sale_id = reference_id.
            $query->leftJoin('sales', 'stock_mutations.reference_id', '=', 'sales.id')
                  ->select('stock_mutations.*', 'sales.invoice_number');

            $this->applyUsageMutationTableFilters($query, $request);
                  
        } else {
            // Tab "all" (Default History)
            
            // Filter by outlet
            if ($request->filled('outlet_id')) {
                $query->where('stock_mutations.outlet_id', $request->outlet_id);
            }
    
            // Filter by product
            if ($request->filled('product_id')) {
                $query->where('stock_mutations.product_id', $request->product_id);
            }
    
            // Filter by mutation type
            if ($request->filled('mutation_type')) {
                $query->where('mutation_type', $request->mutation_type);
            }
    
            // Filter by reference type
            if ($request->filled('reference_type')) {
                $query->where('reference_type', $request->reference_type);
            }

            // Filter by reference id (sale id, purchase id, etc.)
            if ($request->filled('reference_id')) {
                $query->where('stock_mutations.reference_id', (int) $request->reference_id);
            }
    
            // Scope audit khusus COGS penjualan (termasuk reversal pembatalan)
            if ($request->input('reference_scope') === 'sales_cogs') {
                $query->whereIn('reference_type', ['sale', 'sale_cancellation']);
            }
    
            // Filter by date range
            if ($request->filled('start_date')) {
                $query->where('mutation_date', '>=', $request->start_date);
            }
            if ($request->filled('end_date')) {
                $query->where('mutation_date', '<=', $request->end_date);
            }
    
            // Search by product name
            if ($request->filled('search')) {
                $query->whereHas('product', function ($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->search . '%')
                        ->orWhere('sku', 'like', '%' . $request->search . '%');
                });
            }

            $this->applyAllMutationTableFilters($query, $request);
        }

        $mutations = $query->orderBy('mutation_date', 'desc')
            ->orderBy('stock_mutations.created_at', 'desc')
            ->orderBy('stock_mutations.id', 'desc')
            ->paginate(50)
            ->withQueryString();

        // Load data for filters
        $outlets = Outlet::where('is_active', true)->get();
        // For usage tab, we primarily want raw_materials
        $products = Product::orderBy('name')->get();

        return view('admin.stocks.mutations', compact('mutations', 'outlets', 'products', 'tab'));
    }

    private function applyUsageMutationTableFilters($query, Request $request): void
    {
        if ($request->filled('filter_invoice')) {
            $query->where('sales.invoice_number', 'like', $this->likeValue($request->input('filter_invoice')));
        }

        if ($request->filled('filter_tanggal')) {
            $like = $this->likeValue($request->input('filter_tanggal'));
            $query->where(function ($subQuery) use ($like) {
                $subQuery->whereRaw("CAST(stock_mutations.mutation_date AS CHAR) LIKE ?", [$like])
                    ->orWhereRaw("DATE_FORMAT(stock_mutations.mutation_date, '%d %b %Y') LIKE ?", [$like])
                    ->orWhereRaw("DATE_FORMAT(stock_mutations.created_at, '%H:%i') LIKE ?", [$like]);
            });
        }

        if ($request->filled('filter_outlet')) {
            $query->whereHas('outlet', function ($subQuery) use ($request) {
                $subQuery->where('name', 'like', $this->likeValue($request->input('filter_outlet')));
            });
        }

        if ($request->filled('filter_menu')) {
            $query->where('stock_mutations.notes', 'like', $this->likeValue($request->input('filter_menu')));
        }

        if ($request->filled('filter_qty')) {
            $query->whereRaw("CAST(ABS(stock_mutations.quantity) AS CHAR) LIKE ?", [$this->likeValue($request->input('filter_qty'))]);
        }

        if ($request->filled('filter_hpp_unit')) {
            $query->whereRaw("CAST(COALESCE(stock_mutations.unit_cost, 0) AS CHAR) LIKE ?", [$this->likeValue($request->input('filter_hpp_unit'))]);
        }

        if ($request->filled('filter_hpp_nominal')) {
            $query->whereRaw("CAST(ABS(COALESCE(stock_mutations.total_cost, 0)) AS CHAR) LIKE ?", [$this->likeValue($request->input('filter_hpp_nominal'))]);
        }

        if ($request->filled('filter_kasir')) {
            $query->whereHas('creator', function ($subQuery) use ($request) {
                $subQuery->where('name', 'like', $this->likeValue($request->input('filter_kasir')));
            });
        }
    }

    private function applyAllMutationTableFilters($query, Request $request): void
    {
        if ($request->filled('filter_tanggal')) {
            $like = $this->likeValue($request->input('filter_tanggal'));
            $query->where(function ($subQuery) use ($like) {
                $subQuery->whereRaw("CAST(stock_mutations.mutation_date AS CHAR) LIKE ?", [$like])
                    ->orWhereRaw("DATE_FORMAT(stock_mutations.mutation_date, '%d %b %Y') LIKE ?", [$like])
                    ->orWhereRaw("DATE_FORMAT(stock_mutations.created_at, '%H:%i') LIKE ?", [$like]);
            });
        }

        if ($request->filled('filter_produk')) {
            $query->whereHas('product', function ($subQuery) use ($request) {
                $subQuery->where('name', 'like', $this->likeValue($request->input('filter_produk')))
                    ->orWhere('sku', 'like', $this->likeValue($request->input('filter_produk')));
            });
        }

        if ($request->filled('filter_outlet')) {
            $query->whereHas('outlet', function ($subQuery) use ($request) {
                $subQuery->where('name', 'like', $this->likeValue($request->input('filter_outlet')));
            });
        }

        if ($request->filled('filter_tipe')) {
            $query->where('stock_mutations.mutation_type', 'like', $this->likeValue($request->input('filter_tipe')));
        }

        if ($request->filled('filter_qty')) {
            $query->whereRaw("CAST(stock_mutations.quantity AS CHAR) LIKE ?", [$this->likeValue($request->input('filter_qty'))]);
        }

        if ($request->filled('filter_stok_akhir')) {
            $query->whereRaw("CAST(stock_mutations.stock_after AS CHAR) LIKE ?", [$this->likeValue($request->input('filter_stok_akhir'))]);
        }

        if ($request->filled('filter_hpp_unit')) {
            $query->whereRaw("CAST(COALESCE(stock_mutations.unit_cost, 0) AS CHAR) LIKE ?", [$this->likeValue($request->input('filter_hpp_unit'))]);
        }

        if ($request->filled('filter_hpp_nominal')) {
            $query->whereRaw("CAST(ABS(COALESCE(stock_mutations.total_cost, 0)) AS CHAR) LIKE ?", [$this->likeValue($request->input('filter_hpp_nominal'))]);
        }

        if ($request->filled('filter_referensi')) {
            $like = $this->likeValue($request->input('filter_referensi'));
            $query->where(function ($subQuery) use ($request, $like) {
                $subQuery->where('stock_mutations.reference_type', 'like', $like)
                    ->orWhereRaw("CAST(stock_mutations.reference_id AS CHAR) LIKE ?", [$like])
                    ->orWhere('stock_mutations.notes', 'like', $like)
                    ->orWhereHas('creator', function ($creatorQuery) use ($request) {
                        $creatorQuery->where('name', 'like', $this->likeValue($request->input('filter_referensi')));
                    });
            });
        }
    }

    private function likeValue(?string $value): string
    {
        return '%' . trim((string) $value) . '%';
    }

    /**
     * Show stock adjustment form
     */
    public function adjustment()
    {
        $outlets = Outlet::where('is_active', true)->get();
        $products = Product::query()
            ->manualStockSelectable()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'purchase_price']);

        $productsPayload = $products->map(function (Product $product) {
            $sku = !empty($product->sku) ? $product->sku : 'No SKU';
            $searchSku = !empty($product->sku) ? $product->sku : '';
            $unit = !empty($product->unit) ? $product->unit : 'pcs';

            return [
                'id' => (string) $product->id,
                'label' => $product->name . ' - ' . $sku,
                'search' => strtolower($product->name . ' ' . $searchSku),
                'unit' => $unit,
            ];
        })->values();

        return view('admin.stocks.adjustment', compact('outlets', 'productsPayload'));
    }

    /**
     * Get current stock for product at outlet (AJAX)
     */
    public function getCurrentStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'outlet_id' => 'required|exists:outlets,id',
        ]);

        $product = Product::query()
            ->manualStockSelectable()
            ->find($request->product_id);

        if (!$product) {
            throw ValidationException::withMessages([
                'product_id' => 'Produk yang dipilih harus berupa bahan baku atau Air Mineral SKU 122.',
            ]);
        }

        $stock = Stock::where('product_id', $request->product_id)
            ->where('outlet_id', $request->outlet_id)
            ->first();

        return response()->json([
            'success' => true,
            'current_stock' => $stock ? $stock->quantity : 0,
            'product_name' => $product->name,
            'unit' => $product->unit ?? 'pcs',
        ]);
    }

    /**
     * Store stock adjustment
     */
    public function storeAdjustment(Request $request)
    {
        // Bulk mode (preferred): items[][product_id,new_quantity]
        if ($request->filled('items')) {
            $data = $request->validate([
                'outlet_id' => 'required|exists:outlets,id',
                'notes' => 'required|string|max:500',
                'items' => 'required|array|min:1',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.new_quantity' => 'required|numeric|min:0',
            ]);

            $this->ensureManualStockSelectableProductIds(collect($data['items'])->pluck('product_id')->all());

            try {
                DB::beginTransaction();

                foreach ($data['items'] as $item) {
                    $this->stockService->adjustStock(
                        (int) $item['product_id'],
                        (int) $data['outlet_id'],
                        (float) $item['new_quantity'],
                        (string) $data['notes'],
                        auth()->id()
                    );
                }

                DB::commit();

                return redirect()->route('admin.stocks.index')
                    ->with('success', 'Stock adjustment berhasil dilakukan.');
            } catch (\Exception $e) {
                DB::rollBack();
                return back()->withInput()
                    ->with('error', 'Gagal melakukan adjustment: ' . $e->getMessage());
            }
        }

        // Legacy single-item mode
        $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'product_id' => 'required|exists:products,id',
            'new_quantity' => 'required|numeric|min:0',
            'notes' => 'required|string|max:500',
        ]);

        $this->ensureManualStockSelectableProductIds([$request->product_id]);

        try {
            $this->stockService->adjustStock(
                $request->product_id,
                $request->outlet_id,
                $request->new_quantity,
                $request->notes,
                auth()->id()
            );

            return redirect()->route('admin.stocks.index')
                ->with('success', 'Stock adjustment berhasil dilakukan.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal melakukan adjustment: ' . $e->getMessage());
        }
    }

    /**
     * Show stock card for specific product at outlet
     */
    public function stockCard(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'outlet_id' => 'required|exists:outlets,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
        ]);

        $product = Product::with('category')->findOrFail($request->product_id);
        $outlet = Outlet::findOrFail($request->outlet_id);

        $query = StockMutation::query()
            ->where('product_id', $request->product_id)
            ->where('outlet_id', $request->outlet_id);

        if ($request->filled('start_date')) {
            $query->whereDate('mutation_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->whereDate('mutation_date', '<=', $request->end_date);
        }

        $summary = (clone $query)
            ->selectRaw('COUNT(*) as total_rows')
            ->selectRaw('COALESCE(SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END), 0) as total_in')
            ->selectRaw('COALESCE(SUM(CASE WHEN quantity < 0 THEN ABS(quantity) ELSE 0 END), 0) as total_out')
            ->first();

        $latestMutation = (clone $query)
            ->orderBy('mutation_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->orderBy('id', 'desc')
            ->first();

        $mutations = (clone $query)
            ->orderBy('mutation_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->orderBy('id', 'asc')
            ->paginate(100)
            ->withQueryString();

        $currentStock = Stock::where('product_id', $request->product_id)
            ->where('outlet_id', $request->outlet_id)
            ->first();

        $mutations->setCollection(
            $mutations->getCollection()->map(function (StockMutation $mutation) {
                $stockAfter = round((float) ($mutation->stock_after ?? 0), 2);
                $stockBefore = $mutation->stock_before !== null
                    ? round((float) $mutation->stock_before, 2)
                    : round($stockAfter - (float) $mutation->quantity, 2);

                $mutation->display_stock_before = $stockBefore;
                $mutation->display_stock_after = $stockAfter;

                return $mutation;
            })
        );

        $latestUnitCost = (float) ($latestMutation->unit_cost ?? $product->purchase_price ?? 0);
        $estimatedInventoryValue = round((float) ($currentStock->quantity ?? 0) * $latestUnitCost, 2);
        $totalIn = (float) ($summary->total_in ?? 0);
        $totalOut = (float) ($summary->total_out ?? 0);
        $netChange = $totalIn - $totalOut;

        return view('admin.stocks.stock-card', compact(
            'product',
            'outlet',
            'mutations',
            'currentStock',
            'latestUnitCost',
            'estimatedInventoryValue',
            'totalIn',
            'totalOut',
            'netChange'
        ));
    }

    /**
     * Export stock report to Excel/CSV
     */
    public function export(Request $request)
    {
        // Will implement later with Laravel Excel or manual CSV
        return back()->with('info', 'Export feature coming soon!');
    }

    /**
     * @param array<int, mixed> $productIds
     */
    private function ensureManualStockSelectableProductIds(array $productIds): void
    {
        $ids = collect($productIds)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return;
        }

        $matchedCount = Product::query()
            ->manualStockSelectable()
            ->whereIn('id', $ids)
            ->count();

        if ($matchedCount !== $ids->count()) {
            throw ValidationException::withMessages([
                'items' => 'Produk yang dipilih harus bahan baku atau Air Mineral SKU 122.',
            ]);
        }
    }
}

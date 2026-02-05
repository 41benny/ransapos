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
        $query = StockMutation::with(['product', 'outlet', 'creator']);

        // Filter by outlet
        if ($request->filled('outlet_id')) {
            $query->where('outlet_id', $request->outlet_id);
        }

        // Filter by product
        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        // Filter by mutation type
        if ($request->filled('mutation_type')) {
            $query->where('mutation_type', $request->mutation_type);
        }

        // Filter by reference type
        if ($request->filled('reference_type')) {
            $query->where('reference_type', $request->reference_type);
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
                $q->where('name', 'like', '%' . $request->search . '%');
            });
        }

        $mutations = $query->orderBy('mutation_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(50);

        // Load data for filters
        $outlets = Outlet::where('is_active', true)->get();
        $products = Product::orderBy('name')->get();

        return view('admin.stocks.mutations', compact('mutations', 'outlets', 'products'));
    }

    /**
     * Show stock adjustment form
     */
    public function adjustment()
    {
        $outlets = Outlet::where('is_active', true)->get();
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'purchase_price']);

        return view('admin.stocks.adjustment', compact('outlets', 'products'));
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

        $stock = Stock::where('product_id', $request->product_id)
            ->where('outlet_id', $request->outlet_id)
            ->first();

        $product = Product::find($request->product_id);

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

        $product = Product::findOrFail($request->product_id);
        $outlet = Outlet::findOrFail($request->outlet_id);

        $query = StockMutation::where('product_id', $request->product_id)
            ->where('outlet_id', $request->outlet_id);

        if ($request->filled('start_date')) {
            $query->where('mutation_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('mutation_date', '<=', $request->end_date);
        }

        $mutations = $query->orderBy('mutation_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        $currentStock = Stock::where('product_id', $request->product_id)
            ->where('outlet_id', $request->outlet_id)
            ->first();

        return view('admin.stocks.stock-card', compact('product', 'outlet', 'mutations', 'currentStock'));
    }

    /**
     * Export stock report to Excel/CSV
     */
    public function export(Request $request)
    {
        // Will implement later with Laravel Excel or manual CSV
        return back()->with('info', 'Export feature coming soon!');
    }
}

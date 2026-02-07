<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PaymentMethod;
use App\Models\CashSession;
use App\Models\Outlet;
use App\Services\SaleService;
use App\Models\Sale;
use Illuminate\Http\Request;
use Exception;

class SaleController extends Controller
{
    protected SaleService $saleService;

    public function __construct(SaleService $saleService)
    {
        $this->saleService = $saleService;
    }

    /**
     * Tampilkan halaman transaksi penjualan (kasir)
     */
    public function create()
    {
        $priceLevels = config('sales.price_levels', ['regular' => 'Reguler']);
        $currentOutletId = auth()->user()->outlet_id;
        $currentUserId = auth()->id();

        $categories = ProductCategory::where('is_active', true)
            ->with(['products' => function($query) {
                $query->where('is_active', true)
                    ->where('is_sellable', true)
                    ->where('is_pos_available', true)
                    ->whereIn('product_type', ['finished_good', 'service']);
            }])
            ->orderBy('name')
            ->get()
            ->map(function (ProductCategory $category) use ($currentOutletId, $currentUserId, $priceLevels) {
                $products = $category->products
                    ->filter(fn (Product $product) => $product->isAvailableForOutlet($currentOutletId) && $product->isAvailableForUser($currentUserId))
                    ->map(function (Product $product) use ($priceLevels) {
                        $rawPriceLevels = $product->price_levels ?? [];
                        $normalizedPriceLevels = [];

                        foreach (array_keys($priceLevels) as $levelKey) {
                            if (!array_key_exists($levelKey, $rawPriceLevels) || $rawPriceLevels[$levelKey] === null || $rawPriceLevels[$levelKey] === '') {
                                continue;
                            }

                            $normalizedPriceLevels[$levelKey] = (float) $rawPriceLevels[$levelKey];
                        }

                        $normalizedPriceLevels['regular'] = (float) ($normalizedPriceLevels['regular'] ?? $product->selling_price);
                        $product->setAttribute('price_levels', $normalizedPriceLevels);
                        $product->setAttribute('selling_price', $normalizedPriceLevels['regular']);

                        return $product;
                    })
                    ->values();

                $category->setRelation('products', $products);

                return $category;
            })
            ->filter(fn (ProductCategory $category) => $category->products->isNotEmpty())
            ->values();
        $paymentMethods = PaymentMethod::where('is_active', true)->get();

        // Ambil beberapa customer aktif untuk pilihan di POS (loyalty)
        $customers = Customer::active()
            ->orderBy('name')
            ->limit(100)
            ->get()
            ->map(function (Customer $c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'customer_code' => $c->customer_code,
                    'phone' => $c->phone,
                    'loyalty_points' => $c->loyalty_points,
                    'member_tier' => $c->member_tier,
                ];
            });
        
        // Ambil cash session aktif untuk user yang login
        $activeSession = CashSession::where('status', 'open')
            ->where('user_id', auth()->id())
            ->where('outlet_id', auth()->user()->outlet_id)
            ->orderBy('opened_at', 'desc')
            ->first();

        $outlet = Outlet::find(auth()->user()->outlet_id);

        return view('pos.sales.create', compact('categories', 'paymentMethods', 'activeSession', 'customers', 'outlet', 'priceLevels'));
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Simpan transaksi penjualan baru
     */
    public function store(StoreSaleRequest $request)
    {
        try {
            $sale = $this->saleService->createSale($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil disimpan',
                'data' => [
                    'sale_id' => $sale->id,
                    'invoice_number' => $sale->invoice_number,
                    'total_amount' => $sale->total_amount,
                ],
            ], 201);

        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan transaksi',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    /**
     * Cetak struk belanja
     */
    public function print(Sale $sale)
    {
        // Pastikan user punya akses ke outlet ini (kecuali super admin)
        if (!auth()->user()->hasRole('admin') && $sale->outlet_id !== auth()->user()->outlet_id) {
            abort(403, 'Unauthorized action.');
        }

        $sale->load(['items', 'payments.paymentMethod', 'outlet', 'user', 'customer']);

        return view('pos.sales.print', compact('sale'));
    }
}

<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreSaleRequest;
use App\Models\Customer;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\PaymentMethod;
use App\Models\Promotion;
use App\Models\CashSession;
use App\Models\Outlet;
use App\Models\Voucher;
use App\Models\SalesType;
use App\Services\SaleService;
use App\Models\Sale;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

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
        $priceLevels = SalesType::priceLevels();
        $currentOutletId = (int) (auth()->user()->outlet_id ?? 0);
        $currentUserId = (int) (auth()->id() ?? 0);

        $posData = ProductCategory::where('is_active', true)
            ->select(['id', 'name'])
            ->with(['products' => function ($query) use ($currentOutletId, $currentUserId) {
                $query->select([
                    'id',
                    'sku',
                    'name',
                    'category_id',
                    'description',
                    'image_path',
                    'thumbnail_path',
                    'selling_price',
                    'price_levels',
                ]);
                $query->where('is_active', true)
                    ->where('is_sellable', true)
                    ->where('is_pos_available', true)
                    ->whereIn('product_type', ['finished_good', 'service'])
                    ->where(function ($outletQuery) use ($currentOutletId) {
                        $outletQuery->where('is_available_all_outlets', true);

                        if ($currentOutletId > 0) {
                            // Handle both JSON number ([1,2]) and legacy JSON string (["1","2"]).
                            $outletQuery->orWhereJsonContains('pos_outlet_ids', $currentOutletId)
                                ->orWhereJsonContains('pos_outlet_ids', (string) $currentOutletId);
                        }
                    })
                    ->where(function ($userQuery) use ($currentUserId) {
                        $userQuery->where('is_available_all_users', true);

                        if ($currentUserId > 0) {
                            // Handle both JSON number ([1,2]) and legacy JSON string (["1","2"]).
                            $userQuery->orWhereJsonContains('pos_user_ids', $currentUserId)
                                ->orWhereJsonContains('pos_user_ids', (string) $currentUserId);
                        }
                    });
            }])
            ->orderBy('name')
            ->get()
            ->map(function (ProductCategory $category) use ($currentOutletId, $currentUserId, $priceLevels) {
                $products = $category->products
                    ->map(function (Product $product) use ($priceLevels, $currentOutletId) {
                        $normalizedPriceLevels = [];

                        foreach (array_keys($priceLevels) as $levelKey) {
                            // Use getPriceByLevelAndOutlet to get outlet-specific price
                            $price = $product->getPriceByLevelAndOutlet($levelKey, $currentOutletId);

                            // Only include if price is valid
                            if ($price > 0) {
                                $normalizedPriceLevels[$levelKey] = $price;
                            }
                        }

                        // Ensure 'regular' always exists
                        if (!isset($normalizedPriceLevels['regular'])) {
                            $normalizedPriceLevels['regular'] = (float) $product->selling_price;
                        }

                        return [
                            'id' => $product->id,
                            'sku' => $product->sku,
                            'name' => $product->name,
                            'category_id' => $product->category_id,
                            'description' => $product->description,
                            'selling_price' => $normalizedPriceLevels['regular'],
                            'price_levels' => $normalizedPriceLevels,
                            'image_url' => $product->thumbnail_url ?? $product->image_url,
                        ];
                    })
                    ->values();

                return [
                    'id' => $category->id,
                    'name' => $category->name,
                    'products' => $products,
                ];
            })
            ->filter(fn(array $category) => $category['products']->isNotEmpty())
            ->values();
        $categories = $posData->map(fn(array $category) => [
            'id' => $category['id'],
            'name' => $category['name'],
        ])->values();
        $products = $posData->flatMap(fn(array $category) => $category['products'])->values();
        $paymentMethods = PaymentMethod::where('is_active', true)
            ->select(['id', 'name'])
            ->get();

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

        $outlet = Outlet::select(['id', 'tax_rate', 'service_charge_rate'])
            ->find(auth()->user()->outlet_id);

        $activePromotions = Promotion::query()
            ->active()
            ->activeOn(now())
            ->forOutlet((int) auth()->user()->outlet_id)
            ->with(['categoryRules' => function ($query) {
                $query->select(['id', 'promotion_id', 'product_category_id', 'discount_percent']);
            }])
            ->orderBy('name')
            ->get()
            ->map(function (Promotion $promotion) {
                return [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'code' => $promotion->code,
                    'rules' => $promotion->categoryRules->map(function ($rule) {
                        return [
                            'category_id' => (int) $rule->product_category_id,
                            'discount_percent' => (float) $rule->discount_percent,
                        ];
                    })->values(),
                ];
            })
            ->values();

        $activeVouchers = Voucher::query()
            ->active()
            ->activeOn(now())
            ->forOutlet((int) auth()->user()->outlet_id)
            ->where(function ($query) {
                $query->whereNull('usage_limit')
                    ->orWhereColumn('used_count', '<', 'usage_limit');
            })
            ->orderBy('name')
            ->get()
            ->map(function (Voucher $voucher) {
                return [
                    'id' => $voucher->id,
                    'name' => $voucher->name,
                    'code' => $voucher->code,
                    'discount_type' => $voucher->discount_type,
                    'discount_value' => (float) $voucher->discount_value,
                    'min_purchase' => (float) $voucher->min_purchase,
                    'max_discount_amount' => $voucher->max_discount_amount !== null
                        ? (float) $voucher->max_discount_amount
                        : null,
                ];
            })
            ->values();

        return view('pos.sales.create', compact(
            'categories',
            'products',
            'paymentMethods',
            'activeSession',
            'customers',
            'outlet',
            'priceLevels',
            'activePromotions',
            'activeVouchers'
        ));
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
        $user = $request->user();
        if (!$user || !$user->outlet_id) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda belum terhubung ke outlet aktif. Hubungi admin.',
            ], 422);
        }

        try {
            $payload = $request->validated();

            // Enforce outlet & user from authenticated session, not from client payload.
            $payload['outlet_id'] = (int) $user->outlet_id;
            $payload['user_id'] = (int) $user->id;

            $activeSession = CashSession::query()
                ->whereKey($payload['cash_session_id'])
                ->where('status', 'open')
                ->where('user_id', $user->id)
                ->where('outlet_id', $user->outlet_id)
                ->first();

            if (!$activeSession) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sesi kasir tidak aktif atau tidak sesuai dengan akun/outlet Anda.',
                ], 422);
            }

            $sale = $this->saleService->createSale($payload);

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
        // Pastikan user punya akses ke outlet ini (kecuali admin/superadmin).
        if (!auth()->user()->hasRole(['admin', 'superadmin']) && $sale->outlet_id !== auth()->user()->outlet_id) {
            abort(403, 'Unauthorized action.');
        }

        $sale->load(['items', 'payments.paymentMethod', 'outlet', 'user', 'customer']);

        return view('pos.sales.print', compact('sale'));
    }

    /**
     * Get transaction history for current session
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        $outletId = $user->outlet_id;

        // Ambil cash session aktif
        $activeSession = CashSession::where('status', 'open')
            ->where('user_id', $user->id)
            ->where('outlet_id', $outletId)
            ->first();

        if (!$activeSession) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Tidak ada sesi kasir yang aktif',
                    'data' => []
                ]);
            }
            // Return view with empty sales if no session
            return view('pos.sales.history', ['sales' => []]);
        }

        // Ambil penjualan dalam sesi ini
        $sales = Sale::where('cash_session_id', $activeSession->id)
            ->with(['items', 'customer', 'payments.paymentMethod'])
            ->orderBy('created_at', 'desc')
            ->limit(50) // Batasi 50 transaksi terakhir
            ->get();

        if ($request->wantsJson()) {
            return response()->json([
                'success' => true,
                'data' => $sales
            ]);
        }

        return view('pos.sales.history', compact('sales'));
    }

    /**
     * Void / Batalkan Transaksi
     */
    public function void(Request $request, Sale $sale)
    {
        $request->validate([
            'reason' => 'required|string|min:3',
            'token' => 'required|string|size:6',
        ]);

        $user = auth()->user();

        // 1. Validasi Token Void
        $voidToken = \App\Models\VoidToken::where('token', $request->token)
            ->where('is_used', false)
            ->where('outlet_id', $sale->outlet_id) // Validasi Outlet
            ->first();

        if (!$voidToken) {
            return response()->json([
                'success' => false,
                'message' => 'Token Void tidak valid, sudah terpakai, atau tidak berlaku untuk outlet ini.'
            ], 403);
        }

        // 2. Proses Void
        DB::beginTransaction();
        try {
            // Pastikan sale milik outlet user ini
            if ($sale->outlet_id !== $user->outlet_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'Transaksi tidak ditemukan di outlet ini'
                ], 404);
            }

            // Panggil service
            $cancelledSale = $this->saleService->cancelSale($sale->id, $request->reason);

            // Tandai token sudah terpakai
            $voidToken->update([
                'is_used' => true,
                'used_by' => $user->id,
                'used_for_sale_id' => $sale->id,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Transaksi berhasil dibatalkan (Void)',
                'data' => $cancelledSale
            ]);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal membatalkan transaksi: ' . $e->getMessage()
            ], 500);
        }
    }
}

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
use App\Models\Payment;
use App\Models\SaleItem;
use App\Services\SaleService;
use App\Models\Sale;
use App\Support\SpecialPromotion;
use App\Support\Printing\ThermalReceipt;
use Illuminate\Http\Request;
use Exception;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

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
        $priceLevels = SpecialPromotion::filterRuntimeSalesTypes(SalesType::priceLevels());
        // Kode sales_type kanal online yang tersedia di runtime (untuk input harga manual).
        $onlineSalesTypes = array_values(array_intersect(SalesType::onlineCodes(), array_keys($priceLevels)));
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
            ->select(['id', 'code', 'name', 'is_online_only'])
            ->get();

        // Pemetaan tipe penjualan (online) -> metode bayar default yang otomatis dikunci di POS.
        // Hanya sertakan jika metode bayarnya aktif & tersedia di daftar POS.
        $activePaymentMethodIds = $paymentMethods->pluck('id')->map(fn ($id) => (int) $id)->all();
        $salesTypePaymentMap = SalesType::query()
            ->active()
            ->online()
            ->whereNotNull('default_payment_method_id')
            ->pluck('default_payment_method_id', 'code')
            ->filter(fn ($methodId) => in_array((int) $methodId, $activePaymentMethodIds, true))
            ->map(fn ($methodId) => (int) $methodId)
            ->all();

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
                $specialType = SpecialPromotion::classify($promotion->code, $promotion->name);

                return [
                    'id' => $promotion->id,
                    'name' => $promotion->name,
                    'code' => $promotion->code,
                    'special_type' => $specialType,
                    'requires_confirmation' => $specialType !== null,
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
            'onlineSalesTypes',
            'salesTypePaymentMap',
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
            $existingSale = $this->findSaleByIdempotencyKey($payload ?? [], $user);
            if ($existingSale && $e instanceof QueryException && $e->getCode() === '23000') {
                return response()->json([
                    'success' => true,
                    'message' => 'Transaksi sudah tersimpan',
                    'data' => [
                        'sale_id' => $existingSale->id,
                        'invoice_number' => $existingSale->invoice_number,
                        'total_amount' => $existingSale->total_amount,
                    ],
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan transaksi',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function authorizeManualDiscount(Request $request)
    {
        $validated = $request->validate([
            'pin' => ['required', 'digits:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'pin.required' => 'PIN otorisasi wajib diisi.',
            'pin.digits' => 'PIN otorisasi harus 6 digit.',
            'pin.regex' => 'PIN otorisasi harus angka 6 digit.',
        ]);

        $user = $request->user();
        if (!$user || !$user->outlet_id) {
            return response()->json([
                'success' => false,
                'message' => 'Akun Anda belum terhubung ke outlet aktif.',
            ], 422);
        }

        try {
            $authorizer = $this->saleService->authorizeManualDiscount(
                pin: (string) $validated['pin'],
                outletId: (int) $user->outlet_id,
                cashierUserId: (int) $user->id,
            );

            return response()->json([
                'success' => true,
                'message' => 'Otorisasi diskon valid.',
                'data' => [
                    'authorized_by_user_id' => $authorizer->id,
                    'authorized_by_name' => $authorizer->name,
                ],
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 403);
        }
    }

    private function findSaleByIdempotencyKey(array $payload, $user): ?Sale
    {
        $key = trim((string) ($payload['idempotency_key'] ?? ''));
        if ($key === '' || !$user) {
            return null;
        }

        return Sale::query()
            ->where('idempotency_key', $key)
            ->where('outlet_id', (int) $user->outlet_id)
            ->where('user_id', (int) $user->id)
            ->first();
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
    public function print(Sale $sale, $saleId = null)
    {
        $resolvedSale = $sale->getKey()
            ? $sale
            : Sale::query()->findOrFail((int) ($saleId ?? request()->route('sale')));

        // Pastikan user punya akses ke outlet ini (kecuali admin/superadmin).
        if (!auth()->user()->hasRole(['admin', 'superadmin']) && $resolvedSale->outlet_id !== auth()->user()->outlet_id) {
            abort(403, 'Unauthorized action.');
        }

        $resolvedSale->load(['items', 'payments.paymentMethod', 'outlet', 'user', 'customer']);

        return view('pos.sales.print', ['sale' => $resolvedSale]);
    }

    /**
     * Keluarkan struk dalam format ESC/POS (base64) untuk dicetak langsung
     * ke printer thermal Bluetooth, mis. via RawBT di Android.
     */
    public function escpos(Sale $sale)
    {
        if (!auth()->user()->hasRole(['admin', 'superadmin']) && $sale->outlet_id !== auth()->user()->outlet_id) {
            abort(403, 'Unauthorized action.');
        }

        return response()->json([
            'base64' => ThermalReceipt::buildBase64($sale),
        ]);
    }

    /**
     * Get transaction history for cashier sessions (open + closed).
     */
    public function history(Request $request)
    {
        $user = auth()->user();
        $outletId = $user->outlet_id;
        $viewMode = $request->input('view', 'invoice') === 'product' ? 'product' : 'invoice';
        $printMode = $request->boolean('print');
        $dateFromInput = (string) $request->input('date_from', '');
        $dateToInput = (string) $request->input('date_to', '');
        $dateFrom = null;
        $dateTo = null;

        $parseDateInput = function (string $value, bool $isEndOfDay = false): ?Carbon {
            $value = trim($value);
            if ($value === '') {
                return null;
            }

            $formats = ['Y-m-d', 'd/m/Y', 'd-m-Y', 'm/d/Y', 'm-d-Y'];
            foreach ($formats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $value);
                    return $isEndOfDay ? $date->endOfDay() : $date->startOfDay();
                } catch (Exception $e) {
                    // Try next format.
                }
            }

            try {
                $date = Carbon::parse($value);
                return $isEndOfDay ? $date->endOfDay() : $date->startOfDay();
            } catch (Exception $e) {
                return null;
            }
        };

        if ($dateFromInput !== '') {
            $dateFrom = $parseDateInput($dateFromInput, false);
        }

        if ($dateToInput !== '') {
            $dateTo = $parseDateInput($dateToInput, true);
        }

        if ($dateFrom && !$dateTo) {
            $dateTo = now()->endOfDay();
        }

        if (!$dateFrom && $dateTo) {
            $dateFrom = $dateTo->copy()->subMonthNoOverflow()->startOfDay();
        }

        if (!$dateFrom && !$dateTo) {
            $dateTo = now()->endOfDay();
            $dateFrom = now()->subMonthNoOverflow()->startOfDay();
        }

        if ($dateFrom->gt($dateTo)) {
            [$dateFrom, $dateTo] = [$dateTo->copy()->startOfDay(), $dateFrom->copy()->endOfDay()];
        }

        $maxRangeStart = $dateTo->copy()->subMonthNoOverflow()->startOfDay();
        if ($dateFrom->lt($maxRangeStart)) {
            $dateFrom = $maxRangeStart;
        }

        $baseQuery = Sale::query()
            ->where('outlet_id', $outletId)
            ->where('user_id', $user->id)
            ->whereNotNull('cash_session_id')
            ->whereHas('cashSession', function ($query) {
                $query->whereIn('status', ['open', 'closed']);
            });

        if ($dateFrom) {
            $baseQuery->whereDate('sale_date', '>=', $dateFrom->toDateString());
        }

        if ($dateTo) {
            $baseQuery->whereDate('sale_date', '<=', $dateTo->toDateString());
        }

        // Catatan: cetak rekap thermal (format=escpos) juga memakai Accept: application/json,
        // jadi jangan short-circuit ke daftar transaksi saat format=escpos diminta.
        if ($request->wantsJson() && $request->input('format') !== 'escpos') {
            $sales = (clone $baseQuery)
                ->with(['items', 'customer', 'payments.paymentMethod'])
                ->orderBy('created_at', 'desc')
                ->limit(50) // Batasi 50 transaksi terakhir
                ->get();

            return response()->json([
                'success' => true,
                'data' => $sales
            ]);
        }

        $salesQuery = (clone $baseQuery)
            ->with(['items', 'customer', 'payments.paymentMethod'])
            ->orderBy('created_at', 'desc');

        $sales = $printMode
            ? $salesQuery->get()
            : $salesQuery->paginate(20)->withQueryString();

        $completedQuery = (clone $baseQuery)->where('status', 'completed');
        $completedCount = (clone $completedQuery)->count();
        $completedAmount = (float) (clone $completedQuery)->sum('total_amount');
        $voidCount = (clone $baseQuery)->where('status', 'cancelled')->count();
        $productRowsQuery = SaleItem::query()
            ->selectRaw('COALESCE(sale_items.product_name, ?) as product_name', ['Produk Tanpa Nama'])
            ->selectRaw('COALESCE(sale_items.product_sku, ?) as product_sku', ['-'])
            ->selectRaw('SUM(sale_items.quantity) as total_qty')
            ->selectRaw('SUM(sale_items.subtotal) as total_amount')
            ->selectRaw('COUNT(DISTINCT sale_items.sale_id) as total_transactions')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.outlet_id', $outletId)
            ->where('sales.user_id', $user->id)
            ->where('sales.status', 'completed')
            ->whereNotNull('sales.cash_session_id')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('cash_sessions')
                    ->whereColumn('cash_sessions.id', 'sales.cash_session_id')
                    ->whereIn('cash_sessions.status', ['open', 'closed']);
            })
            ->whereDate('sales.sale_date', '>=', $dateFrom->toDateString())
            ->whereDate('sales.sale_date', '<=', $dateTo->toDateString())
            ->groupBy('sale_items.product_name', 'sale_items.product_sku')
            ->orderByDesc('total_qty');

        $productRows = $printMode
            ? $productRowsQuery->get()
            : $productRowsQuery->paginate(20, ['*'], 'products_page')->withQueryString();

        $paymentBreakdown = Payment::query()
            ->selectRaw('COALESCE(payment_methods.name, ?) as method_name, SUM(payments.amount) as total_amount, COUNT(payments.id) as payment_count', ['Tanpa Metode'])
            ->join('sales', 'sales.id', '=', 'payments.sale_id')
            ->leftJoin('payment_methods', 'payment_methods.id', '=', 'payments.payment_method_id')
            ->where('sales.outlet_id', $outletId)
            ->where('sales.user_id', $user->id)
            ->whereNotNull('sales.cash_session_id')
            ->whereExists(function ($query) {
                $query->selectRaw('1')
                    ->from('cash_sessions')
                    ->whereColumn('cash_sessions.id', 'sales.cash_session_id')
                    ->whereIn('cash_sessions.status', ['open', 'closed']);
            });

        if ($dateFrom) {
            $paymentBreakdown->whereDate('sales.sale_date', '>=', $dateFrom->toDateString());
        }

        if ($dateTo) {
            $paymentBreakdown->whereDate('sales.sale_date', '<=', $dateTo->toDateString());
        }

        $paymentBreakdown = $paymentBreakdown
            ->groupBy('payment_methods.name')
            ->orderByDesc('total_amount')
            ->get();

        $salesTypeLabels = SalesType::priceLevels();
        $salesTypeBreakdown = (clone $completedQuery)
            ->select(['sales_type', 'total_amount'])
            ->get()
            ->groupBy(function ($sale) {
                $rawType = strtolower(trim((string) ($sale->sales_type ?? '')));
                return $rawType !== '' ? $rawType : 'regular';
            })
            ->map(function ($rows, string $key) use ($salesTypeLabels) {
                $totalAmount = (float) $rows->sum(fn ($sale) => (float) $sale->total_amount);

                return (object) [
                    'sales_type_key' => $key,
                    'sales_type_name' => $salesTypeLabels[$key] ?? ucfirst(str_replace('_', ' ', $key)),
                    'transaction_count' => $rows->count(),
                    'total_amount' => $totalAmount,
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        $summary = [
            'transactions' => $completedCount,
            'void_transactions' => $voidCount,
            'gross_sales' => $completedAmount,
            'avg_ticket' => $completedCount > 0 ? $completedAmount / $completedCount : 0,
        ];

        if ($printMode) {
            $recapFilters = [
                'date_from' => $dateFrom ? $dateFrom->toDateString() : '',
                'date_to' => $dateTo ? $dateTo->toDateString() : '',
            ];

            // Cetak langsung ke printer thermal Bluetooth (Web Bluetooth/RawBT).
            if ($request->input('format') === 'escpos') {
                return response()->json([
                    'base64' => \App\Support\Printing\ThermalRecap::buildBase64([
                        'outlet_name' => $user->outlet->name ?? 'Outlet',
                        'outlet_address' => $user->outlet->address ?? null,
                        'outlet_phone' => $user->outlet->phone ?? null,
                        'cashier_name' => $user->name ?? 'Kasir',
                        'filters' => $recapFilters,
                        'summary' => $summary,
                        'payment_breakdown' => $paymentBreakdown,
                        'sales_type_breakdown' => $salesTypeBreakdown,
                        'product_rows' => $productRows,
                    ]),
                ]);
            }

            return view('pos.sales.history-thermal', [
                'sales' => $sales,
                'summary' => $summary,
                'paymentBreakdown' => $paymentBreakdown,
                'salesTypeBreakdown' => $salesTypeBreakdown,
                'productRows' => $productRows,
                'filters' => $recapFilters,
            ]);
        }

        return view('pos.sales.history', [
            'sales' => $sales,
            'summary' => $summary,
            'paymentBreakdown' => $paymentBreakdown,
            'salesTypeBreakdown' => $salesTypeBreakdown,
            'productRows' => $productRows,
            'viewMode' => $viewMode,
            'filters' => [
                'date_from' => $dateFrom ? $dateFrom->toDateString() : '',
                'date_to' => $dateTo ? $dateTo->toDateString() : '',
            ],
            'printMode' => $printMode,
        ]);
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

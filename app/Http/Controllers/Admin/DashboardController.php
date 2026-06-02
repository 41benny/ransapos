<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard admin
     */
    public function index()
    {
        $outlets = Outlet::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        $defaultDate = today()->toDateString();

        return view('admin.dashboard', compact('outlets', 'defaultDate'));
    }

    public function summary(Request $request)
    {
        $fallbackDate = (string) $request->input('date', today()->toDateString());
        $dateFromInput = (string) $request->input('date_from', $fallbackDate);
        $dateToInput = (string) $request->input('date_to', $dateFromInput);

        try {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFromInput)->toDateString();
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateToInput)->toDateString();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid date range format. Use YYYY-MM-DD.'], 422);
        }

        if ($dateFrom > $dateTo) {
            return response()->json(['message' => 'Tanggal awal tidak boleh lebih besar dari tanggal akhir.'], 422);
        }

        $periodStart = Carbon::createFromFormat('Y-m-d', $dateFrom);
        $periodEnd = Carbon::createFromFormat('Y-m-d', $dateTo);
        $periodDays = $periodStart->diffInDays($periodEnd) + 1;
        $prevDateTo = $periodStart->copy()->subDay()->toDateString();
        $prevDateFrom = $periodStart->copy()->subDays($periodDays)->toDateString();

        $selectedOutletIds = null;
        if ($request->has('outlet_ids')) {
            $rawOutletIds = $request->input('outlet_ids', []);
            if (!is_array($rawOutletIds)) {
                return response()->json(['message' => 'Invalid outlet_ids. Use array of positive integers.'], 422);
            }

            $normalizedOutletIds = collect($rawOutletIds)
                ->filter(fn($id) => $id !== null && $id !== '')
                ->map(function ($id) {
                    if (!is_numeric($id) || (int) $id <= 0) {
                        return null;
                    }

                    return (int) $id;
                });

            if ($normalizedOutletIds->contains(null)) {
                return response()->json(['message' => 'Invalid outlet_ids. Use array of positive integers.'], 422);
            }

            $selectedOutletIds = $normalizedOutletIds->unique()->sort()->values()->all();

            if (empty($selectedOutletIds)) {
                $selectedOutletIds = null;
            }
        } else {
            $outletIdRaw = $request->input('outlet_id', 'all');
            if ($outletIdRaw !== null && $outletIdRaw !== '' && $outletIdRaw !== 'all') {
                if (!is_numeric($outletIdRaw) || (int) $outletIdRaw <= 0) {
                    return response()->json(['message' => 'Invalid outlet_id. Use "all" or a positive integer.'], 422);
                }

                $selectedOutletIds = [(int) $outletIdRaw];
            }
        }

        if ($selectedOutletIds !== null) {
            $outletsCount = Outlet::query()->whereIn('id', $selectedOutletIds)->count();
            if ($outletsCount !== count($selectedOutletIds)) {
                return response()->json(['message' => 'Outlet not found.'], 404);
            }
        }

        $isAllOutlets = $selectedOutletIds === null;
        $singleOutletId = $selectedOutletIds !== null && count($selectedOutletIds) === 1
            ? (int) $selectedOutletIds[0]
            : null;
        $outletScopeKey = $isAllOutlets ? 'all' : ('ids-' . implode('-', $selectedOutletIds));

        $cacheKey = 'admin.dashboard.summary:' . $dateFrom . ':' . $dateTo . ':' . $outletScopeKey;

        $payload = Cache::remember($cacheKey, now()->addSeconds(10), function () use ($dateFrom, $dateTo, $periodDays, $selectedOutletIds, $isAllOutlets, $singleOutletId, $outletScopeKey) {
            $salesBase = Sale::query()
                ->whereBetween('sale_date', [$dateFrom, $dateTo])
                ->where('status', 'completed')
                ->when($selectedOutletIds !== null, fn($q) => $q->whereIn('outlet_id', $selectedOutletIds));

            $kpis = (clone $salesBase)
                ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sales, COUNT(*) as total_transactions, COALESCE(SUM(discount_amount), 0) as discount_total')
                ->first();

            $totalSales = (float) ($kpis->total_sales ?? 0);
            $totalTransactions = (int) ($kpis->total_transactions ?? 0);
            $avgTransaction = $totalTransactions > 0 ? ($totalSales / $totalTransactions) : 0.0;

            $discountTotal = (float) ($kpis->discount_total ?? 0);

            $cancelledBase = Sale::query()
                ->whereBetween('sale_date', [$dateFrom, $dateTo])
                ->where('status', 'cancelled')
                ->when($selectedOutletIds !== null, fn($q) => $q->whereIn('outlet_id', $selectedOutletIds));

            $cancelledKpis = (clone $cancelledBase)
                ->selectRaw('COUNT(*) as cancelled_transactions, COALESCE(SUM(total_amount), 0) as cancelled_amount')
                ->first();

            $cancelledTransactions = (int) ($cancelledKpis->cancelled_transactions ?? 0);
            $cancelledAmount = (float) ($cancelledKpis->cancelled_amount ?? 0);

            $hourly = (clone $salesBase)
                ->selectRaw('HOUR(created_at) as hour, COALESCE(SUM(total_amount), 0) as amount')
                ->groupBy('hour')
                ->pluck('amount', 'hour');

            $salesPerHour = [];
            for ($h = 0; $h <= 23; $h++) {
                $salesPerHour[] = [
                    'hour' => $h,
                    'amount' => (float) ($hourly[$h] ?? 0),
                ];
            }

            $topProducts = SaleItem::query()
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
                ->where('sales.status', 'completed')
                ->when($selectedOutletIds !== null, fn($q) => $q->whereIn('sales.outlet_id', $selectedOutletIds))
                ->groupBy('sale_items.product_id', 'sale_items.product_name', 'products.thumbnail_path', 'products.image_path')
                ->selectRaw('sale_items.product_id as product_id, sale_items.product_name as product_name, products.thumbnail_path, products.image_path, COALESCE(SUM(sale_items.quantity), 0) as qty, COALESCE(SUM(sale_items.subtotal), 0) as amount')
                ->orderByDesc('amount')
                ->orderByDesc('qty')
                ->orderBy('sale_items.product_id')
                ->limit(10)
                ->get()
                ->map(fn($row) => [
                    'product_id' => (int) $row->product_id,
                    'product_name' => (string) $row->product_name,
                    'image_url' => $row->thumbnail_path ? \Illuminate\Support\Facades\Storage::url($row->thumbnail_path) : ($row->image_path ? \Illuminate\Support\Facades\Storage::url($row->image_path) : null),
                    'qty' => (float) $row->qty,
                    'amount' => (float) $row->amount,
                ])
                ->values();

            $topStateKey = 'dashboard.top_products.state.' . $outletScopeKey;
            $stateRaw = Setting::getValue($topStateKey, null);
            $state = is_string($stateRaw) ? json_decode($stateRaw, true) : null;

            $prevRankState = [];
            $prevBadgeState = [];
            if (is_array($state) && ($state['date_from'] ?? null) === $dateFrom && ($state['date_to'] ?? null) === $dateTo) {
                $prevRankState = is_array($state['ranks'] ?? null) ? $state['ranks'] : [];
                $prevBadgeState = is_array($state['badges'] ?? null) ? $state['badges'] : [];
            }

            $nextRankState = [];
            $nextBadgeState = [];

            $topProducts = $topProducts
                ->values()
                ->map(function (array $row, int $idx) use ($prevRankState, $prevBadgeState, &$nextRankState, &$nextBadgeState) {
                    $productId = (int) ($row['product_id'] ?? 0);
                    $productName = strtolower(trim((string) ($row['product_name'] ?? '')));
                    $key = $productId > 0 ? "id:{$productId}" : "name:{$productName}";

                    $currentRank = $idx + 1;
                    $nextRankState[$key] = $currentRank;

                    $badge = null;
                    if (is_array($prevBadgeState) && isset($prevBadgeState[$key]) && is_array($prevBadgeState[$key])) {
                        $badge = $prevBadgeState[$key];
                    }

                    $prevRank = null;
                    if (is_array($prevRankState) && array_key_exists($key, $prevRankState)) {
                        $candidatePrev = (int) $prevRankState[$key];
                        if ($candidatePrev > 0) {
                            $prevRank = $candidatePrev;
                        }
                    }

                    if ($prevRank !== null) {
                        if ($currentRank < $prevRank) {
                            $badge = [
                                'direction' => 'up',
                                'delta' => $prevRank - $currentRank,
                            ];
                        } elseif ($currentRank > $prevRank) {
                            $badge = [
                                'direction' => 'down',
                                'delta' => $currentRank - $prevRank,
                            ];
                        }
                    }

                    if (is_array($badge) && isset($badge['direction'], $badge['delta'])) {
                        $nextBadgeState[$key] = $badge;
                    }

                    $row['movement'] = $badge;

                    return $row;
                })
                ->values();

            Setting::setValue($topStateKey, json_encode([
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'ranks' => $nextRankState,
                'badges' => $nextBadgeState,
                'updated_at' => now()->toIso8601String(),
            ], JSON_UNESCAPED_UNICODE));

            $categorySales = SaleItem::query()
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
                ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
                ->where('sales.status', 'completed')
                ->when($selectedOutletIds !== null, fn($q) => $q->whereIn('sales.outlet_id', $selectedOutletIds))
                ->groupBy('product_categories.id', 'product_categories.name')
                ->selectRaw("COALESCE(product_categories.name, 'Uncategorized') as category, COALESCE(SUM(sale_items.subtotal), 0) as amount")
                ->orderByDesc('amount')
                ->limit(10)
                ->get()
                ->map(fn($row) => [
                    'category' => (string) $row->category,
                    'amount' => (float) $row->amount,
                ])
                ->values();

            $paymentMix = \App\Models\Payment::query()
                ->join('sales', 'payments.sale_id', '=', 'sales.id')
                ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
                ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
                ->where('sales.status', 'completed')
                ->when($selectedOutletIds !== null, fn($q) => $q->whereIn('sales.outlet_id', $selectedOutletIds))
                ->groupBy('payment_methods.id', 'payment_methods.name')
                ->selectRaw('payment_methods.id as payment_method_id, payment_methods.name as payment_method_name, COALESCE(SUM(payments.amount), 0) as amount')
                ->orderByDesc('amount')
                ->get()
                ->map(fn($row) => [
                    'payment_method_id' => (int) $row->payment_method_id,
                    'payment_method_name' => (string) $row->payment_method_name,
                    'amount' => (float) $row->amount,
                ])
                ->values();

            $showBreakdown = $isAllOutlets || (is_array($selectedOutletIds) && count($selectedOutletIds) > 1);

            $outletSales = collect();
            if ($showBreakdown) {
                $cogsByOutlet = SaleItem::query()
                    ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                    ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
                    ->where('sales.status', 'completed')
                    ->when($selectedOutletIds !== null, fn($q) => $q->whereIn('sales.outlet_id', $selectedOutletIds))
                    ->groupBy('sales.outlet_id')
                    ->selectRaw('sales.outlet_id, COALESCE(SUM(sale_items.cogs), 0) as total_cogs')
                    ->pluck('total_cogs', 'outlet_id');

                $outletSalesBase = Sale::query()
                    ->join('outlets', 'sales.outlet_id', '=', 'outlets.id')
                    ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
                    ->where('sales.status', 'completed')
                    ->when($selectedOutletIds !== null, fn($q) => $q->whereIn('sales.outlet_id', $selectedOutletIds));

                $outletSales = $outletSalesBase
                    ->groupBy('outlets.id', 'outlets.name')
                    ->selectRaw('outlets.id as outlet_id, outlets.name as outlet_name, COALESCE(SUM(sales.total_amount), 0) as amount, COUNT(*) as transactions, MAX(sales.created_at) as last_sale_at')
                    ->orderBy('outlets.name')
                    ->get()
                    ->map(fn($row) => [
                        'outlet_id' => (int) $row->outlet_id,
                        'outlet_name' => (string) $row->outlet_name,
                        'amount' => (float) $row->amount,
                        'cogs' => (float) ($cogsByOutlet[$row->outlet_id] ?? 0),
                        'transactions' => (int) $row->transactions,
                        'last_sale_at' => $row->last_sale_at ? Carbon::parse($row->last_sale_at)->toIso8601String() : null,
                    ])
                    ->values();
            }

            $dailyTarget = (float) Setting::getValue('dashboard.daily_sales_target', 0);
            $target = $dailyTarget > 0 ? ($dailyTarget * $periodDays) : 0;
            $targetProgressPct = $target > 0 ? min(100, max(0, ($totalSales / $target) * 100)) : null;

            $hourlyStacked = null;
            $hourlyStackedMeta = null;

            if ($showBreakdown) {
                $topOutlets = $outletSales->sortByDesc('amount')->take(5)->values();
                $topOutletIds = $topOutlets->pluck('outlet_id')->all();

                $outletNameById = $outletSales
                    ->mapWithKeys(fn($r) => [(int) $r['outlet_id'] => (string) $r['outlet_name']])
                    ->all();

                $hourOutletRows = Sale::query()
                    ->whereBetween('sale_date', [$dateFrom, $dateTo])
                    ->where('status', 'completed')
                    ->when($selectedOutletIds !== null, fn($q) => $q->whereIn('outlet_id', $selectedOutletIds))
                    ->selectRaw('outlet_id as outlet_id, HOUR(created_at) as hour, COALESCE(SUM(total_amount), 0) as amount')
                    ->groupBy('outlet_id', 'hour')
                    ->get();

                $amountByHourOutlet = [];
                foreach ($hourOutletRows as $row) {
                    $hour = (int) $row->hour;
                    $oid = (int) $row->outlet_id;
                    $amountByHourOutlet[$hour][$oid] = (float) $row->amount;
                }

                $hourlyStacked = [];
                for ($h = 0; $h <= 23; $h++) {
                    $perOutlet = $amountByHourOutlet[$h] ?? [];
                    $total = (float) array_sum($perOutlet);

                    $segments = [];
                    $topSum = 0.0;
                    foreach ($topOutletIds as $oid) {
                        $amt = (float) ($perOutlet[$oid] ?? 0);
                        $topSum += $amt;
                        $segments[] = [
                            'type' => 'outlet',
                            'outlet_id' => (int) $oid,
                            'outlet_name' => (string) ($outletNameById[$oid] ?? ("Outlet #{$oid}")),
                            'amount' => $amt,
                        ];
                    }

                    $othersAmount = max(0, $total - $topSum);

                    $othersBreakdown = [];
                    if ($othersAmount > 0) {
                        foreach ($perOutlet as $oid => $amt) {
                            if (in_array((int) $oid, $topOutletIds, true)) {
                                continue;
                            }
                            if ((float) $amt <= 0) {
                                continue;
                            }
                            $othersBreakdown[] = [
                                'outlet_id' => (int) $oid,
                                'outlet_name' => (string) ($outletNameById[(int) $oid] ?? ("Outlet #{$oid}")),
                                'amount' => (float) $amt,
                            ];
                        }

                        usort($othersBreakdown, fn($a, $b) => ($b['amount'] <=> $a['amount']));
                    }

                    $hourlyStacked[] = [
                        'hour' => $h,
                        'total' => $total,
                        'segments' => $segments,
                        'others' => [
                            'amount' => $othersAmount,
                            'breakdown' => $othersBreakdown,
                        ],
                    ];
                }

                $hourlyStackedMeta = [
                    'top_outlets' => $topOutlets
                        ->map(fn($r) => [
                            'outlet_id' => (int) $r['outlet_id'],
                            'outlet_name' => (string) $r['outlet_name'],
                        ])
                        ->values(),
                ];
            }

            return [
                'date' => $dateTo,
                'date_from' => $dateFrom,
                'date_to' => $dateTo,
                'outlet_id' => $singleOutletId,
                'outlet_ids' => $selectedOutletIds ?? [],
                'is_all_outlets' => $isAllOutlets,
                'show_breakdown' => $showBreakdown,
                'kpis' => [
                    'total_sales' => $totalSales,
                    'total_transactions' => $totalTransactions,
                    'avg_transaction' => $avgTransaction,
                    'discount_total' => $discountTotal,
                    'cancelled_transactions' => $cancelledTransactions,
                    'cancelled_amount' => $cancelledAmount,
                ],
                'target' => [
                    'daily_sales_target' => $dailyTarget > 0 ? $dailyTarget : null,
                    'period_sales_target' => $target > 0 ? $target : null,
                    'period_days' => $periodDays,
                    'progress_pct' => $targetProgressPct,
                ],
                'sales_per_hour' => $salesPerHour,
                'hourly_stacked' => $hourlyStacked,
                'hourly_stacked_meta' => $hourlyStackedMeta,
                'category_sales' => $categorySales,
                'top_products' => $topProducts,
                'payment_mix' => $paymentMix,
                'outlet_sales' => $outletSales,
                'generated_at' => now()->toIso8601String(),
            ];
        });

        $prevCacheKey = 'admin.dashboard.summary.prev:' . $prevDateFrom . ':' . $prevDateTo . ':' . $outletScopeKey;
        $prevPayload = Cache::remember($prevCacheKey, now()->addSeconds(30), function () use ($prevDateFrom, $prevDateTo, $selectedOutletIds) {
            $base = Sale::query()
                ->whereBetween('sale_date', [$prevDateFrom, $prevDateTo])
                ->where('status', 'completed')
                ->when($selectedOutletIds !== null, fn($q) => $q->whereIn('outlet_id', $selectedOutletIds));

            $kpis = (clone $base)
                ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sales, COUNT(*) as total_transactions')
                ->first();

            return [
                'date_from' => $prevDateFrom,
                'date_to' => $prevDateTo,
                'total_sales' => (float) ($kpis->total_sales ?? 0),
                'total_transactions' => (int) ($kpis->total_transactions ?? 0),
            ];
        });

        $deltaSales = (float) ($payload['kpis']['total_sales'] - $prevPayload['total_sales']);
        $deltaTransactions = (int) ($payload['kpis']['total_transactions'] - $prevPayload['total_transactions']);

        $payload['trend_vs_prev_day'] = [
            'prev_date' => $prevPayload['date_to'],
            'prev_date_from' => $prevPayload['date_from'],
            'prev_date_to' => $prevPayload['date_to'],
            'prev_total_sales' => $prevPayload['total_sales'],
            'prev_total_transactions' => $prevPayload['total_transactions'],
            'delta_total_sales' => $deltaSales,
            'delta_total_sales_pct' => $prevPayload['total_sales'] > 0 ? ($deltaSales / $prevPayload['total_sales']) * 100 : null,
            'delta_total_transactions' => $deltaTransactions,
            'delta_total_transactions_pct' => $prevPayload['total_transactions'] > 0 ? ($deltaTransactions / $prevPayload['total_transactions']) * 100 : null,
        ];

        return response()->json($payload);
    }
}

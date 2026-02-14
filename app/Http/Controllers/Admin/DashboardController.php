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
        $dateInput = (string) $request->input('date', today()->toDateString());

        try {
            $date = Carbon::createFromFormat('Y-m-d', $dateInput)->toDateString();
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Invalid date format. Use YYYY-MM-DD.'], 422);
        }

        $prevDate = Carbon::createFromFormat('Y-m-d', $date)->subDay()->toDateString();

        $outletIdRaw = $request->input('outlet_id', 'all');
        $outletId = null;

        if ($outletIdRaw !== null && $outletIdRaw !== '' && $outletIdRaw !== 'all') {
            if (!is_numeric($outletIdRaw) || (int) $outletIdRaw <= 0) {
                return response()->json(['message' => 'Invalid outlet_id. Use "all" or a positive integer.'], 422);
            }

            $outletId = (int) $outletIdRaw;

            if (!Outlet::query()->whereKey($outletId)->exists()) {
                return response()->json(['message' => 'Outlet not found.'], 404);
            }
        }

        $cacheKey = 'admin.dashboard.summary:' . $date . ':' . ($outletId ?? 'all');

        $payload = Cache::remember($cacheKey, now()->addSeconds(10), function () use ($date, $outletId) {
            $salesBase = Sale::query()
                ->where('sale_date', $date)
                ->where('status', 'completed')
                ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId));

            $kpis = (clone $salesBase)
                ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sales, COUNT(*) as total_transactions, COALESCE(SUM(discount_amount), 0) as discount_total')
                ->first();

            $totalSales = (float) ($kpis->total_sales ?? 0);
            $totalTransactions = (int) ($kpis->total_transactions ?? 0);
            $avgTransaction = $totalTransactions > 0 ? ($totalSales / $totalTransactions) : 0.0;

            $discountTotal = (float) ($kpis->discount_total ?? 0);

            $cancelledBase = Sale::query()
                ->where('sale_date', $date)
                ->where('status', 'cancelled')
                ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId));

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
                ->where('sales.sale_date', $date)
                ->where('sales.status', 'completed')
                ->when($outletId, fn ($q) => $q->where('sales.outlet_id', $outletId))
                ->groupBy('sale_items.product_id', 'sale_items.product_name')
                ->selectRaw('sale_items.product_id as product_id, sale_items.product_name as product_name, COALESCE(SUM(sale_items.quantity), 0) as qty, COALESCE(SUM(sale_items.subtotal), 0) as amount')
                ->orderByDesc('amount')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'product_id' => (int) $row->product_id,
                    'product_name' => (string) $row->product_name,
                    'qty' => (float) $row->qty,
                    'amount' => (float) $row->amount,
                ])
                ->values();

            $categorySales = SaleItem::query()
                ->join('sales', 'sale_items.sale_id', '=', 'sales.id')
                ->join('products', 'sale_items.product_id', '=', 'products.id')
                ->leftJoin('product_categories', 'products.category_id', '=', 'product_categories.id')
                ->where('sales.sale_date', $date)
                ->where('sales.status', 'completed')
                ->when($outletId, fn ($q) => $q->where('sales.outlet_id', $outletId))
                ->groupBy('product_categories.id', 'product_categories.name')
                ->selectRaw("COALESCE(product_categories.name, 'Uncategorized') as category, COALESCE(SUM(sale_items.subtotal), 0) as amount")
                ->orderByDesc('amount')
                ->limit(10)
                ->get()
                ->map(fn ($row) => [
                    'category' => (string) $row->category,
                    'amount' => (float) $row->amount,
                ])
                ->values();

            $paymentMix = \App\Models\Payment::query()
                ->join('sales', 'payments.sale_id', '=', 'sales.id')
                ->join('payment_methods', 'payments.payment_method_id', '=', 'payment_methods.id')
                ->where('sales.sale_date', $date)
                ->where('sales.status', 'completed')
                ->when($outletId, fn ($q) => $q->where('sales.outlet_id', $outletId))
                ->groupBy('payment_methods.id', 'payment_methods.name')
                ->selectRaw('payment_methods.id as payment_method_id, payment_methods.name as payment_method_name, COALESCE(SUM(payments.amount), 0) as amount')
                ->orderByDesc('amount')
                ->get()
                ->map(fn ($row) => [
                    'payment_method_id' => (int) $row->payment_method_id,
                    'payment_method_name' => (string) $row->payment_method_name,
                    'amount' => (float) $row->amount,
                ])
                ->values();

            $outletSales = collect();
            if (!$outletId) {
                $outletSales = Sale::query()
                    ->join('outlets', 'sales.outlet_id', '=', 'outlets.id')
                    ->where('sales.sale_date', $date)
                    ->where('sales.status', 'completed')
                    ->groupBy('outlets.id', 'outlets.name')
                    ->selectRaw('outlets.id as outlet_id, outlets.name as outlet_name, COALESCE(SUM(sales.total_amount), 0) as amount, COUNT(*) as transactions, MAX(sales.created_at) as last_sale_at')
                    ->orderByDesc('amount')
                    ->get()
                    ->map(fn ($row) => [
                        'outlet_id' => (int) $row->outlet_id,
                        'outlet_name' => (string) $row->outlet_name,
                        'amount' => (float) $row->amount,
                        'transactions' => (int) $row->transactions,
                        'last_sale_at' => $row->last_sale_at ? Carbon::parse($row->last_sale_at)->toIso8601String() : null,
                    ])
                    ->values();
            }

            $target = (float) Setting::getValue('dashboard.daily_sales_target', 0);
            $targetProgressPct = $target > 0 ? min(100, max(0, ($totalSales / $target) * 100)) : null;

            $hourlyStacked = null;
            $hourlyStackedMeta = null;

            if (!$outletId) {
                $topOutlets = $outletSales->take(5)->values();
                $topOutletIds = $topOutlets->pluck('outlet_id')->all();

                $outletNameById = $outletSales
                    ->mapWithKeys(fn ($r) => [(int) $r['outlet_id'] => (string) $r['outlet_name']])
                    ->all();

                $hourOutletRows = Sale::query()
                    ->where('sale_date', $date)
                    ->where('status', 'completed')
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

                        usort($othersBreakdown, fn ($a, $b) => ($b['amount'] <=> $a['amount']));
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
                        ->map(fn ($r) => [
                            'outlet_id' => (int) $r['outlet_id'],
                            'outlet_name' => (string) $r['outlet_name'],
                        ])
                        ->values(),
                ];
            }

            return [
                'date' => $date,
                'outlet_id' => $outletId,
                'kpis' => [
                    'total_sales' => $totalSales,
                    'total_transactions' => $totalTransactions,
                    'avg_transaction' => $avgTransaction,
                    'discount_total' => $discountTotal,
                    'cancelled_transactions' => $cancelledTransactions,
                    'cancelled_amount' => $cancelledAmount,
                ],
                'target' => [
                    'daily_sales_target' => $target > 0 ? $target : null,
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

        $prevCacheKey = 'admin.dashboard.summary.prev:' . $prevDate . ':' . ($outletId ?? 'all');
        $prevPayload = Cache::remember($prevCacheKey, now()->addSeconds(30), function () use ($prevDate, $outletId) {
            $base = Sale::query()
                ->where('sale_date', $prevDate)
                ->where('status', 'completed')
                ->when($outletId, fn ($q) => $q->where('outlet_id', $outletId));

            $kpis = (clone $base)
                ->selectRaw('COALESCE(SUM(total_amount), 0) as total_sales, COUNT(*) as total_transactions')
                ->first();

            return [
                'date' => $prevDate,
                'total_sales' => (float) ($kpis->total_sales ?? 0),
                'total_transactions' => (int) ($kpis->total_transactions ?? 0),
            ];
        });

        $deltaSales = (float) ($payload['kpis']['total_sales'] - $prevPayload['total_sales']);
        $deltaTransactions = (int) ($payload['kpis']['total_transactions'] - $prevPayload['total_transactions']);

        $payload['trend_vs_prev_day'] = [
            'prev_date' => $prevPayload['date'],
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

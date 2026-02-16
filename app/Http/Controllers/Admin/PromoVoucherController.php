<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\ProductCategory;
use App\Models\Promotion;
use App\Models\Sale;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class PromoVoucherController extends Controller
{
    public function index(Request $request)
    {
        $today = now()->toDateString();
        $defaultFrom = now()->startOfMonth()->toDateString();

        $dateFromInput = (string) $request->input('date_from', $defaultFrom);
        $dateToInput = (string) $request->input('date_to', $today);

        try {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFromInput)->toDateString();
        } catch (\Throwable $e) {
            $dateFrom = $defaultFrom;
        }

        try {
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateToInput)->toDateString();
        } catch (\Throwable $e) {
            $dateTo = $today;
        }

        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        $outlets = Outlet::query()
            ->active()
            ->orderBy('name')
            ->get(['id', 'name']);

        $outletId = $request->input('outlet_id');
        $selectedOutletId = null;
        if (is_numeric($outletId) && $outlets->contains('id', (int) $outletId)) {
            $selectedOutletId = (int) $outletId;
        }

        $salesBase = Sale::query()
            ->where('status', 'completed')
            ->whereBetween('sale_date', [$dateFrom, $dateTo])
            ->when($selectedOutletId, fn ($query) => $query->where('outlet_id', $selectedOutletId));

        $summary = (clone $salesBase)
            ->selectRaw('
                COUNT(*) as total_transactions,
                COALESCE(SUM(subtotal), 0) as subtotal_amount,
                COALESCE(SUM(discount_amount), 0) as invoice_discount_amount,
                COALESCE(SUM(total_amount), 0) as net_sales_amount
            ')
            ->first();

        $itemDiscountAmount = (float) DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
            ->when($selectedOutletId, fn ($query) => $query->where('sales.outlet_id', $selectedOutletId))
            ->sum('sale_items.discount_amount');

        $subtotalAmount = (float) ($summary->subtotal_amount ?? 0);
        $invoiceDiscountAmount = (float) ($summary->invoice_discount_amount ?? 0);
        $totalDiscountAmount = $invoiceDiscountAmount + $itemDiscountAmount;
        $discountRate = $subtotalAmount > 0 ? ($totalDiscountAmount / $subtotalAmount) * 100 : 0;

        $categoryDiscountRows = DB::table('sale_items')
            ->join('sales', 'sales.id', '=', 'sale_items.sale_id')
            ->join('products', 'products.id', '=', 'sale_items.product_id')
            ->leftJoin('product_categories', 'product_categories.id', '=', 'products.category_id')
            ->where('sales.status', 'completed')
            ->whereBetween('sales.sale_date', [$dateFrom, $dateTo])
            ->when($selectedOutletId, fn ($query) => $query->where('sales.outlet_id', $selectedOutletId))
            ->groupBy('product_categories.id', 'product_categories.name')
            ->selectRaw("
                COALESCE(product_categories.name, 'Tanpa Kategori') as category_name,
                COALESCE(SUM(sale_items.discount_amount), 0) as item_discount_total,
                COALESCE(SUM(sale_items.subtotal), 0) as net_sales_total
            ")
            ->orderByDesc('item_discount_total')
            ->limit(8)
            ->get();

        $overview = [
            'total_transactions' => (int) ($summary->total_transactions ?? 0),
            'subtotal_amount' => $subtotalAmount,
            'invoice_discount_amount' => $invoiceDiscountAmount,
            'item_discount_amount' => $itemDiscountAmount,
            'total_discount_amount' => $totalDiscountAmount,
            'net_sales_amount' => (float) ($summary->net_sales_amount ?? 0),
            'discount_rate' => $discountRate,
        ];

        $categories = ProductCategory::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $promotions = Promotion::query()
            ->with(['outlet:id,name', 'categoryRules.category:id,name'])
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->paginate(8, ['*'], 'promo_page')
            ->withQueryString();

        $vouchers = Voucher::query()
            ->with('outlet:id,name')
            ->orderByDesc('is_active')
            ->orderByDesc('id')
            ->paginate(8, ['*'], 'voucher_page')
            ->withQueryString();

        return view('admin.promo-vouchers.index', [
            'outlets' => $outlets,
            'categories' => $categories,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'selectedOutletId' => $selectedOutletId,
            'overview' => $overview,
            'categoryDiscountRows' => $categoryDiscountRows,
            'promotions' => $promotions,
            'vouchers' => $vouchers,
        ]);
    }

    public function storePromotion(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'code' => ['nullable', 'string', 'max:50', 'alpha_dash', Rule::unique('promotions', 'code')],
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'notes' => ['nullable', 'string'],
            'category_discounts' => ['required', 'array'],
        ]);

        $inputRules = collect($validated['category_discounts'] ?? []);
        $activeCategoryIds = ProductCategory::query()
            ->where('is_active', true)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $rules = $inputRules
            ->map(function ($value, $categoryId) use ($activeCategoryIds) {
                if (!is_numeric($categoryId)) {
                    return null;
                }

                $categoryId = (int) $categoryId;
                $percent = is_numeric($value) ? (float) $value : 0.0;

                if ($percent <= 0 || $percent > 100) {
                    return null;
                }

                if (!in_array($categoryId, $activeCategoryIds, true)) {
                    return null;
                }

                return [
                    'product_category_id' => $categoryId,
                    'discount_percent' => round($percent, 2),
                ];
            })
            ->filter()
            ->values();

        if ($rules->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', 'Minimal isi satu diskon kategori dengan nilai 0.01% - 100%.');
        }

        DB::transaction(function () use ($validated, $rules) {
            $promotion = Promotion::create([
                'name' => $validated['name'],
                'code' => !empty($validated['code']) ? strtoupper(trim($validated['code'])) : null,
                'outlet_id' => $validated['outlet_id'] ?? null,
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'],
                'is_active' => true,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            foreach ($rules as $rule) {
                $promotion->categoryRules()->create($rule);
            }
        });

        return redirect()
            ->route('admin.promo-vouchers.index')
            ->with('success', 'Promo kategori berhasil dibuat.');
    }

    public function togglePromotion(Promotion $promotion)
    {
        $promotion->update(['is_active' => !$promotion->is_active]);

        return back()->with('success', 'Status promo berhasil diperbarui.');
    }

    public function destroyPromotion(Promotion $promotion)
    {
        if ($promotion->sales()->exists()) {
            return back()->with('error', 'Promo tidak dapat dihapus karena sudah dipakai transaksi.');
        }

        $promotion->delete();

        return back()->with('success', 'Promo berhasil dihapus.');
    }

    public function storeVoucher(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:200'],
            'code' => ['required', 'string', 'max:60', 'alpha_dash', Rule::unique('vouchers', 'code')],
            'outlet_id' => ['nullable', 'exists:outlets,id'],
            'discount_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'discount_value' => ['required', 'numeric', 'min:0.01'],
            'min_purchase' => ['nullable', 'numeric', 'min:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($validated['discount_type'] === 'percentage' && (float) $validated['discount_value'] > 100) {
            return back()->withInput()->with('error', 'Diskon persen voucher maksimal 100%.');
        }

        Voucher::create([
            'name' => $validated['name'],
            'code' => strtoupper(trim($validated['code'])),
            'outlet_id' => $validated['outlet_id'] ?? null,
            'discount_type' => $validated['discount_type'],
            'discount_value' => $validated['discount_value'],
            'min_purchase' => $validated['min_purchase'] ?? 0,
            'max_discount_amount' => $validated['max_discount_amount'] ?? null,
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'usage_limit' => $validated['usage_limit'] ?? null,
            'used_count' => 0,
            'is_active' => true,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return redirect()
            ->route('admin.promo-vouchers.index')
            ->with('success', 'Voucher berhasil dibuat.');
    }

    public function toggleVoucher(Voucher $voucher)
    {
        $voucher->update(['is_active' => !$voucher->is_active]);

        return back()->with('success', 'Status voucher berhasil diperbarui.');
    }

    public function destroyVoucher(Voucher $voucher)
    {
        if ($voucher->sales()->exists()) {
            return back()->with('error', 'Voucher tidak dapat dihapus karena sudah dipakai transaksi.');
        }

        $voucher->delete();

        return back()->with('success', 'Voucher berhasil dihapus.');
    }
}

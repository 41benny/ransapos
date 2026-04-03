<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\CashTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DebtReportController extends Controller
{
    /**
     * Display a listing of suppliers with their debt summary.
     */
    public function index(Request $request)
    {
        $statusFilter = $request->input('status', 'unpaid'); // unpaid, all
        $query = $this->buildSupplierDebtSummaryQuery($request);

        if ($statusFilter === 'unpaid') {
            $query->whereRaw($this->remainingDebtExpression('debt_summary.total_debt', 'debt_summary.total_paid') . ' > 0');
        }

        $totalRemainingDebt = (float) DB::query()
            ->fromSub((clone $query), 'supplier_rows')
            ->sum('remaining_debt');

        $suppliers = $query
            ->orderByDesc('remaining_debt')
            ->orderBy('suppliers.name')
            ->paginate(50)
            ->withQueryString();

        return view('admin.reports.debts.index', compact('suppliers', 'statusFilter', 'totalRemainingDebt'));
    }

    /**
     * Display the debt mutation (Buku Hutang) for a specific supplier.
     */
    public function show(Request $request, Supplier $supplier, $supplierId = null)
    {
        $resolvedSupplier = $supplier->getKey()
            ? $supplier
            : Supplier::query()->findOrFail(
                (int) ($supplierId ?? $request->route('supplier'))
            );

        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        $purchasesBaseQuery = Purchase::query()
            ->where('supplier_id', $resolvedSupplier->id)
            ->where('status', 'received');

        $paymentsBaseQuery = CashTransaction::query()
            ->join('purchases', 'cash_transactions.reference_id', '=', 'purchases.id')
            ->where('cash_transactions.reference_type', 'purchase')
            ->where('purchases.supplier_id', $resolvedSupplier->id)
            ->where('purchases.status', 'received');

        // Hitung saldo awal (Sebelum start date)
        $pastPurchasesDebt = (float) (clone $purchasesBaseQuery)
            ->where('purchase_date', '<', $startDate)
            ->sum('total_amount');

        $pastPaymentsSum = (float) (clone $paymentsBaseQuery)
            ->where('transaction_date', '<', $startDate)
            ->sum('cash_transactions.amount');

        $openingBalance = $pastPurchasesDebt - $pastPaymentsSum;

        $purchaseMutations = (clone $purchasesBaseQuery)
            ->whereBetween('purchase_date', [$startDate, $endDate])
            ->selectRaw('purchase_date as row_date')
            ->selectRaw('1 as sort_priority')
            ->selectRaw('purchases.id as row_id')
            ->selectRaw("'purchase' as row_kind")
            ->selectRaw('purchase_number as reference')
            ->selectRaw('NULL as description')
            ->selectRaw('0 as debit')
            ->selectRaw('total_amount as credit');

        $paymentMutations = (clone $paymentsBaseQuery)
            ->whereBetween('cash_transactions.transaction_date', [$startDate, $endDate])
            ->selectRaw('cash_transactions.transaction_date as row_date')
            ->selectRaw('2 as sort_priority')
            ->selectRaw('cash_transactions.id as row_id')
            ->selectRaw("'payment' as row_kind")
            ->selectRaw('cash_transactions.transaction_number as reference')
            ->selectRaw('cash_transactions.description as description')
            ->selectRaw('cash_transactions.amount as debit')
            ->selectRaw('0 as credit');

        $mutationsBaseQuery = DB::query()
            ->fromSub($purchaseMutations->unionAll($paymentMutations), 'mutations')
            ->select('mutations.*')
            ->selectRaw(
                '? + SUM(credit - debit) OVER (ORDER BY row_date asc, sort_priority asc, row_id asc ROWS BETWEEN UNBOUNDED PRECEDING AND CURRENT ROW) as balance',
                [$openingBalance]
            );

        $mutations = DB::query()
            ->fromSub($mutationsBaseQuery, 'ledger')
            ->orderBy('row_date', 'asc')
            ->orderBy('sort_priority', 'asc')
            ->orderBy('row_id', 'asc')
            ->paginate(100)
            ->withQueryString();

        $mutations->setCollection(
            $mutations->getCollection()->map(function ($mutation) {
                $isPurchase = $mutation->row_kind === 'purchase';

                return [
                    'date' => Carbon::parse($mutation->row_date)->startOfDay(),
                    'type' => $isPurchase ? 'Penambahan Hutang' : 'Pelunasan Hutang',
                    'description' => $isPurchase
                        ? "Pembelian PO #{$mutation->reference}"
                        : ($mutation->description ?: 'Pembayaran Pembelian'),
                    'reference' => $mutation->reference,
                    'debit' => (float) $mutation->debit,
                    'credit' => (float) $mutation->credit,
                    'balance' => (float) $mutation->balance,
                    'is_purchase' => $isPurchase,
                    'is_payment' => ! $isPurchase,
                ];
            })
        );

        $totalCredit = (float) (clone $purchasesBaseQuery)
            ->whereBetween('purchase_date', [$startDate, $endDate])
            ->sum('total_amount');

        $totalDebit = (float) (clone $paymentsBaseQuery)
            ->whereBetween('cash_transactions.transaction_date', [$startDate, $endDate])
            ->sum('cash_transactions.amount');

        $currentBalance = $openingBalance + $totalCredit - $totalDebit;

        // Summary all time
        $allTimeDebt = (float) (clone $purchasesBaseQuery)->sum('total_amount');
        $allTimePaid = (float) (clone $paymentsBaseQuery)->sum('cash_transactions.amount');
        $endingBalanceAllTime = max(0, $allTimeDebt - $allTimePaid);

        return view('admin.reports.debts.show', compact(
            'mutations', 
            'openingBalance', 
            'currentBalance',
            'startDate',
            'endDate',
            'totalDebit',
            'totalCredit',
            'allTimeDebt',
            'allTimePaid',
            'endingBalanceAllTime'
        ))->with('supplier', $resolvedSupplier);
    }

    private function buildSupplierDebtSummaryQuery(Request $request)
    {
        $paymentSummary = CashTransaction::query()
            ->where('reference_type', 'purchase')
            ->selectRaw('reference_id as purchase_id')
            ->selectRaw('COALESCE(SUM(amount), 0) as total_paid')
            ->groupBy('reference_id');

        $purchaseSummary = Purchase::query()
            ->where('status', 'received')
            ->leftJoinSub($paymentSummary, 'payment_summary', function ($join) {
                $join->on('payment_summary.purchase_id', '=', 'purchases.id');
            })
            ->selectRaw('purchases.supplier_id')
            ->selectRaw('COALESCE(SUM(purchases.total_amount), 0) as total_debt')
            ->selectRaw('COALESCE(SUM(COALESCE(payment_summary.total_paid, 0)), 0) as total_paid')
            ->groupBy('purchases.supplier_id');

        $query = Supplier::query()
            ->leftJoinSub($purchaseSummary, 'debt_summary', function ($join) {
                $join->on('debt_summary.supplier_id', '=', 'suppliers.id');
            })
            ->select('suppliers.*')
            ->selectRaw('COALESCE(debt_summary.total_debt, 0) as total_debt')
            ->selectRaw('COALESCE(debt_summary.total_paid, 0) as total_paid')
            ->selectRaw($this->remainingDebtExpression('debt_summary.total_debt', 'debt_summary.total_paid') . ' as remaining_debt');

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($subQuery) use ($search) {
                $subQuery->where('suppliers.name', 'like', "%{$search}%")
                    ->orWhere('suppliers.code', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    private function remainingDebtExpression(string $debtColumn, string $paidColumn): string
    {
        $baseExpression = "COALESCE({$debtColumn}, 0) - COALESCE({$paidColumn}, 0)";

        if (DB::connection()->getDriverName() === 'sqlite') {
            return "MAX({$baseExpression}, 0)";
        }

        return "GREATEST({$baseExpression}, 0)";
    }
}

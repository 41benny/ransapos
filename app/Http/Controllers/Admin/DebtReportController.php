<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\Purchase;
use App\Models\CashTransaction;
use Illuminate\Http\Request;
use Carbon\Carbon;

class DebtReportController extends Controller
{
    /**
     * Display a listing of suppliers with their debt summary.
     */
    public function index(Request $request)
    {
        $statusFilter = $request->input('status', 'unpaid'); // unpaid, all
        
        $query = Supplier::query()->with(['purchases' => function($q) {
            $q->where('status', 'received')
              ->withSum('cashTransactions as total_paid', 'amount');
        }]);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
        }

        $suppliers = $query->get()->map(function ($supplier) {
            $totalDebt = 0;
            $totalPaid = 0;
            
            foreach ($supplier->purchases as $purchase) {
                $totalDebt += $purchase->total_amount;
                $totalPaid += $purchase->total_paid ?? 0;
            }
            
            $supplier->total_debt = $totalDebt;
            $supplier->total_paid = $totalPaid;
            $supplier->remaining_debt = max(0, $totalDebt - $totalPaid);
            
            return $supplier;
        });

        if ($statusFilter === 'unpaid') {
            $suppliers = $suppliers->filter(function ($supplier) {
                return $supplier->remaining_debt > 0;
            })->values();
        }

        // Pagination if needed, but collection pagination can be tricky, 
        // we'll just pass the full collection or manually paginate.
        // For simplicity with collections, let's just pass the collection 
        // as data size per supplier is relatively small.
        
        return view('admin.reports.debts.index', compact('suppliers', 'statusFilter'));
    }

    /**
     * Display the debt mutation (Buku Hutang) for a specific supplier.
     */
    public function show(Request $request, Supplier $supplier)
    {
        $startDate = $request->input('start_date', Carbon::now()->startOfMonth()->format('Y-m-d'));
        $endDate = $request->input('end_date', Carbon::now()->endOfMonth()->format('Y-m-d'));

        // Ambil semua PO received
        $purchasesQuery = $supplier->purchases()->where('status', 'received');
        $allPurchases = clone $purchasesQuery;
        
        $purchases = $purchasesQuery->whereBetween('purchase_date', [$startDate, $endDate])->get();
        $purchaseIds = $allPurchases->pluck('id');

        // Ambil semua transaksi kas (pembayaran PO)
        $payments = CashTransaction::where('reference_type', 'purchase')
            ->whereIn('reference_id', $purchaseIds)
            ->whereBetween('transaction_date', [$startDate, $endDate])
            ->with(['cashAccount', 'creator'])
            ->get();

        // Hitung saldo awal (Sebelum start date)
        $pastPurchasesDebt = $supplier->purchases()
            ->where('status', 'received')
            ->where('purchase_date', '<', $startDate)
            ->sum('total_amount');
            
        $pastPaymentsSum = CashTransaction::where('reference_type', 'purchase')
            ->whereIn('reference_id', $purchaseIds)
            ->where('transaction_date', '<', $startDate)
            ->sum('amount');
            
        $openingBalance = $pastPurchasesDebt - $pastPaymentsSum;

        // Gabungkan mutasi
        $mutations = collect();

        foreach ($purchases as $purchase) {
            $mutations->push([
                'date' => Carbon::parse($purchase->purchase_date)->startOfDay(),
                'type' => 'Penambahan Hutang',
                'description' => "Pembelian PO #{$purchase->purchase_number}",
                'reference' => $purchase->purchase_number,
                'debit' => 0, // Hutang berkurang
                'credit' => $purchase->total_amount, // Hutang bertambah
                'is_purchase' => true,
                'model' => $purchase
            ]);
        }

        foreach ($payments as $payment) {
            $mutations->push([
                'date' => Carbon::parse($payment->transaction_date)->startOfDay(),
                'type' => 'Pelunasan Hutang',
                'description' => $payment->description ?: "Pembayaran Pembelian",
                'reference' => $payment->transaction_number,
                'debit' => $payment->amount, // Hutang berkurang (Kas Keluar)
                'credit' => 0,
                'is_payment' => true,
                'model' => $payment
            ]);
        }

        // Sort by date ascending
        $mutations = $mutations->sortBy(function ($item) {
            return $item['date']->timestamp;
        })->values();

        // Hitung running balance
        $currentBalance = $openingBalance;
        $totalDebit = 0;
        $totalCredit = 0;
        
        $mutations = $mutations->map(function ($mutation) use (&$currentBalance, &$totalDebit, &$totalCredit) {
            $currentBalance += $mutation['credit']; // Hutang bertambah
            $currentBalance -= $mutation['debit']; // Hutang berkurang
            
            $totalDebit += $mutation['debit'];
            $totalCredit += $mutation['credit'];
            
            $mutation['balance'] = $currentBalance;
            return $mutation;
        });

        // Summary all time
        $allTimeDebt = $supplier->purchases()->where('status', 'received')->sum('total_amount');
        $allTimePaid = CashTransaction::where('reference_type', 'purchase')
            ->whereIn('reference_id', $purchaseIds)
            ->sum('amount');
        $endingBalanceAllTime = max(0, $allTimeDebt - $allTimePaid);

        return view('admin.reports.debts.show', compact(
            'supplier', 
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
        ));
    }
}

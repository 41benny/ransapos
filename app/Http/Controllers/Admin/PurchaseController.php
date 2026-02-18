<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Purchase;
use App\Models\Outlet;
use App\Models\Supplier;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\CashAccount;
use App\Http\Requests\StorePurchaseRequest;
use App\Http\Requests\UpdatePurchaseRequest;
use App\Services\PurchaseService;
use App\Services\CashAccountService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Exception;

class PurchaseController extends Controller
{
    protected PurchaseService $purchaseService;
    protected CashAccountService $cashAccountService;

    public function __construct(PurchaseService $purchaseService, CashAccountService $cashAccountService)
    {
        $this->purchaseService = $purchaseService;
        $this->cashAccountService = $cashAccountService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $purchases = $this->purchaseService->getPurchases($request->all());
        
        $outlets = Outlet::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();

        return view('admin.purchases.index', compact('purchases', 'outlets', 'suppliers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $outlets = Outlet::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();
        $categories = ProductCategory::where('is_active', true)
            ->with(['products' => function($query) {
                $query->where('is_active', true);
            }])
            ->get();

        return view('admin.purchases.create', compact('outlets', 'suppliers', 'categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePurchaseRequest $request)
    {
        $validated = $request->validated();

        try {
            $purchase = $this->purchaseService->createPurchase($validated);

            return redirect()
                ->route('admin.purchases.show', $purchase)
                ->with('success', 'Pembelian berhasil dibuat dengan nomor: ' . $purchase->purchase_number);

        } catch (Exception $e) {
            $errorRef = (string) Str::uuid();
            Log::error('Gagal membuat pembelian', [
                'error_ref' => $errorRef,
                'user_id' => auth()->id(),
                'outlet_id' => $validated['outlet_id'] ?? null,
                'supplier_id' => $validated['supplier_id'] ?? null,
                'purchase_date' => $validated['purchase_date'] ?? null,
                'item_count' => isset($validated['items']) ? count($validated['items']) : 0,
                'message' => $e->getMessage(),
            ]);

            $message = 'Gagal membuat pembelian. Ref: ' . $errorRef;
            if (app()->environment('local')) {
                $message .= ' | Detail: ' . $e->getMessage();
            }

            return redirect()
                ->back()
                ->withInput()
                ->with('error', $message);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Purchase $purchase)
    {
        $purchase->load(['items.product', 'outlet', 'supplier', 'creator', 'receiver', 'cashTransactions.cashAccount']);

        // Hitung total yang sudah dibayar
        $totalPaid = $purchase->cashTransactions()->sum('amount');
        $remaining = $purchase->total_amount - $totalPaid;

        return view('admin.purchases.show', compact('purchase', 'totalPaid', 'remaining'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Purchase $purchase)
    {
        // Hanya bisa edit jika masih draft
        if (!$purchase->isDraft()) {
            return redirect()
                ->route('admin.purchases.show', $purchase)
                ->with('warning', 'Hanya pembelian dengan status draft yang bisa diubah');
        }

        $purchase->load('items.product');
        $outlets = Outlet::where('is_active', true)->get();
        $suppliers = Supplier::where('is_active', true)->get();
        $categories = ProductCategory::where('is_active', true)
            ->with(['products' => function($query) {
                $query->where('is_active', true);
            }])
            ->get();

        return view('admin.purchases.edit', compact('purchase', 'outlets', 'suppliers', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePurchaseRequest $request, Purchase $purchase)
    {
        try {
            $purchase = $this->purchaseService->updatePurchase($purchase, $request->validated());

            return redirect()
                ->route('admin.purchases.show', $purchase)
                ->with('success', 'Pembelian berhasil diperbarui');

        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal memperbarui pembelian: ' . $e->getMessage());
        }
    }

    /**
     * Terima barang (ubah status jadi received & tambah stok)
     */
    public function receive(Purchase $purchase)
    {
        try {
            $purchase = $this->purchaseService->receivePurchase($purchase);

            return redirect()
                ->route('admin.purchases.show', $purchase)
                ->with('success', 'Pembelian berhasil diterima. Stok telah ditambahkan ke sistem.');

        } catch (Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menerima pembelian: ' . $e->getMessage());
        }
    }

    /**
     * Batalkan pembelian
     */
    public function cancel(Request $request, Purchase $purchase)
    {
        try {
            $reason = $request->input('reason', 'Dibatalkan oleh admin');
            $purchase = $this->purchaseService->cancelPurchase($purchase, $reason);

            return redirect()
                ->route('admin.purchases.show', $purchase)
                ->with('success', 'Pembelian berhasil dibatalkan');

        } catch (Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal membatalkan pembelian: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Purchase $purchase)
    {
        try {
            // Hanya bisa hapus jika masih draft
            if (!$purchase->isDraft()) {
                throw new Exception('Hanya pembelian dengan status draft yang bisa dihapus');
            }

            $purchase->delete();

            return redirect()
                ->route('admin.purchases.index')
                ->with('success', 'Pembelian berhasil dihapus');

        } catch (Exception $e) {
            return redirect()
                ->back()
                ->with('error', 'Gagal menghapus pembelian: ' . $e->getMessage());
        }
    }

    /**
     * Show form untuk catat pembayaran purchase
     */
    public function showPaymentForm(Purchase $purchase)
    {
        // Harus sudah received
        if (!$purchase->isReceived()) {
            return redirect()
                ->route('admin.purchases.show', $purchase)
                ->with('warning', 'Purchase harus sudah diterima sebelum bisa dibayar');
        }

        $purchase->load(['cashTransactions.cashAccount']);
        $cashAccounts = CashAccount::active()->orderBy('name')->get();
        
        // Hitung sisa yang harus dibayar
        $totalPaid = $purchase->cashTransactions()->sum('amount');
        $remaining = $purchase->total_amount - $totalPaid;

        return view('admin.purchases.payment', compact('purchase', 'cashAccounts', 'totalPaid', 'remaining'));
    }

    /**
     * Store pembayaran purchase
     */
    public function storePayment(Request $request, Purchase $purchase)
    {
        $request->validate([
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'amount' => 'required|numeric|min:0.01',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ], [
            'cash_account_id.required' => 'Akun kas/bank harus dipilih',
            'amount.required' => 'Jumlah pembayaran harus diisi',
            'amount.min' => 'Jumlah pembayaran minimal Rp 0,01',
            'transaction_date.required' => 'Tanggal pembayaran harus diisi',
        ]);

        try {
            $data = [
                'cash_account_id' => $request->cash_account_id,
                'amount' => $request->amount,
                'transaction_date' => $request->transaction_date,
                'notes' => $request->notes,
                'created_by' => auth()->id() ?? 1, // TODO: Replace with actual auth
            ];

            $transaction = $this->cashAccountService->recordPurchasePayment($purchase, $data);

            return redirect()
                ->route('admin.purchases.show', $purchase)
                ->with('success', 'Pembayaran berhasil dicatat! Nomor transaksi: ' . $transaction->transaction_number);

        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal mencatat pembayaran: ' . $e->getMessage());
        }
    }
}

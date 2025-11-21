<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\Outlet;
use App\Models\Product;
use App\Services\StockTransferService;
use Illuminate\Http\Request;

class StockTransferController extends Controller
{
    protected $transferService;

    public function __construct(StockTransferService $transferService)
    {
        $this->transferService = $transferService;
    }

    /**
     * Display a listing of transfers
     */
    public function index(Request $request)
    {
        $query = StockTransfer::with(['fromOutlet', 'toOutlet', 'creator', 'items']);

        // Filter by from outlet
        if ($request->filled('from_outlet_id')) {
            $query->where('from_outlet_id', $request->from_outlet_id);
        }

        // Filter by to outlet
        if ($request->filled('to_outlet_id')) {
            $query->where('to_outlet_id', $request->to_outlet_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Filter by date range
        if ($request->filled('start_date')) {
            $query->where('transfer_date', '>=', $request->start_date);
        }
        if ($request->filled('end_date')) {
            $query->where('transfer_date', '<=', $request->end_date);
        }

        // Search by transfer number
        if ($request->filled('search')) {
            $query->where('transfer_number', 'like', '%' . $request->search . '%');
        }

        $transfers = $query->orderBy('transfer_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $outlets = Outlet::where('is_active', true)->get();

        return view('admin.stock-transfers.index', compact('transfers', 'outlets'));
    }

    /**
     * Show the form for creating a new transfer
     */
    public function create()
    {
        $outlets = Outlet::where('is_active', true)->get();
        $products = Product::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('admin.stock-transfers.create', compact('outlets', 'products'));
    }

    /**
     * Store a newly created transfer
     */
    public function store(Request $request)
    {
        $request->validate([
            'from_outlet_id' => 'required|exists:outlets,id',
            'to_outlet_id' => 'required|exists:outlets,id|different:from_outlet_id',
            'transfer_date' => 'required|date',
            'notes' => 'nullable|string|max:1000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.notes' => 'nullable|string|max:500',
        ], [
            'to_outlet_id.different' => 'Outlet tujuan harus berbeda dengan outlet asal.',
            'items.required' => 'Minimal harus ada 1 produk.',
            'items.min' => 'Minimal harus ada 1 produk.',
        ]);

        try {
            $transfer = $this->transferService->createTransfer($request->all());

            return redirect()->route('admin.stock-transfers.show', $transfer->id)
                ->with('success', 'Transfer stok berhasil dibuat.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal membuat transfer: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified transfer
     */
    public function show(StockTransfer $stockTransfer)
    {
        $stockTransfer->load([
            'items.product',
            'fromOutlet',
            'toOutlet',
            'creator',
            'sender',
            'receiver',
            'canceller'
        ]);

        return view('admin.stock-transfers.show', compact('stockTransfer'));
    }

    /**
     * Send/dispatch the transfer
     */
    public function send(StockTransfer $stockTransfer)
    {
        try {
            $transfer = $this->transferService->sendTransfer($stockTransfer);

            return redirect()->route('admin.stock-transfers.show', $transfer->id)
                ->with('success', 'Transfer berhasil dikirim. Stok telah dikurangi dari outlet pengirim.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal mengirim transfer: ' . $e->getMessage());
        }
    }

    /**
     * Show receive form
     */
    public function receiveForm(StockTransfer $stockTransfer)
    {
        if (!$stockTransfer->canBeReceived()) {
            return redirect()->route('admin.stock-transfers.show', $stockTransfer->id)
                ->with('error', 'Transfer tidak dapat diterima. Status: ' . $stockTransfer->status);
        }

        $stockTransfer->load(['items.product', 'fromOutlet', 'toOutlet']);

        return view('admin.stock-transfers.receive', compact('stockTransfer'));
    }

    /**
     * Receive the transfer
     */
    public function receive(Request $request, StockTransfer $stockTransfer)
    {
        $request->validate([
            'items' => 'required|array',
            'items.*' => 'required|numeric|min:0',
        ]);

        try {
            $transfer = $this->transferService->receiveTransfer($stockTransfer, $request->items);

            return redirect()->route('admin.stock-transfers.show', $transfer->id)
                ->with('success', 'Transfer berhasil diterima. Stok telah ditambahkan ke outlet penerima.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal menerima transfer: ' . $e->getMessage());
        }
    }

    /**
     * Cancel the transfer
     */
    public function cancel(Request $request, StockTransfer $stockTransfer)
    {
        $request->validate([
            'cancel_reason' => 'required|string|max:1000',
        ]);

        try {
            $transfer = $this->transferService->cancelTransfer($stockTransfer, $request->cancel_reason);

            return redirect()->route('admin.stock-transfers.show', $transfer->id)
                ->with('success', 'Transfer berhasil dibatalkan.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal membatalkan transfer: ' . $e->getMessage());
        }
    }

    /**
     * Get available stock for AJAX
     */
    public function getAvailableStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|exists:products,id',
            'outlet_id' => 'required|exists:outlets,id',
        ]);

        $stock = \App\Models\Stock::where('product_id', $request->product_id)
            ->where('outlet_id', $request->outlet_id)
            ->first();

        $product = Product::find($request->product_id);

        return response()->json([
            'success' => true,
            'available_stock' => $stock ? $stock->quantity : 0,
            'product_name' => $product->name,
            'unit' => $product->unit ?? 'pcs',
        ]);
    }
}

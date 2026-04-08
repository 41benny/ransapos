<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\StockTransfer;
use App\Models\StockMutation;
use App\Models\Outlet;
use App\Models\Product;
use App\Services\CostService;
use App\Services\StockTransferService;
use App\Support\ReportExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StockTransferController extends Controller
{
    protected $transferService;
    protected $costService;

    public function __construct(StockTransferService $transferService, CostService $costService)
    {
        $this->transferService = $transferService;
        $this->costService = $costService;
    }

    /**
     * Display a listing of transfers
     */
    public function index(Request $request)
    {
        $query = $this->buildFilteredTransfersQuery($request);

        $transfers = $query->orderBy('transfer_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        $transferNominals = $this->getTransferNominals($transfers->getCollection()->pluck('id'));

        $outlets = Outlet::where('is_active', true)->get();

        return view('admin.stock-transfers.index', compact('transfers', 'outlets', 'transferNominals'));
    }

    public function exportExcel(Request $request)
    {
        $transfers = $this->buildFilteredTransfersQuery($request)
            ->orderBy('transfer_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $transferNominals = $this->getTransferNominals($transfers->pluck('id'));

        $columns = [
            ['key' => 'transfer_number', 'label' => 'No. Transfer'],
            ['key' => 'transfer_date', 'label' => 'Tanggal'],
            ['key' => 'from_outlet', 'label' => 'Asal'],
            ['key' => 'to_outlet', 'label' => 'Tujuan'],
            ['key' => 'item_count', 'label' => 'Jumlah Item', 'type' => 'number'],
            ['key' => 'nominal_hpp', 'label' => 'Nilai Kirim HPP', 'type' => 'number'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'author', 'label' => 'Author'],
            ['key' => 'notes', 'label' => 'Catatan'],
        ];

        $rows = $transfers->map(function (StockTransfer $transfer) use ($transferNominals) {
            return [
                'transfer_number' => $transfer->transfer_number,
                'transfer_date' => optional($transfer->transfer_date)->format('Y-m-d'),
                'from_outlet' => $transfer->fromOutlet->name ?? '-',
                'to_outlet' => $transfer->toOutlet->name ?? '-',
                'item_count' => $transfer->items->count(),
                'nominal_hpp' => (float) ($transferNominals[$transfer->id] ?? 0),
                'status' => strtoupper((string) $transfer->status),
                'author' => $transfer->creator->name ?? '-',
                'notes' => $transfer->notes ?? '',
            ];
        });

        ReportExport::xlsx(
            'stock_transfers_' . now()->format('Ymd_His') . '.xlsx',
            'Stock Transfers',
            $columns,
            $rows
        );
    }

    public function exportPdf(Request $request)
    {
        $transfers = $this->buildFilteredTransfersQuery($request)
            ->orderBy('transfer_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        $transferNominals = $this->getTransferNominals($transfers->pluck('id'));

        $columns = [
            ['key' => 'transfer_number', 'label' => 'No. Transfer'],
            ['key' => 'transfer_date', 'label' => 'Tanggal'],
            ['key' => 'from_outlet', 'label' => 'Asal'],
            ['key' => 'to_outlet', 'label' => 'Tujuan'],
            ['key' => 'item_count', 'label' => 'Item', 'type' => 'number'],
            ['key' => 'nominal_hpp', 'label' => 'Nilai HPP', 'type' => 'number'],
            ['key' => 'status', 'label' => 'Status'],
            ['key' => 'author', 'label' => 'Author'],
        ];

        $rows = $transfers->map(function (StockTransfer $transfer) use ($transferNominals) {
            return [
                'transfer_number' => $transfer->transfer_number,
                'transfer_date' => optional($transfer->transfer_date)->format('Y-m-d'),
                'from_outlet' => $transfer->fromOutlet->name ?? '-',
                'to_outlet' => $transfer->toOutlet->name ?? '-',
                'item_count' => $transfer->items->count(),
                'nominal_hpp' => (float) ($transferNominals[$transfer->id] ?? 0),
                'status' => strtoupper((string) $transfer->status),
                'author' => $transfer->creator->name ?? '-',
            ];
        });

        ReportExport::pdf(
            'stock_transfers_' . now()->format('Ymd_His') . '.pdf',
            'Laporan Transfer Stok',
            $columns,
            $rows
        );
    }

    /**
     * Show the form for creating a new transfer
     */
    public function create()
    {
        $outlets = Outlet::where('is_active', true)->get();
        $productsPayload = $this->buildProductsPayload();

        return view('admin.stock-transfers.create', compact('outlets', 'productsPayload'));
    }

    /**
     * Show the form for editing pending transfer
     */
    public function edit(StockTransfer $stockTransfer)
    {
        if (!$stockTransfer->isPending()) {
            return redirect()->route('admin.stock-transfers.show', $stockTransfer->id)
                ->with('error', 'Transfer hanya bisa diedit saat status masih pending.');
        }

        $stockTransfer->load('items.product');
        $outlets = Outlet::where('is_active', true)->get();
        $productsPayload = $this->buildProductsPayload();

        $prefillItems = $stockTransfer->items->map(function ($item) {
            return [
                'product_id' => (int) $item->product_id,
                'quantity' => (float) $item->quantity,
                'notes' => $item->notes,
            ];
        })->values();

        return view('admin.stock-transfers.create', compact(
            'stockTransfer',
            'outlets',
            'productsPayload',
            'prefillItems'
        ));
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
     * Update pending transfer
     */
    public function update(Request $request, StockTransfer $stockTransfer)
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
            $transfer = $this->transferService->updateTransfer($stockTransfer, $request->all());

            return redirect()->route('admin.stock-transfers.show', $transfer->id)
                ->with('success', 'Transfer stok berhasil diperbarui.');
        } catch (\Exception $e) {
            return back()->withInput()
                ->with('error', 'Gagal memperbarui transfer: ' . $e->getMessage());
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

        [
            $itemHppMap,
            $transferNominalTotal,
            $transferBillingNominalTotal,
            $valuationSource
        ] = $this->buildTransferValuation($stockTransfer);
        $discrepancySummary = $this->buildDiscrepancySummary($stockTransfer, $itemHppMap);

        return view('admin.stock-transfers.show', compact(
            'stockTransfer',
            'itemHppMap',
            'transferNominalTotal',
            'transferBillingNominalTotal',
            'valuationSource',
            'discrepancySummary'
        ));
    }

    /**
     * Print transfer document for shipment / inter-outlet billing
     */
    public function print(StockTransfer $stockTransfer)
    {
        $stockTransfer->load([
            'items.product',
            'fromOutlet',
            'toOutlet',
            'creator',
        ]);

        [
            $itemHppMap,
            $transferNominalTotal,
            $transferBillingNominalTotal,
            $valuationSource
        ] = $this->buildTransferValuation($stockTransfer);
        $discrepancySummary = $this->buildDiscrepancySummary($stockTransfer, $itemHppMap);

        return view('admin.stock-transfers.print', compact(
            'stockTransfer',
            'itemHppMap',
            'transferNominalTotal',
            'transferBillingNominalTotal',
            'valuationSource',
            'discrepancySummary'
        ));
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
            Log::warning('Stock transfer send failed', [
                'transfer_id' => $stockTransfer->id,
                'transfer_number' => $stockTransfer->transfer_number,
                'status' => $stockTransfer->status,
                'user_id' => auth()->id(),
                'error' => $e->getMessage(),
            ]);

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

    /**
     * Build transfer valuation map.
     * Priority:
     * - actual from transfer_out mutation (if transfer already sent)
     * - estimated from current avg cost of source outlet (if still draft)
     */
    private function buildTransferValuation(StockTransfer $stockTransfer): array
    {
        $mutationRows = StockMutation::query()
            ->select(
                'product_id',
                DB::raw('SUM(ABS(quantity)) as total_qty'),
                DB::raw('SUM(COALESCE(total_cost, ABS(quantity) * COALESCE(unit_cost, 0))) as total_nominal')
            )
            ->where('reference_type', 'stock_transfer')
            ->where('reference_id', $stockTransfer->id)
            ->where('mutation_type', 'transfer_out')
            ->where('outlet_id', $stockTransfer->from_outlet_id)
            ->groupBy('product_id')
            ->get()
            ->keyBy('product_id');

        $itemHppMap = collect();
        $hasEstimated = false;
        $hasActual = false;
        $shipmentTotal = 0.0;
        $billingTotal = 0.0;

        foreach ($stockTransfer->items as $item) {
            $row = $mutationRows->get($item->product_id);
            $sentQty = (float) $item->quantity;
            $receivedQty = is_null($item->received_quantity) ? 0.0 : (float) $item->received_quantity;

            if ($row) {
                $totalQty = (float) $row->total_qty;
                $totalNominal = (float) $row->total_nominal;
                $unitHpp = $totalQty > 0 ? ($totalNominal / $totalQty) : 0;
                $source = 'actual';
                $hasActual = true;
            } else {
                $unitHpp = (float) $this->costService->getAvgCost((int) $item->product_id, (int) $stockTransfer->from_outlet_id);
                $source = 'estimated';
                $hasEstimated = true;
            }

            $shipmentNominal = $unitHpp * $sentQty;
            $billingNominal = $stockTransfer->isReceived() ? ($unitHpp * $receivedQty) : $shipmentNominal;
            $billedQty = $stockTransfer->isReceived() ? $receivedQty : $sentQty;

            $shipmentTotal += $shipmentNominal;
            $billingTotal += $billingNominal;

            $itemHppMap->put((int) $item->product_id, [
                'unit_hpp' => $unitHpp,
                'sent_qty' => $sentQty,
                'received_qty' => $receivedQty,
                'billed_qty' => $billedQty,
                'shipment_nominal' => $shipmentNominal,
                'billing_nominal' => $billingNominal,
                'total_nominal' => $billingNominal,
                'source' => $source,
            ]);
        }

        $valuationSource = $hasEstimated && $hasActual
            ? 'mixed'
            : ($hasEstimated ? 'estimated' : 'actual');

        return [$itemHppMap, (float) $shipmentTotal, (float) $billingTotal, $valuationSource];
    }

    private function buildProductsPayload()
    {
        return Product::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'sku', 'unit', 'product_type'])
            ->map(function (Product $product) {
                return [
                    'id' => (int) $product->id,
                    'name' => (string) $product->name,
                    'sku' => $product->sku,
                    'unit' => $product->unit,
                    'product_type' => $product->product_type,
                ];
            })
            ->values();
    }

    private function buildDiscrepancySummary(StockTransfer $stockTransfer, $itemHppMap): array
    {
        $summary = [
            'has_discrepancy' => false,
            'item_count' => 0,
            'shortage_qty' => 0.0,
            'excess_qty' => 0.0,
            'shortage_nominal' => 0.0,
            'excess_nominal' => 0.0,
        ];

        if (!$stockTransfer->isReceived()) {
            return $summary;
        }

        foreach ($stockTransfer->items as $item) {
            $sentQty = (float) $item->quantity;
            $receivedQty = (float) ($item->received_quantity ?? 0);
            $diff = $receivedQty - $sentQty;

            if (abs($diff) < 0.00001) {
                continue;
            }

            $summary['has_discrepancy'] = true;
            $summary['item_count']++;

            $unitHpp = (float) (($itemHppMap[$item->product_id]['unit_hpp'] ?? 0));

            if ($diff < 0) {
                $shortageQty = abs($diff);
                $summary['shortage_qty'] += $shortageQty;
                $summary['shortage_nominal'] += $shortageQty * $unitHpp;
            } else {
                $summary['excess_qty'] += $diff;
                $summary['excess_nominal'] += $diff * $unitHpp;
            }
        }

        return $summary;
    }

    private function buildFilteredTransfersQuery(Request $request)
    {
        $query = StockTransfer::with(['fromOutlet', 'toOutlet', 'creator', 'items']);

        if ($request->filled('from_outlet_id')) {
            $query->where('from_outlet_id', $request->from_outlet_id);
        }

        if ($request->filled('to_outlet_id')) {
            $query->where('to_outlet_id', $request->to_outlet_id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('start_date')) {
            $query->where('transfer_date', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->where('transfer_date', '<=', $request->end_date);
        }

        if ($request->filled('search')) {
            $query->where('transfer_number', 'like', '%' . $request->search . '%');
        }

        return $query;
    }

    private function getTransferNominals($transferIds)
    {
        $transferIds = collect($transferIds)->filter()->values();

        if ($transferIds->isEmpty()) {
            return collect();
        }

        return StockMutation::query()
            ->select(
                'reference_id',
                DB::raw('SUM(COALESCE(total_cost, ABS(quantity) * COALESCE(unit_cost, 0))) as nominal_hpp')
            )
            ->where('reference_type', 'stock_transfer')
            ->where('mutation_type', 'transfer_out')
            ->whereIn('reference_id', $transferIds)
            ->groupBy('reference_id')
            ->pluck('nominal_hpp', 'reference_id');
    }
}

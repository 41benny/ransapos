<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PackagingAdjustment;
use App\Services\CashSessionService;
use App\Services\PackagingService;
use Illuminate\Http\Request;

class PackagingAdjustmentController extends Controller
{
    protected CashSessionService $cashSessionService;

    protected PackagingService $packagingService;

    public function __construct(CashSessionService $cashSessionService, PackagingService $packagingService)
    {
        $this->cashSessionService = $cashSessionService;
        $this->packagingService = $packagingService;
    }

    /**
     * Form adjustment + riwayat adjustment shift berjalan.
     */
    public function index()
    {
        $activeSession = $this->cashSessionService->getActiveSessionFor();

        if (! $activeSession) {
            return redirect()
                ->route('pos.dashboard')
                ->with('warning', 'Tidak ada shift aktif. Buka shift terlebih dahulu untuk membuat adjustment packaging.');
        }

        $packagingItems = $this->packagingService->activeItems();

        $adjustments = PackagingAdjustment::where('cash_session_id', $activeSession->id)
            ->with(['packagingItem', 'requestedBy'])
            ->orderByDesc('id')
            ->get();

        return view('pos.packaging.adjustment', compact('activeSession', 'packagingItems', 'adjustments'));
    }

    /**
     * Simpan request adjustment (status pending).
     */
    public function store(Request $request)
    {
        $activeSession = $this->cashSessionService->getActiveSessionFor();

        if (! $activeSession) {
            return redirect()
                ->route('pos.dashboard')
                ->with('warning', 'Tidak ada shift aktif.');
        }

        $validated = $request->validate([
            'packaging_item_id' => 'required|exists:packaging_items,id',
            'type' => 'required|in:in,out',
            'qty' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:150',
            'note' => 'nullable|string|max:500',
        ], [], [
            'packaging_item_id' => 'item packaging',
            'qty' => 'jumlah',
            'reason' => 'alasan',
        ]);

        PackagingAdjustment::create([
            'cash_session_id' => $activeSession->id,
            'outlet_id' => $activeSession->outlet_id,
            'packaging_item_id' => $validated['packaging_item_id'],
            'type' => $validated['type'],
            'qty' => $validated['qty'],
            'reason' => $validated['reason'],
            'note' => $validated['note'] ?? null,
            'status' => 'pending',
            'requested_by' => auth()->id(),
        ]);

        return redirect()
            ->route('pos.packaging.adjustment.index')
            ->with('success', 'Adjustment packaging dikirim dan menunggu approval backoffice.');
    }
}

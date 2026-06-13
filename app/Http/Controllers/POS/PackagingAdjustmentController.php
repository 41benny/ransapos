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
            'type' => 'required|in:in,out',
            'reason' => 'required|string|max:150',
            'note' => 'nullable|string|max:500',
            'items' => 'required|array',
            'items.*' => 'nullable|numeric|min:0',
        ], [], [
            'reason' => 'alasan',
        ]);

        // Ambil hanya item dengan qty > 0 dan id yang valid.
        $validItemIds = \App\Models\PackagingItem::pluck('id')->all();
        $rows = collect($validated['items'])
            ->filter(fn ($qty, $itemId) => (float) $qty > 0 && in_array((int) $itemId, $validItemIds, true));

        if ($rows->isEmpty()) {
            return back()->withInput()->with('error', 'Isi jumlah minimal satu item packaging.');
        }

        foreach ($rows as $itemId => $qty) {
            PackagingAdjustment::create([
                'cash_session_id' => $activeSession->id,
                'outlet_id' => $activeSession->outlet_id,
                'packaging_item_id' => (int) $itemId,
                'type' => $validated['type'],
                'qty' => (float) $qty,
                'reason' => $validated['reason'],
                'note' => $validated['note'] ?? null,
                'status' => 'pending',
                'requested_by' => auth()->id(),
            ]);
        }

        $typeLabel = $validated['type'] === 'in' ? 'masuk' : 'keluar';

        return redirect()
            ->route('pos.packaging.adjustment.index')
            ->with('success', $rows->count() . " item ({$typeLabel}) dikirim dan menunggu approval backoffice.");
    }
}

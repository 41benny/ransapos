<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\PackagingAdjustment;
use App\Models\PackagingItem;
use App\Services\PackagingService;
use Illuminate\Http\Request;

class PackagingAdjustmentController extends Controller
{
    protected PackagingService $packagingService;

    public function __construct(PackagingService $packagingService)
    {
        $this->packagingService = $packagingService;
    }

    public function index(Request $request)
    {
        $query = PackagingAdjustment::query()
            ->with(['packagingItem', 'outlet', 'cashSession', 'requestedBy', 'approvedBy', 'rejectedBy']);

        if ($status = $request->get('status')) {
            $query->where('status', $status);
        }
        if ($type = $request->get('type')) {
            $query->where('type', $type);
        }
        if ($outletId = $request->get('outlet_id')) {
            $query->where('outlet_id', $outletId);
        }
        if ($itemId = $request->get('packaging_item_id')) {
            $query->where('packaging_item_id', $itemId);
        }
        if ($from = $request->get('date_from')) {
            $query->whereDate('created_at', '>=', $from);
        }
        if ($to = $request->get('date_to')) {
            $query->whereDate('created_at', '<=', $to);
        }

        // Default: tampilkan pending dahulu.
        if (! $request->hasAny(['status', 'type', 'outlet_id', 'packaging_item_id', 'date_from', 'date_to'])) {
            $query->where('status', 'pending');
            $defaultPending = true;
        }

        $adjustments = $query->orderByRaw("FIELD(status,'pending','approved','rejected')")
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $outlets = Outlet::orderBy('name')->get();
        $packagingItems = PackagingItem::ordered()->get();
        $pendingCount = PackagingAdjustment::where('status', 'pending')->count();

        return view('admin.packaging-adjustments.index', compact(
            'adjustments', 'outlets', 'packagingItems', 'pendingCount'
        ) + ['defaultPending' => $defaultPending ?? false]);
    }

    public function approve(PackagingAdjustment $packagingAdjustment)
    {
        $this->packagingService->approveAdjustment($packagingAdjustment, auth()->user());

        return back()->with('success', 'Adjustment disetujui dan mempengaruhi stok resmi shift.');
    }

    public function reject(PackagingAdjustment $packagingAdjustment)
    {
        $this->packagingService->rejectAdjustment($packagingAdjustment, auth()->user());

        return back()->with('success', 'Adjustment ditolak.');
    }
}

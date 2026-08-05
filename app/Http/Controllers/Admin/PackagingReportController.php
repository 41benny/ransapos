<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Services\PackagingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackagingReportController extends Controller
{
    /**
     * Laporan closing packaging per shift per item.
     */
    public function closing(Request $request)
    {
        $applyFilters = function ($query) use ($request) {
            if ($outletId = $request->get('outlet_id')) {
                $query->where('s.outlet_id', $outletId);
            }
            if ($from = $request->get('date_from')) {
                $query->whereDate('s.closed_at', '>=', $from);
            }
            if ($to = $request->get('date_to')) {
                $query->whereDate('s.closed_at', '<=', $to);
            }
            if ($request->boolean('only_diff')) {
                $query->where('c.difference_qty', '!=', 0);
            }

            return $query;
        };

        $query = DB::table('cash_session_packaging_closings as c')
            ->join('cash_sessions as s', 'c.cash_session_id', '=', 's.id')
            ->join('packaging_items as p', 'c.packaging_item_id', '=', 'p.id')
            ->leftJoin('outlets as o', 's.outlet_id', '=', 'o.id')
            ->leftJoin('users as u', 's.user_id', '=', 'u.id')
            ->select(
                's.id as session_id',
                's.session_number',
                's.closed_at',
                'o.name as outlet_name',
                'u.name as kasir_name',
                'p.name as item_name',
                'c.opening_qty',
                'c.approved_adjustment_in_qty',
                'c.approved_adjustment_out_qty',
                'c.pending_adjustment_in_qty',
                'c.pending_adjustment_out_qty',
                'c.closing_physical_qty',
                'c.actual_used_qty',
                'c.estimated_sales_used_qty',
                'c.difference_qty'
            );

        $applyFilters($query);

        $rows = $query->orderByDesc('s.closed_at')
            ->orderBy('p.sort_order')
            ->paginate(50)
            ->withQueryString();

        $outlets = Outlet::orderBy('name')->get();
        $dimsumSummary = $this->buildDimsumPiecesSummary($applyFilters);

        return view('admin.packaging-reports.closing', compact('rows', 'outlets', 'dimsumSummary'));
    }

    /**
     * Ringkasan total pcs dimsum yang laku pada rentang & outlet terfilter.
     *
     * Diambil lewat query agregat TERPISAH, bukan dari $rows, karena $rows
     * dipaginasi 50 baris — menjumlah dari sana hanya akan menghitung halaman
     * yang sedang dibuka dan menghasilkan total yang salah tanpa terlihat salah.
     *
     * Basisnya `estimated_sales_used_qty` (box yang laku menurut penjualan),
     * bukan `actual_used_qty` (box yang berkurang secara fisik), karena yang
     * ditanyakan adalah berapa dimsum yang TERJUAL.
     *
     * @param  callable  $applyFilters
     * @return array{total_pieces: int, breakdown: array<int, array{item_name: string, pieces_per_box: int, boxes: float, pieces: int}>}
     */
    private function buildDimsumPiecesSummary(callable $applyFilters): array
    {
        $query = DB::table('cash_session_packaging_closings as c')
            ->join('cash_sessions as s', 'c.cash_session_id', '=', 's.id')
            ->join('packaging_items as p', 'c.packaging_item_id', '=', 'p.id')
            ->select('p.name as item_name', DB::raw('SUM(c.estimated_sales_used_qty) as boxes'))
            ->groupBy('p.name');

        $applyFilters($query);

        $breakdown = [];
        $totalPieces = 0;

        foreach ($query->get() as $row) {
            // Aturan penamaan box dipakai bersama dengan layar closing kasir
            // supaya kedua tempat tidak pernah menghasilkan angka berbeda.
            $piecesPerBox = PackagingService::piecesPerBoxFromName($row->item_name);
            if ($piecesPerBox === null) {
                continue;
            }

            $boxes = (float) $row->boxes;
            if ($boxes <= 0) {
                continue;
            }

            $pieces = (int) round($boxes * $piecesPerBox);
            $totalPieces += $pieces;

            $breakdown[] = [
                'item_name' => (string) $row->item_name,
                'pieces_per_box' => $piecesPerBox,
                'boxes' => $boxes,
                'pieces' => $pieces,
            ];
        }

        usort($breakdown, fn ($a, $b) => $a['pieces_per_box'] <=> $b['pieces_per_box']);

        return [
            'total_pieces' => $totalPieces,
            'breakdown' => $breakdown,
        ];
    }

    /**
     * Laporan produk terjual yang belum punya mapping packaging.
     */
    public function unmapped(Request $request)
    {
        $query = DB::table('sale_items as si')
            ->join('sales as sa', 'si.sale_id', '=', 'sa.id')
            ->join('cash_sessions as s', 'sa.cash_session_id', '=', 's.id')
            ->leftJoin('outlets as o', 's.outlet_id', '=', 'o.id')
            ->leftJoin('users as u', 's.user_id', '=', 'u.id')
            ->leftJoin('products as pr', 'si.product_id', '=', 'pr.id')
            ->leftJoin('product_categories as pc', 'pr.category_id', '=', 'pc.id')
            ->where('sa.status', 'completed')
            ->whereNotExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('product_packaging_mappings as ppm')
                    ->whereColumn('ppm.product_id', 'si.product_id')
                    ->where('ppm.is_active', true);
            });

        if ($outletId = $request->get('outlet_id')) {
            $query->where('s.outlet_id', $outletId);
        }
        if ($from = $request->get('date_from')) {
            $query->whereDate('sa.created_at', '>=', $from);
        }
        if ($to = $request->get('date_to')) {
            $query->whereDate('sa.created_at', '<=', $to);
        }

        $rows = $query->groupBy(
                's.id', 's.session_number', 's.business_date', 'o.name', 'u.name',
                'si.product_id', 'si.product_name', 'si.product_sku', 'pc.name'
            )
            ->select(
                's.session_number',
                's.business_date',
                'o.name as outlet_name',
                'u.name as kasir_name',
                'si.product_id',
                'si.product_name',
                'si.product_sku',
                'pc.name as category',
                DB::raw('SUM(si.quantity) as qty_sold')
            )
            ->orderByDesc('s.business_date')
            ->orderByDesc('qty_sold')
            ->paginate(50)
            ->withQueryString();

        $outlets = Outlet::orderBy('name')->get();

        return view('admin.packaging-reports.unmapped', compact('rows', 'outlets'));
    }
}

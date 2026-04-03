<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\CashSession;
use App\Models\Sale;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Tampilkan dashboard POS (kasir)
     */
    public function index()
    {
        // Cek cash session aktif untuk user yang login
        $activeSession = CashSession::where('status', 'open')
            ->where('user_id', auth()->id())
            ->where('outlet_id', auth()->user()->outlet_id)
            ->orderBy('opened_at', 'desc')
            ->first();

        $todaySales = null;
        $todaySalesCount = 0;
        $todaySalesTotalAmount = 0;
        $todaySalesAverageAmount = 0;
        if ($activeSession) {
            $todaySalesQuery = Sale::query()
                ->with([
                    'items:id,sale_id,product_name',
                    'payments.paymentMethod',
                ])
                ->where('cash_session_id', $activeSession->id)
                ->where('status', 'completed')
                ->orderByDesc('created_at');

            $summary = (clone $todaySalesQuery)
                ->selectRaw('COUNT(*) as total_sales')
                ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
                ->selectRaw('COALESCE(AVG(total_amount), 0) as average_amount')
                ->first();

            $todaySalesCount = (int) ($summary->total_sales ?? 0);
            $todaySalesTotalAmount = (float) ($summary->total_amount ?? 0);
            $todaySalesAverageAmount = (float) ($summary->average_amount ?? 0);

            $todaySales = $todaySalesQuery
                ->paginate(20, ['*'], 'sales_page')
                ->withQueryString();
        }

        return view('pos.dashboard', compact(
            'activeSession',
            'todaySales',
            'todaySalesCount',
            'todaySalesTotalAmount',
            'todaySalesAverageAmount'
        ));
    }
}

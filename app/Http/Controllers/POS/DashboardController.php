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
        if ($activeSession) {
            $todaySales = Sale::where('cash_session_id', $activeSession->id)
                ->where('status', 'completed')
                ->get();
        }

        return view('pos.dashboard', compact('activeSession', 'todaySales'));
    }
}

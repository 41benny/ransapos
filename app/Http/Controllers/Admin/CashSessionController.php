<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\User;
use App\Services\CashSessionService;
use Illuminate\Http\Request;

class CashSessionController extends Controller
{
    protected CashSessionService $cashSessionService;

    public function __construct(CashSessionService $cashSessionService)
    {
        $this->cashSessionService = $cashSessionService;
    }

    /**
     * Tampilkan riwayat cash sessions
     */
    public function index(Request $request)
    {
        $filters = $request->only(['outlet_id', 'user_id', 'status', 'date_from', 'date_to']);
        
        $sessions = $this->cashSessionService->getSessionHistory($filters);
        
        // Data untuk filter
        $outlets = Outlet::where('is_active', true)->get();
        $users = User::whereHas('role', function($query) {
            $query->whereIn('name', ['kasir', 'admin']);
        })->get();

        return view('admin.cash-sessions.index', compact('sessions', 'outlets', 'users', 'filters'));
    }
}

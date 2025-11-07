<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\OpenCashSessionRequest;
use App\Http\Requests\POS\CloseCashSessionRequest;
use App\Models\CashSession;
use App\Models\Outlet;
use App\Services\CashSessionService;
use Exception;

class CashSessionController extends Controller
{
    protected CashSessionService $cashSessionService;

    public function __construct(CashSessionService $cashSessionService)
    {
        $this->cashSessionService = $cashSessionService;
    }

    /**
     * Tampilkan form buka shift
     */
    public function open()
    {
        // Cek apakah sudah ada session aktif
        $activeSession = $this->cashSessionService->getActiveSessionFor();
        
        if ($activeSession) {
            return redirect()
                ->route('pos.dashboard')
                ->with('warning', 'Anda sudah memiliki shift yang aktif. Tutup shift terlebih dahulu.');
        }

        // Gunakan outlet dari user yang login
        $userOutlet = auth()->user()->outlet;

        return view('pos.sessions.open', compact('userOutlet'));
    }

    /**
     * Proses buka shift
     */
    public function store(OpenCashSessionRequest $request)
    {
        try {
            $session = $this->cashSessionService->openSession($request->validated());

            return redirect()
                ->route('pos.dashboard')
                ->with('success', "Shift berhasil dibuka! Session: {$session->session_number}");

        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal membuka shift: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan form tutup shift
     */
    public function close()
    {
        // Ambil session aktif
        $activeSession = $this->cashSessionService->getActiveSessionFor();
        
        if (!$activeSession) {
            return redirect()
                ->route('pos.dashboard')
                ->with('warning', 'Tidak ada shift aktif yang bisa ditutup.');
        }

        // Load sales untuk ringkasan
        $activeSession->load(['sales' => function($query) {
            $query->where('status', 'completed')
                ->with('payments.paymentMethod');
        }]);

        return view('pos.sessions.close', compact('activeSession'));
    }

    /**
     * Proses tutup shift
     */
    public function closeStore(CloseCashSessionRequest $request, CashSession $cashSession)
    {
        try {
            $session = $this->cashSessionService->closeSession(
                $cashSession,
                $request->actual_balance,
                $request->notes
            );

            $diffMessage = '';
            if ($session->difference > 0) {
                $diffMessage = ' (Lebih Rp ' . number_format($session->difference, 0, ',', '.') . ')';
            } elseif ($session->difference < 0) {
                $diffMessage = ' (Kurang Rp ' . number_format(abs($session->difference), 0, ',', '.') . ')';
            }

            return redirect()
                ->route('pos.dashboard')
                ->with('success', "Shift berhasil ditutup!{$diffMessage}");

        } catch (Exception $e) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Gagal menutup shift: ' . $e->getMessage());
        }
    }
}

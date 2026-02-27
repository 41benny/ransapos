<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\OpenCashSessionRequest;
use App\Http\Requests\POS\CloseCashSessionRequest;
use App\Models\CashSession;
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
            $posDevice = $request->attributes->get('pos_device');

            $session = $this->cashSessionService->openSession(
                $request->validated(),
                auth()->user(),
                $posDevice?->id,
                $request->ip()
            );

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

        // Calculate Payment Method Stats
        $paymentStats = [];
        foreach ($activeSession->sales as $sale) {
            foreach ($sale->payments as $payment) {
                $methodName = $payment->paymentMethod->name ?? 'Unknown';
                if (!isset($paymentStats[$methodName])) {
                    $paymentStats[$methodName] = [
                        'name' => $methodName,
                        'count' => 0,
                        'total' => 0
                    ];
                }
                $paymentStats[$methodName]['count']++;
                $paymentStats[$methodName]['total'] += $payment->amount;
            }
        }

        return view('pos.sessions.close', compact('activeSession', 'paymentStats'));
    }

    /**
     * Cetak Laporan Shift (Thermal)
     */
    public function print(CashSession $cashSession)
    {
        // Pastikan user punya akses ke session outlet ini
        if ($cashSession->outlet_id !== auth()->user()->outlet_id && !auth()->user()->hasRole(['admin', 'manager', 'superadmin'])) {
            abort(403);
        }

        $cashSession->load(['outlet', 'user', 'sales' => function($q) {
            $q->where('status', 'completed')->with(['payments.paymentMethod', 'items.product.category']);
        }]);

        $stats = $this->buildSessionReportStats($cashSession);

        return view('pos.sessions.print', array_merge(['session' => $cashSession], $stats));
    }

    /**
     * Proses tutup shift
     */
    public function closeStore(CloseCashSessionRequest $request, CashSession $cashSession)
    {
        try {
            $posDevice = $request->attributes->get('pos_device');

            $session = $this->cashSessionService->closeSession(
                $cashSession,
                $request->actual_balance,
                $request->notes,
                $posDevice?->id,
                $request->ip()
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

    /**
     * Bangun data ringkasan laporan shift untuk print thermal.
     *
     * @return array{
     *   paymentStats: array<int, array{name: string, count: int, total: float}>,
     *   salesTypeStats: array<int, array{name: string, count: int, total: float}>,
     *   categoryStats: array<int, array{name: string, qty: float, total: float}>,
     *   productStats: array<int, array{name: string, sku: string, unit: string, qty: float, total: float}>
     * }
     */
    protected function buildSessionReportStats(CashSession $session): array
    {
        $salesTypeLabels = config('sales.price_levels', []);

        $paymentStats = [];
        $salesTypeStats = [];
        $categoryStats = [];
        $productStats = [];

        foreach ($session->sales as $sale) {
            $rawSalesType = strtolower(trim((string) ($sale->sales_type ?? '')));
            $salesTypeKey = $rawSalesType !== '' ? $rawSalesType : 'regular';
            $salesTypeName = $salesTypeLabels[$salesTypeKey] ?? ucfirst(str_replace('_', ' ', $salesTypeKey));

            if (!isset($salesTypeStats[$salesTypeKey])) {
                $salesTypeStats[$salesTypeKey] = [
                    'name' => $salesTypeName,
                    'count' => 0,
                    'total' => 0,
                ];
            }
            $salesTypeStats[$salesTypeKey]['count']++;
            $salesTypeStats[$salesTypeKey]['total'] += (float) $sale->total_amount;

            foreach ($sale->payments as $payment) {
                $methodName = $payment->paymentMethod->name ?? 'Unknown';

                if (!isset($paymentStats[$methodName])) {
                    $paymentStats[$methodName] = [
                        'name' => $methodName,
                        'count' => 0,
                        'total' => 0,
                    ];
                }

                $paymentStats[$methodName]['count']++;
                $paymentStats[$methodName]['total'] += (float) $payment->amount;
            }

            foreach ($sale->items as $item) {
                $categoryName = $item->product?->category?->name ?? 'Tanpa Kategori';

                if (!isset($categoryStats[$categoryName])) {
                    $categoryStats[$categoryName] = [
                        'name' => $categoryName,
                        'qty' => 0,
                        'total' => 0,
                    ];
                }
                $categoryStats[$categoryName]['qty'] += (float) $item->quantity;
                $categoryStats[$categoryName]['total'] += (float) $item->subtotal;

                $productSku = trim((string) ($item->product_sku ?? ''));
                $productName = (string) ($item->product_name ?? $item->product?->name ?? 'Item');
                $productUnit = trim((string) ($item->product?->unit ?? ''));
                $productKey = $productSku !== ''
                    ? 'SKU:' . strtoupper($productSku)
                    : (string) ($item->product_id ?? strtolower($productName));

                if (!isset($productStats[$productKey])) {
                    $productStats[$productKey] = [
                        'name' => $productName,
                        'sku' => $productSku,
                        'unit' => $productUnit,
                        'qty' => 0,
                        'total' => 0,
                    ];
                }

                if ($productStats[$productKey]['unit'] === '' && $productUnit !== '') {
                    $productStats[$productKey]['unit'] = $productUnit;
                }

                $productStats[$productKey]['qty'] += (float) $item->quantity;
                $productStats[$productKey]['total'] += (float) $item->subtotal;
            }
        }

        $sortByTotalDesc = static function (array $stats): array {
            return collect($stats)->sortByDesc('total')->values()->all();
        };

        $paymentStats = $sortByTotalDesc($paymentStats);
        $salesTypeStats = $sortByTotalDesc($salesTypeStats);
        $categoryStats = $sortByTotalDesc($categoryStats);
        $productStats = collect($productStats)
            ->sortBy(fn (array $item) => strtolower($item['name']))
            ->values()
            ->all();

        return compact('paymentStats', 'salesTypeStats', 'categoryStats', 'productStats');
    }
}

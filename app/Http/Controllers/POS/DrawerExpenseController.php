<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\StoreDrawerExpenseRequest;
use App\Models\CashSessionExpense;
use App\Services\CashSessionService;
use App\Services\PosDrawerExpenseService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Pengeluaran Kios — uang tunai yang keluar dari LACI kasir.
 *
 * Berbeda dari PettyCashController yang memotong akun kas kecil terpisah.
 * Lihat docs/PRD-POS-PENGELUARAN-KIOS-PETTY-CASH.md.
 */
class DrawerExpenseController extends Controller
{
    public function __construct(
        protected PosDrawerExpenseService $expenseService,
        protected CashSessionService $cashSessionService,
    ) {
    }

    public function index()
    {
        if (! $this->expenseService->isEnabled()) {
            return redirect()
                ->route('pos.dashboard')
                ->with('warning', 'Modul Pengeluaran Kios belum diaktifkan. Hubungi admin.');
        }

        $session = $this->cashSessionService->getActiveSessionFor();

        if (! $session) {
            return redirect()
                ->route('pos.dashboard')
                ->with('warning', 'Buka shift terlebih dahulu sebelum mencatat pengeluaran.');
        }

        $expenses = $this->expenseService->expensesFor($session, includeVoided: true);
        $onHand = $this->expenseService->currentDrawerOnHand($session);

        return view('pos.expenses.index', [
            'session' => $session,
            'expenses' => $expenses,
            'onHand' => $onHand,
            'totalCashExpense' => (float) $session->total_cash_expense,
            'totalNoncashExpense' => (float) $session->total_noncash_expense,
        ]);
    }

    public function create()
    {
        if (! $this->expenseService->isEnabled()) {
            return redirect()->route('pos.dashboard');
        }

        $session = $this->cashSessionService->getActiveSessionFor();

        if (! $session) {
            return redirect()
                ->route('pos.dashboard')
                ->with('warning', 'Buka shift terlebih dahulu sebelum mencatat pengeluaran.');
        }

        return view('pos.expenses.create', [
            'session' => $session,
            'coaAccounts' => $this->expenseService->selectableCoaAccounts(),
            'onHand' => $this->expenseService->currentDrawerOnHand($session),
            'paymentSources' => CashSessionExpense::selectablePaymentSources(),
            'sourceAccounts' => $this->expenseService->selectableSourceAccounts((int) $session->outlet_id),
            'maxPerTransaction' => $this->expenseService->maxPerTransaction(),
            // Digenerate saat form dibuka, bukan saat submit, supaya klik ganda
            // pada form yang sama membawa kunci yang sama (TEK-01).
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function store(StoreDrawerExpenseRequest $request)
    {
        if (! $this->expenseService->isEnabled()) {
            return redirect()->route('pos.dashboard');
        }

        $session = $this->cashSessionService->getActiveSessionFor();

        if (! $session) {
            return redirect()
                ->route('pos.dashboard')
                ->with('warning', 'Shift tidak aktif. Pengeluaran tidak disimpan.');
        }

        $user = auth()->user();
        $allowOverLimit = $user->hasPermission('pos.expense.override_limit')
            || $user->hasRole(['admin', 'manager', 'superadmin']);

        try {
            $expense = $this->expenseService->record(
                $session,
                $request->validated(),
                $user,
                $allowOverLimit
            );

            return redirect()
                ->route('pos.expenses.index')
                ->with('success', 'Pengeluaran Rp ' . number_format((float) $expense->amount, 0, ',', '.') . ' tercatat.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    public function void(Request $request, CashSessionExpense $expense)
    {
        $validated = $request->validate([
            'void_reason' => 'required|string|max:200',
        ], [
            'void_reason.required' => 'Alasan pembatalan wajib diisi.',
        ]);

        $session = $this->cashSessionService->getActiveSessionFor();

        // Kasir hanya boleh membatalkan baris di shift-nya sendiri yang masih
        // terbuka. Tanpa cek ini, id di URL bisa diganti ke shift orang lain.
        if (! $session || (int) $expense->cash_session_id !== (int) $session->id) {
            return redirect()
                ->route('pos.expenses.index')
                ->with('error', 'Pengeluaran ini bukan milik shift Anda yang sedang berjalan.');
        }

        try {
            $this->expenseService->void($expense, auth()->user(), $validated['void_reason']);

            return redirect()
                ->route('pos.expenses.index')
                ->with('success', 'Pengeluaran dibatalkan. Baris aslinya tetap tersimpan sebagai jejak audit.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

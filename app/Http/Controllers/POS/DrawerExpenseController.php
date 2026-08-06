<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\StoreDrawerCashInRequest;
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

        // Kas masuk dan pengeluaran ditampilkan berdampingan supaya kasir bisa
        // menelusuri urutan uang di laci dalam satu layar.
        $rows = $this->expenseService->ledgerFor($session);
        $onHand = $this->expenseService->currentDrawerOnHand($session);

        return view('pos.expenses.index', [
            'session' => $session,
            'expenses' => $rows,
            'onHand' => $onHand,
            'totalCashExpense' => (float) $session->total_cash_expense,
            'totalNoncashExpense' => (float) $session->total_noncash_expense,
            'totalCashIn' => (float) $session->total_cash_in,
            'canCashIn' => $this->userCanCashIn(),
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

    /**
     * Form kas masuk laci — modal tambahan dari pemilik atau ambilan kas besar.
     */
    public function createCashIn()
    {
        if (! $this->expenseService->isEnabled()) {
            return redirect()->route('pos.dashboard');
        }

        if (! $this->userCanCashIn()) {
            return redirect()
                ->route('pos.expenses.index')
                ->with('error', 'Anda tidak punya izin mencatat kas masuk laci.');
        }

        $session = $this->cashSessionService->getActiveSessionFor();

        if (! $session) {
            return redirect()
                ->route('pos.dashboard')
                ->with('warning', 'Buka shift terlebih dahulu sebelum mencatat kas masuk.');
        }

        return view('pos.expenses.cash-in', [
            'session' => $session,
            'onHand' => $this->expenseService->currentDrawerOnHand($session),
            'sourceAccounts' => $this->expenseService->selectableCashInSources((int) $session->outlet_id),
            'idempotencyKey' => (string) Str::uuid(),
        ]);
    }

    public function storeCashIn(StoreDrawerCashInRequest $request)
    {
        if (! $this->expenseService->isEnabled()) {
            return redirect()->route('pos.dashboard');
        }

        if (! $this->userCanCashIn()) {
            return redirect()
                ->route('pos.expenses.index')
                ->with('error', 'Anda tidak punya izin mencatat kas masuk laci.');
        }

        $session = $this->cashSessionService->getActiveSessionFor();

        if (! $session) {
            return redirect()
                ->route('pos.dashboard')
                ->with('warning', 'Shift tidak aktif. Kas masuk tidak disimpan.');
        }

        try {
            $cashIn = $this->expenseService->recordCashIn(
                $session,
                $request->validated(),
                auth()->user()
            );

            return redirect()
                ->route('pos.expenses.index')
                ->with('success', 'Kas masuk Rp ' . number_format((float) $cashIn->amount, 0, ',', '.') . ' tercatat.');
        } catch (Exception $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Kas masuk dipisahkan dari izin input pengeluaran karena ini satu-satunya
     * jalur uang MASUK ke laci di luar penjualan — kalau perlu dikunci ke
     * supervisor saja, permissionnya bisa dicabut dari role kasir tanpa ikut
     * mematikan input pengeluaran sehari-hari.
     */
    protected function userCanCashIn(): bool
    {
        $user = auth()->user();

        return $user
            && ($user->hasPermission('pos.session.cash_in')
                || $user->hasRole(['admin', 'manager', 'superadmin']));
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
                ->with('error', 'Baris ini bukan milik shift Anda yang sedang berjalan.');
        }

        // Pembatalan kas masuk mengurangi uang yang diakui ada di laci, jadi
        // dikunci ke pemegang izin yang sama dengan yang boleh mencatatnya.
        if ($expense->isCashIn() && ! $this->userCanCashIn()) {
            return redirect()
                ->route('pos.expenses.index')
                ->with('error', 'Anda tidak punya izin membatalkan kas masuk laci.');
        }

        try {
            $this->expenseService->void($expense, auth()->user(), $validated['void_reason']);

            return redirect()
                ->route('pos.expenses.index')
                ->with('success', ($expense->isCashIn() ? 'Kas masuk' : 'Pengeluaran')
                    . ' dibatalkan. Baris aslinya tetap tersimpan sebagai jejak audit.');
        } catch (Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}

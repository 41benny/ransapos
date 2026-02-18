<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Http\Requests\POS\StorePettyCashPosRequest;
use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\CoaAccount;
use App\Services\CashAccountService;
use Exception;

class PettyCashController extends Controller
{
    private const DEFAULT_EXPENSE_COA_CODE = '6-135';
    private const LEGACY_DEFAULT_EXPENSE_COA_CODES = [
        '6-190',
        'EXP-OUTLET-LAINNYA',
    ];

    protected CashAccountService $cashAccountService;

    public function __construct(CashAccountService $cashAccountService)
    {
        $this->cashAccountService = $cashAccountService;
    }

    /**
     * Halaman index petty cash POS (monitor saldo + riwayat transaksi).
     */
    public function index()
    {
        $user = auth()->user();
        $outletId = (int) ($user->outlet_id ?? 0);
        $today = now()->toDateString();

        $pettyCashAccount = $this->resolvePettyCashAccount($outletId);

        $transactions = null;

        if ($pettyCashAccount) {
            $transactions = CashTransaction::query()
                ->where('cash_account_id', $pettyCashAccount->id)
                ->where('reference_type', 'petty_cash_pos')
                ->where('type', 'out')
                ->whereDate('transaction_date', $today)
                ->orderBy('transaction_date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate(15)
                ->withQueryString();
        }

        return view('pos.petty-cash.index', compact('pettyCashAccount', 'transactions'));
    }

    /**
     * Form input petty cash POS.
     */
    public function create()
    {
        $user = auth()->user();
        $outletId = (int) ($user->outlet_id ?? 0);

        $pettyCashAccount = $this->resolvePettyCashAccount($outletId);
        $defaultExpenseAccount = $this->resolveDefaultExpenseAccount();

        return view('pos.petty-cash.create', compact('pettyCashAccount', 'defaultExpenseAccount'));
    }

    /**
     * Simpan transaksi petty cash POS.
     */
    public function store(StorePettyCashPosRequest $request)
    {
        $validated = $request->validated();
        $user = auth()->user();
        $outletId = (int) ($user->outlet_id ?? 0);

        $pettyCashAccount = $this->resolvePettyCashAccount($outletId);
        if (! $pettyCashAccount) {
            return back()->withInput()->with('error',
                'Akun petty cash outlet belum disetting. Minta admin set akun kas/bank dengan tipe penggunaan "Petty Cash Outlet".');
        }

        $defaultExpenseAccount = $this->resolveDefaultExpenseAccount();
        if (! $defaultExpenseAccount) {
            return back()->withInput()->with('error',
                'Akun biaya default petty cash belum tersedia. Hubungi admin untuk setup akun "Keperluan Outlet Lainnya".');
        }

        try {
            $rows = $validated['rows'] ?? [];
            $mappedRows = array_map(function (array $row) use ($defaultExpenseAccount) {
                $description = sprintf(
                    'Penerima: %s | %s',
                    trim((string) ($row['recipient_name'] ?? '')),
                    trim((string) ($row['description'] ?? ''))
                );

                return [
                    'coa_account_id' => $defaultExpenseAccount->id,
                    'amount' => (float) ($row['amount'] ?? 0),
                    'description' => $description,
                ];
            }, $rows);

            $batchReferenceId = count($mappedRows) > 1
                ? (int) round(microtime(true) * 1000000)
                : null;

            $transactions = $this->cashAccountService->recordTransactionsBulk([
                'cash_account_id' => $pettyCashAccount->id,
                'coa_account_id' => $defaultExpenseAccount->id,
                'type' => 'out',
                'transaction_date' => $validated['transaction_date'],
                'reference_type' => 'petty_cash_pos',
                'reference_id' => $batchReferenceId,
                'notes' => null,
                'created_by' => auth()->id(),
            ], $mappedRows);

            $count = count($transactions);
            $voucherNumber = $transactions[0]->voucher_number ?? $transactions[0]->transaction_number ?? null;

            return redirect()
                ->route('pos.petty-cash.index')
                ->with('success', $count === 1
                    ? 'Pengeluaran petty cash berhasil disimpan. Nomor voucher: ' . $voucherNumber
                    : 'Pengeluaran petty cash berhasil disimpan. Nomor voucher: ' . $voucherNumber . ' (' . $count . ' baris).');
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal simpan petty cash: ' . $e->getMessage());
        }
    }

    /**
     * Form edit transaksi petty cash POS.
     */
    public function edit(CashTransaction $cashTransaction)
    {
        $user = auth()->user();
        $outletId = (int) ($user->outlet_id ?? 0);

        $pettyCashAccount = $this->resolvePettyCashAccount($outletId);
        if (! $this->isEditablePettyCashTransaction($cashTransaction, $pettyCashAccount)) {
            return redirect()
                ->route('pos.petty-cash.index')
                ->with('error', 'Transaksi petty cash tidak ditemukan atau tidak bisa diakses.');
        }

        $defaultExpenseAccount = $this->resolveDefaultExpenseAccount();
        $parsedDescription = $this->parsePettyCashDescription((string) $cashTransaction->description);

        return view('pos.petty-cash.edit', compact(
            'cashTransaction',
            'pettyCashAccount',
            'defaultExpenseAccount',
            'parsedDescription'
        ));
    }

    /**
     * Simpan update transaksi petty cash POS.
     */
    public function update(StorePettyCashPosRequest $request, CashTransaction $cashTransaction)
    {
        $validated = $request->validated();
        $user = auth()->user();
        $outletId = (int) ($user->outlet_id ?? 0);

        $pettyCashAccount = $this->resolvePettyCashAccount($outletId);
        if (! $this->isEditablePettyCashTransaction($cashTransaction, $pettyCashAccount)) {
            return redirect()
                ->route('pos.petty-cash.index')
                ->with('error', 'Transaksi petty cash tidak ditemukan atau tidak bisa diakses.');
        }

        $description = sprintf(
            'Penerima: %s | %s',
            trim((string) $validated['recipient_name']),
            trim((string) $validated['description'])
        );

        try {
            $this->cashAccountService->updateTransaction($cashTransaction, [
                'transaction_date' => $validated['transaction_date'],
                'amount' => (float) $validated['amount'],
                'description' => $description,
            ]);

            return redirect()
                ->route('pos.petty-cash.index')
                ->with('success', 'Transaksi petty cash berhasil diperbarui. Nomor: ' . $cashTransaction->transaction_number);
        } catch (Exception $e) {
            return back()
                ->withInput()
                ->with('error', 'Gagal update petty cash: ' . $e->getMessage());
        }
    }

    protected function resolvePettyCashAccount(int $outletId): ?CashAccount
    {
        if ($outletId <= 0) {
            return null;
        }

        return CashAccount::query()
            ->where('outlet_id', $outletId)
            ->where('is_active', true)
            ->where('type', 'cash')
            ->where('usage_type', 'petty_cash')
            ->orderBy('name')
            ->first();
    }

    protected function resolveDefaultExpenseAccount(): ?CoaAccount
    {
        $preferredCodes = [
            self::DEFAULT_EXPENSE_COA_CODE,
            ...self::LEGACY_DEFAULT_EXPENSE_COA_CODES,
        ];

        return CoaAccount::query()
            ->whereIn('code', $preferredCodes)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderByRaw(
                'CASE code WHEN ? THEN 0 WHEN ? THEN 1 WHEN ? THEN 2 ELSE 3 END',
                $preferredCodes
            )
            ->first();
    }

    protected function isEditablePettyCashTransaction(CashTransaction $transaction, ?CashAccount $pettyCashAccount): bool
    {
        if (! $pettyCashAccount) {
            return false;
        }

        return (int) $transaction->cash_account_id === (int) $pettyCashAccount->id
            && $transaction->reference_type === 'petty_cash_pos'
            && $transaction->type === 'out';
    }

    protected function parsePettyCashDescription(string $description): array
    {
        $result = [
            'recipient_name' => '',
            'description' => trim($description),
        ];

        if (preg_match('/^Penerima:\s*(.*?)\s*\|\s*(.*)$/u', $description, $matches)) {
            $result['recipient_name'] = trim((string) ($matches[1] ?? ''));
            $result['description'] = trim((string) ($matches[2] ?? ''));
        }

        return $result;
    }
}

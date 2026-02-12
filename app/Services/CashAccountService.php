<?php

namespace App\Services;

use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\Purchase;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class CashAccountService
{
    /**
     * Buat akun kas/bank baru
     */
    public function createAccount(array $data): CashAccount
    {
        try {
            DB::beginTransaction();

            // Set opening_balance sama dengan current_balance
            if (isset($data['opening_balance'])) {
                $data['current_balance'] = $data['opening_balance'];
            }

            $account = CashAccount::create($data);

            DB::commit();

            return $account->fresh(['creator']);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Gagal membuat akun kas/bank: ' . $e->getMessage());
        }
    }

    /**
     * Update akun kas/bank
     */
    public function updateAccount(CashAccount $account, array $data): CashAccount
    {
        try {
            DB::beginTransaction();

            // Tidak boleh ubah opening_balance dan current_balance via update biasa
            // Harus via transaction
            unset($data['opening_balance'], $data['current_balance']);

            $account->update($data);

            DB::commit();

            return $account->fresh(['creator']);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Gagal update akun kas/bank: ' . $e->getMessage());
        }
    }

    /**
     * Generate transaction number
     * Format: {CC}{K|M}{YYMM}{SEQ4}
     * Contoh kas keluar: BCK26020001
     * Contoh kas masuk: BCM26020001
     */
    public function generateTransactionNumber(CashAccount $account, string $type, ?string $transactionDate = null): string
    {
        $normalizedType = strtolower($type);
        if (! in_array($normalizedType, ['in', 'out'], true)) {
            throw new Exception('Tipe transaksi tidak valid untuk generate nomor voucher.');
        }

        $period = Carbon::parse($transactionDate ?? now())->format('ym');
        $directionCode = $normalizedType === 'out' ? 'K' : 'M';
        $prefix = $this->resolveAccountCodePrefix($account) . $directionCode . $period;

        // Cari nomor terakhir untuk prefix (reset otomatis per bulan & per tipe)
        $lastTransaction = CashTransaction::where('transaction_number', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderBy('transaction_number', 'desc')
            ->first();

        if ($lastTransaction) {
            // Extract sequence number
            $lastNumber = (int) substr($lastTransaction->transaction_number, -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad((string) $newNumber, 4, '0', STR_PAD_LEFT);
    }

    protected function resolveAccountCodePrefix(CashAccount $account): string
    {
        $lastCodeSegment = null;
        if (! empty($account->code)) {
            $segments = preg_split('/[-_\s]+/', (string) $account->code);
            $lastCodeSegment = is_array($segments) && ! empty($segments)
                ? end($segments)
                : $account->code;
        }

        $candidates = [
            $lastCodeSegment,
            $account->bank_name,
            $account->code,
            $account->name,
        ];

        foreach ($candidates as $candidate) {
            $letters = preg_replace('/[^A-Z]/', '', strtoupper((string) $candidate));
            if ($letters === '') {
                continue;
            }

            return strlen($letters) >= 2
                ? substr($letters, 0, 2)
                : $letters . 'X';
        }

        return 'CA';
    }

    /**
     * Catat transaksi kas/bank
     */
    public function recordTransaction(array $data): CashTransaction
    {
        try {
            DB::beginTransaction();

            $account = CashAccount::findOrFail($data['cash_account_id']);
            $transaction = $this->applyTransaction($account, $data);

            DB::commit();

            return $transaction->fresh(['cashAccount', 'creator', 'coaAccount']);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Gagal catat transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Catat transaksi kas/bank secara bulk
     *
     * @return array<CashTransaction>
     */
    public function recordTransactionsBulk(array $header, array $rows): array
    {
        try {
            DB::beginTransaction();

            $account = CashAccount::findOrFail($header['cash_account_id']);
            $transactions = [];

            foreach ($rows as $row) {
                $data = array_merge($header, $row);
                unset($data['rows']);

                $transactions[] = $this->applyTransaction($account, $data)
                    ->fresh(['cashAccount', 'creator', 'coaAccount']);
            }

            DB::commit();

            return $transactions;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Gagal catat transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Catat pembayaran purchase (kas keluar)
     */
    public function recordPurchasePayment(Purchase $purchase, array $data): CashTransaction
    {
        try {
            DB::beginTransaction();

            // Validasi purchase harus sudah received
            if (! $purchase->isReceived()) {
                throw new Exception('Purchase harus sudah diterima sebelum bisa dibayar');
            }

            // Hitung sisa yang harus dibayar
            $totalPaid = $purchase->cashTransactions()->sum('amount');
            $remaining = $purchase->total_amount - $totalPaid;

            if ($data['amount'] > $remaining) {
                throw new Exception('Jumlah pembayaran melebihi sisa tagihan. Sisa: ' . number_format($remaining, 0, ',', '.'));
            }

            // Get COA Account untuk HPP (Harga Pokok Penjualan)
            // Jika tidak ada COA yang dipilih, gunakan COA HPP sebagai default
            $coaAccountId = $data['coa_account_id'] ?? \App\Models\CoaAccount::where('group', 'HPP')->where('is_active', true)->first()?->id;

            if (! $coaAccountId) {
                throw new Exception('COA Account untuk HPP tidak ditemukan. Pastikan akun COA untuk HPP sudah dibuat.');
            }

            // Create transaction dengan reference ke purchase
            $transaction = $this->recordTransaction([
                'cash_account_id' => $data['cash_account_id'],
                'coa_account_id' => $coaAccountId,
                'type' => 'out',
                'transaction_date' => $data['transaction_date'] ?? now()->format('Y-m-d'),
                'amount' => $data['amount'],
                'description' => "Pembayaran Purchase #{$purchase->purchase_number}",
                'reference_type' => 'purchase',
                'reference_id' => $purchase->id,
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'],
            ]);

            // Update payment_status di purchase
            $totalPaidNow = $totalPaid + $data['amount'];

            if ($totalPaidNow >= $purchase->total_amount) {
                $purchase->update(['payment_status' => 'paid']);
            } elseif ($totalPaidNow > 0) {
                $purchase->update(['payment_status' => 'partial']);
            }

            DB::commit();

            return $transaction->fresh(['cashAccount', 'creator', 'coaAccount']);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Gagal catat pembayaran purchase: ' . $e->getMessage());
        }
    }

    /**
     * Terapkan 1 transaksi pada akun (tanpa commit transaction DB)
     */
    protected function applyTransaction(CashAccount $account, array $data): CashTransaction
    {
        // Validasi: Transaksi keluar harus ada COA (akun biaya) - kecuali untuk transaksi dengan referensi
        if ($data['type'] === 'out' && empty($data['reference_type'])) {
            if (!isset($data['coa_account_id']) || $data['coa_account_id'] === '' || $data['coa_account_id'] === null) {
                throw new Exception('Transaksi kas keluar harus memilih akun biaya (COA)');
            }
        }

        // Handle expense reference if provided
        if (!empty($data['expense_id'])) {
            $data['reference_type'] = 'expense';
            $data['reference_id'] = $data['expense_id'];
            unset($data['expense_id']);
        }

        // Generate transaction number
        $data['transaction_number'] = $this->generateTransactionNumber(
            $account,
            $data['type'],
            $data['transaction_date'] ?? null
        );

        // Set balance before
        $data['balance_before'] = $account->current_balance;

        // Calculate balance after
        if ($data['type'] === 'in') {
            $data['balance_after'] = $account->current_balance + $data['amount'];
        } else { // out
            $data['balance_after'] = $account->current_balance - $data['amount'];
        }

        // Validasi saldo tidak boleh negatif
        if ($data['balance_after'] < 0) {
            throw new Exception('Saldo tidak mencukupi. Saldo saat ini: ' . number_format($account->current_balance, 0, ',', '.'));
        }

        // Create transaction
        $transaction = CashTransaction::create($data);

        // Update current balance di account
        $account->current_balance = $data['balance_after'];
        $account->save();

        // Mark linked expense as paid
        if (!empty($data['reference_type']) && $data['reference_type'] === 'expense' && !empty($data['reference_id'])) {
            $expense = \App\Models\Expense::find($data['reference_id']);
            if ($expense && $expense->status === 'approved') {
                $expense->update([
                    'status' => 'paid',
                    'cash_account_id' => $account->id,
                ]);
            }
        }

        return $transaction;
    }

    /**
     * Hitung ulang saldo (running balance) mulai dari tanggal tertentu
     */
    public function recalculateBalances(CashAccount $account, string $fromDate)
    {
        // Ambil semua transaksi mulai dari tanggal tersebut, urutkan ascending
        $transactions = CashTransaction::where('cash_account_id', $account->id)
            ->where('transaction_date', '>=', $fromDate)
            ->orderBy('transaction_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Ambil saldo terakhir sebelum tanggal tersebut
        $lastTransactionBefore = CashTransaction::where('cash_account_id', $account->id)
            ->where('transaction_date', '<', $fromDate)
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->first();

        $runningBalance = $lastTransactionBefore
            ? $lastTransactionBefore->balance_after
            : $account->opening_balance;

        foreach ($transactions as $transaction) {
            $transaction->balance_before = $runningBalance;

            if ($transaction->type === 'in') {
                $runningBalance += $transaction->amount;
            } else {
                $runningBalance -= $transaction->amount;
            }

            $transaction->balance_after = $runningBalance;
            $transaction->save();
        }

        // Update saldo akhir akun
        $account->current_balance = $runningBalance;
        $account->save();
    }

    /**
     * Update transaksi kas/bank
     */
    public function updateTransaction(CashTransaction $transaction, array $data): CashTransaction
    {
        try {
            DB::beginTransaction();

            $oldDate = $transaction->transaction_date;
            $newDate = $data['transaction_date'] ?? $oldDate;

            // Update data transaksi
            $transaction->update($data);

            // Tentukan tanggal mana yang lebih lampau untuk mulai recalculate
            $recalcFromDate = $oldDate < $newDate ? $oldDate : $newDate;

            // Recalculate balances
            $this->recalculateBalances($transaction->cashAccount, $recalcFromDate);

            DB::commit();

            return $transaction->fresh();
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Gagal update transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Hapus transaksi kas/bank
     */
    public function deleteTransaction(CashTransaction $transaction): bool
    {
        try {
            DB::beginTransaction();

            $account = $transaction->cashAccount;
            $date = $transaction->transaction_date;

            // Hapus referensi expense jika ada
            if ($transaction->reference_type === 'expense' && $transaction->reference_id) {
                // Kembalikan status expense jadi approved (belum dibayar)
                $expense = \App\Models\Expense::find($transaction->reference_id);
                if ($expense) {
                    $expense->update(['status' => 'approved', 'cash_account_id' => null]);
                }
            }

            // Hapus referensi purchase jika ada
            if ($transaction->reference_type === 'purchase' && $transaction->reference_id) {
                $purchase = \App\Models\Purchase::find($transaction->reference_id);
                if ($purchase) {
                    // Recalculate payment status logic needs to be handled if needed, 
                    // but for now we just delete the transaction.
                    // Ideally we should re-check total paid for the purchase.
                    // Simplified: Set to partial or unpaid? 
                    // Let's defer strict purchase status update for now or handle it:
                    // We would need to sum remaining transactions for this purchase.
                }
            }

            $transaction->delete();

            // Recalculate balances dari tanggal transaksi yang dihapus
            $this->recalculateBalances($account, $date);

            DB::commit();

            return true;
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception('Gagal hapus transaksi: ' . $e->getMessage());
        }
    }

    /**
     * Get transactions dengan filter
     */
    public function getTransactions(array $filters = [])
    {
        $query = CashTransaction::with(['cashAccount.outlet', 'creator', 'coaAccount'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        $this->applyTransactionFilters($query, $filters);

        return $query->paginate(20)->withQueryString();
    }

    public function getTransactionTotals(array $filters = []): array
    {
        $query = CashTransaction::query();
        $this->applyTransactionFilters($query, $filters);

        $totalDebit = (clone $query)->where('type', 'in')->sum('amount');
        $totalCredit = (clone $query)->where('type', 'out')->sum('amount');

        return [
            'debit' => (float) $totalDebit,
            'credit' => (float) $totalCredit,
        ];
    }

    protected function applyTransactionFilters(Builder $query, array $filters): void
    {
        // Filter by transaction number
        if (! empty($filters['transaction_number'])) {
            $query->where('transaction_number', 'like', '%' . $filters['transaction_number'] . '%');
        }

        // Filter by exact transaction date
        if (! empty($filters['transaction_date'])) {
            $query->whereDate('transaction_date', $filters['transaction_date']);
        }

        // Filter by cash account
        if (! empty($filters['cash_account_id'])) {
            $query->where('cash_account_id', $filters['cash_account_id']);
        }

        // Filter by outlet (via cash account)
        if (! empty($filters['outlet_id'])) {
            $query->whereHas('cashAccount', function ($q) use ($filters) {
                $q->where('outlet_id', $filters['outlet_id']);
            });
        }

        // Filter by type
        if (! empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by description keyword
        if (! empty($filters['description'])) {
            $query->where('description', 'like', '%' . $filters['description'] . '%');
        }

        // Filter by exact amount
        if (! empty($filters['amount'])) {
            $amount = $this->parseDecimalFilter($filters['amount']);
            if ($amount !== null) {
                $query->where('amount', $amount);
            }
        }

        // Filter by exact ending balance
        if (! empty($filters['balance_after'])) {
            $balanceAfter = $this->parseDecimalFilter($filters['balance_after']);
            if ($balanceAfter !== null) {
                $query->where('balance_after', $balanceAfter);
            }
        }

        // Filter by date range
        if (! empty($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }
        if (! empty($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }

        // Filter by reference type
        if (! empty($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        // Filter by specific COA account
        if (! empty($filters['coa_account_id'])) {
            $query->where('coa_account_id', $filters['coa_account_id']);
        }

        // Filter by COA properties
        if (
            ! empty($filters['coa_type']) ||
            ! empty($filters['coa_group']) ||
            ! empty($filters['exclude_coa_group'])
        ) {
            $query->whereHas('coaAccount', function ($q) use ($filters) {
                if (! empty($filters['coa_type'])) {
                    $q->where('type', $filters['coa_type']);
                }

                if (! empty($filters['coa_group'])) {
                    $q->where('group', $filters['coa_group']);
                }

                if (! empty($filters['exclude_coa_group'])) {
                    $q->where('group', '!=', $filters['exclude_coa_group']);
                }
            });
        }
    }

    protected function parseDecimalFilter($value): ?float
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_ireplace('rp', '', $normalized);
        $normalized = str_replace([' ', '.'], '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        if (! is_numeric($normalized)) {
            return null;
        }

        return (float) $normalized;
    }

    /**
     * Get laporan mutasi kas per akun
     */
    public function getMutationReport(int $cashAccountId, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $account = CashAccount::with(['creator'])->findOrFail($cashAccountId);

        // Default date range: bulan ini
        if (! $dateFrom) {
            $dateFrom = now()->startOfMonth()->format('Y-m-d');
        }
        if (! $dateTo) {
            $dateTo = now()->endOfMonth()->format('Y-m-d');
        }

        // Hitung saldo awal per tanggal from
        // Saldo Awal = Opening Balance + (Sum In < DateFrom) - (Sum Out < DateFrom)
        $priorIn = CashTransaction::where('cash_account_id', $cashAccountId)
            ->where('transaction_date', '<', $dateFrom)
            ->where('type', 'in')
            ->sum('amount');

        $priorOut = CashTransaction::where('cash_account_id', $cashAccountId)
            ->where('transaction_date', '<', $dateFrom)
            ->where('type', 'out')
            ->sum('amount');

        $beginningBalance = $account->opening_balance + $priorIn - $priorOut;

        // Get transactions dalam periode
        $transactions = CashTransaction::where('cash_account_id', $cashAccountId)
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Calculate summary
        $totalIn = $transactions->where('type', 'in')->sum('amount');
        $totalOut = $transactions->where('type', 'out')->sum('amount');

        $endingBalance = $beginningBalance + $totalIn - $totalOut;

        return [
            'account' => $account,
            'transactions' => $transactions,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'beginning_balance' => $beginningBalance,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'ending_balance' => $endingBalance,
        ];
    }

    /**
     * Get ringkasan semua akun
     */
    public function getAccountsSummary()
    {
        $accounts = CashAccount::with(['creator'])
            ->withCount('transactions')
            ->get();

        $totalCash = $accounts->where('type', 'cash')->sum('current_balance');
        $totalBank = $accounts->where('type', 'bank')->sum('current_balance');
        $totalBalance = $totalCash + $totalBank;

        return [
            'accounts' => $accounts,
            'total_cash' => $totalCash,
            'total_bank' => $totalBank,
            'total_balance' => $totalBalance,
        ];
    }
}

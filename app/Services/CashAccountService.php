<?php

namespace App\Services;

use App\Models\CashAccount;
use App\Models\CashTransaction;
use App\Models\Purchase;
use Illuminate\Support\Facades\DB;
use Exception;

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
            throw new Exception("Gagal membuat akun kas/bank: " . $e->getMessage());
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
            throw new Exception("Gagal update akun kas/bank: " . $e->getMessage());
        }
    }

    /**
     * Generate transaction number
     * Format: CT-{account_code}-{date}-{seq}
     * Contoh: CT-KAS001-20251107-001
     */
    public function generateTransactionNumber(string $accountCode): string
    {
        $date = now()->format('Ymd');
        $prefix = "CT-{$accountCode}-{$date}-";

        // Cari nomor terakhir hari ini
        $lastTransaction = CashTransaction::where('transaction_number', 'like', $prefix . '%')
            ->orderBy('transaction_number', 'desc')
            ->first();

        if ($lastTransaction) {
            // Extract sequence number
            $lastNumber = (int) substr($lastTransaction->transaction_number, -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }

        return $prefix . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Catat transaksi kas/bank
     */
    public function recordTransaction(array $data): CashTransaction
    {
        try {
            DB::beginTransaction();

            $account = CashAccount::findOrFail($data['cash_account_id']);

            // Generate transaction number
            $data['transaction_number'] = $this->generateTransactionNumber($account->code);

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
                throw new Exception("Saldo tidak mencukupi. Saldo saat ini: " . number_format($account->current_balance, 0, ',', '.'));
            }

            // Create transaction
            $transaction = CashTransaction::create($data);

            // Update current balance di account
            $account->update([
                'current_balance' => $data['balance_after']
            ]);

            DB::commit();
            return $transaction->fresh(['cashAccount', 'creator']);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Gagal catat transaksi: " . $e->getMessage());
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
            if (!$purchase->isReceived()) {
                throw new Exception("Purchase harus sudah diterima sebelum bisa dibayar");
            }

            // Hitung sisa yang harus dibayar
            $totalPaid = $purchase->cashTransactions()->sum('amount');
            $remaining = $purchase->total_amount - $totalPaid;

            if ($data['amount'] > $remaining) {
                throw new Exception("Jumlah pembayaran melebihi sisa tagihan. Sisa: " . number_format($remaining, 0, ',', '.'));
            }

            // Create transaction dengan reference ke purchase
            $transaction = $this->recordTransaction([
                'cash_account_id' => $data['cash_account_id'],
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
            return $transaction->fresh(['cashAccount', 'creator']);
        } catch (Exception $e) {
            DB::rollBack();
            throw new Exception("Gagal catat pembayaran purchase: " . $e->getMessage());
        }
    }

    /**
     * Get transactions dengan filter
     */
    public function getTransactions(array $filters = [])
    {
        $query = CashTransaction::with(['cashAccount', 'creator'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Filter by cash account
        if (!empty($filters['cash_account_id'])) {
            $query->where('cash_account_id', $filters['cash_account_id']);
        }

        // Filter by type
        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }

        // Filter by reference type
        if (!empty($filters['reference_type'])) {
            $query->where('reference_type', $filters['reference_type']);
        }

        return $query->paginate(20);
    }

    /**
     * Get laporan mutasi kas per akun
     */
    public function getMutationReport(int $cashAccountId, ?string $dateFrom = null, ?string $dateTo = null)
    {
        $account = CashAccount::with(['creator'])->findOrFail($cashAccountId);

        // Default date range: bulan ini
        if (!$dateFrom) {
            $dateFrom = now()->startOfMonth()->format('Y-m-d');
        }
        if (!$dateTo) {
            $dateTo = now()->endOfMonth()->format('Y-m-d');
        }

        // Get transactions dalam periode
        $transactions = CashTransaction::where('cash_account_id', $cashAccountId)
            ->whereBetween('transaction_date', [$dateFrom, $dateTo])
            ->orderBy('transaction_date', 'asc')
            ->orderBy('created_at', 'asc')
            ->get();

        // Calculate summary
        $totalIn = $transactions->where('type', 'in')->sum('amount');
        $totalOut = $transactions->where('type', 'out')->sum('amount');
        $beginningBalance = $account->opening_balance; // Simplified: bisa dihitung dari transaksi sebelumnya

        return [
            'account' => $account,
            'transactions' => $transactions,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'beginning_balance' => $beginningBalance,
            'total_in' => $totalIn,
            'total_out' => $totalOut,
            'ending_balance' => $account->current_balance,
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


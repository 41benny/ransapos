<?php

namespace App\Services;

use App\Models\CashAccount;
use App\Models\CashSession;
use App\Models\CashSessionExpense;
use App\Models\CashTransaction;
use App\Models\CoaAccount;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Posting jurnal shift POS ke buku besar kas.
 *
 * Seluruh baris diposting sekali saat closing, dengan urutan yang membuat
 * identitas kas tertutup rapat:
 *
 *   buku laci setelah penerimaan & pengeluaran = modal + P - K = expected
 *   + baris selisih  -> buku laci = hitung fisik
 *   + baris setoran  -> buku laci = modal disisakan
 *   -> jadi opening_balance shift berikutnya
 *
 * Penerimaan diposting sebagai SATU baris agregat per shift, bukan per struk:
 * detail per transaksi tetap utuh di `sales`/`payments`, dan `cash_session_id`
 * jadi jembatan telusurnya.
 */
class ShiftClosingPostingService
{
    private const COA_SALES_REVENUE = '4-100';
    private const COA_CASH_SHORTAGE = '6-190';
    private const COA_OTHER_INCOME = '4-900';

    public function __construct(
        protected CashAccountService $cashAccountService,
        protected PosDrawerExpenseService $expenseService,
    ) {
    }

    /**
     * Samakan saldo buku laci dengan modal awal shift saat shift dibuka.
     *
     * Dibutuhkan karena modal pagi datang dari brankas dan perpindahan itu
     * belum pernah dibukukan. Tanpa ini, buku laci mulai dari angka yang salah
     * dan seluruh rantai posting closing ikut meleset.
     */
    public function syncOpeningBalance(CashSession $session, CashAccount $drawer): void
    {
        $target = (float) $session->opening_balance;
        $current = (float) $drawer->current_balance;
        $delta = round($target - $current, 2);

        if (abs($delta) < 0.01) {
            return;
        }

        $clearingCoaId = $this->cashAccountService->resolveTransferClearingCoaAccountId();
        $description = $delta > 0
            ? 'Modal awal shift ' . $session->session_number
            : 'Penyesuaian modal awal shift ' . $session->session_number;

        $drawerRow = $this->post($session, $drawer, [
            'type' => $delta > 0 ? 'in' : 'out',
            'amount' => abs($delta),
            'coa_account_id' => $clearingCoaId,
            'description' => $description,
            'reference_type' => 'shift_opening',
        ]);

        // Sisi lawan di kas besar. Uang modal pagi tidak muncul dari udara —
        // ia diambil dari kas besar outlet. Tanpa baris ini, saldo ayat silang
        // 1-190 akan terus menumpuk ke arah negatif dan buku kas menunjukkan
        // uang yang seolah tercipta sendiri setiap pagi.
        $mainAccount = $this->expenseService->ensureMainCashAccount((int) $session->outlet_id);

        if ((int) $mainAccount->id === (int) $drawer->id) {
            return;
        }

        $this->post($session, $mainAccount, [
            'type' => $delta > 0 ? 'out' : 'in',
            'amount' => abs($delta),
            'coa_account_id' => $clearingCoaId,
            'description' => $description . ' (keluar ke laci)',
            'reference_type' => 'shift_opening',
            'voucher_number' => $drawerRow->voucher_number,
        ]);
    }

    /**
     * Posting seluruh baris closing.
     *
     * Dipanggil dari dalam transaksi DB milik closeSession, jadi kegagalan di
     * baris mana pun membatalkan seluruh closing (TEK-03).
     *
     * @return array<int, CashTransaction>
     */
    public function postClosing(CashSession $session, CashAccount $drawer): array
    {
        $posted = [];

        // 1) Penerimaan tunai — harus lebih dulu supaya saldo buku cukup
        //    untuk menanggung pengeluaran di langkah berikutnya.
        $cashIn = round((float) $session->total_cash, 2);
        if ($cashIn > 0) {
            $posted[] = $this->post($session, $drawer, [
                'type' => 'in',
                'amount' => $cashIn,
                'coa_account_id' => $this->resolveCoaId(self::COA_SALES_REVENUE),
                'description' => 'Penerimaan tunai shift ' . $session->session_number,
                'reference_type' => 'shift_cash_sales',
            ]);
        }

        // 2) Pengeluaran kios — satu baris jurnal per baris pengeluaran supaya
        //    telusurnya 1:1 dan COA-nya tidak tercampur.
        $expenses = CashSessionExpense::query()
            ->where('cash_session_id', $session->id)
            ->where('status', CashSessionExpense::STATUS_POSTED)
            ->where('payment_source', CashSessionExpense::SOURCE_DRAWER)
            ->whereNull('cash_transaction_id')
            ->orderBy('id')
            ->get();

        foreach ($expenses as $expense) {
            $transaction = $this->post($session, $drawer, [
                'type' => 'out',
                'amount' => (float) $expense->amount,
                'coa_account_id' => $expense->coa_account_id,
                'description' => sprintf(
                    'Pengeluaran kios | Penerima: %s | %s',
                    $expense->recipient_name,
                    $expense->description
                ),
                'reference_type' => 'pos_drawer_expense',
                'reference_id' => $expense->id,
            ]);

            $expense->update(['cash_transaction_id' => $transaction->id]);
            $posted[] = $transaction;
        }

        // 3) Selisih kas — inilah yang membuat buku laci sama dengan uang fisik.
        //    Tanpa baris ini, buku dan fisik tidak akan pernah bertemu.
        $difference = round((float) $session->difference, 2);
        if (abs($difference) >= 0.01) {
            $posted[] = $this->post($session, $drawer, [
                'type' => $difference > 0 ? 'in' : 'out',
                'amount' => abs($difference),
                'coa_account_id' => $difference > 0
                    ? $this->resolveCoaId(self::COA_OTHER_INCOME)
                    : $this->resolveCoaId(self::COA_CASH_SHORTAGE),
                'description' => ($difference > 0 ? 'Selisih kas lebih' : 'Selisih kas kurang')
                    . ' shift ' . $session->session_number,
                'reference_type' => 'shift_cash_difference',
            ]);
        }

        // 4) Setoran ke kas besar, lewat ayat silang seperti pindah buku biasa.
        $deposit = round((float) $session->deposit_amount, 2);
        if ($deposit > 0) {
            $clearingCoaId = $this->cashAccountService->resolveTransferClearingCoaAccountId();
            $description = 'Setoran shift ' . $session->session_number;

            $out = $this->post($session, $drawer, [
                'type' => 'out',
                'amount' => $deposit,
                'coa_account_id' => $clearingCoaId,
                'description' => $description,
                'reference_type' => 'shift_deposit',
            ]);
            $posted[] = $out;

            // 5) Sisi lawan WAJIB ada. Akun kas besar dibuat otomatis kalau
            //    belum ada, karena tanpa sisi lawan setoran hanya keluar dari
            //    laci tanpa pernah masuk ke mana pun — saldo ayat silang jadi
            //    menggantung dan buku kas benar-benar tidak seimbang.
            $mainAccount = $this->expenseService->ensureMainCashAccount((int) $session->outlet_id);

            $posted[] = $this->post($session, $mainAccount, [
                'type' => 'in',
                'amount' => $deposit,
                'coa_account_id' => $clearingCoaId,
                'description' => $description . ' (terima dari laci)',
                'reference_type' => 'shift_deposit',
                'voucher_number' => $out->voucher_number,
            ]);
        }

        // Saldo berjalan dihitung ulang karena baris-baris di atas bisa
        // bertanggal mundur relatif terhadap transaksi admin (TEK-04).
        $this->cashAccountService->recalculateBalances($drawer, $this->businessDate($session));

        return $posted;
    }

    /**
     * Buat satu baris cash_transactions pada akun tertentu.
     */
    protected function post(CashSession $session, CashAccount $account, array $data): CashTransaction
    {
        $account->refresh();

        $amount = round((float) $data['amount'], 2);
        $balanceBefore = (float) $account->current_balance;
        $balanceAfter = $data['type'] === 'in'
            ? $balanceBefore + $amount
            : $balanceBefore - $amount;

        $transactionNumber = $this->cashAccountService->generateTransactionNumber(
            $account,
            $data['type'],
            $this->businessDate($session)
        );

        $transaction = CashTransaction::create([
            'transaction_number' => $transactionNumber,
            'voucher_number' => $data['voucher_number'] ?? $transactionNumber,
            'cash_account_id' => $account->id,
            'cash_session_id' => $session->id,
            'coa_account_id' => $data['coa_account_id'],
            'type' => $data['type'],
            'transaction_date' => $this->businessDate($session),
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $balanceAfter,
            'description' => $data['description'],
            'reference_type' => $data['reference_type'] ?? null,
            'reference_id' => $data['reference_id'] ?? null,
            'payment_source' => $data['payment_source'] ?? 'drawer',
            'notes' => null,
            'created_by' => $session->user_id,
        ]);

        $account->current_balance = $balanceAfter;
        $account->save();

        return $transaction;
    }

    /**
     * Tanggal pembukuan shift. Diambil dari sistem, tidak pernah dari input
     * kasir, dan memakai tanggal buka shift supaya shift yang lewat tengah
     * malam tetap membukukan ke hari operasionalnya (WKT-02).
     */
    protected function businessDate(CashSession $session): string
    {
        if ($session->business_date) {
            return $session->business_date->toDateString();
        }

        return $session->opened_at
            ? $session->opened_at->toDateString()
            : now()->toDateString();
    }

    /**
     * @throws Exception
     */
    protected function resolveCoaId(string $code): int
    {
        $id = CoaAccount::query()->where('code', $code)->value('id');

        if (! $id) {
            throw new Exception(
                "Akun COA {$code} belum tersedia. Jalankan migrasi modul Pengeluaran Kios terlebih dahulu."
            );
        }

        return (int) $id;
    }
}

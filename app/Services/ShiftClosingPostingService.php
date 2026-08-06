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

        // 1b) Kas masuk laci (modal tambahan) — harus mendahului pengeluaran
        //     dengan alasan yang sama seperti penerimaan tunai: uang yang
        //     ditambahkan pemilik pagi hari sering langsung dipakai belanja,
        //     dan kalau pengeluarannya diposting lebih dulu saldo buku laci
        //     sempat minus padahal uangnya jelas ada.
        $posted = array_merge($posted, $this->postCashIns($session, $drawer));

        // 2) Pengeluaran kios — satu baris jurnal per baris pengeluaran supaya
        //    telusurnya 1:1 dan COA-nya tidak tercampur.
        //
        //    Baris tunai memotong laci; baris non-tunai memotong rekening yang
        //    dipilih kasir. Keduanya WAJIB diposting: kalau non-tunai dilewati,
        //    uangnya sudah keluar tapi biayanya tidak pernah diakui sehingga
        //    laba terlihat lebih besar dari kenyataan.
        $expenses = CashSessionExpense::query()
            ->where('cash_session_id', $session->id)
            ->where('status', CashSessionExpense::STATUS_POSTED)
            ->where('direction', CashSessionExpense::DIRECTION_OUT)
            ->whereIn('payment_source', [
                CashSessionExpense::SOURCE_DRAWER,
                CashSessionExpense::SOURCE_NONCASH,
            ])
            ->whereNull('cash_transaction_id')
            ->orderBy('id')
            ->get();

        foreach ($expenses as $expense) {
            $isDrawer = $expense->payment_source === CashSessionExpense::SOURCE_DRAWER;

            $account = $isDrawer
                ? $drawer
                : CashAccount::find($expense->source_cash_account_id);

            // Baris non-tunai lama (sebelum kolom rekening sumber ada) tidak
            // punya akun asal. Dilewati dengan sengaja daripada ditebak ke akun
            // acak — menebak akan memotong saldo rekening yang salah.
            if (! $account) {
                continue;
            }

            $transaction = $this->post($session, $account, [
                'type' => 'out',
                'amount' => (float) $expense->amount,
                'coa_account_id' => $expense->coa_account_id,
                'description' => sprintf(
                    'Pengeluaran kios%s | Penerima: %s | %s',
                    $isDrawer ? '' : ' (non-tunai)',
                    $expense->recipient_name,
                    $expense->description
                ),
                'reference_type' => 'pos_drawer_expense',
                'reference_id' => $expense->id,
                'payment_source' => $expense->payment_source,
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
        //
        // Dihitung untuk SEMUA akun yang tersentuh, bukan hanya laci: closing
        // juga memotong rekening non-tunai dan menambah kas besar, dan akun
        // yang tidak dihitung ulang akan menyimpan balance_after yang basi.
        $touchedAccountIds = collect($posted)
            ->pluck('cash_account_id')
            ->push($drawer->id)
            ->unique()
            ->values();

        foreach ($touchedAccountIds as $accountId) {
            $account = CashAccount::find($accountId);
            if ($account) {
                $this->cashAccountService->recalculateBalances($account, $this->businessDate($session));
            }
        }

        return $posted;
    }

    /**
     * Posting seluruh baris kas masuk laci.
     *
     * Dua bentuk jurnal, tergantung asal uangnya:
     *
     *  - Dari akun kas outlet: dua baris berpasangan lewat ayat silang, persis
     *    seperti setoran tapi arah sebaliknya. Sisi lawan WAJIB ada — tanpa itu
     *    uang bertambah di laci tanpa berkurang di mana pun, dan saldo ayat
     *    silang 1-190 menumpuk seperti bug yang dulu membuat buku kas tidak
     *    seimbang.
     *
     *  - Dari uang pribadi pemilik: cukup satu baris ke laci dengan lawan akun
     *    Modal Disetor Pemilik. Uangnya memang tidak keluar dari akun kas mana
     *    pun, jadi memaksakan sisi lawan di kas besar justru membuat kas besar
     *    minus tanpa sebab.
     *
     * @return array<int, CashTransaction>
     */
    protected function postCashIns(CashSession $session, CashAccount $drawer): array
    {
        $posted = [];

        $cashIns = CashSessionExpense::query()
            ->where('cash_session_id', $session->id)
            ->where('status', CashSessionExpense::STATUS_POSTED)
            ->where('direction', CashSessionExpense::DIRECTION_IN)
            ->whereNull('cash_transaction_id')
            ->orderBy('id')
            ->get();

        foreach ($cashIns as $cashIn) {
            $amount = round((float) $cashIn->amount, 2);
            if ($amount <= 0) {
                continue;
            }

            $sourceAccount = $cashIn->source_cash_account_id
                ? CashAccount::find($cashIn->source_cash_account_id)
                : null;

            $description = sprintf(
                'Kas masuk laci%s | %s | %s',
                $sourceAccount ? '' : ' (modal pemilik)',
                $cashIn->recipient_name,
                $cashIn->description
            );

            $in = $this->post($session, $drawer, [
                'type' => 'in',
                'amount' => $amount,
                'coa_account_id' => $cashIn->coa_account_id,
                'description' => $description,
                'reference_type' => 'pos_drawer_cash_in',
                'reference_id' => $cashIn->id,
            ]);

            $posted[] = $in;

            if ($sourceAccount && (int) $sourceAccount->id !== (int) $drawer->id) {
                $posted[] = $this->post($session, $sourceAccount, [
                    'type' => 'out',
                    'amount' => $amount,
                    'coa_account_id' => $cashIn->coa_account_id,
                    'description' => $description . ' (keluar ke laci)',
                    'reference_type' => 'pos_drawer_cash_in',
                    'reference_id' => $cashIn->id,
                    'voucher_number' => $in->voucher_number,
                ]);
            }

            $cashIn->update(['cash_transaction_id' => $in->id]);
        }

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

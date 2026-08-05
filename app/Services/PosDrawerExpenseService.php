<?php

namespace App\Services;

use App\Models\CashAccount;
use App\Models\CashSession;
use App\Models\CashSessionExpense;
use App\Models\CoaAccount;
use App\Models\Setting;
use App\Models\User;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Pengeluaran Kios (petty cash kasir) — lihat docs/PRD-POS-PENGELUARAN-KIOS-PETTY-CASH.md
 *
 * Selama shift berjalan, pengeluaran HANYA dicatat di `cash_session_expenses`.
 * Posting ke buku besar (`cash_transactions`) dilakukan sekali saat closing
 * oleh CashSessionService, dengan urutan penerimaan dulu baru pengeluaran.
 *
 * Alasan tidak posting real-time: kas penjualan tidak masuk buku besar per
 * struk, jadi kalau pengeluaran diposting saat diinput, saldo buku laci akan
 * minus sepanjang shift padahal uangnya jelas ada di laci.
 */
class PosDrawerExpenseService
{
    public const FEATURE_FLAG = 'pos_drawer_expense_enabled';

    /**
     * Apakah modul sudah diaktifkan (cutover Tahap 2).
     */
    public function isEnabled(): bool
    {
        return Setting::getBool(self::FEATURE_FLAG, false);
    }

    public function receiptThreshold(): float
    {
        return (float) Setting::getValue('pos_expense_receipt_threshold', 100000);
    }

    public function maxPerTransaction(): float
    {
        return (float) Setting::getValue('pos_expense_max_per_transaction', 500000);
    }

    public function differenceTolerance(): float
    {
        return (float) Setting::getValue('pos_cash_difference_tolerance', 20000);
    }

    /**
     * Akun laci milik outlet. Satu outlet = satu laci (SHIFT-08).
     */
    public function resolveDrawerAccount(int $outletId): ?CashAccount
    {
        if ($outletId <= 0) {
            return null;
        }

        return CashAccount::query()
            ->where('outlet_id', $outletId)
            ->where('usage_type', 'pos_drawer')
            ->where('is_active', true)
            ->orderBy('id')
            ->first();
    }

    /**
     * COA yang boleh dipilih kasir (KLR-06).
     *
     * @return Collection<int, CoaAccount>
     */
    public function selectableCoaAccounts(): Collection
    {
        return CoaAccount::query()
            ->posSelectable()
            ->orderBy('code')
            ->get();
    }

    /**
     * Baris pengeluaran sebuah shift.
     *
     * @return Collection<int, CashSessionExpense>
     */
    public function expensesFor(CashSession $session, bool $includeVoided = false): Collection
    {
        $query = $session->expenses()
            ->with(['coaAccount', 'creator', 'voider'])
            ->orderByDesc('id');

        if (! $includeVoided) {
            $query->where('status', CashSessionExpense::STATUS_POSTED);
        }

        return $query->get();
    }

    /**
     * Hitung ulang total pengeluaran dari baris detailnya, lalu simpan ke
     * kolom agregat di `cash_sessions`.
     *
     * Kolom agregat diperlakukan sebagai cache, bukan sumber kebenaran (TEK-02):
     * angka final selalu bisa dibuktikan ulang dari `cash_session_expenses`.
     *
     * @return array{cash: float, noncash: float}
     */
    public function recalculateSessionTotals(CashSession $session): array
    {
        $totals = CashSessionExpense::query()
            ->where('cash_session_id', $session->id)
            ->where('status', CashSessionExpense::STATUS_POSTED)
            ->selectRaw(
                "COALESCE(SUM(CASE WHEN payment_source = ? THEN amount ELSE 0 END), 0) as cash_total,
                 COALESCE(SUM(CASE WHEN payment_source <> ? THEN amount ELSE 0 END), 0) as noncash_total",
                [CashSessionExpense::SOURCE_DRAWER, CashSessionExpense::SOURCE_DRAWER]
            )
            ->first();

        $cash = (float) ($totals->cash_total ?? 0);
        $noncash = (float) ($totals->noncash_total ?? 0);

        $session->total_cash_expense = $cash;
        $session->total_noncash_expense = $noncash;

        // Shift yang sudah ditutup tidak boleh berubah saldonya (KLR-09).
        if ($session->status === 'open') {
            $session->expected_balance = $session->calculateExpectedBalance();
        }

        $session->save();

        return ['cash' => $cash, 'noncash' => $noncash];
    }

    /**
     * Catat satu pengeluaran kios.
     *
     * @param  array{coa_account_id:int, recipient_name:string, description:string, amount:float, payment_source?:string, idempotency_key?:string|null, attachment_path?:string|null}  $data
     *
     * @throws Exception
     */
    public function record(CashSession $session, array $data, User $user, bool $allowOverLimit = false): CashSessionExpense
    {
        return DB::transaction(function () use ($session, $data, $user, $allowOverLimit) {
            // Kunci baris shift supaya penjumlahan sisa laci tidak balapan
            // dengan closing yang berjalan bersamaan (TEK-02).
            $session = CashSession::whereKey($session->id)->lockForUpdate()->firstOrFail();

            $this->assertSessionAcceptsExpense($session);

            $idempotencyKey = $data['idempotency_key'] ?? null;
            if ($idempotencyKey) {
                $existing = CashSessionExpense::query()
                    ->where('idempotency_key', $idempotencyKey)
                    ->first();

                // Submit kedua dari klik ganda mengembalikan baris yang sama,
                // bukan membuat pengeluaran kembar (TEK-01).
                if ($existing) {
                    return $existing;
                }
            }

            $amount = round((float) $data['amount'], 2);
            if ($amount <= 0) {
                throw new Exception('Jumlah pengeluaran harus lebih besar dari nol.');
            }

            $paymentSource = $data['payment_source'] ?? CashSessionExpense::SOURCE_DRAWER;
            if (! array_key_exists($paymentSource, CashSessionExpense::paymentSourceLabels())) {
                throw new Exception('Sumber pembayaran tidak dikenal.');
            }

            $this->assertCoaSelectable((int) $data['coa_account_id']);

            $max = $this->maxPerTransaction();
            if (! $allowOverLimit && $max > 0 && $amount > $max) {
                throw new Exception(
                    'Pengeluaran melebihi batas Rp ' . number_format($max, 0, ',', '.') .
                    ' per transaksi. Minta otorisasi supervisor.'
                );
            }

            // Hanya pengeluaran tunai yang dibatasi isi laci (KLR-04).
            // Non-tunai dan talangan pribadi tidak mengambil uang dari laci.
            if ($paymentSource === CashSessionExpense::SOURCE_DRAWER) {
                $onHand = $this->currentDrawerOnHand($session);

                if (! $allowOverLimit && $amount > $onHand) {
                    throw new Exception(
                        'Uang di laci tidak cukup. Sisa saat ini Rp ' . number_format($onHand, 0, ',', '.') . '.'
                    );
                }
            }

            $expense = CashSessionExpense::create([
                'cash_session_id' => $session->id,
                'outlet_id' => $session->outlet_id,
                'coa_account_id' => (int) $data['coa_account_id'],
                'recipient_name' => trim((string) $data['recipient_name']),
                'description' => trim((string) $data['description']),
                'amount' => $amount,
                'payment_source' => $paymentSource,
                'attachment_path' => $data['attachment_path'] ?? null,
                'status' => CashSessionExpense::STATUS_POSTED,
                'idempotency_key' => $idempotencyKey,
                'created_by' => $user->id,
            ]);

            $this->recalculateSessionTotals($session);

            return $expense->fresh(['coaAccount', 'creator']);
        });
    }

    /**
     * Batalkan baris pengeluaran.
     *
     * Void, bukan hapus atau update di tempat: untuk uang tunai, nominal yang
     * pernah tercatat adalah fakta yang harus tetap bisa ditelusuri (KLR-08).
     *
     * @throws Exception
     */
    public function void(CashSessionExpense $expense, User $user, string $reason): CashSessionExpense
    {
        return DB::transaction(function () use ($expense, $user, $reason) {
            $session = CashSession::whereKey($expense->cash_session_id)->lockForUpdate()->firstOrFail();

            if ($expense->isVoided()) {
                throw new Exception('Pengeluaran ini sudah dibatalkan sebelumnya.');
            }

            // Setelah shift ditutup, koreksi adalah wewenang admin lewat jurnal
            // bertanggal hari ini — bukan mengubah angka shift lama (KLR-09).
            if ($session->status !== 'open') {
                throw new Exception(
                    'Shift sudah ditutup. Koreksi harus lewat jurnal penyesuaian di menu Kas & Bank.'
                );
            }

            $reason = trim($reason);
            if ($reason === '') {
                throw new Exception('Alasan pembatalan wajib diisi.');
            }

            $expense->update([
                'status' => CashSessionExpense::STATUS_VOID,
                'voided_by' => $user->id,
                'void_reason' => $reason,
                'voided_at' => now(),
            ]);

            $this->recalculateSessionTotals($session);

            return $expense->fresh(['coaAccount', 'creator', 'voider']);
        });
    }

    /**
     * Sisa uang tunai di laci menurut sistem, dihitung ulang dari baris detail
     * agar tidak bergantung pada kolom agregat yang bisa melenceng.
     */
    public function currentDrawerOnHand(CashSession $session): float
    {
        $spent = (float) CashSessionExpense::query()
            ->where('cash_session_id', $session->id)
            ->where('status', CashSessionExpense::STATUS_POSTED)
            ->where('payment_source', CashSessionExpense::SOURCE_DRAWER)
            ->sum('amount');

        return (float) $session->opening_balance + (float) $session->total_cash - $spent;
    }

    /**
     * @throws Exception
     */
    protected function assertSessionAcceptsExpense(CashSession $session): void
    {
        if ($session->status !== 'open') {
            throw new Exception('Shift sudah ditutup. Pengeluaran hanya bisa diinput saat shift terbuka.');
        }

        // Shift koreksi backdate adalah wadah pembukuan, bukan shift operasional:
        // tidak punya laci fisik, jadi tidak boleh jadi sumber pengeluaran (SHIFT-12).
        if ($session->isBackdateCorrection()) {
            throw new Exception('Shift koreksi backdate tidak bisa dipakai untuk input pengeluaran.');
        }
    }

    /**
     * @throws Exception
     */
    protected function assertCoaSelectable(int $coaAccountId): void
    {
        $coa = CoaAccount::query()->find($coaAccountId);

        if (! $coa || ! $coa->is_active) {
            throw new Exception('Kategori biaya tidak ditemukan atau tidak aktif.');
        }

        // Whitelist ditegakkan di server, bukan sekadar membatasi isi dropdown:
        // kalau hanya di UI, request yang dikarang tangan bisa memilih COA mana
        // pun termasuk akun neraca.
        if (! $coa->is_pos_selectable) {
            throw new Exception('Kategori biaya ini tidak boleh dipilih dari POS.');
        }
    }
}

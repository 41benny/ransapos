<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\User;
use App\Models\Outlet;
use Illuminate\Support\Facades\DB;
use Exception;

class CashSessionService
{
    public function __construct(
        protected ?PosDrawerExpenseService $expenseService = null,
        protected ?ShiftClosingPostingService $postingService = null,
    ) {
        $this->expenseService ??= app(PosDrawerExpenseService::class);
        $this->postingService ??= app(ShiftClosingPostingService::class);
    }

    /**
     * Modal awal yang disarankan untuk shift berikutnya: modal disisakan dari
     * shift operasional terakhir yang sudah ditutup di outlet ini (SHIFT-01).
     *
     * Selama modal awal bisa diketik bebas, rantai modal terputus dan selisih
     * hari ini bisa ditutup dengan mengetik ulang modal besok.
     */
    public function suggestedOpeningBalance(int $outletId): ?float
    {
        $lastClosed = CashSession::query()
            ->where('outlet_id', $outletId)
            ->where('status', 'closed')
            ->where('session_type', '!=', 'backdate_correction')
            ->orderByDesc('closed_at')
            ->orderByDesc('id')
            ->first();

        if (! $lastClosed) {
            return null;
        }

        return (float) $lastClosed->retained_balance;
    }

    /**
     * Buka shift kasir baru
     * 
     * @param array $data
     * @param User|null $user
     * @return CashSession
     * @throws Exception
     */
    public function openSession(
        array $data,
        ?User $user = null,
        ?int $openedPosDeviceId = null,
        ?string $openedIp = null
    ): CashSession
    {
        DB::beginTransaction();
        
        try {
            // Get user (auth atau default untuk testing)
            $userId = $user ? $user->id : (auth()->id() ?? 2);

            // Serialize requests for the same cashier. The request-level validator
            // runs before this transaction, so two near-simultaneous submissions
            // can both pass it. Locking the user row and checking again here keeps
            // the invariant that a cashier can only have one open session.
            User::whereKey($userId)->lockForUpdate()->firstOrFail();

            $existingSession = CashSession::where('user_id', $userId)
                ->where('status', 'open')
                ->lockForUpdate()
                ->first();

            if ($existingSession) {
                throw new Exception('Anda sudah memiliki shift yang aktif. Tutup shift terlebih dahulu.');
            }
            
            // Generate session number
            $sessionNumber = $this->generateSessionNumber($data['outlet_id']);
            
            // Buat cash session baru
            $session = CashSession::create([
                'session_number' => $sessionNumber,
                'outlet_id' => $data['outlet_id'],
                'user_id' => $userId,
                'opened_pos_device_id' => $openedPosDeviceId,
                'opened_ip' => $openedIp,
                'opening_balance' => $data['opening_balance'],
                'expected_balance' => $data['opening_balance'], // Awal sama dengan opening
                'actual_balance' => null,
                'difference' => 0,
                'total_sales' => 0,
                'total_cash' => 0,
                'total_non_cash' => 0,
                'opened_at' => now(),
                'closed_at' => null,
                'notes' => $data['notes'] ?? null,
                'status' => 'open',
            ]);

            // Modul Pengeluaran Kios: kaitkan shift ke laci outlet dan samakan
            // saldo buku laci dengan modal awal. Dilewati kalau flag masih mati
            // supaya perilaku lama benar-benar tidak tersentuh.
            if ($this->expenseService->isEnabled()) {
                $drawer = $this->expenseService->resolveDrawerAccount((int) $data['outlet_id']);

                if (! $drawer) {
                    throw new Exception(
                        'Akun laci kasir untuk outlet ini belum ada. Minta admin membuat akun kas dengan tipe penggunaan "Laci Kasir".'
                    );
                }

                $session->drawer_cash_account_id = $drawer->id;
                $session->save();

                $this->postingService->syncOpeningBalance($session, $drawer);
            }

            DB::commit();

            return $session->load(['outlet', 'user']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Tutup shift kasir
     * 
     * @param CashSession $session
     * @param float $actualBalance
     * @param string|null $notes
     * @return CashSession
     * @throws Exception
     */
    public function closeSession(
        CashSession $session,
        float $actualBalance,
        ?string $notes = null,
        ?int $closedPosDeviceId = null,
        ?string $closedIp = null,
        ?float $retainedBalance = null
    ): CashSession
    {
        DB::beginTransaction();

        try {
            // Kunci baris shift supaya input pengeluaran yang datang bersamaan
            // tidak lolos setelah totalnya dihitung (TEK-02).
            $session = CashSession::whereKey($session->id)->lockForUpdate()->firstOrFail();

            // Validasi session masih open
            if ($session->status !== 'open') {
                throw new Exception('Sesi kasir sudah ditutup sebelumnya');
            }

            $moduleEnabled = $this->expenseService->isEnabled() && ! $session->isBackdateCorrection();

            if ($moduleEnabled) {
                // Hitung ulang dari baris detail, bukan percaya kolom agregat.
                $this->expenseService->recalculateSessionTotals($session);
                $session->refresh();

                $expected = $session->calculateExpectedBalance();

                $retained = round((float) ($retainedBalance ?? 0), 2);
                if ($retained < 0) {
                    throw new Exception('Modal disisakan tidak boleh negatif.');
                }

                // Tidak bisa menyisakan uang yang tidak ada di laci (SETOR-03).
                if ($retained > $actualBalance) {
                    throw new Exception(
                        'Modal disisakan (Rp ' . number_format($retained, 0, ',', '.') .
                        ') melebihi uang fisik (Rp ' . number_format($actualBalance, 0, ',', '.') . ').'
                    );
                }

                $session->expected_balance = $expected;
                $session->retained_balance = $retained;

                // Setoran adalah turunan: yang bisa disetor hanyalah uang yang
                // benar-benar ada, bukan uang yang seharusnya ada (SETOR-02).
                $session->deposit_amount = round($actualBalance - $retained, 2);
                $session->deposit_status = 'pending';
            }

            // Hitung selisih (actual - expected)
            $difference = round($actualBalance - (float) $session->expected_balance, 2);

            $session->fill([
                'actual_balance' => $actualBalance,
                'difference' => $difference,
                'closed_at' => now(),
                'closed_pos_device_id' => $closedPosDeviceId,
                'closed_ip' => $closedIp,
                'status' => 'closed',
                'notes' => $notes ? ($session->notes ? $session->notes . "\n\n" . $notes : $notes) : $session->notes,
            ]);
            $session->save();

            if ($moduleEnabled) {
                $drawer = $session->drawer_cash_account_id
                    ? \App\Models\CashAccount::find($session->drawer_cash_account_id)
                    : $this->expenseService->resolveDrawerAccount((int) $session->outlet_id);

                if (! $drawer) {
                    throw new Exception('Akun laci kasir tidak ditemukan. Closing dibatalkan.');
                }

                $this->postingService->postClosing($session, $drawer);
            }

            DB::commit();

            return $session->fresh()->load(['outlet', 'user']);

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Ambil session aktif untuk user & outlet tertentu
     * 
     * @param User|null $user
     * @param int|null $outletId
     * @return CashSession|null
     */
    public function getActiveSessionFor(?User $user = null, ?int $outletId = null): ?CashSession
    {
        $userId = $user ? $user->id : (auth()->id() ?? 2);
        
        $query = CashSession::where('user_id', $userId)
            ->where('status', 'open');
        
        if ($outletId) {
            $query->where('outlet_id', $outletId);
        }
        
        return $query->with(['outlet', 'user'])
            ->orderBy('opened_at', 'desc')
            ->first();
    }

    /**
     * Generate session number unik
     * 
     * @param int $outletId
     * @return string
     */
    protected function generateSessionNumber(int $outletId): string
    {
        $date = now()->format('Ymd');
        $outlet = Outlet::find($outletId);
        $outletCode = $outlet ? $outlet->code : str_pad($outletId, 3, '0', STR_PAD_LEFT);
        
        // Cari session terakhir hari ini untuk outlet ini
        $lastSession = CashSession::where('outlet_id', $outletId)
            ->whereDate('opened_at', now())
            ->lockForUpdate()
            ->orderBy('id', 'desc')
            ->first();

        if ($lastSession) {
            // Extract nomor urut terakhir
            $lastNumber = (int) substr($lastSession->session_number, -3);
            $nextNumber = $lastNumber + 1;
        } else {
            $nextNumber = 1;
        }

        $sequence = str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        return "CS-{$outletCode}-{$date}-{$sequence}";
    }

    /**
     * Ambil riwayat cash sessions dengan filter
     * 
     * @param array $filters
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function getSessionHistory(array $filters = [])
    {
        $query = CashSession::with(['outlet', 'user']);

        // Filter by outlet
        if (!empty($filters['outlet_id'])) {
            $query->where('outlet_id', $filters['outlet_id']);
        }

        // Filter by user
        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        // Filter by status
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Filter by date range
        if (!empty($filters['date_from'])) {
            $query->whereDate('opened_at', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('opened_at', '<=', $filters['date_to']);
        }

        return $query->orderBy('opened_at', 'desc')
            ->paginate(15);
    }
}


<?php

namespace App\Services;

use App\Models\CashSession;
use App\Models\User;
use App\Models\Outlet;
use Illuminate\Support\Facades\DB;
use Exception;

class CashSessionService
{
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
        ?string $closedIp = null
    ): CashSession
    {
        DB::beginTransaction();
        
        try {
            // Validasi session masih open
            if ($session->status !== 'open') {
                throw new Exception('Sesi kasir sudah ditutup sebelumnya');
            }

            // Hitung selisih (actual - expected)
            $difference = $actualBalance - $session->expected_balance;

            // Update session
            $session->update([
                'actual_balance' => $actualBalance,
                'difference' => $difference,
                'closed_at' => now(),
                'closed_pos_device_id' => $closedPosDeviceId,
                'closed_ip' => $closedIp,
                'status' => 'closed',
                'notes' => $notes ? ($session->notes ? $session->notes . "\n\n" . $notes : $notes) : $session->notes,
            ]);

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


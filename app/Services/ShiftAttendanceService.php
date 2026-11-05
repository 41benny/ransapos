<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\CashSession;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Support\Carbon;
use Illuminate\Validation\ValidationException;

class ShiftAttendanceService
{
    public const SETTING = 'require_attendance_checkout_before_close';

    public function dayStartFor(User $cashier): Carbon
    {
        $session = CashSession::query()->where('user_id', $cashier->id)
            ->where('outlet_id', $cashier->outlet_id)->where('status', 'open')
            ->where(fn ($query) => $query->whereNull('session_type')->orWhere('session_type', '!=', 'backdate_correction'))
            ->latest('opened_at')->first();

        return $session ? $session->opened_at->copy()->startOfDay() : today();
    }

    public function pendingClockOuts(CashSession $session): Collection
    {
        if ($session->isBackdateCorrection() || !Setting::getBool(self::SETTING, true)) {
            return new Collection();
        }

        // Include employees arriving before the drawer opened and shifts crossing midnight.
        // Use the physical attendance outlet, including employees visiting from another outlet.
        return Attendance::query()->with('user:id,name')
            ->where('outlet_id', $session->outlet_id)
            ->whereBetween('clock_in', [$session->opened_at->copy()->startOfDay(), now()])
            ->whereNull('clock_out')->orderBy('clock_in')->get();
    }

    public function forCashierDay(Builder|Relation $query, User $cashier): Builder
    {
        $query = $query instanceof Relation ? $query->getQuery() : $query;
        $start = $this->dayStartFor($cashier);

        // Keep today's history visible, plus unfinished attendance from an overnight drawer shift.
        return $query->where('clock_in', '<=', now())->where(function ($query) use ($start) {
            $query->whereDate('clock_in', today())
                ->orWhere(fn ($query) => $query->where('clock_in', '>=', $start)->whereNull('clock_out'));
        });
    }

    public function assertCanClose(CashSession $session): void
    {
        $pending = $this->pendingClockOuts($session);
        if ($pending->isEmpty()) {
            return;
        }

        $names = $pending->map(fn (Attendance $attendance) => $attendance->user?->name ?? 'Karyawan #' . $attendance->user_id)
            ->unique()->implode(', ');

        throw ValidationException::withMessages([
            'attendance' => 'Shift belum bisa ditutup. Karyawan yang belum check-out absensi: ' . $names . '. Selesaikan check-out di menu Absensi terlebih dahulu.',
        ]);
    }
}

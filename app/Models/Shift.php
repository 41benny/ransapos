<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shift extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'start_time',
        'end_time',
        'late_tolerance_minutes',
        'is_overnight',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'late_tolerance_minutes' => 'integer',
        'is_overnight' => 'boolean',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    /**
     * Relasi ke absensi yang memakai shift ini.
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Scope: hanya shift aktif (urut sesuai sort_order lalu nama).
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true)->orderBy('sort_order')->orderBy('name');
    }

    /**
     * Waktu mulai shift untuk tanggal tertentu.
     */
    public function startFor(Carbon $date): Carbon
    {
        return $date->copy()->startOfDay()->setTimeFromTimeString($this->start_time);
    }

    /**
     * Waktu selesai shift untuk tanggal tertentu.
     * Bila shift melewati tengah malam, jatuh di hari berikutnya.
     */
    public function endFor(Carbon $date): Carbon
    {
        $end = $date->copy()->startOfDay()->setTimeFromTimeString($this->end_time);

        if ($this->is_overnight) {
            $end->addDay();
        }

        return $end;
    }

    /**
     * Batas waktu sebelum dihitung terlambat (jam masuk + toleransi).
     */
    public function lateThresholdFor(Carbon $date): Carbon
    {
        return $this->startFor($date)->addMinutes((int) $this->late_tolerance_minutes);
    }

    /**
     * Label rentang jam, mis. "08:00–16:00".
     */
    public function timeRangeLabel(): string
    {
        $start = Carbon::parse($this->start_time)->format('H:i');
        $end = Carbon::parse($this->end_time)->format('H:i');

        return "{$start}–{$end}";
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'logged_in_user_id',
        'outlet_id',
        'clock_in',
        'clock_out',
        'notes',
        'status',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
    ];

    /**
     * Relasi ke user (karyawan yang absen)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke user yang login di POS (kasir)
     */
    public function loggedInUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'logged_in_user_id');
    }

    /**
     * Relasi ke outlet
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Cek apakah sudah clock out
     */
    public function isClockOut(): bool
    {
        return !is_null($this->clock_out);
    }

    /**
     * Get durasi kerja dalam menit
     */
    public function getDuration(): ?int
    {
        if (!$this->isClockOut()) {
            return null;
        }

        return $this->clock_in->diffInMinutes($this->clock_out);
    }

    /**
     * Get durasi kerja dalam format jam:menit
     */
    public function getDurationFormatted(): ?string
    {
        $duration = $this->getDuration();

        if (is_null($duration)) {
            return null;
        }

        $hours = floor($duration / 60);
        $minutes = $duration % 60;

        return sprintf('%d jam %d menit', $hours, $minutes);
    }

    /**
     * Get durasi kerja saat ini (untuk yang belum clock out)
     */
    public function getCurrentDuration(): int
    {
        if ($this->isClockOut()) {
            return $this->getDuration();
        }

        return $this->clock_in->diffInMinutes(now());
    }

    /**
     * Get durasi kerja saat ini dalam format
     */
    public function getCurrentDurationFormatted(): string
    {
        $duration = $this->getCurrentDuration();
        $hours = floor($duration / 60);
        $minutes = $duration % 60;

        return sprintf('%d jam %d menit', $hours, $minutes);
    }
}

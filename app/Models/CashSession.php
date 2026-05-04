<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    protected $fillable = [
        'session_number',
        'session_type',
        'business_date',
        'outlet_id',
        'user_id',
        'opened_pos_device_id',
        'closed_pos_device_id',
        'opened_ip',
        'closed_ip',
        'opening_balance',
        'expected_balance',
        'actual_balance',
        'difference',
        'total_sales',
        'total_cash',
        'total_non_cash',
        'opened_at',
        'closed_at',
        'notes',
        'status',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'expected_balance' => 'decimal:2',
        'actual_balance' => 'decimal:2',
        'difference' => 'decimal:2',
        'total_sales' => 'decimal:2',
        'total_cash' => 'decimal:2',
        'total_non_cash' => 'decimal:2',
        'business_date' => 'date',
        'opened_at' => 'datetime',
        'closed_at' => 'datetime',
    ];

    /**
     * Relasi ke outlet
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Relasi ke user (kasir)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Perangkat saat shift dibuka
     */
    public function openedDevice(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class, 'opened_pos_device_id');
    }

    /**
     * Perangkat saat shift ditutup
     */
    public function closedDevice(): BelongsTo
    {
        return $this->belongsTo(PosDevice::class, 'closed_pos_device_id');
    }

    /**
     * Relasi ke sales
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}

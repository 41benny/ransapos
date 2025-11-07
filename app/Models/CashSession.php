<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashSession extends Model
{
    protected $fillable = [
        'session_number',
        'outlet_id',
        'user_id',
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
     * Relasi ke sales
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }
}

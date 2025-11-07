<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAccount extends Model
{
    protected $fillable = [
        'name',
        'code',
        'type',
        'is_active',
        'opening_balance',
        'current_balance',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
    ];

    /**
     * Relasi ke User (creator)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke CashTransaction
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class);
    }

    /**
     * Helper: Cek apakah akun aktif
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Helper: Cek apakah tipe cash
     */
    public function isCash(): bool
    {
        return $this->type === 'cash';
    }

    /**
     * Helper: Cek apakah tipe bank
     */
    public function isBank(): bool
    {
        return $this->type === 'bank';
    }

    /**
     * Scope: Hanya akun aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Hanya tipe cash
     */
    public function scopeCash($query)
    {
        return $query->where('type', 'cash');
    }

    /**
     * Scope: Hanya tipe bank
     */
    public function scopeBank($query)
    {
        return $query->where('type', 'bank');
    }
}

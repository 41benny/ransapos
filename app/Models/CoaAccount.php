<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CoaAccount extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'group',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke CashTransaction
     */
    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'coa_account_id');
    }

    /**
     * Helper: Cek apakah akun aktif
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Helper: Cek apakah tipe income
     */
    public function isIncome(): bool
    {
        return $this->type === 'income';
    }

    /**
     * Helper: Cek apakah tipe expense
     */
    public function isExpense(): bool
    {
        return $this->type === 'expense';
    }

    /**
     * Scope: Hanya akun aktif
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope: Hanya tipe income
     */
    public function scopeIncome($query)
    {
        return $query->where('type', 'income');
    }

    /**
     * Scope: Hanya tipe expense
     */
    public function scopeExpense($query)
    {
        return $query->where('type', 'expense');
    }

    /**
     * Scope: Filter by group
     */
    public function scopeGroup($query, string $group)
    {
        return $query->where('group', $group);
    }
}

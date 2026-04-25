<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashAccount extends Model
{
    protected $fillable = [
        'outlet_id',
        'name',
        'code',
        'type',
        'usage_type',
        'bank_name',
        'account_number',
        'account_holder',
        'branch',
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
     * Relasi ke Outlet
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Relasi ke transfers out (sebagai rekening sumber)
     */
    public function transfersOut(): HasMany
    {
        return $this->hasMany(BankTransfer::class, 'from_cash_account_id');
    }

    /**
     * Relasi ke transfers in (sebagai rekening tujuan)
     */
    public function transfersIn(): HasMany
    {
        return $this->hasMany(BankTransfer::class, 'to_cash_account_id');
    }

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
     * Helper: Cek apakah akun dipakai untuk petty cash
     */
    public function isPettyCash(): bool
    {
        if ($this->usage_type === 'petty_cash') {
            return true;
        }

        $identifier = strtolower((string) $this->name . ' ' . (string) $this->code);
        $normalizedIdentifier = preg_replace('/[^a-z0-9]+/', '', $identifier);

        return str_contains($normalizedIdentifier, 'pettycash')
            || str_contains($normalizedIdentifier, 'petycash');
    }

    /**
     * Helper: Cek apakah akun boleh memiliki saldo negatif.
     */
    public function allowsNegativeBalance(): bool
    {
        return $this->isPettyCash();
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

    /**
     * Scope: Hanya akun petty cash
     */
    public function scopePettyCash($query)
    {
        return $query->where('usage_type', 'petty_cash');
    }

    /**
     * Scope: Filter by outlet
     */
    public function scopeByOutlet($query, $outletId)
    {
        return $query->where('outlet_id', $outletId);
    }
}

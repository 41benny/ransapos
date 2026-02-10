<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;


class BankTransfer extends Model
{
    protected $fillable = [
        'transfer_number',
        'from_cash_account_id',
        'to_cash_account_id',
        'transfer_date',
        'amount',
        'description',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'amount' => 'decimal:2',
    ];

    /**
     * Relasi ke from account (rekening sumber)
     */
    public function fromAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'from_cash_account_id');
    }

    /**
     * Relasi ke to account (rekening tujuan)
     */
    public function toAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'to_cash_account_id');
    }

    /**
     * Relasi ke creator (user yang membuat transfer)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke cash transactions (2 transaksi: out & in)
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'reference_id')
            ->where('reference_type', 'bank_transfer');
    }

    /**
     * Helper: Get from outlet name
     */
    public function getFromOutletNameAttribute(): string
    {
        return $this->fromAccount->outlet->name ?? '-';
    }

    /**
     * Helper: Get to outlet name
     */
    public function getToOutletNameAttribute(): string
    {
        return $this->toAccount->outlet->name ?? '-';
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('transfer_date', [$from, $to]);
    }

    /**
     * Scope: Filter by from account
     */
    public function scopeFromAccount($query, $accountId)
    {
        return $query->where('from_cash_account_id', $accountId);
    }

    /**
     * Scope: Filter by to account
     */
    public function scopeToAccount($query, $accountId)
    {
        return $query->where('to_cash_account_id', $accountId);
    }
}

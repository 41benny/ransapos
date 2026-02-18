<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashTransaction extends Model
{
    protected $fillable = [
        'transaction_number',
        'voucher_number',
        'cash_account_id',
        'coa_account_id',
        'type',
        'transaction_date',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'reference_type',
        'reference_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'transaction_date' => 'date',
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    /**
     * Relasi ke CashAccount
     */
    public function cashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class);
    }

    /**
     * Relasi ke CoaAccount
     */
    public function coaAccount(): BelongsTo
    {
        return $this->belongsTo(CoaAccount::class, 'coa_account_id');
    }

    /**
     * Relasi ke User (creator)
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi polimorfik ke referensi (purchase, sale, dll)
     * Note: Ini simplified, bisa jadi morphTo kalau mau strict
     */
    public function reference()
    {
        if ($this->reference_type === 'purchase') {
            return $this->belongsTo(Purchase::class, 'reference_id');
        }

        if ($this->reference_type === 'sale') {
            return $this->belongsTo(Sale::class, 'reference_id');
        }

        return null;
    }

    /**
     * Helper: Cek apakah transaksi masuk (in)
     */
    public function isIn(): bool
    {
        return $this->type === 'in';
    }

    /**
     * Helper: Cek apakah transaksi keluar (out)
     */
    public function isOut(): bool
    {
        return $this->type === 'out';
    }

    /**
     * Scope: Hanya transaksi masuk
     */
    public function scopeIn($query)
    {
        return $query->where('type', 'in');
    }

    /**
     * Scope: Hanya transaksi keluar
     */
    public function scopeOut($query)
    {
        return $query->where('type', 'out');
    }

    /**
     * Scope: Filter by date range
     */
    public function scopeDateRange($query, $from, $to)
    {
        return $query->whereBetween('transaction_date', [$from, $to]);
    }
}

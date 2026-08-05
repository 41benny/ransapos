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
        'total_cash_expense',
        'total_noncash_expense',
        'retained_balance',
        'deposit_amount',
        'deposit_status',
        'drawer_cash_account_id',
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
        'total_cash_expense' => 'decimal:2',
        'total_noncash_expense' => 'decimal:2',
        'retained_balance' => 'decimal:2',
        'deposit_amount' => 'decimal:2',
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

    /**
     * Akun laci yang dipakai shift ini
     */
    public function drawerCashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'drawer_cash_account_id');
    }

    /**
     * Pengeluaran kios pada shift ini (termasuk yang sudah void)
     */
    public function expenses(): HasMany
    {
        return $this->hasMany(CashSessionExpense::class);
    }

    /**
     * Shift koreksi backdate bukan shift operasional: tidak punya laci fisik,
     * tidak punya closing, tidak menghasilkan setoran (SHIFT-12).
     */
    public function isBackdateCorrection(): bool
    {
        return $this->session_type === 'backdate_correction';
    }

    /**
     * Saldo laci seharusnya = modal awal + penerimaan tunai - pengeluaran tunai.
     */
    public function calculateExpectedBalance(): float
    {
        return (float) $this->opening_balance
            + (float) $this->total_cash
            - (float) $this->total_cash_expense;
    }

    /**
     * Sisa uang di laci saat ini menurut sistem — dipakai untuk membatasi
     * input pengeluaran (KLR-04).
     */
    public function drawerCashOnHand(): float
    {
        return $this->calculateExpectedBalance();
    }

    /**
     * Stok awal packaging shift
     */
    public function packagingOpenings(): HasMany
    {
        return $this->hasMany(CashSessionPackagingOpening::class);
    }

    /**
     * Hasil closing packaging shift
     */
    public function packagingClosings(): HasMany
    {
        return $this->hasMany(CashSessionPackagingClosing::class);
    }

    /**
     * Adjustment packaging shift
     */
    public function packagingAdjustments(): HasMany
    {
        return $this->hasMany(PackagingAdjustment::class);
    }
}

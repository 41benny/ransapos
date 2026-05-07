<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Sale extends Model
{
    protected $fillable = [
        'invoice_number',
        'idempotency_key',
        'outlet_id',
        'cash_session_id',
        'user_id',
        'customer_id',
        'promotion_id',
        'voucher_id',
        'voucher_code',
        'sale_date',
        'sales_type',
        'subtotal',
        'discount_type',
        'discount_value',
        'discount_amount',
        'tax_amount',
        'service_charge_amount',
        'rounding_amount',
        'total_amount',
        'customer_name',
        'loyalty_points_earned',
        'notes',
        'status',
        'kitchen_status',
        'is_backdated',
        'backdated_by',
        'backdated_at',
        'backdate_reason',
        'manual_reference',
    ];

    protected $casts = [
        'sale_date' => 'date',
        'sales_type' => 'string',
        'promotion_id' => 'integer',
        'voucher_id' => 'integer',
        'subtotal' => 'decimal:2',
        'discount_value' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'service_charge_amount' => 'decimal:2',
        'rounding_amount' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'is_backdated' => 'boolean',
        'backdated_at' => 'datetime',
    ];

    /**
     * Relasi ke outlet
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Relasi ke cash session
     */
    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    /**
     * Relasi ke user (kasir)
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function backdatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'backdated_by');
    }

    /**
     * Relasi ke customer
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * Relasi ke promo (opsional)
     */
    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    /**
     * Relasi ke voucher (opsional)
     */
    public function voucher(): BelongsTo
    {
        return $this->belongsTo(Voucher::class);
    }

    /**
     * Relasi ke sale items
     */
    public function items(): HasMany
    {
        return $this->hasMany(SaleItem::class);
    }

    /**
     * Relasi ke payments
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * Nama customer yang ditampilkan pada UI/laporan.
     */
    public function getResolvedCustomerNameAttribute(): string
    {
        $snapshotName = trim((string) $this->customer_name);
        if ($snapshotName !== '') {
            return $snapshotName;
        }

        $customerName = trim((string) optional($this->customer)->name);
        if ($customerName !== '') {
            return $customerName;
        }

        return 'Walk-in';
    }
}

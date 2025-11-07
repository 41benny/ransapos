<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Purchase extends Model
{
    protected $fillable = [
        'purchase_number',
        'outlet_id',
        'supplier_id',
        'purchase_date',
        'status',
        'received_at',
        'received_by',
        'subtotal',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'payment_status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'purchase_date' => 'date',
        'received_at' => 'datetime',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
    ];

    /**
     * Relasi ke outlet
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Relasi ke supplier
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Relasi ke creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke receiver (yang menerima barang)
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Relasi ke purchase items
     */
    public function items(): HasMany
    {
        return $this->hasMany(PurchaseItem::class);
    }

    /**
     * Relasi ke cash transactions (pembayaran)
     */
    public function cashTransactions(): HasMany
    {
        return $this->hasMany(CashTransaction::class, 'reference_id')
            ->where('reference_type', 'purchase');
    }

    /**
     * Cek apakah purchase sudah diterima
     */
    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    /**
     * Cek apakah purchase masih draft
     */
    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    /**
     * Cek apakah purchase dibatalkan
     */
    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }
}

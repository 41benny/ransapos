<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StockTransfer extends Model
{
    protected $fillable = [
        'transfer_number',
        'from_outlet_id',
        'to_outlet_id',
        'transfer_date',
        'status',
        'notes',
        'created_by',
        'sent_at',
        'sent_by',
        'received_at',
        'received_by',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
    ];

    protected $casts = [
        'transfer_date' => 'date',
        'sent_at' => 'datetime',
        'received_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Relasi ke outlet pengirim
     */
    public function fromOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'from_outlet_id');
    }

    /**
     * Relasi ke outlet penerima
     */
    public function toOutlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class, 'to_outlet_id');
    }

    /**
     * Relasi ke items
     */
    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class);
    }

    /**
     * Relasi ke creator
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi ke sender
     */
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sent_by');
    }

    /**
     * Relasi ke receiver
     */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }

    /**
     * Relasi ke canceller
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Helper methods
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isInTransit(): bool
    {
        return $this->status === 'in_transit';
    }

    public function isReceived(): bool
    {
        return $this->status === 'received';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function canBeSent(): bool
    {
        return $this->status === 'pending';
    }

    public function canBeReceived(): bool
    {
        return $this->status === 'in_transit';
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, ['pending', 'in_transit']);
    }

    public function canCorrectDate(): bool
    {
        return !$this->isCancelled();
    }
}

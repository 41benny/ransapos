<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StockTransferItem extends Model
{
    protected $fillable = [
        'stock_transfer_id',
        'product_id',
        'quantity',
        'received_quantity',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'received_quantity' => 'decimal:2',
    ];

    /**
     * Relasi ke stock transfer
     */
    public function stockTransfer(): BelongsTo
    {
        return $this->belongsTo(StockTransfer::class);
    }

    /**
     * Relasi ke product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get difference between sent and received quantity
     */
    public function getDifferenceAttribute(): float
    {
        if ($this->received_quantity === null) {
            return 0;
        }
        return $this->received_quantity - $this->quantity;
    }

    /**
     * Check if there's a shortage (received less than sent)
     */
    public function hasShortage(): bool
    {
        return $this->received_quantity !== null && $this->received_quantity < $this->quantity;
    }

    /**
     * Check if there's an excess (received more than sent)
     */
    public function hasExcess(): bool
    {
        return $this->received_quantity !== null && $this->received_quantity > $this->quantity;
    }
}

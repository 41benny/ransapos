<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleItem extends Model
{
    protected $fillable = [
        'sale_id',
        'product_id',
        'product_name',
        'product_sku',
        'quantity',
        'unit_price',
        'normal_price',
        'is_manual_price',
        'discount_amount',
        'subtotal',
        'cogs',
        'notes',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'unit_price' => 'decimal:2',
        'normal_price' => 'decimal:2',
        'is_manual_price' => 'boolean',
        'discount_amount' => 'decimal:2',
        'subtotal' => 'decimal:2',
        'cogs' => 'decimal:2',
    ];

    /**
     * Relasi ke sale
     */
    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    /**
     * Relasi ke product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductCost extends Model
{
    protected $fillable = [
        'product_id',
        'outlet_id',
        'avg_cost',
        'last_calculated_at',
    ];

    protected $casts = [
        'avg_cost' => 'decimal:4',
        'last_calculated_at' => 'datetime',
    ];

    /**
     * Relasi ke product
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Relasi ke outlet
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}

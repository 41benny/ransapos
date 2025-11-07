<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Stock extends Model
{
    protected $fillable = [
        'product_id',
        'outlet_id',
        'quantity',
        'last_mutation_at',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'last_mutation_at' => 'datetime',
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

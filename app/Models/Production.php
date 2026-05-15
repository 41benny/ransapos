<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Production extends Model
{
    protected $fillable = [
        'production_number',
        'bom_id',
        'product_id',
        'outlet_id',
        'production_date',
        'quantity',
        'total_cost',
        'unit_cost',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'production_date' => 'date',
        'quantity' => 'decimal:4',
        'total_cost' => 'decimal:2',
        'unit_cost' => 'decimal:4',
    ];

    public function bom(): BelongsTo
    {
        return $this->belongsTo(BomHeader::class, 'bom_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function materials(): HasMany
    {
        return $this->hasMany(ProductionMaterial::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

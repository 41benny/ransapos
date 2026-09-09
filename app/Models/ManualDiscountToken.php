<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ManualDiscountToken extends Model
{
    protected $fillable = [
        'token',
        'outlet_id',
        'is_used',
        'generated_by',
        'used_by',
        'used_for_sale_id',
        'used_at',
    ];

    protected $casts = [
        'is_used' => 'boolean',
        'used_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_used', false);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function generator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function usedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class, 'used_for_sale_id');
    }
}

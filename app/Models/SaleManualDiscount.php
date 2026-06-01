<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SaleManualDiscount extends Model
{
    protected $fillable = [
        'sale_id',
        'cashier_user_id',
        'authorized_by_user_id',
        'outlet_id',
        'discount_type',
        'discount_value',
        'discount_amount_applied',
        'source',
        'reason',
        'authorized_at',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'discount_amount_applied' => 'decimal:2',
        'authorized_at' => 'datetime',
    ];

    public function sale(): BelongsTo
    {
        return $this->belongsTo(Sale::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_user_id');
    }

    public function authorizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'authorized_by_user_id');
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }
}

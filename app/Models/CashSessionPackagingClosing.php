<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashSessionPackagingClosing extends Model
{
    protected $fillable = [
        'cash_session_id',
        'packaging_item_id',
        'opening_qty',
        'approved_adjustment_in_qty',
        'approved_adjustment_out_qty',
        'pending_adjustment_in_qty',
        'pending_adjustment_out_qty',
        'closing_physical_qty',
        'actual_used_qty',
        'estimated_sales_used_qty',
        'difference_qty',
    ];

    protected $casts = [
        'opening_qty' => 'decimal:2',
        'approved_adjustment_in_qty' => 'decimal:2',
        'approved_adjustment_out_qty' => 'decimal:2',
        'pending_adjustment_in_qty' => 'decimal:2',
        'pending_adjustment_out_qty' => 'decimal:2',
        'closing_physical_qty' => 'decimal:2',
        'actual_used_qty' => 'decimal:2',
        'estimated_sales_used_qty' => 'decimal:2',
        'difference_qty' => 'decimal:2',
    ];

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function packagingItem(): BelongsTo
    {
        return $this->belongsTo(PackagingItem::class);
    }
}

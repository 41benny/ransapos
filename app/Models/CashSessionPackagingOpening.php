<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashSessionPackagingOpening extends Model
{
    protected $fillable = [
        'cash_session_id',
        'packaging_item_id',
        'opening_qty',
        'source_last_closing_qty',
        'is_manual_corrected',
        'created_by',
    ];

    protected $casts = [
        'opening_qty' => 'decimal:2',
        'source_last_closing_qty' => 'decimal:2',
        'is_manual_corrected' => 'boolean',
    ];

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function packagingItem(): BelongsTo
    {
        return $this->belongsTo(PackagingItem::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}

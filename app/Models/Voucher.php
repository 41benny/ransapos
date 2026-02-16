<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'outlet_id',
        'discount_type',
        'discount_value',
        'min_purchase',
        'max_discount_amount',
        'start_date',
        'end_date',
        'usage_limit',
        'used_count',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'discount_value' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'max_discount_amount' => 'decimal:2',
        'usage_limit' => 'integer',
        'used_count' => 'integer',
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeActiveOn($query, ?Carbon $date = null)
    {
        $onDate = ($date ?? now())->toDateString();

        return $query
            ->whereDate('start_date', '<=', $onDate)
            ->whereDate('end_date', '>=', $onDate);
    }

    public function scopeForOutlet($query, ?int $outletId)
    {
        return $query->where(function ($q) use ($outletId) {
            $q->whereNull('outlet_id');

            if ($outletId) {
                $q->orWhere('outlet_id', $outletId);
            }
        });
    }

    public function isValidFor(?int $outletId, float $subtotal, ?Carbon $date = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $checkDate = ($date ?? now())->toDateString();
        if ($this->start_date?->toDateString() > $checkDate || $this->end_date?->toDateString() < $checkDate) {
            return false;
        }

        if ($this->outlet_id !== null && (int) $this->outlet_id !== (int) $outletId) {
            return false;
        }

        if ($subtotal < (float) $this->min_purchase) {
            return false;
        }

        if ($this->usage_limit !== null && $this->used_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function calculateDiscountAmount(float $subtotal): float
    {
        if ($this->discount_type === 'percentage') {
            $amount = $subtotal * ((float) $this->discount_value / 100);
        } else {
            $amount = (float) $this->discount_value;
        }

        if ($this->max_discount_amount !== null) {
            $amount = min($amount, (float) $this->max_discount_amount);
        }

        return max(0, min($amount, $subtotal));
    }
}


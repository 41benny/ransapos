<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Promotion extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'outlet_id',
        'start_date',
        'end_date',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
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

    public function categoryRules(): HasMany
    {
        return $this->hasMany(PromotionCategoryRule::class);
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

    public function isValidFor(?int $outletId, ?Carbon $date = null): bool
    {
        if (!$this->is_active) {
            return false;
        }

        $checkDate = ($date ?? now())->toDateString();
        if ($this->start_date?->toDateString() > $checkDate || $this->end_date?->toDateString() < $checkDate) {
            return false;
        }

        return $this->outlet_id === null || (int) $this->outlet_id === (int) $outletId;
    }
}


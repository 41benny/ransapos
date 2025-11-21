<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    protected $fillable = [
        'customer_code',
        'name',
        'phone',
        'email',
        'address',
        'customer_type',
        'member_tier',
        'loyalty_points',
        'total_spending',
        'total_transactions',
        'birth_date',
        'gender',
        'notes',
        'member_since',
        'last_visit',
        'is_active',
    ];

    protected $casts = [
        'birth_date' => 'date',
        'member_since' => 'date',
        'last_visit' => 'date',
        'loyalty_points' => 'integer',
        'total_spending' => 'decimal:2',
        'total_transactions' => 'integer',
        'is_active' => 'boolean',
    ];

    /**
     * Relasi ke sales
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class)->orderBy('created_at', 'desc');
    }

    /**
     * Scope untuk active customers
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope untuk filter by type
     */
    public function scopeByType($query, $type)
    {
        return $query->where('customer_type', $type);
    }

    /**
     * Scope untuk filter by tier
     */
    public function scopeByTier($query, $tier)
    {
        return $query->where('member_tier', $tier);
    }

    /**
     * Scope untuk VIP customers
     */
    public function scopeVip($query)
    {
        return $query->where('customer_type', 'vip');
    }

    /**
     * Scope untuk members
     */
    public function scopeMembers($query)
    {
        return $query->where('customer_type', 'member');
    }

    /**
     * Get customer type label
     */
    public function getTypeLabel(): string
    {
        return match($this->customer_type) {
            'regular' => 'Regular',
            'member' => 'Member',
            'vip' => 'VIP',
            default => 'Unknown',
        };
    }

    /**
     * Get customer type badge color
     */
    public function getTypeBadgeAttribute(): string
    {
        return match($this->customer_type) {
            'regular' => 'gray',
            'member' => 'blue',
            'vip' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Get member tier label
     */
    public function getTierLabel(): string
    {
        if (!$this->member_tier) {
            return '-';
        }

        return match($this->member_tier) {
            'bronze' => 'Bronze',
            'silver' => 'Silver',
            'gold' => 'Gold',
            'platinum' => 'Platinum',
            default => '-',
        };
    }

    /**
     * Get member tier badge color
     */
    public function getTierBadgeAttribute(): string
    {
        return match($this->member_tier) {
            'bronze' => 'orange',
            'silver' => 'gray',
            'gold' => 'yellow',
            'platinum' => 'purple',
            default => 'gray',
        };
    }

    /**
     * Check if customer is VIP
     */
    public function isVip(): bool
    {
        return $this->customer_type === 'vip';
    }

    /**
     * Check if customer is member
     */
    public function isMember(): bool
    {
        return $this->customer_type === 'member';
    }

    /**
     * Add loyalty points
     */
    public function addPoints(int $points): void
    {
        $this->increment('loyalty_points', $points);
        $this->updateMemberTier();
    }

    /**
     * Redeem loyalty points
     */
    public function redeemPoints(int $points): bool
    {
        if ($this->loyalty_points < $points) {
            return false;
        }

        $this->decrement('loyalty_points', $points);
        $this->updateMemberTier();
        return true;
    }

    /**
     * Update member tier based on total spending
     */
    public function updateMemberTier(): void
    {
        if ($this->customer_type !== 'member') {
            return;
        }

        $spending = (float) $this->total_spending;

        if ($spending >= 50000000) { // 50 juta
            $tier = 'platinum';
        } elseif ($spending >= 20000000) { // 20 juta
            $tier = 'gold';
        } elseif ($spending >= 10000000) { // 10 juta
            $tier = 'silver';
        } else {
            $tier = 'bronze';
        }

        if ($this->member_tier !== $tier) {
            $this->update(['member_tier' => $tier]);
        }
    }

    /**
     * Update customer stats after sale
     */
    public function updateStats(float $amount): void
    {
        $this->increment('total_transactions');
        $this->increment('total_spending', $amount);
        $this->update(['last_visit' => now()]);
        $this->updateMemberTier();
    }

    /**
     * Get age
     */
    public function getAgeAttribute(): ?int
    {
        if (!$this->birth_date) {
            return null;
        }

        return $this->birth_date->age;
    }

    /**
     * Get average transaction
     */
    public function getAverageTransactionAttribute(): float
    {
        if ($this->total_transactions == 0) {
            return 0;
        }

        return $this->total_spending / $this->total_transactions;
    }
}

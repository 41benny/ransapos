<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_active',
        'is_online_only',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_online_only' => 'boolean',
    ];

    /**
     * Relasi ke payments
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

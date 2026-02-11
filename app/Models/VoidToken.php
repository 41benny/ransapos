<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VoidToken extends Model
{
    protected $fillable = [
        'token',
        'outlet_id',
        'is_used',
        'generated_by',
        'used_by',
        'used_for_sale_id',
    ];

    protected $casts = [
        'is_used' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_used', false);
    }

    public function outlet()
    {
        return $this->belongsTo(Outlet::class);
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    public function usedBy()
    {
        return $this->belongsTo(User::class, 'used_by');
    }

    public function sale()
    {
        return $this->belongsTo(\App\Models\Sale::class, 'used_for_sale_id');
    }
}

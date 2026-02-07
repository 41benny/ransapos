<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PosDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'outlet_id',
        'name',
        'token_hash',
        'pairing_code',
        'pairing_expires_at',
        'paired_at',
        'last_seen_at',
        'is_active',
        'created_by',
        'revoked_at',
    ];

    protected $casts = [
        'pairing_expires_at' => 'datetime',
        'paired_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'revoked_at' => 'datetime',
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

    public function isPaired(): bool
    {
        return !empty($this->token_hash) && $this->paired_at !== null;
    }
}

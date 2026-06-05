<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'user_name',
        'event',
        'subject_type',
        'subject_id',
        'description',
        'properties',
        'ip_address',
        'user_agent',
        'url',
        'method',
    ];

    protected $casts = [
        'properties' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withDefault([
            'name' => null,
        ]);
    }

    /**
     * Label event siap tampil + warna badge.
     */
    public function eventLabel(): string
    {
        return match ($this->event) {
            'created'      => 'Tambah',
            'updated'      => 'Ubah',
            'deleted'      => 'Hapus',
            'login'        => 'Login',
            'logout'       => 'Logout',
            'login_failed' => 'Login Gagal',
            default        => ucfirst((string) $this->event),
        };
    }

    public function eventColor(): string
    {
        return match ($this->event) {
            'created'      => 'green',
            'updated'      => 'amber',
            'deleted'      => 'rose',
            'login'        => 'blue',
            'logout'       => 'slate',
            'login_failed' => 'red',
            default        => 'gray',
        };
    }

    /**
     * Nama pelaku (snapshot, fallback ke relasi user).
     */
    public function actorName(): string
    {
        return $this->user_name
            ?? $this->user?->name
            ?? 'Sistem';
    }
}

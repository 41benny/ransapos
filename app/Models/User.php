<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role_id',
        'outlet_id',
        'is_active',
        'attendance_pin',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Relasi ke role
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * Relasi ke outlet
     */
    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    /**
     * Relasi ke cash sessions
     */
    public function cashSessions(): HasMany
    {
        return $this->hasMany(CashSession::class);
    }

    /**
     * Relasi ke sales
     */
    public function sales(): HasMany
    {
        return $this->hasMany(Sale::class);
    }

    /**
     * Relasi ke products yang dibuat
     */
    public function createdProducts(): HasMany
    {
        return $this->hasMany(Product::class, 'created_by');
    }

    /**
     * Relasi ke stock mutations yang dibuat
     */
    public function stockMutations(): HasMany
    {
        return $this->hasMany(StockMutation::class, 'created_by');
    }

    /**
     * Relasi ke purchases yang dibuat
     */
    public function purchases(): HasMany
    {
        return $this->hasMany(Purchase::class, 'created_by');
    }

    /**
     * Relasi ke attendances (absensi karyawan)
     */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    /**
     * Cek apakah user memiliki role tertentu
     */
    public function hasRole(string|array $roles): bool
    {
        $roleName = $this->role?->name;

        if (!$roleName) {
            return false;
        }

        if (is_array($roles)) {
            return in_array($roleName, $roles, true);
        }

        return $roleName === $roles;
    }

    /**
     * Cek apakah user adalah admin
     */
    public function isAdmin(): bool
    {
        return $this->role?->name === 'admin';
    }

    /**
     * Cek apakah user adalah kasir
     */
    public function isKasir(): bool
    {
        return $this->role?->name === 'kasir';
    }

    /**
     * Cek apakah user adalah manager
     */
    public function isManager(): bool
    {
        return $this->role?->name === 'manager';
    }
}

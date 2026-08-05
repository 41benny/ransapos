<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PaymentMethod extends Model
{
    protected $fillable = [
        'code',
        'name',
        'is_cash',
        'is_active',
        'is_online_only',
    ];

    protected $casts = [
        'is_cash' => 'boolean',
        'is_active' => 'boolean',
        'is_online_only' => 'boolean',
    ];

    /**
     * Satu-satunya sumber kebenaran apakah sebuah metode itu tunai (JUAL-04).
     *
     * Sebelumnya deteksi dilakukan dengan mencocokkan string nama/kode di
     * beberapa tempat dengan aturan yang berbeda-beda, dan pernah menghasilkan
     * bug QRIS terhitung sebagai kas.
     *
     * `is_cash` NULL berarti belum pernah ditentukan siapa pun — bukan berarti
     * "bukan tunai". Untuk baris seperti itu heuristik lama tetap dipakai,
     * supaya metode pembayaran yang dibuat sebelum atau sesudah migrasi tidak
     * diam-diam berubah arti.
     */
    public function isCash(): bool
    {
        if ($this->getAttribute('is_cash') !== null) {
            return (bool) $this->getAttribute('is_cash');
        }

        $code = strtoupper(trim((string) $this->code));
        $name = strtolower(trim((string) $this->name));

        return $code === 'CASH'
            || str_contains($name, 'cash')
            || str_contains($name, 'tunai');
    }

    public function scopeCash($query)
    {
        return $query->where('is_cash', true);
    }

    /**
     * Relasi ke payments
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }
}

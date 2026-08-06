<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Buku pembantu pengeluaran kios per shift.
 *
 * `cash_transactions` tetap jadi buku besar; tabel ini yang jadi sumber
 * kebenaran matematika shift dan menyimpan detail operasional (penerima,
 * foto nota) yang tidak layak dijejalkan ke deskripsi string.
 */
class CashSessionExpense extends Model
{
    public const SOURCE_DRAWER = 'drawer';
    public const SOURCE_NONCASH = 'noncash';
    public const SOURCE_PERSONAL_ADVANCE = 'personal_advance';

    public const STATUS_POSTED = 'posted';
    public const STATUS_VOID = 'void';

    public const DIRECTION_OUT = 'out';
    public const DIRECTION_IN = 'in';

    protected $fillable = [
        'cash_session_id',
        'outlet_id',
        'coa_account_id',
        'recipient_name',
        'description',
        'amount',
        'payment_source',
        'direction',
        'source_cash_account_id',
        'attachment_path',
        'status',
        'cash_transaction_id',
        'idempotency_key',
        'created_by',
        'voided_by',
        'void_reason',
        'voided_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'voided_at' => 'datetime',
    ];

    public function cashSession(): BelongsTo
    {
        return $this->belongsTo(CashSession::class);
    }

    public function outlet(): BelongsTo
    {
        return $this->belongsTo(Outlet::class);
    }

    public function coaAccount(): BelongsTo
    {
        return $this->belongsTo(CoaAccount::class, 'coa_account_id');
    }

    public function cashTransaction(): BelongsTo
    {
        return $this->belongsTo(CashTransaction::class, 'cash_transaction_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function voider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    /**
     * Hanya baris yang masih berlaku. Baris void tetap disimpan sebagai jejak
     * audit (KLR-08) tapi tidak boleh ikut hitungan apa pun.
     */
    public function scopePosted($query)
    {
        return $query->where('status', self::STATUS_POSTED);
    }

    /**
     * Hanya pengeluaran yang benar-benar mengurangi uang di laci.
     * Non-tunai (KLR-02) dan talangan pribadi (KLR-03) tidak termasuk.
     */
    public function scopeFromDrawer($query)
    {
        return $query->where('payment_source', self::SOURCE_DRAWER);
    }

    /**
     * Baris pengeluaran saja. Wajib dipakai di setiap penjumlahan biaya:
     * sejak kas masuk ikut menumpang tabel ini, penjumlahan tanpa filter arah
     * akan menganggap modal tambahan owner sebagai pengeluaran.
     */
    public function scopeOutgoing($query)
    {
        return $query->where('direction', self::DIRECTION_OUT);
    }

    public function scopeIncoming($query)
    {
        return $query->where('direction', self::DIRECTION_IN);
    }

    public function isVoided(): bool
    {
        return $this->status === self::STATUS_VOID;
    }

    public function isCashIn(): bool
    {
        return $this->direction === self::DIRECTION_IN;
    }

    /**
     * Kas masuk dari uang pribadi pemilik, bukan pindah buku dari akun kas
     * outlet. Dibedakan dari ketiadaan rekening sumber: uang yang datang dari
     * kantong owner memang tidak keluar dari akun mana pun.
     */
    public function isOwnerCapital(): bool
    {
        return $this->isCashIn() && $this->source_cash_account_id === null;
    }

    public function reducesDrawer(): bool
    {
        return $this->payment_source === self::SOURCE_DRAWER
            && $this->direction === self::DIRECTION_OUT
            && $this->status === self::STATUS_POSTED;
    }

    public function increasesDrawer(): bool
    {
        return $this->direction === self::DIRECTION_IN
            && $this->status === self::STATUS_POSTED;
    }

    public function sourceCashAccount(): BelongsTo
    {
        return $this->belongsTo(CashAccount::class, 'source_cash_account_id');
    }

    /**
     * Semua label sumber, termasuk yang sudah tidak boleh dipilih lagi.
     *
     * Dipakai untuk MENAMPILKAN data — termasuk baris lama ber-sumber
     * `personal_advance` — supaya riwayat tidak berubah jadi kode mentah
     * hanya karena opsinya dicabut dari form.
     *
     * @return array<string, string>
     */
    public static function paymentSourceLabels(): array
    {
        return [
            self::SOURCE_DRAWER => 'Tunai dari laci',
            self::SOURCE_NONCASH => 'Non-tunai (transfer/QRIS outlet)',
            self::SOURCE_PERSONAL_ADVANCE => 'Talangan uang pribadi',
        ];
    }

    /**
     * Sumber yang boleh DIPILIH kasir di POS.
     *
     * `personal_advance` sengaja dikeluarkan: talangan uang pribadi tidak
     * menyentuh akun kas mana pun, sedangkan `cash_transactions` adalah buku
     * kas yang setiap barisnya wajib menempel ke sebuah akun kas. Tanpa jurnal
     * non-kas, baris talangan tidak akan pernah sampai ke Laba Rugi dan
     * biayanya diam-diam hilang. Alurnya diganti: kasir minta ganti uang dari
     * laci, lalu dicatat sebagai pengeluaran laci biasa.
     *
     * @return array<string, string>
     */
    public static function selectablePaymentSources(): array
    {
        return [
            self::SOURCE_DRAWER => self::paymentSourceLabels()[self::SOURCE_DRAWER],
            self::SOURCE_NONCASH => self::paymentSourceLabels()[self::SOURCE_NONCASH],
        ];
    }
}

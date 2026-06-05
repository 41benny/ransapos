<?php

namespace App\Support;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class ActivityLogger
{
    /**
     * Daftar model yang diaudit beserta label tampilannya.
     * Tambahkan model baru di sini agar otomatis tercatat.
     *
     * @return array<class-string, string>
     */
    public static function auditedModels(): array
    {
        return [
            \App\Models\Product::class          => 'Produk',
            \App\Models\ProductCategory::class  => 'Kategori Produk',
            \App\Models\Outlet::class           => 'Outlet',
            \App\Models\User::class             => 'User',
            \App\Models\Role::class             => 'Role',
            \App\Models\Supplier::class         => 'Supplier',
            \App\Models\Customer::class         => 'Customer',
            \App\Models\PaymentMethod::class    => 'Metode Bayar',
            \App\Models\SalesType::class        => 'Metode Penjualan',
            \App\Models\CoaAccount::class       => 'Akun COA',
            \App\Models\CashAccount::class      => 'Kas & Bank',
            \App\Models\ExpenseCategory::class  => 'Kategori Biaya',
            \App\Models\Expense::class          => 'Expense',
            \App\Models\Purchase::class         => 'Pembelian (PO)',
            \App\Models\Sale::class             => 'Penjualan',
            \App\Models\StockTransfer::class    => 'Transfer Stok',
            \App\Models\Promotion::class        => 'Promo',
            \App\Models\Voucher::class          => 'Voucher',
            \App\Models\Setting::class          => 'Pengaturan',
            \App\Models\Shift::class            => 'Shift Absensi',
            \App\Models\BomHeader::class        => 'BOM',
            \App\Models\Production::class        => 'Produksi',
        ];
    }

    public static function labelFor(Model $model): string
    {
        return self::auditedModels()[get_class($model)] ?? class_basename($model);
    }

    /**
     * Ambil pengenal yang mudah dibaca dari sebuah model.
     */
    public static function identifier(Model $model): ?string
    {
        foreach (['name', 'invoice_number', 'code', 'title', 'display_name', 'reference', 'email'] as $key) {
            $value = $model->getAttribute($key);
            if (is_string($value) && $value !== '') {
                return $value;
            }
        }

        return $model->getKey() ? '#' . $model->getKey() : null;
    }

    /**
     * Tulis satu baris log.
     *
     * @param  array<string, mixed>  $properties
     */
    public static function log(string $event, ?string $description, ?Model $subject = null, array $properties = []): void
    {
        $user = Auth::user();

        ActivityLog::create([
            'user_id'      => $user?->getKey(),
            'user_name'    => $user?->name,
            'event'        => $event,
            'subject_type' => $subject ? get_class($subject) : null,
            'subject_id'   => $subject?->getKey(),
            'description'  => $description ?? $event,
            'properties'   => $properties ?: null,
            'ip_address'   => Request::ip(),
            'user_agent'   => substr((string) Request::userAgent(), 0, 1000) ?: null,
            'url'          => substr((string) Request::fullUrl(), 0, 255) ?: null,
            'method'       => Request::method(),
        ]);
    }

    /**
     * Field yang tidak boleh ikut tercatat (sensitif / noise).
     *
     * @return array<int, string>
     */
    public static function ignoredAttributes(Model $model): array
    {
        return array_values(array_unique(array_merge(
            $model->getHidden(),
            ['password', 'remember_token', 'updated_at', 'created_at', 'pin', 'pin_hash'],
        )));
    }
}

<?php

namespace App\Http\Requests\POS;

use App\Models\CashSessionExpense;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDrawerExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'coa_account_id' => 'required|integer|exists:coa_accounts,id',
            'recipient_name' => 'required|string|max:60',
            'description' => 'required|string|max:150',
            'amount' => 'required|numeric|min:1',
            'payment_source' => [
                'required',
                Rule::in(array_keys(CashSessionExpense::selectablePaymentSources())),
            ],
            // Wajib hanya saat non-tunai; kepemilikan outlet-nya diverifikasi
            // ulang di service supaya tidak bisa menunjuk rekening outlet lain.
            'source_cash_account_id' => [
                'nullable',
                'required_if:payment_source,' . CashSessionExpense::SOURCE_NONCASH,
                'integer',
                'exists:cash_accounts,id',
            ],
            'idempotency_key' => 'nullable|string|max:64',
        ];
    }

    /**
     * Tanggal transaksi sengaja TIDAK ada di daftar aturan: tanggal pembukuan
     * diambil dari shift oleh sistem, bukan dari input kasir (WKT-02).
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'coa_account_id' => 'kategori biaya',
            'recipient_name' => 'nama penerima',
            'description' => 'keperluan',
            'amount' => 'jumlah',
            'payment_source' => 'sumber pembayaran',
            'source_cash_account_id' => 'rekening sumber dana',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Jumlah pengeluaran minimal Rp 1.',
            'coa_account_id.required' => 'Pilih dulu kategori biayanya.',
            'source_cash_account_id.required_if' => 'Pilih rekening sumber dananya untuk pengeluaran non-tunai.',
        ];
    }
}

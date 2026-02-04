<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashTransactionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'type' => 'required|in:in,out',
            'coa_account_id' => 'nullable|exists:coa_accounts,id',
            'transaction_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'description' => 'required|string|max:255',
            'reference_type' => 'nullable|string|max:50',
            'reference_id' => 'nullable|integer',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'cash_account_id' => 'akun kas/bank',
            'type' => 'jenis transaksi',
            'coa_account_id' => 'akun biaya (COA)',
            'transaction_date' => 'tanggal transaksi',
            'amount' => 'jumlah',
            'description' => 'deskripsi',
            'reference_type' => 'tipe referensi',
            'reference_id' => 'ID referensi',
            'notes' => 'catatan',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'cash_account_id.required' => 'Akun kas/bank harus dipilih',
            'cash_account_id.exists' => 'Akun kas/bank tidak valid',
            'type.required' => 'Jenis transaksi harus dipilih',
            'type.in' => 'Jenis transaksi harus Masuk atau Keluar',
            'transaction_date.required' => 'Tanggal transaksi harus diisi',
            'amount.required' => 'Jumlah harus diisi',
            'amount.min' => 'Jumlah minimal Rp 0,01',
            'description.required' => 'Deskripsi harus diisi',
        ];
    }
}

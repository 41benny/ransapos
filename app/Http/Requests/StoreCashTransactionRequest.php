<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCashTransactionRequest extends FormRequest
{
    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $category = $this->input('transaction_category', 'general');
        $rows = $this->input('rows', []);

        if ($category === 'general' && is_array($rows)) {
            $rows = array_values(array_filter($rows, function ($row) {
                if (!is_array($row)) {
                    return false;
                }

                foreach (['coa_account_id', 'amount', 'description'] as $key) {
                    if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
                        return true;
                    }
                }

                return false;
            }));
        } else {
            $rows = [];
        }

        $mergeData = [
            'transaction_category' => $category,
            'rows' => $rows,
        ];

        if (in_array($category, ['purchase_payment', 'book_transfer'], true)) {
            $mergeData['type'] = 'out';
        }

        $this->merge([
            ...$mergeData,
        ]);
    }

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
        $category = $this->input('transaction_category', 'general');

        $rules = [
            'transaction_category' => 'required|in:general,purchase_payment,book_transfer',
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
        ];

        if ($category === 'general') {
            $rules['type'] = 'required|in:in,out';
            $rules['rows'] = 'required|array|min:1';
            $rules['rows.*.coa_account_id'] = 'required|exists:coa_accounts,id';
            $rules['rows.*.amount'] = 'required|numeric|min:0.01';
            $rules['rows.*.description'] = 'required|string|max:255';
        }

        if ($category === 'purchase_payment') {
            $rules['purchase_id'] = 'required|exists:purchases,id';
            $rules['purchase_amount'] = 'required|numeric|min:0.01';
            $rules['purchase_notes'] = 'nullable|string';
        }

        if ($category === 'book_transfer') {
            $rules['transfer_to_cash_account_id'] = 'required|exists:cash_accounts,id|different:cash_account_id';
            $rules['transfer_amount'] = 'required|numeric|min:0.01';
            $rules['transfer_description'] = 'required|string|max:500';
            $rules['transfer_notes'] = 'nullable|string';
        }

        return $rules;
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'transaction_category' => 'kategori transaksi',
            'cash_account_id' => 'akun kas/bank',
            'type' => 'jenis transaksi',
            'transaction_date' => 'tanggal transaksi',
            'notes' => 'catatan',
            'rows' => 'baris transaksi',
            'rows.*.coa_account_id' => 'akun COA',
            'rows.*.amount' => 'jumlah',
            'rows.*.description' => 'deskripsi',
            'purchase_id' => 'nomor purchase',
            'purchase_amount' => 'jumlah pembayaran purchase',
            'purchase_notes' => 'catatan pembayaran purchase',
            'transfer_to_cash_account_id' => 'rekening tujuan pindah buku',
            'transfer_amount' => 'jumlah pindah buku',
            'transfer_description' => 'deskripsi pindah buku',
            'transfer_notes' => 'catatan pindah buku',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'transaction_category.required' => 'Kategori transaksi harus dipilih',
            'transaction_category.in' => 'Kategori transaksi tidak valid',
            'cash_account_id.required' => 'Akun kas/bank harus dipilih',
            'cash_account_id.exists' => 'Akun kas/bank tidak valid',
            'type.required' => 'Jenis transaksi harus dipilih',
            'type.in' => 'Jenis transaksi harus Masuk atau Keluar',
            'transaction_date.required' => 'Tanggal transaksi harus diisi',
            'rows.required' => 'Minimal harus ada 1 baris transaksi',
            'rows.min' => 'Minimal harus ada 1 baris transaksi',
            'rows.*.coa_account_id.required' => 'Akun COA harus dipilih',
            'rows.*.amount.required' => 'Jumlah harus diisi',
            'rows.*.amount.min' => 'Jumlah minimal Rp 0,01',
            'rows.*.description.required' => 'Deskripsi harus diisi',
            'purchase_id.required' => 'Purchase harus dipilih',
            'purchase_id.exists' => 'Purchase tidak valid',
            'purchase_amount.required' => 'Jumlah pembayaran purchase harus diisi',
            'purchase_amount.min' => 'Jumlah pembayaran purchase minimal Rp 0,01',
            'transfer_to_cash_account_id.required' => 'Rekening tujuan pindah buku harus dipilih',
            'transfer_to_cash_account_id.exists' => 'Rekening tujuan tidak valid',
            'transfer_to_cash_account_id.different' => 'Rekening tujuan harus berbeda dengan rekening sumber',
            'transfer_amount.required' => 'Jumlah pindah buku harus diisi',
            'transfer_amount.min' => 'Jumlah pindah buku minimal Rp 0,01',
            'transfer_description.required' => 'Deskripsi pindah buku harus diisi',
        ];
    }
}

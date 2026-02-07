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
        $rows = $this->input('rows', []);

        if (is_array($rows)) {
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
        }

        $this->merge([
            'rows' => $rows,
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
        return [
            'cash_account_id' => 'required|exists:cash_accounts,id',
            'type' => 'required|in:in,out',
            'transaction_date' => 'required|date',
            'notes' => 'nullable|string',
            'rows' => 'required|array|min:1',
            'rows.*.coa_account_id' => 'required|exists:coa_accounts,id',
            'rows.*.amount' => 'required|numeric|min:0.01',
            'rows.*.description' => 'required|string|max:255',
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
            'transaction_date' => 'tanggal transaksi',
            'notes' => 'catatan',
            'rows' => 'baris transaksi',
            'rows.*.coa_account_id' => 'akun COA',
            'rows.*.amount' => 'jumlah',
            'rows.*.description' => 'deskripsi',
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
            'rows.required' => 'Minimal harus ada 1 baris transaksi',
            'rows.min' => 'Minimal harus ada 1 baris transaksi',
            'rows.*.coa_account_id.required' => 'Akun COA harus dipilih',
            'rows.*.amount.required' => 'Jumlah harus diisi',
            'rows.*.amount.min' => 'Jumlah minimal Rp 0,01',
            'rows.*.description.required' => 'Deskripsi harus diisi',
        ];
    }
}

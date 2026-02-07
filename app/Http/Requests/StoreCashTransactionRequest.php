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

                foreach (['type', 'coa_account_id', 'expense_id', 'amount', 'description', 'notes'] as $key) {
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
            'transaction_date' => 'required|date',
            'rows' => 'required|array|min:1',
            'rows.*.type' => 'required|in:in,out',
            'rows.*.coa_account_id' => 'nullable|exists:coa_accounts,id',
            'rows.*.expense_id' => 'nullable|exists:expenses,id',
            'rows.*.amount' => 'required|numeric|min:0.01',
            'rows.*.description' => 'required|string|max:255',
            'rows.*.notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'cash_account_id' => 'akun kas/bank',
            'transaction_date' => 'tanggal transaksi',
            'rows' => 'baris transaksi',
            'rows.*.type' => 'jenis transaksi',
            'rows.*.coa_account_id' => 'akun biaya (COA)',
            'rows.*.expense_id' => 'pengajuan biaya',
            'rows.*.amount' => 'jumlah',
            'rows.*.description' => 'deskripsi',
            'rows.*.notes' => 'catatan',
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
            'transaction_date.required' => 'Tanggal transaksi harus diisi',
            'rows.required' => 'Minimal harus ada 1 baris transaksi',
            'rows.min' => 'Minimal harus ada 1 baris transaksi',
            'rows.*.type.required' => 'Jenis transaksi harus dipilih',
            'rows.*.type.in' => 'Jenis transaksi harus Masuk atau Keluar',
            'rows.*.amount.required' => 'Jumlah harus diisi',
            'rows.*.amount.min' => 'Jumlah minimal Rp 0,01',
            'rows.*.description.required' => 'Deskripsi harus diisi',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            $rows = $this->input('rows', []);

            foreach ($rows as $index => $row) {
                $type = $row['type'] ?? null;
                $coa = $row['coa_account_id'] ?? null;

                if ($type === 'out' && empty($coa)) {
                    $validator->errors()->add(
                        "rows.{$index}.coa_account_id",
                        'Baris ' . ($index + 1) . ': Akun biaya (COA) wajib diisi untuk transaksi keluar.'
                    );
                }
            }
        });
    }
}

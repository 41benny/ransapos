<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCashAccountRequest extends FormRequest
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
            'outlet_id' => 'required|exists:outlets,id',
            'name' => 'required|string|max:255',
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('cash_accounts', 'code')
                    ->where(fn ($query) => $query->where('outlet_id', $this->input('outlet_id'))),
            ],
            'type' => 'required|in:cash,bank',
            'usage_type' => 'required|in:operational,petty_cash',
            'bank_name' => 'nullable|string|max:100',
            'account_number' => 'nullable|string|max:50',
            'account_holder' => 'nullable|string|max:200',
            'branch' => 'nullable|string|max:200',
            'is_active' => 'boolean',
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'outlet_id' => 'outlet',
            'name' => 'nama akun',
            'code' => 'kode akun',
            'type' => 'jenis akun',
            'usage_type' => 'tipe penggunaan akun',
            'bank_name' => 'nama bank',
            'account_number' => 'nomor rekening',
            'account_holder' => 'nama pemegang rekening',
            'branch' => 'cabang bank',
            'is_active' => 'status aktif',
            'opening_balance' => 'saldo awal',
            'notes' => 'catatan',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'outlet_id.required' => 'Outlet harus dipilih',
            'outlet_id.exists' => 'Outlet tidak valid',
            'name.required' => 'Nama akun harus diisi',
            'code.required' => 'Kode akun harus diisi',
            'code.unique' => 'Kode akun sudah digunakan di outlet ini',
            'type.required' => 'Jenis akun harus dipilih',
            'type.in' => 'Jenis akun harus Cash atau Bank',
            'usage_type.required' => 'Tipe penggunaan akun harus dipilih',
            'usage_type.in' => 'Tipe penggunaan akun tidak valid',
            'opening_balance.required' => 'Saldo awal harus diisi',
            'opening_balance.min' => 'Saldo awal tidak boleh negatif',
        ];
    }
}

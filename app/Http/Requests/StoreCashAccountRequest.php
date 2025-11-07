<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:50|unique:cash_accounts,code',
            'type' => 'required|in:cash,bank',
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
            'name' => 'nama akun',
            'code' => 'kode akun',
            'type' => 'jenis akun',
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
            'name.required' => 'Nama akun harus diisi',
            'code.required' => 'Kode akun harus diisi',
            'code.unique' => 'Kode akun sudah digunakan',
            'type.required' => 'Jenis akun harus dipilih',
            'type.in' => 'Jenis akun harus Cash atau Bank',
            'opening_balance.required' => 'Saldo awal harus diisi',
            'opening_balance.min' => 'Saldo awal tidak boleh negatif',
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreCoaAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'code' => 'required|string|max:50|unique:coa_accounts,code',
            'name' => 'required|string|max:255',
            'type' => 'required|in:income,expense,asset,liability,equity',
            'group' => 'required|string|max:100',
            'is_active' => 'boolean',
            'notes' => 'nullable|string',
        ];
    }

    public function attributes(): array
    {
        return [
            'code' => 'kode akun',
            'name' => 'nama akun',
            'type' => 'tipe akun',
            'group' => 'grup akun',
            'is_active' => 'status aktif',
            'notes' => 'catatan',
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode akun harus diisi',
            'code.unique' => 'Kode akun sudah digunakan',
            'name.required' => 'Nama akun harus diisi',
            'type.required' => 'Tipe akun harus dipilih',
            'type.in' => 'Tipe akun tidak valid',
            'group.required' => 'Grup akun harus diisi',
        ];
    }
}

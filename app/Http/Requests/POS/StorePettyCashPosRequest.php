<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class StorePettyCashPosRequest extends FormRequest
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
            'transaction_date' => 'required|date',
            'recipient_name' => 'required|string|max:60',
            'description' => 'required|string|max:150',
            'amount' => 'required|numeric|min:0.01',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     */
    public function attributes(): array
    {
        return [
            'transaction_date' => 'tanggal transaksi',
            'recipient_name' => 'nama penerima',
            'description' => 'deskripsi',
            'amount' => 'jumlah',
        ];
    }
}

<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class CloseCashSessionRequest extends FormRequest
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
     */
    public function rules(): array
    {
        return [
            'actual_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
            'packaging_physical' => 'nullable|array',
            'packaging_physical.*' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'actual_balance.required' => 'Kas fisik aktual harus diisi',
            'actual_balance.numeric' => 'Kas fisik aktual harus berupa angka',
            'actual_balance.min' => 'Kas fisik aktual minimal 0',
            'notes.max' => 'Catatan maksimal 500 karakter',
        ];
    }
}

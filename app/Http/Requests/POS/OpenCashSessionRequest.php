<?php

namespace App\Http\Requests\POS;

use App\Models\CashSession;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class OpenCashSessionRequest extends FormRequest
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
            'outlet_id' => 'required|exists:outlets,id',
            'opening_balance' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:500',
        ];
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'outlet_id.required' => 'Outlet harus dipilih',
            'outlet_id.exists' => 'Outlet tidak valid',
            'opening_balance.required' => 'Saldo awal harus diisi',
            'opening_balance.numeric' => 'Saldo awal harus berupa angka',
            'opening_balance.min' => 'Saldo awal minimal 0',
            'notes.max' => 'Catatan maksimal 500 karakter',
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Cek apakah sudah ada session yang open untuk outlet ini
            // Gunakan user_id dari auth (atau hardcode untuk testing)
            $userId = auth()->id() ?? 2; // Default kasir id=2 untuk testing
            
            $existingSession = CashSession::where('outlet_id', $this->outlet_id)
                ->where('user_id', $userId)
                ->where('status', 'open')
                ->first();

            if ($existingSession) {
                $validator->errors()->add(
                    'outlet_id',
                    'Masih ada shift kasir yang belum ditutup untuk outlet ini.'
                );
            }
        });
    }
}

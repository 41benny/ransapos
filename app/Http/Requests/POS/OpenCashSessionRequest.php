<?php

namespace App\Http\Requests\POS;

use App\Models\CashSession;
use App\Services\CashSessionService;
use App\Services\PosDrawerExpenseService;
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
            'opening_override_reason' => 'nullable|string|max:200',
            'notes' => 'nullable|string|max:500',
            'packaging' => 'nullable|array',
            'packaging.*' => 'nullable|numeric|min:0',
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

            $this->validateOpeningBalanceChain($validator);
        });
    }

    /**
     * Tegakkan rantai modal awal (SHIFT-01) di sisi server.
     *
     * Wajib dicek di sini, bukan cuma lewat atribut readonly di form: atribut
     * itu hanya menghalangi pengetikan di layar dan bisa dilewati dengan
     * mengirim request langsung. Selama modal awal bisa diisi bebas, selisih
     * kas hari ini bisa ditutup hanya dengan mengarang modal shift berikutnya.
     */
    private function validateOpeningBalanceChain(Validator $validator): void
    {
        $expenseService = app(PosDrawerExpenseService::class);

        if (! $expenseService->isEnabled()) {
            return;
        }

        $suggested = app(CashSessionService::class)
            ->suggestedOpeningBalance((int) $this->outlet_id);

        // Belum ada shift tertutup sebelumnya: titik awal rantai, boleh manual.
        if ($suggested === null) {
            return;
        }

        $submitted = round((float) $this->opening_balance, 2);
        if (abs($submitted - round($suggested, 2)) < 0.01) {
            return;
        }

        $user = auth()->user();
        $canOverride = $user
            && ($user->hasPermission('pos.session.override_opening')
                || $user->hasRole(['admin', 'manager', 'superadmin']));

        if (! $canOverride) {
            $validator->errors()->add(
                'opening_balance',
                'Modal awal harus Rp ' . number_format($suggested, 0, ',', '.') .
                ' (modal disisakan shift sebelumnya). Minta supervisor untuk mengubahnya.'
            );

            return;
        }

        if (trim((string) $this->opening_override_reason) === '') {
            $validator->errors()->add(
                'opening_override_reason',
                'Alasan wajib diisi kalau modal awal berbeda dari modal disisakan shift sebelumnya.'
            );
        }
    }
}

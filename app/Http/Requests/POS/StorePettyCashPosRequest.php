<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class StorePettyCashPosRequest extends FormRequest
{
    /**
     * Normalize input sebelum validasi.
     */
    protected function prepareForValidation(): void
    {
        if (! $this->isMethod('post')) {
            return;
        }

        $rows = $this->input('rows', []);
        if (! is_array($rows)) {
            $rows = [];
        }

        $rows = array_values(array_filter($rows, function ($row) {
            if (! is_array($row)) {
                return false;
            }

            foreach (['recipient_name', 'description', 'amount'] as $key) {
                if (isset($row[$key]) && $row[$key] !== '' && $row[$key] !== null) {
                    return true;
                }
            }

            return false;
        }));

        // Backward compatibility jika masih ada form single-row lama.
        if (count($rows) === 0) {
            $legacyRecipient = $this->input('recipient_name');
            $legacyDescription = $this->input('description');
            $legacyAmount = $this->input('amount');

            if ($legacyRecipient !== null || $legacyDescription !== null || $legacyAmount !== null) {
                $rows[] = [
                    'recipient_name' => $legacyRecipient,
                    'description' => $legacyDescription,
                    'amount' => $legacyAmount,
                ];
            }
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
        if (! $this->isMethod('post')) {
            return [
                'transaction_date' => 'required|date',
                'recipient_name' => 'required|string|max:60',
                'description' => 'required|string|max:150',
                'amount' => 'required|numeric|min:0.01',
            ];
        }

        return [
            'transaction_date' => 'required|date',
            'rows' => 'required|array|min:1',
            'rows.*.recipient_name' => 'required|string|max:60',
            'rows.*.description' => 'required|string|max:150',
            'rows.*.amount' => 'required|numeric|min:0.01',
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
            'rows' => 'baris transaksi',
            'rows.*.recipient_name' => 'nama penerima',
            'rows.*.description' => 'deskripsi',
            'rows.*.amount' => 'jumlah',
        ];
    }
}

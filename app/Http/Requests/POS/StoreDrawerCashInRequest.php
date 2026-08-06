<?php

namespace App\Http\Requests\POS;

use Illuminate\Foundation\Http\FormRequest;

class StoreDrawerCashInRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'amount' => 'required|numeric|min:1',
            'description' => 'required|string|max:150',
            'recipient_name' => 'required|string|max:60',
            'from_owner' => 'nullable|boolean',
            // Wajib kecuali uangnya dari kantong pemilik. Kepemilikan outletnya
            // diverifikasi ulang di service supaya request yang dikarang tangan
            // tidak bisa menarik dana dari rekening outlet lain.
            'source_cash_account_id' => [
                'nullable',
                'required_without:from_owner',
                'integer',
                'exists:cash_accounts,id',
            ],
            'idempotency_key' => 'nullable|string|max:64',
        ];
    }

    protected function prepareForValidation(): void
    {
        // Checkbox yang tidak dicentang tidak terkirim sama sekali. Tanpa
        // normalisasi ini, `required_without` tidak pernah aktif dan kas masuk
        // bisa tersimpan tanpa asal dana sama sekali.
        $fromOwner = $this->boolean('from_owner');

        $this->merge([
            'from_owner' => $fromOwner,
            'source_cash_account_id' => $fromOwner ? null : $this->input('source_cash_account_id'),
        ]);

        if ($fromOwner) {
            // `required_without` membaca keberadaan key, bukan nilainya, jadi
            // key-nya harus benar-benar dilepas saat sumbernya pemilik.
            $this->request->remove('source_cash_account_id');
        }
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => 'jumlah',
            'description' => 'keterangan',
            'recipient_name' => 'nama penyerah uang',
            'source_cash_account_id' => 'sumber dana',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'amount.min' => 'Jumlah kas masuk minimal Rp 1.',
            'recipient_name.required' => 'Tulis siapa yang menyerahkan uangnya.',
            'description.required' => 'Tulis keterangan kas masuknya.',
            'source_cash_account_id.required_without' => 'Pilih sumber dananya, atau centang "uang pribadi pemilik".',
        ];
    }
}

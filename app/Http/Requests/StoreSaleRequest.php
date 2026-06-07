<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\SalesType;
use App\Support\SpecialPromotion;

class StoreSaleRequest extends FormRequest
{
    /**
     * Normalisasi input agar payload POS tetap valid walau field diskon tidak dikirim.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'discount_type' => $this->input('discount_type', 'none'),
            'discount_value' => $this->input('discount_value', 0),
            'voucher_code' => $this->input('voucher_code') ? strtoupper(trim((string) $this->input('voucher_code'))) : null,
            'manual_discount_type' => $this->input('manual_discount_type', 'none'),
            'manual_discount_value' => $this->input('manual_discount_value', 0),
        ]);
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Nanti bisa ditambahkan logic auth
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $salesTypeKeys = array_keys(SpecialPromotion::filterRuntimeSalesTypes(SalesType::priceLevels()));
        $user = $this->user();

        $outletRule = ['required', 'exists:outlets,id'];
        if ($user?->outlet_id) {
            $outletRule = ['required', 'integer', Rule::in([(int) $user->outlet_id])];
        }

        $cashSessionRule = Rule::exists('cash_sessions', 'id')
            ->where(function ($query) use ($user) {
                $query->where('status', 'open');

                if ($user) {
                    $query->where('user_id', $user->id);

                    if ($user->outlet_id) {
                        $query->where('outlet_id', $user->outlet_id);
                    }
                }
            });

        return [
            // Header transaksi
            'idempotency_key' => 'nullable|string|max:100',
            'outlet_id' => $outletRule,
            'cash_session_id' => ['required', $cashSessionRule],
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:200',
            'notes' => 'nullable|string',
            'sales_type' => ['nullable', 'string', Rule::in($salesTypeKeys)],
            'promotion_id' => ['nullable', 'exists:promotions,id'],
            'voucher_code' => 'nullable|string|max:60',
            
            // Diskon global
            'discount_type' => 'required|in:none,percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'manual_discount_type' => 'nullable|in:none,percentage,fixed',
            'manual_discount_value' => 'nullable|numeric|min:0',
            'manual_discount_authorization_pin' => 'nullable|string|size:6|regex:/^[0-9]{6}$/',
            'manual_discount_reason' => 'nullable|string|max:255',
            
            // Items
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
            
            // Payment
            'payment_method_id' => 'required_without:payments|nullable|exists:payment_methods,id',
            'payment_amount' => 'required_without:payments|nullable|numeric|min:0',
            'payment_tendered_amount' => 'nullable|numeric|min:0',
            'payment_change_amount' => 'nullable|numeric|min:0',
            'payment_reference' => 'nullable|string|max:200',
            'payments' => 'nullable|array|min:1',
            'payments.*.payment_method_id' => 'required_with:payments|exists:payment_methods,id',
            'payments.*.amount' => 'required_with:payments|numeric|min:0.01',
            'payments.*.tendered_amount' => 'nullable|numeric|min:0',
            'payments.*.change_amount' => 'nullable|numeric|min:0',
            'payments.*.reference_number' => 'nullable|string|max:200',
            'payments.*.notes' => 'nullable|string|max:255',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'outlet_id.required' => 'Outlet harus dipilih',
            'outlet_id.in' => 'Outlet transaksi harus sesuai outlet akun yang login',
            'outlet_id.exists' => 'Outlet tidak valid',
            'cash_session_id.required' => 'Sesi kasir harus aktif',
            'cash_session_id.exists' => 'Sesi kasir tidak valid, tidak aktif, atau bukan milik akun Anda',
            'sales_type.in' => 'Tipe penjualan tidak valid',
            'items.required' => 'Minimal harus ada 1 produk',
            'items.min' => 'Minimal harus ada 1 produk',
            'items.*.product_id.required' => 'Produk harus dipilih',
            'items.*.product_id.exists' => 'Produk tidak valid',
            'items.*.quantity.required' => 'Kuantitas harus diisi',
            'items.*.quantity.min' => 'Kuantitas minimal 0.01',
            'items.*.unit_price.required' => 'Harga harus diisi',
            'items.*.notes.max' => 'Catatan item maksimal 255 karakter',
            'promotion_id.exists' => 'Promo tidak ditemukan',
            'voucher_code.max' => 'Kode voucher maksimal 60 karakter',
            'payment_method_id.required' => 'Metode pembayaran harus dipilih',
            'payment_method_id.required_without' => 'Metode pembayaran harus dipilih',
            'payment_method_id.exists' => 'Metode pembayaran tidak valid',
            'payment_amount.required' => 'Jumlah pembayaran harus diisi',
            'payment_amount.required_without' => 'Jumlah pembayaran harus diisi',
            'payment_amount.min' => 'Jumlah pembayaran minimal 0',
            'payments.min' => 'Minimal harus ada satu pembayaran',
            'payments.*.payment_method_id.required_with' => 'Metode pembayaran wajib dipilih',
            'payments.*.payment_method_id.exists' => 'Metode pembayaran tidak valid',
            'payments.*.amount.required_with' => 'Nominal pembayaran wajib diisi',
            'payments.*.amount.min' => 'Nominal pembayaran harus lebih dari 0',
            'manual_discount_authorization_pin.size' => 'PIN otorisasi diskon harus 6 digit',
            'manual_discount_authorization_pin.regex' => 'PIN otorisasi diskon harus angka 6 digit',
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (SpecialPromotion::isSpecialSalesType($this->input('sales_type'))) {
                $validator->errors()->add(
                    'sales_type',
                    'Meal karyawan dan compliment sekarang harus diproses melalui promo, bukan metode penjualan.'
                );
            }

            $manualDiscountType = (string) $this->input('manual_discount_type', 'none');
            $manualDiscountValue = (float) $this->input('manual_discount_value', 0);
            if ($manualDiscountType !== 'none' && $manualDiscountValue > 0 && !$this->filled('manual_discount_authorization_pin')) {
                $validator->errors()->add('manual_discount_authorization_pin', 'PIN otorisasi wajib diisi untuk diskon manual dPOS.');
            }

            if ($manualDiscountType !== 'none' && $manualDiscountValue > 0 && $this->filled('voucher_code')) {
                $validator->errors()->add('manual_discount_value', 'Diskon manual dPOS tidak bisa digabung dengan voucher pada transaksi yang sama.');
            }
        });
    }
}

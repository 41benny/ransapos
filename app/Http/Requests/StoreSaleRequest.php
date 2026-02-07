<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSaleRequest extends FormRequest
{
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
        $salesTypeKeys = array_keys(config('sales.price_levels', ['regular' => 'Reguler']));

        return [
            // Header transaksi
            'outlet_id' => 'required|exists:outlets,id',
            'cash_session_id' => 'required|exists:cash_sessions,id',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'nullable|string|max:200',
            'notes' => 'nullable|string',
            'sales_type' => ['nullable', 'string', Rule::in($salesTypeKeys)],
            
            // Diskon global
            'discount_type' => 'required|in:none,percentage,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            
            // Items
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
            'items.*.notes' => 'nullable|string|max:255',
            
            // Payment
            'payment_method_id' => 'required|exists:payment_methods,id',
            'payment_amount' => 'required|numeric|min:0',
            'payment_reference' => 'nullable|string|max:200',
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
            'cash_session_id.required' => 'Sesi kasir harus aktif',
            'cash_session_id.exists' => 'Sesi kasir tidak valid',
            'sales_type.in' => 'Tipe penjualan tidak valid',
            'items.required' => 'Minimal harus ada 1 produk',
            'items.min' => 'Minimal harus ada 1 produk',
            'items.*.product_id.required' => 'Produk harus dipilih',
            'items.*.product_id.exists' => 'Produk tidak valid',
            'items.*.quantity.required' => 'Kuantitas harus diisi',
            'items.*.quantity.min' => 'Kuantitas minimal 0.01',
            'items.*.unit_price.required' => 'Harga harus diisi',
            'items.*.notes.max' => 'Catatan item maksimal 255 karakter',
            'payment_method_id.required' => 'Metode pembayaran harus dipilih',
            'payment_method_id.exists' => 'Metode pembayaran tidak valid',
            'payment_amount.required' => 'Jumlah pembayaran harus diisi',
            'payment_amount.min' => 'Jumlah pembayaran minimal 0',
        ];
    }
}

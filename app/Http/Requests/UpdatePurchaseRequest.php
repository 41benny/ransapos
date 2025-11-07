<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdatePurchaseRequest extends FormRequest
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
            'outlet_id' => 'required|exists:outlets,id',
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'tax_amount' => 'nullable|numeric|min:0',
            'discount_amount' => 'nullable|numeric|min:0',
            'payment_status' => 'nullable|in:pending,partial,paid',
            'notes' => 'nullable|string',
            
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_amount' => 'nullable|numeric|min:0',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'outlet_id' => 'outlet',
            'supplier_id' => 'supplier',
            'purchase_date' => 'tanggal pembelian',
            'tax_amount' => 'pajak',
            'discount_amount' => 'diskon',
            'payment_status' => 'status pembayaran',
            'notes' => 'catatan',
            'items' => 'item produk',
            'items.*.product_id' => 'produk',
            'items.*.quantity' => 'jumlah',
            'items.*.unit_price' => 'harga satuan',
            'items.*.discount_amount' => 'diskon item',
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'items.required' => 'Minimal harus ada 1 item produk',
            'items.min' => 'Minimal harus ada 1 item produk',
        ];
    }
}

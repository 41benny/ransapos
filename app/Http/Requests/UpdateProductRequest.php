<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends FormRequest
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
        $productParam = $this->route('product');
        $productId = is_object($productParam) ? $productParam->id : $productParam;

        return [
            'sku' => 'required|string|max:50|unique:products,sku,' . $productId,
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:product_categories,id',
            'product_type' => 'required|in:raw_material,finished_good,service',
            'description' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'unit' => 'required|string|max:50',
            'purchase_price' => 'required|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'price_levels' => 'nullable|array',
            'bundle_mode' => 'nullable|boolean',
            'bundle_components' => 'nullable|array',
            'bundle_components.*.component_product_id' => 'nullable|exists:products,id',
            'bundle_components.*.quantity' => 'nullable|numeric|min:0.0001',
            'bundle_components.*.uom' => 'nullable|string|max:50',
            'min_stock' => 'nullable|integer|min:0',
            'is_sellable' => 'boolean',
            'is_pos_available' => 'boolean',
            'is_online_order_available' => 'boolean',
            'is_available_all_outlets' => 'boolean',
            'is_available_all_users' => 'boolean',
            'pos_outlet_ids' => 'nullable|array',
            'pos_outlet_ids.*' => 'exists:outlets,id',
            'pos_user_ids' => 'nullable|array',
            'pos_user_ids.*' => 'exists:users,id',
            'is_active' => 'boolean',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     */
    public function attributes(): array
    {
        return [
            'sku' => 'SKU',
            'name' => 'nama produk',
            'category_id' => 'kategori',
            'product_type' => 'jenis produk',
            'description' => 'deskripsi',
            'image' => 'gambar produk',
            'unit' => 'satuan',
            'purchase_price' => 'harga beli',
            'selling_price' => 'harga jual',
            'price_levels' => 'harga per level',
            'bundle_mode' => 'mode bundle',
            'bundle_components' => 'komponen bundle',
            'min_stock' => 'stok minimal',
            'is_sellable' => 'status produk dijual',
            'is_pos_available' => 'status tersedia di POS',
            'is_online_order_available' => 'status tersedia di online order',
            'is_available_all_outlets' => 'ketersediaan semua outlet',
            'is_available_all_users' => 'ketersediaan semua pengguna',
            'pos_outlet_ids' => 'outlet POS',
            'pos_user_ids' => 'pengguna POS',
            'is_active' => 'status',
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            if (!$this->boolean('is_pos_available')) {
                return;
            }

            if (!$this->boolean('is_available_all_outlets') && count($this->normalizeIdList($this->input('pos_outlet_ids', []))) === 0) {
                $validator->errors()->add('pos_outlet_ids', 'Pilih minimal 1 outlet jika produk tidak tersedia di semua outlet.');
            }

            if (!$this->boolean('is_available_all_users') && count($this->normalizeIdList($this->input('pos_user_ids', []))) === 0) {
                $validator->errors()->add('pos_user_ids', 'Pilih minimal 1 pengguna POS jika produk tidak tersedia untuk semua pengguna.');
            }
        });
    }

    private function normalizeIdList(mixed $value): array
    {
        if (is_string($value)) {
            $value = array_filter(array_map('trim', explode(',', $value)));
        }

        if (!is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn($id) => is_numeric($id))
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }
}

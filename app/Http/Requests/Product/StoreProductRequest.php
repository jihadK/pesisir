<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('products.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'sku'              => ['required', 'string', 'max:50', 'regex:/^[A-Z0-9-]+$/', Rule::unique('tbm_products', 'sku')->whereNull('deleted_date')],
            'barcode'          => ['nullable', 'string', 'max:50', Rule::unique('tbm_products', 'barcode')->whereNull('deleted_date')],
            'name'             => ['required', 'string', 'max:150'],
            'scientific_name'  => ['nullable', 'string', 'max:150'],
            'origin'           => ['nullable', 'string', 'max:100'],
            'description'      => ['nullable', 'string'],

            'category_id'      => ['required', 'integer', Rule::exists('tbm_categories', 'id')],
            'grade_id'         => ['nullable', 'integer', Rule::exists('tbm_product_grades', 'id')],
            'base_uom_id'      => ['required', 'integer', Rule::exists('tbm_units_of_measure', 'id')],

            'storage_temp_min' => ['nullable', 'numeric', 'between:-50,50'],
            'storage_temp_max' => ['nullable', 'numeric', 'between:-50,50', 'gte:storage_temp_min'],
            'shelf_life_days'  => ['nullable', 'integer', 'min:0', 'max:3650'],
            'is_perishable'    => ['nullable', 'boolean'],

            'min_stock_level'  => ['nullable', 'numeric', 'min:0'],
            'max_stock_level'  => ['nullable', 'numeric', 'min:0', 'gte:min_stock_level'],

            'default_cost_price' => ['nullable', 'numeric', 'min:0'],
            'default_sell_price' => ['nullable', 'numeric', 'min:0'],

            'image'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'is_active'        => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'sku.regex'                  => 'SKU hanya boleh huruf kapital, angka, dan tanda hubung.',
            'sku.unique'                 => 'SKU sudah dipakai.',
            'barcode.unique'             => 'Barcode sudah dipakai produk lain.',
            'category_id.exists'         => 'Kategori yang dipilih tidak valid.',
            'grade_id.exists'            => 'Grade yang dipilih tidak valid.',
            'base_uom_id.exists'         => 'Satuan yang dipilih tidak valid.',
            'storage_temp_max.gte'       => 'Suhu maksimum harus ≥ suhu minimum.',
            'max_stock_level.gte'        => 'Stock maksimum harus ≥ stock minimum.',
            'image.image'                => 'File harus berupa gambar.',
            'image.mimes'                => 'Format gambar harus JPG/PNG/WebP.',
            'image.max'                  => 'Ukuran gambar maksimal 2 MB.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'sku'           => strtoupper(trim((string) $this->input('sku'))),
            'is_active'     => $this->boolean('is_active'),
            'is_perishable' => $this->boolean('is_perishable'),
            'default_cost_price' => $this->cleanRupiah($this->input('default_cost_price')),
            'default_sell_price' => $this->cleanRupiah($this->input('default_sell_price')),
        ]);
    }

    private function cleanRupiah(mixed $val): ?float
    {
        if ($val === null || $val === '') return null;
        $cleaned = preg_replace('/[^0-9]/', '', (string) $val);
        return $cleaned === '' ? null : (float) $cleaned;
    }
}

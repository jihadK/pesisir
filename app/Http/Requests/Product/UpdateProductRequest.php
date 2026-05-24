<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('products.update') ?? false;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id ?? $this->route('product');

        return [
            // SKU read-only setelah create
            'barcode'          => ['nullable', 'string', 'max:50', Rule::unique('tbm_products', 'barcode')->ignore($productId)->whereNull('deleted_date')],
            'name'             => ['required', 'string', 'max:150'],
            'scientific_name'  => ['nullable', 'string', 'max:150'],
            'origin'           => ['nullable', 'string', 'max:100'],
            'description'      => ['nullable', 'string'],

            'category_id'      => ['required', 'integer', Rule::exists('tbm_categories', 'id')],
            'grade_id'         => ['required', 'integer', Rule::exists('tbm_product_grades', 'id')],
            'base_uom_id'      => ['required', 'integer', Rule::exists('tbm_units_of_measure', 'id')],

            'storage_temp_min' => ['nullable', 'numeric', 'between:-50,50'],
            'storage_temp_max' => ['nullable', 'numeric', 'between:-50,50', 'gte:storage_temp_min'],
            'shelf_life_days'  => ['nullable', 'integer', 'min:0', 'max:3650'],
            'is_perishable'    => ['nullable', 'boolean'],

            'min_stock_level'  => ['nullable', 'integer', 'min:0'],
            'max_stock_level'  => ['nullable', 'integer', 'min:0', 'gte:min_stock_level'],

            'default_cost_price'     => ['nullable', 'numeric', 'min:0'],
            'default_sell_price'     => ['nullable', 'numeric', 'min:0'],
            'default_margin_percent' => ['nullable', 'numeric', 'min:0', 'max:9999.99'],

            'pack_content_type'  => ['required', 'in:ekor,potong'],
            'pack_content_min'   => ['required', 'integer', 'min:1', 'max:9999'],
            'pack_content_max'   => ['required', 'integer', 'min:1', 'max:9999', 'gte:pack_content_min'],
            'pack_weight_min_g'  => ['required', 'numeric', 'min:0.01', 'max:999999.99'],
            'pack_weight_max_g'  => ['required', 'numeric', 'min:0.01', 'max:999999.99', 'gte:pack_weight_min_g'],

            'image'            => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_image'     => ['nullable', 'boolean'],
            'is_active'        => ['nullable', 'boolean'],

            'badge'                  => ['nullable', Rule::in(array_keys(\App\Models\Product::badgeOptions()))],
            'nutrition_info'          => ['nullable', 'array', 'max:10'],
            'nutrition_info.*.label'  => ['required_with:nutrition_info.*', 'string', 'max:50'],
            'nutrition_info.*.icon'   => ['nullable', 'string', 'max:40'],
            'nutrition_info.*.detail' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($v) {
            $catId = $this->input('category_id');
            if ($catId) {
                $cat = \App\Models\Category::find($catId);
                if ($cat && ! $cat->parent_id) {
                    $v->errors()->add('category_id', 'Pilih sub-kategori (level-2), bukan group root.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'barcode.unique'             => 'Barcode sudah dipakai produk lain.',
            'category_id.exists'         => 'Kategori yang dipilih tidak valid.',
            'grade_id.exists'            => 'Grade yang dipilih tidak valid.',
            'base_uom_id.exists'         => 'Satuan yang dipilih tidak valid.',
            'storage_temp_max.gte'       => 'Suhu maksimum harus ≥ suhu minimum.',
            'max_stock_level.gte'        => 'Stock maksimum harus ≥ stock minimum.',
            'pack_content_type.required' => 'Tipe isi pack (ekor/potong) wajib dipilih.',
            'pack_content_type.in'       => 'Tipe isi harus "ekor" atau "potong".',
            'pack_content_min.required'  => 'Jumlah isi minimum wajib diisi.',
            'pack_content_max.required'  => 'Jumlah isi maksimum wajib diisi.',
            'pack_content_max.gte'       => 'Isi maksimum harus ≥ isi minimum.',
            'pack_weight_min_g.required' => 'Berat minimum (gram) wajib diisi.',
            'pack_weight_max_g.required' => 'Berat maksimum (gram) wajib diisi.',
            'pack_weight_max_g.gte'      => 'Berat maksimum harus ≥ berat minimum.',
            'image.image'                => 'File harus berupa gambar.',
            'image.mimes'                => 'Format gambar harus JPG/PNG/WebP.',
            'image.max'                  => 'Ukuran gambar maksimal 2 MB.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_active'     => $this->boolean('is_active'),
            'is_perishable' => $this->boolean('is_perishable'),
            'remove_image'  => $this->boolean('remove_image'),
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

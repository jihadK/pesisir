<?php

namespace App\Http\Requests\Category;

use App\Services\CategoryService;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('categories.update') ?? false;
    }

    public function rules(): array
    {
        $catId = $this->route('category')?->id ?? $this->route('category');

        return [
            'name'        => ['required', 'string', 'max:100'],
            'code'        => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'slug'        => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', Rule::unique('tbm_categories', 'slug')->ignore($catId)],
            'parent_id'   => ['nullable', 'integer', Rule::exists('tbm_categories', 'id')],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.required' => 'Kode kategori wajib diisi (dipakai untuk SKU produk).',
            'code.regex'    => 'Kode hanya boleh huruf kapital & angka (mis. FISH, TUNA).',
            'slug.regex'  => 'Slug hanya boleh huruf kecil, angka, dan tanda hubung.',
            'slug.unique' => 'Slug sudah dipakai kategori lain.',
            'parent_id.exists' => 'Kategori induk tidak valid.',
        ];
    }

    public function prepareForValidation(): void
    {
        $name = trim((string) $this->input('name'));
        $slug = trim((string) $this->input('slug'));
        $catId = $this->route('category')?->id ?? $this->route('category');

        if (! $slug && $name) {
            $slug = app(CategoryService::class)->generateSlug($name, $catId ? (int) $catId : null);
        }

        $this->merge([
            'code'      => strtoupper(trim((string) $this->input('code'))) ?: null,
            'slug'      => $slug ?: null,
            'parent_id' => $this->input('parent_id') ?: null,
        ]);
    }

    /**
     * Cycle prevention check setelah validation pass.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($v) {
            $catId = $this->route('category')?->id ?? $this->route('category');
            $newParent = $this->input('parent_id');
            if (! $catId || ! $newParent) return;

            if (app(CategoryService::class)->wouldCreateCycle((int) $catId, (int) $newParent)) {
                $v->errors()->add('parent_id', 'Kategori induk tidak valid: tidak boleh memilih diri sendiri atau sub-kategorinya (cycle).');
            }
        });
    }
}

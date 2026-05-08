<?php

namespace App\Http\Requests\Category;

use App\Services\CategoryService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('categories.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'slug'        => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', Rule::unique('tbm_categories', 'slug')],
            'parent_id'   => ['nullable', 'integer', Rule::exists('tbm_categories', 'id')],
            'description' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'slug.regex'  => 'Slug hanya boleh huruf kecil, angka, dan tanda hubung.',
            'slug.unique' => 'Slug sudah dipakai kategori lain.',
            'parent_id.exists' => 'Kategori induk tidak valid.',
        ];
    }

    public function prepareForValidation(): void
    {
        // Auto-generate slug kalau kosong
        $name = trim((string) $this->input('name'));
        $slug = trim((string) $this->input('slug'));

        if (! $slug && $name) {
            $slug = app(CategoryService::class)->generateSlug($name);
        }

        $this->merge([
            'slug'      => $slug ?: null,
            'parent_id' => $this->input('parent_id') ?: null,
        ]);
    }
}

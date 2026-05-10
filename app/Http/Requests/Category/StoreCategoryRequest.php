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
            'code'        => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/'],
            'slug'        => ['nullable', 'string', 'max:100', 'regex:/^[a-z0-9-]+$/', Rule::unique('tbm_categories', 'slug')],
            'parent_id'   => ['nullable', 'integer', Rule::exists('tbm_categories', 'id')],
            'description' => ['nullable', 'string'],
        ];
    }

    public function withValidator(\Illuminate\Contracts\Validation\Validator $validator): void
    {
        $validator->after(function ($v) {
            $code = $this->input('code');
            $parentId = $this->input('parent_id');
            if (! $code) return;
            $exists = \Illuminate\Support\Facades\DB::table('tbm_categories')
                ->where('code', $code)
                ->where(function ($q) use ($parentId) {
                    if ($parentId) $q->where('parent_id', $parentId);
                    else $q->whereNull('parent_id');
                })
                ->exists();
            if ($exists) {
                $v->errors()->add('code', 'Kode sudah dipakai kategori lain dalam induk yang sama.');
            }
        });
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
        // Auto-generate slug kalau kosong
        $name = trim((string) $this->input('name'));
        $slug = trim((string) $this->input('slug'));

        if (! $slug && $name) {
            $slug = app(CategoryService::class)->generateSlug($name);
        }

        $this->merge([
            'code'      => strtoupper(trim((string) $this->input('code'))) ?: null,
            'slug'      => $slug ?: null,
            'parent_id' => $this->input('parent_id') ?: null,
        ]);
    }
}

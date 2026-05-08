<?php

namespace App\Http\Requests\PriceTier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePriceTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('price_tiers.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:50', Rule::unique('tbm_price_tiers', 'name')],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active'   => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return ['name.unique' => 'Nama tier sudah dipakai.'];
    }

    public function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}

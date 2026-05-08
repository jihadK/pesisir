<?php

namespace App\Http\Requests\PriceTier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePriceTierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('price_tiers.update') ?? false;
    }

    public function rules(): array
    {
        $tierId = $this->route('price_tier')?->id ?? $this->route('price_tier');

        return [
            'name'        => ['required', 'string', 'max:50', Rule::unique('tbm_price_tiers', 'name')->ignore($tierId)],
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

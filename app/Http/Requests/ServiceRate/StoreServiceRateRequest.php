<?php

namespace App\Http\Requests\ServiceRate;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('service_rate.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:100'],
            'category_id' => ['nullable', 'integer', Rule::exists('tbm_categories', 'id')],
            'rate_per_kg' => ['required', 'numeric', 'min:0'],
            'is_active'   => ['nullable', 'boolean'],
            'notes'       => ['nullable', 'string', 'max:255'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_active'   => $this->boolean('is_active'),
            'rate_per_kg' => $this->cleanRupiah($this->input('rate_per_kg')),
            'category_id' => $this->input('category_id') ?: null,
        ]);
    }

    private function cleanRupiah(mixed $val): ?float
    {
        if ($val === null || $val === '') return null;
        $cleaned = preg_replace('/[^0-9]/', '', (string) $val);
        return $cleaned === '' ? null : (float) $cleaned;
    }
}

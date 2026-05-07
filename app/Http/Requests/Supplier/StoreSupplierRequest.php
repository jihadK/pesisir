<?php

namespace App\Http\Requests\Supplier;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSupplierRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('suppliers.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('tbm_suppliers', 'code')->whereNull('deleted_date'),
            ],
            'name'               => ['required', 'string', 'max:150'],
            'contact_person'     => ['nullable', 'string', 'max:100'],
            'phone'              => ['nullable', 'string', 'max:20'],
            'email'              => ['nullable', 'email', 'max:100'],
            'address'            => ['nullable', 'string'],
            'city'               => ['nullable', 'string', 'max:100'],
            'npwp'               => ['nullable', 'string', 'max:30'],
            'bank_name'          => ['nullable', 'string', 'max:50'],
            'bank_account'       => ['nullable', 'string', 'max:50'],
            'payment_terms_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_active'          => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex'              => 'Kode hanya boleh huruf kapital, angka, dan tanda hubung.',
            'code.unique'             => 'Kode supplier sudah dipakai.',
            'email.email'             => 'Format email tidak valid.',
            'payment_terms_days.min'  => 'TOP minimal 0 hari.',
            'payment_terms_days.max'  => 'TOP maksimal 365 hari.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'code'      => strtoupper(trim((string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
            'payment_terms_days' => (int) ($this->input('payment_terms_days') ?: 0),
        ]);
    }
}

<?php

namespace App\Http\Requests\Customer;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('customers.update') ?? false;
    }

    public function rules(): array
    {
        return [
            // code TIDAK ikut diupdate (read-only setelah create)
            'name'               => ['required', 'string', 'max:150'],
            'customer_type'      => ['required', Rule::in(array_keys(Customer::TYPES))],
            'price_tier_id'      => ['nullable', 'integer', Rule::exists('tbm_price_tiers', 'id')],
            'contact_person'     => ['nullable', 'string', 'max:100'],
            'phone'              => ['nullable', 'string', 'max:20'],
            'email'              => ['nullable', 'email', 'max:100'],
            'address'            => ['nullable', 'string'],
            'city'               => ['nullable', 'string', 'max:100'],
            'npwp'               => ['nullable', 'string', 'max:30', $this->npwpRule()],
            'credit_limit'       => ['required', 'numeric', 'min:0', 'max:9999999999999.99'],
            'payment_terms_days' => ['required', 'integer', 'min:0', 'max:365'],
            'is_active'          => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'customer_type.in'        => 'Tipe customer tidak valid.',
            'price_tier_id.exists'    => 'Tier harga yang dipilih tidak valid.',
            'email.email'             => 'Format email tidak valid.',
            'credit_limit.min'        => 'Credit limit tidak boleh negatif.',
            'payment_terms_days.min'  => 'TOP minimal 0 hari.',
            'payment_terms_days.max'  => 'TOP maksimal 365 hari.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'is_active'    => $this->boolean('is_active'),
            'credit_limit' => $this->cleanRupiah($this->input('credit_limit')),
            'payment_terms_days' => (int) ($this->input('payment_terms_days') ?: 0),
        ]);
    }

    private function cleanRupiah(mixed $val): float
    {
        if ($val === null || $val === '') return 0.0;
        $cleaned = preg_replace('/[^0-9]/', '', (string) $val);
        return $cleaned === '' ? 0.0 : (float) $cleaned;
    }

    private function npwpRule(): \Closure
    {
        return function (string $attribute, ?string $value, \Closure $fail) {
            if (! $value) return;
            $digits = preg_replace('/\D/', '', $value);
            if (! in_array(strlen($digits), [15, 16], true)) {
                $fail('Format NPWP tidak valid (harus 15 atau 16 digit).');
            }
        };
    }
}

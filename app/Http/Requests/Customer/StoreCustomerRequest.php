<?php

namespace App\Http\Requests\Customer;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('customers.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('tbm_customers', 'code')->whereNull('deleted_date'),
            ],
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
            'code.regex'              => 'Kode hanya boleh huruf kapital, angka, dan tanda hubung.',
            'code.unique'             => 'Kode customer sudah dipakai.',
            'customer_type.in'        => 'Tipe customer tidak valid.',
            'price_tier_id.exists'    => 'Tier harga yang dipilih tidak valid.',
            'email.email'             => 'Format email tidak valid.',
            'credit_limit.min'        => 'Credit limit tidak boleh negatif.',
            'payment_terms_days.min'  => 'TOP minimal 0 hari.',
            'payment_terms_days.max'  => 'TOP maksimal 365 hari.',
            'npwp.format'             => 'Format NPWP tidak valid (harus 15 digit XX.XXX.XXX.X-XXX.XXX atau 16 digit polos).',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'code'         => strtoupper(trim((string) $this->input('code'))),
            'is_active'    => $this->boolean('is_active'),
            'credit_limit' => $this->cleanRupiah($this->input('credit_limit')),
            'payment_terms_days' => (int) ($this->input('payment_terms_days') ?: 0),
        ]);
    }

    /**
     * Bersihkan input rupiah (hapus titik/koma/spasi).
     */
    private function cleanRupiah(mixed $val): float
    {
        if (is_numeric($val)) return (float) $val;
        $cleaned = preg_replace('/[^0-9]/', '', (string) $val);
        return $cleaned === '' ? 0.0 : (float) $cleaned;
    }

    /**
     * Closure validator untuk NPWP — accept 15 atau 16 digit dengan/tanpa separator.
     */
    private function npwpRule(): \Closure
    {
        return function (string $attribute, ?string $value, \Closure $fail) {
            if (! $value) return; // nullable
            $digits = preg_replace('/\D/', '', $value);
            if (! in_array(strlen($digits), [15, 16], true)) {
                $fail('Format NPWP tidak valid (harus 15 atau 16 digit).');
            }
        };
    }
}

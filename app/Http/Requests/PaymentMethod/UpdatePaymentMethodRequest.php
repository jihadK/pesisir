<?php

namespace App\Http\Requests\PaymentMethod;

use App\Models\PaymentMethod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdatePaymentMethodRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('payment_method.update') ?? false;
    }

    public function rules(): array
    {
        $id = $this->route('payment_method')?->id ?? $this->route('payment_method');

        return [
            'code'           => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/', Rule::unique('tbm_payment_methods', 'code')->ignore($id)],
            'name'           => ['required', 'string', 'max:50'],
            'type'           => ['required', Rule::in(array_keys(PaymentMethod::typeLabels()))],
            'bank_name'      => ['nullable', 'string', 'max:50'],
            'account_no'     => ['nullable', 'string', 'max:50'],
            'account_holder' => ['nullable', 'string', 'max:100'],
            'qris_image_url' => ['nullable', 'string', 'max:255'],
            'display_order'  => ['nullable', 'integer', 'min:0', 'max:9999'],
            'description'    => ['nullable', 'string', 'max:255'],
            'is_active'      => ['nullable', 'boolean'],
            'qris_image'     => ['nullable', 'image', 'mimes:png,jpg,jpeg,webp', 'max:1024'],
            'remove_qris'    => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex'  => 'Kode hanya huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode sudah dipakai metode lain.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'code'        => strtoupper(trim((string) $this->input('code'))),
            'is_active'   => $this->boolean('is_active'),
            'remove_qris' => $this->boolean('remove_qris'),
        ]);
    }
}

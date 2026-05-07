<?php

namespace App\Http\Requests\Warehouse;

use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('warehouses.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'code' => [
                'required', 'string', 'max:20',
                'regex:/^[A-Z0-9-]+$/',
                Rule::unique('tbm_warehouses', 'code'),
            ],
            'name'             => ['required', 'string', 'max:100'],
            'address'          => ['nullable', 'string'],
            'type'             => ['required', Rule::in(array_keys(Warehouse::TYPES))],
            'temperature_min'  => ['nullable', 'numeric', 'between:-50,50'],
            'temperature_max'  => ['nullable', 'numeric', 'between:-50,50', 'gte:temperature_min'],
            'pic_user_id'      => ['nullable', 'integer', Rule::exists('tbm_users', 'id')->whereNull('deleted_date')],
            'is_active'        => ['nullable', 'boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex'              => 'Kode hanya boleh berisi huruf kapital, angka, dan tanda hubung.',
            'code.unique'             => 'Kode gudang sudah dipakai.',
            'temperature_max.gte'     => 'Suhu maksimum tidak boleh lebih kecil dari suhu minimum.',
            'pic_user_id.exists'      => 'PIC yang dipilih tidak valid.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'code'      => strtoupper(trim((string) $this->input('code'))),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}

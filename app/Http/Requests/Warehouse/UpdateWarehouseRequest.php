<?php

namespace App\Http\Requests\Warehouse;

use App\Models\Warehouse;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWarehouseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('warehouses.update') ?? false;
    }

    public function rules(): array
    {
        $warehouseId = $this->route('warehouse')?->id ?? $this->route('warehouse');

        return [
            // code TIDAK ikut diupdate (read-only setelah create) — tapi tetap divalidasi kalau dikirim
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
            'temperature_max.gte' => 'Suhu maksimum tidak boleh lebih kecil dari suhu minimum.',
            'pic_user_id.exists'  => 'PIC yang dipilih tidak valid.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }
}

<?php

namespace App\Http\Requests\Uom;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreUomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('uom.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'code'        => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/', Rule::unique('tbm_units_of_measure', 'code')],
            'name'        => ['required', 'string', 'max:50'],
            'symbol'      => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex'  => 'Kode hanya boleh huruf kapital dan angka.',
            'code.unique' => 'Kode sudah dipakai.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }
}

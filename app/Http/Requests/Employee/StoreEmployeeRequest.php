<?php

namespace App\Http\Requests\Employee;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreEmployeeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('employee.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'code'      => ['required', 'string', 'max:20', 'regex:/^[A-Z0-9-]+$/', Rule::unique('tbm_employees', 'code')],
            'name'      => ['required', 'string', 'max:100'],
            'position'  => ['nullable', 'string', 'max:50'],
            'phone'     => ['nullable', 'string', 'max:20'],
            'is_active' => ['nullable', 'boolean'],
            'notes'     => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex'  => 'Kode hanya huruf kapital, angka, dan tanda hubung.',
            'code.unique' => 'Kode sudah dipakai pegawai lain.',
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

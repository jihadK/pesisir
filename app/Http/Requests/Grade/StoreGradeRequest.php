<?php

namespace App\Http\Requests\Grade;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('grades.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'code'  => ['required', 'string', 'max:10', 'regex:/^[A-Z0-9]+$/', Rule::unique('tbm_product_grades', 'code')],
            'name'  => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'code.regex'  => 'Kode hanya boleh huruf kapital dan angka.',
            'code.unique' => 'Kode sudah dipakai.',
            'color.regex' => 'Format warna harus hex 6 digit (mis. #FFD700).',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge(['code' => strtoupper(trim((string) $this->input('code')))]);
    }
}

<?php

namespace App\Http\Requests\Grade;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('grades.update') ?? false;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:50'],
            'color' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'color.regex' => 'Format warna harus hex 6 digit (mis. #FFD700).',
        ];
    }
}

<?php

namespace App\Http\Requests\Uom;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUomRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('uom.update') ?? false;
    }

    public function rules(): array
    {
        return [
            // code read-only setelah create
            'name'        => ['required', 'string', 'max:50'],
            'symbol'      => ['nullable', 'string', 'max:10'],
            'description' => ['nullable', 'string', 'max:255'],
        ];
    }
}

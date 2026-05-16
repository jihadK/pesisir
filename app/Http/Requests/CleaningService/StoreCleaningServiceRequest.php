<?php

namespace App\Http\Requests\CleaningService;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCleaningServiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('cleaning_service.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'service_date' => ['required', 'date'],
            'employee_id'  => ['required', 'integer', Rule::exists('tbm_employees', 'id')],
            'category_id'  => ['required', 'integer', Rule::exists('tbm_categories', 'id')],
            'qty_kg'       => ['required', 'numeric', 'min:0.001'],
            'rate_per_kg'  => ['required', 'numeric', 'min:0'],
            'notes'        => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'qty_kg'      => $this->cleanNumber($this->input('qty_kg')),
            'rate_per_kg' => $this->cleanNumber($this->input('rate_per_kg')),
        ]);
    }

    private function cleanNumber(mixed $val): ?float
    {
        if ($val === null || $val === '') return null;
        $s = (string) $val;
        $s = str_replace('.', '', $s);   // strip thousand separator id-ID
        $s = str_replace(',', '.', $s);  // decimal id-ID → standard
        $s = preg_replace('/[^0-9.]/', '', $s);
        return $s === '' ? null : (float) $s;
    }
}

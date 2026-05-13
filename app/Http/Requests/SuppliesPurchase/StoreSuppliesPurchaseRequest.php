<?php

namespace App\Http\Requests\SuppliesPurchase;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSuppliesPurchaseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('supplies_purchase.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'purchase_date' => ['required', 'date'],
            'supplier_id'   => ['nullable', 'integer', Rule::exists('tbm_suppliers', 'id')->whereNull('deleted_date')],
            'description'   => ['required', 'string', 'max:255'],
            'qty'           => ['required', 'numeric', 'min:0.001'],
            'unit'          => ['required', 'string', 'max:20'],
            'unit_price'    => ['required', 'numeric', 'min:0'],
            'notes'         => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'qty'         => $this->cleanNumber($this->input('qty')),
            'unit_price'  => $this->cleanNumber($this->input('unit_price')),
            'supplier_id' => $this->input('supplier_id') ?: null,
        ]);
    }

    private function cleanNumber(mixed $val): ?float
    {
        if ($val === null || $val === '') return null;
        $s = preg_replace('/[^0-9.]/', '', (string) $val);
        return $s === '' ? null : (float) $s;
    }
}

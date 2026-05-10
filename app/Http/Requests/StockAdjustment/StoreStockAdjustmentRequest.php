<?php

namespace App\Http\Requests\StockAdjustment;

use App\Services\StockAdjustmentService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockAdjustmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('stock_adjustment.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', Rule::exists('tbm_warehouses', 'id')],
            'product_id'   => ['required', 'integer', Rule::exists('tbm_products', 'id')->whereNull('deleted_date')],
            'batch_id'     => ['nullable', 'integer', Rule::exists('tbm_product_batches', 'id')],
            'direction'    => ['required', Rule::in(['in', 'out'])],
            'reason'       => ['required', Rule::in(array_keys(StockAdjustmentService::REASONS))],
            'quantity'     => ['required', 'numeric', 'min:0.001'],
            'notes'        => ['required', 'string', 'min:5', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'notes.required' => 'Catatan wajib diisi (audit trail).',
            'notes.min'      => 'Catatan minimal 5 karakter.',
            'reason.in'      => 'Alasan tidak valid.',
            'quantity.min'   => 'Qty minimal 0.001.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'batch_id' => $this->input('batch_id') ?: null,
        ]);
    }
}

<?php

namespace App\Http\Requests\PurchaseOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePurchaseOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('purchase_order.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'supplier_id'   => ['required', 'integer', Rule::exists('tbm_suppliers', 'id')->whereNull('deleted_date')],
            'warehouse_id'  => ['required', 'integer', Rule::exists('tbm_warehouses', 'id')],
            'po_date'       => ['required', 'date'],
            'expected_date' => ['nullable', 'date', 'after_or_equal:po_date'],
            'notes'         => ['nullable', 'string', 'max:1000'],

            'items'                  => ['required', 'array', 'min:1'],
            'items.*.category_id'    => ['required', 'integer', Rule::exists('tbm_categories', 'id')],
            'items.*.qty_gram'       => ['required', 'numeric', 'min:1'],
            'items.*.price_per_kg'   => ['required', 'numeric', 'min:0'],
            'items.*.notes'          => ['nullable', 'string', 'max:255'],

            'costs'                  => ['nullable', 'array'],
            'costs.*.cost_type'      => ['required_with:costs', Rule::in(['cleaning', 'other'])],
            'costs.*.description'    => ['required_with:costs', 'string', 'max:255'],
            'costs.*.qty'            => ['required_with:costs', 'numeric', 'min:0.001'],
            'costs.*.unit'           => ['required_with:costs', 'string', 'max:20'],
            'costs.*.unit_price'     => ['required_with:costs', 'numeric', 'min:0'],
            'costs.*.employee_id'    => ['nullable', 'integer', Rule::exists('tbm_employees', 'id')],
            'costs.*.po_item_index'  => ['nullable'],
            'costs.*.service_rate_id'=> ['nullable', 'integer', Rule::exists('tbm_service_rates', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required'                => 'Minimal 1 item produk.',
            'items.*.category_id.required'  => 'Sub-kategori wajib dipilih.',
            'items.*.qty_gram.min'          => 'Qty minimum 1 gram.',
            'expected_date.after_or_equal'  => 'Tanggal expected delivery harus ≥ tanggal PO.',
        ];
    }

    public function prepareForValidation(): void
    {
        $items = $this->input('items', []);
        if (is_array($items)) {
            foreach ($items as $idx => $row) {
                if (! is_array($row)) continue;
                if (isset($row['qty_gram']))     $items[$idx]['qty_gram']     = $this->cleanNumber($row['qty_gram']);
                if (isset($row['price_per_kg'])) $items[$idx]['price_per_kg'] = $this->cleanNumber($row['price_per_kg']);
                if (empty($row['category_id']) || empty($items[$idx]['qty_gram'])) unset($items[$idx]);
            }
            $this->merge(['items' => array_values($items)]);
        }

        $costs = $this->input('costs', []);
        if (is_array($costs)) {
            foreach ($costs as $idx => $row) {
                if (! is_array($row)) continue;
                if (isset($row['qty']))        $costs[$idx]['qty']        = $this->cleanNumber($row['qty']);
                if (isset($row['unit_price'])) $costs[$idx]['unit_price'] = $this->cleanNumber($row['unit_price']);
                if (empty($row['description']) || empty($costs[$idx]['qty']) || empty($costs[$idx]['unit_price'])) unset($costs[$idx]);
            }
            $this->merge(['costs' => array_values($costs)]);
        }
    }

    private function cleanNumber(mixed $val): ?float
    {
        if ($val === null || $val === '') return null;
        $s = preg_replace('/[^0-9.]/', '', (string) $val);
        return $s === '' ? null : (float) $s;
    }
}

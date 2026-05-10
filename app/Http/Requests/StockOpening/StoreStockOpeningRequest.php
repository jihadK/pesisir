<?php

namespace App\Http\Requests\StockOpening;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStockOpeningRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('stock_opening.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'warehouse_id' => ['required', 'integer', Rule::exists('tbm_warehouses', 'id')],
            'notes'        => ['nullable', 'string', 'max:500'],

            'items'                       => ['required', 'array', 'min:1'],
            'items.*.product_id'          => ['required', 'integer', Rule::exists('tbm_products', 'id')->whereNull('deleted_date')],
            'items.*.quantity'            => ['required', 'numeric', 'min:0.001'],
            'items.*.cost_price'          => ['required', 'numeric', 'min:0'],
            'items.*.production_date'     => ['nullable', 'date'],
            'items.*.expiry_date'         => ['nullable', 'date', 'after_or_equal:items.*.production_date'],
            'items.*.catch_date'          => ['nullable', 'date'],
            'items.*.catch_location'      => ['nullable', 'string', 'max:150'],
        ];
    }

    public function messages(): array
    {
        return [
            'warehouse_id.required'   => 'Pilih warehouse tujuan.',
            'items.required'          => 'Minimal 1 produk harus diisi.',
            'items.*.product_id.required' => 'Produk wajib dipilih.',
            'items.*.quantity.required'   => 'Qty wajib diisi.',
            'items.*.cost_price.required' => 'Harga pokok wajib diisi.',
            'items.*.expiry_date.after_or_equal' => 'Expiry date harus ≥ production date.',
        ];
    }

    public function prepareForValidation(): void
    {
        $items = $this->input('items', []);
        if (is_array($items)) {
            foreach ($items as $idx => $row) {
                if (! is_array($row)) continue;
                if (isset($row['cost_price'])) {
                    $items[$idx]['cost_price'] = $this->cleanRupiah($row['cost_price']);
                }
                // Filter row kosong (qty=0 atau tidak ada product)
                if (empty($row['product_id']) || empty($row['quantity'])) {
                    unset($items[$idx]);
                }
            }
            $this->merge(['items' => array_values($items)]);
        }
    }

    private function cleanRupiah(mixed $val): ?float
    {
        if ($val === null || $val === '') return null;
        $cleaned = preg_replace('/[^0-9]/', '', (string) $val);
        return $cleaned === '' ? null : (float) $cleaned;
    }
}

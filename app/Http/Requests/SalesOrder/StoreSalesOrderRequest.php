<?php

namespace App\Http\Requests\SalesOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreSalesOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('sales_order.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id'        => ['required', 'integer', Rule::exists('tbm_customers', 'id')->whereNull('deleted_date')],
            'warehouse_id'       => ['required', 'integer', Rule::exists('tbm_warehouses', 'id')],
            'sales_user_id'      => ['nullable', 'integer', Rule::exists('tbm_users', 'id')],
            'order_date'         => ['required', 'date'],
            'delivery_date'      => ['nullable', 'date', 'after_or_equal:order_date'],
            'payment_terms_days' => ['nullable', 'integer', 'min:0', 'max:365'],
            'payment_method_id'  => ['nullable', 'integer', Rule::exists('tbm_payment_methods', 'id')],
            'shipping_cost'      => ['nullable', 'numeric', 'min:0'],
            'discount_amount'    => ['nullable', 'numeric', 'min:0'],
            'notes'              => ['nullable', 'string', 'max:1000'],

            'items'                  => ['required', 'array', 'min:1'],
            'items.*.product_id'     => ['required', 'integer', Rule::exists('tbm_products', 'id')->whereNull('deleted_date')],
            'items.*.quantity'       => ['required', 'numeric', 'min:0.001'],
            'items.*.unit_price'     => ['required', 'numeric', 'min:0'],
            'items.*.discount_pct'   => ['nullable', 'numeric', 'min:0', 'max:100'],
            'items.*.notes'          => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'items.required' => 'Minimal 1 item produk.',
            'delivery_date.after_or_equal' => 'Tanggal kirim harus ≥ tanggal order.',
        ];
    }

    public function prepareForValidation(): void
    {
        $items = $this->input('items', []);
        if (is_array($items)) {
            foreach ($items as $idx => $row) {
                if (! is_array($row)) continue;
                if (isset($row['unit_price'])) {
                    $items[$idx]['unit_price'] = $this->cleanRupiah($row['unit_price']);
                }
                if (empty($row['product_id']) || empty($row['quantity'])) {
                    unset($items[$idx]);
                }
            }
            $this->merge(['items' => array_values($items)]);
        }

        $this->merge([
            'shipping_cost'   => $this->cleanRupiah($this->input('shipping_cost')) ?? 0,
            'discount_amount' => $this->cleanRupiah($this->input('discount_amount')) ?? 0,
        ]);
    }

    private function cleanRupiah(mixed $val): ?float
    {
        if ($val === null || $val === '') return null;
        $cleaned = preg_replace('/[^0-9]/', '', (string) $val);
        return $cleaned === '' ? null : (float) $cleaned;
    }
}

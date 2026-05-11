<?php

namespace App\Http\Requests\DeliveryOrder;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreDeliveryOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('delivery_order.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'so_id'         => ['required', 'integer', Rule::exists('tbr_sales_orders', 'id')],
            'delivery_date' => ['required', 'date'],
            'driver_name'   => ['nullable', 'string', 'max:100'],
            'vehicle_no'    => ['nullable', 'string', 'max:20'],
            'notes'         => ['nullable', 'string', 'max:1000'],

            'items'                  => ['required', 'array', 'min:1'],
            'items.*.so_item_id'     => ['required', 'integer', Rule::exists('tbr_sales_order_items', 'id')],
            'items.*.quantity'       => ['required', 'numeric', 'min:0.001'],
            'items.*.batch_id'       => ['nullable', 'integer', Rule::exists('tbm_product_batches', 'id')],
        ];
    }

    public function messages(): array
    {
        return [
            'so_id.required' => 'Pilih Sales Order untuk dibuatkan DO.',
            'items.required' => 'Minimal 1 item harus diisi qty kirim.',
        ];
    }

    public function prepareForValidation(): void
    {
        $items = $this->input('items', []);
        if (is_array($items)) {
            foreach ($items as $idx => $row) {
                if (! is_array($row)) continue;
                // Filter row yang qty = 0 (tidak dikirim)
                if (empty($row['so_item_id']) || ! ($row['quantity'] ?? null) || (float) $row['quantity'] <= 0) {
                    unset($items[$idx]);
                }
            }
            $this->merge(['items' => array_values($items)]);
        }
    }
}

<?php

namespace App\Http\Requests\Payment;

use App\Models\Payment;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasPermission('payment.create') ?? false;
    }

    public function rules(): array
    {
        return [
            'customer_id'        => ['required', 'integer', Rule::exists('tbm_customers', 'id')->whereNull('deleted_date')],
            'payment_method_id'  => ['required', 'integer', Rule::exists('tbm_payment_methods', 'id')],
            'payment_date'       => ['required', 'date'],
            'amount'             => ['required', 'numeric', 'min:1'],
            'reference_no'       => ['nullable', 'string', 'max:50'],
            'notes'              => ['nullable', 'string', 'max:1000'],
            'status'             => ['nullable', Rule::in([Payment::STATUS_PENDING, Payment::STATUS_CLEARED])],

            'allocations'                  => ['required', 'array', 'min:1'],
            'allocations.*.invoice_id'     => ['required', 'integer', Rule::exists('tbr_invoices', 'id')],
            'allocations.*.amount'         => ['required', 'numeric', 'min:0.01'],
        ];
    }

    public function messages(): array
    {
        return [
            'allocations.required' => 'Minimal 1 alokasi invoice harus diisi.',
            'amount.min'           => 'Jumlah pembayaran minimal Rp 1.',
        ];
    }

    public function prepareForValidation(): void
    {
        $this->merge([
            'amount' => $this->cleanRupiah($this->input('amount')),
        ]);

        $allocs = $this->input('allocations', []);
        if (is_array($allocs)) {
            foreach ($allocs as $idx => $row) {
                if (! is_array($row)) continue;
                if (isset($row['amount'])) {
                    $allocs[$idx]['amount'] = $this->cleanRupiah($row['amount']);
                }
                if (empty($row['invoice_id']) || empty($allocs[$idx]['amount'])) {
                    unset($allocs[$idx]);
                }
            }
            $this->merge(['allocations' => array_values($allocs)]);
        }
    }

    private function cleanRupiah(mixed $val): ?float
    {
        if ($val === null || $val === '') return null;
        $cleaned = preg_replace('/[^0-9]/', '', (string) $val);
        return $cleaned === '' ? null : (float) $cleaned;
    }
}

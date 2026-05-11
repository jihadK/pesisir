<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;

class PaymentService
{
    public function __construct(
        private readonly StockMovementService $movements,
        private readonly InvoiceService $invoices,
    ) {}

    /**
     * Catat pembayaran customer dengan alokasi ke 1+ invoice.
     *
     * @param array $payload {
     *   customer_id: int, payment_method_id: int,
     *   payment_date: string, amount: float,
     *   reference_no: ?string, notes: ?string,
     *   status: 'pending'|'cleared',
     *   created_by: int,
     *   allocations: array<int, array{ invoice_id: int, amount: float }>
     * }
     */
    public function record(array $payload): Payment
    {
        return DB::transaction(function () use ($payload) {
            // Validasi total alokasi = amount
            $totalAlloc = array_sum(array_map(fn($a) => (float) $a['amount'], $payload['allocations']));
            if (abs($totalAlloc - (float) $payload['amount']) > 0.01) {
                throw new \RuntimeException(sprintf(
                    "Total alokasi (Rp %s) tidak sama dengan jumlah pembayaran (Rp %s).",
                    number_format($totalAlloc, 0, ',', '.'),
                    number_format((float) $payload['amount'], 0, ',', '.')
                ));
            }

            // Validasi: tiap alokasi tidak melebihi outstanding invoice
            $invoiceIds = array_column($payload['allocations'], 'invoice_id');
            $invoices   = Invoice::whereIn('id', $invoiceIds)->lockForUpdate()->get()->keyBy('id');

            foreach ($payload['allocations'] as $alloc) {
                $inv = $invoices->get($alloc['invoice_id']);
                if (! $inv) {
                    throw new \RuntimeException("Invoice ID {$alloc['invoice_id']} tidak ditemukan.");
                }
                if ($inv->customer_id !== (int) $payload['customer_id']) {
                    throw new \RuntimeException("Invoice {$inv->invoice_number} bukan milik customer ini.");
                }
                $outstanding = (float) $inv->total_amount - (float) $inv->paid_amount;
                if ((float) $alloc['amount'] > $outstanding + 0.01) {
                    throw new \RuntimeException(sprintf(
                        "Alokasi untuk %s (Rp %s) melebihi outstanding (Rp %s).",
                        $inv->invoice_number,
                        number_format((float) $alloc['amount'], 0, ',', '.'),
                        number_format($outstanding, 0, ',', '.')
                    ));
                }
            }

            // Create payment
            $paymentNumber = $this->movements->nextDocNumber('PAY');
            $payment = Payment::create([
                'payment_number'    => $paymentNumber,
                'customer_id'       => $payload['customer_id'],
                'payment_method_id' => $payload['payment_method_id'],
                'payment_date'      => $payload['payment_date'],
                'amount'            => $payload['amount'],
                'reference_no'      => $payload['reference_no'] ?? null,
                'notes'             => $payload['notes'] ?? null,
                'status'            => $payload['status'] ?? Payment::STATUS_CLEARED,
                'created_by'        => $payload['created_by'],
            ]);

            // Create invoice_payments rows
            foreach ($payload['allocations'] as $alloc) {
                DB::table('tbr_invoice_payments')->insert([
                    'invoice_id'       => $alloc['invoice_id'],
                    'payment_id'       => $payment->id,
                    'allocated_amount' => $alloc['amount'],
                    'created_date'     => now(),
                ]);
            }

            // Recalc setiap invoice yang ter-alokasi
            foreach ($invoices as $inv) {
                $this->invoices->recalcPaidStatus($inv);
            }

            return $payment->fresh(['invoices', 'customer', 'paymentMethod']);
        });
    }

    /**
     * Cancel payment → hapus alokasi → recalc invoice paid_amount & status.
     */
    public function cancel(Payment $payment): Payment
    {
        if ($payment->status === Payment::STATUS_CANCELLED) {
            throw new \RuntimeException('Payment sudah ter-cancel.');
        }

        return DB::transaction(function () use ($payment) {
            $invoiceIds = DB::table('tbr_invoice_payments')
                ->where('payment_id', $payment->id)
                ->pluck('invoice_id');

            $payment->update(['status' => Payment::STATUS_CANCELLED]);

            // Recalc each affected invoice
            foreach (Invoice::whereIn('id', $invoiceIds)->get() as $inv) {
                $this->invoices->recalcPaidStatus($inv);
            }

            return $payment->fresh();
        });
    }

    /**
     * Helper: list invoice outstanding untuk customer (untuk dropdown alokasi).
     */
    public function outstandingInvoicesForCustomer(int $customerId)
    {
        return Invoice::where('customer_id', $customerId)
            ->outstanding()
            ->orderBy('due_date')
            ->get(['id', 'invoice_number', 'invoice_date', 'due_date', 'total_amount', 'paid_amount']);
    }
}

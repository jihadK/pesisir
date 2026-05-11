<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class InvoiceService
{
    public function __construct(private readonly StockMovementService $movements) {}

    /**
     * Generate Invoice dari DO shipped/delivered.
     * - Items dari DO items
     * - Pricing & discount dari SO header
     * - Due date = invoice_date + payment_terms_days (dari SO)
     */
    public function createFromDO(DeliveryOrder $do, int $userId): Invoice
    {
        return DB::transaction(function () use ($do, $userId) {
            // Validasi: DO harus shipped/delivered
            if (! in_array($do->status, [DeliveryOrder::STATUS_SHIPPED, DeliveryOrder::STATUS_DELIVERED], true)) {
                throw new \RuntimeException('Invoice hanya bisa dibuat dari DO yang sudah shipped/delivered.');
            }

            // Cek duplikat: 1 DO = 1 Invoice
            $existing = Invoice::where('do_id', $do->id)
                ->whereNotIn('status', [Invoice::STATUS_CANCELLED, Invoice::STATUS_VOID])
                ->first();
            if ($existing) {
                throw new \RuntimeException("Invoice untuk DO ini sudah ada: {$existing->invoice_number}");
            }

            $do->load(['items.product', 'items.uom', 'salesOrder', 'customer']);
            $so = $do->salesOrder;

            // Hitung subtotal dari items
            $subtotal = 0;
            $itemsData = [];
            foreach ($do->items as $doItem) {
                $itemSubtotal = (float) $doItem->quantity * (float) $doItem->unit_price;
                $subtotal += $itemSubtotal;
                $itemsData[] = [
                    'do_item_id'      => $doItem->id,
                    'product_id'      => $doItem->product_id,
                    'description'     => $doItem->product->name,
                    'quantity'        => $doItem->quantity,
                    'uom_id'          => $doItem->uom_id,
                    'unit_price'      => $doItem->unit_price,
                    'discount_amount' => 0,
                    'subtotal'        => $itemSubtotal,
                ];
            }

            // Header: ambil discount/shipping dari SO (kalau partial DO, proporsional? sementara: full)
            $discount = $so ? (float) $so->discount_amount : 0;
            $shipping = $so ? (float) $so->shipping_cost : 0;
            $termDays = $so ? (int) $so->payment_terms_days : 0;
            $total    = max(0, $subtotal - $discount + $shipping);

            $invoiceDate = Carbon::now()->toDateString();
            $dueDate     = Carbon::parse($invoiceDate)->addDays($termDays)->toDateString();

            $invoiceNumber = $this->movements->nextDocNumber('INV');

            $invoice = Invoice::create([
                'invoice_number'     => $invoiceNumber,
                'so_id'              => $do->so_id,
                'do_id'              => $do->id,
                'customer_id'        => $do->customer_id,
                'invoice_date'       => $invoiceDate,
                'due_date'           => $dueDate,
                'subtotal'           => $subtotal,
                'discount_amount'    => $discount,
                'tax_amount'         => 0,
                'shipping_cost'      => $shipping,
                'total_amount'       => $total,
                'paid_amount'        => 0,
                'status'             => Invoice::STATUS_ISSUED,
                'payment_terms_days' => $termDays,
                'currency'           => 'IDR',
                'created_by'         => $userId,
            ]);

            foreach ($itemsData as $line) {
                $line['invoice_id'] = $invoice->id;
                InvoiceItem::create($line);
            }

            return $invoice->fresh(['items']);
        });
    }

    /**
     * Cancel invoice yang belum ada pembayaran.
     */
    public function cancel(Invoice $invoice): Invoice
    {
        if (! $invoice->isCancellable()) {
            throw new \RuntimeException('Invoice tidak bisa dicancel — sudah ada pembayaran atau status tidak valid.');
        }
        $invoice->update(['status' => Invoice::STATUS_CANCELLED]);
        return $invoice;
    }

    /**
     * Recalc paid_amount dari sum invoice_payments (yg payment cleared) + update status.
     * Dipanggil setelah Payment create/cancel.
     */
    public function recalcPaidStatus(Invoice $invoice): void
    {
        $paid = (float) DB::table('tbr_invoice_payments as ip')
            ->join('tbr_payments as p', 'p.id', '=', 'ip.payment_id')
            ->where('ip.invoice_id', $invoice->id)
            ->where('p.status', '!=', \App\Models\Payment::STATUS_CANCELLED)
            ->sum('ip.allocated_amount');

        $total = (float) $invoice->total_amount;

        $newStatus = $invoice->status;
        if ($paid <= 0) {
            // Kalau sebelumnya partial/paid → balik ke issued
            if (in_array($invoice->status, [Invoice::STATUS_PARTIAL, Invoice::STATUS_PAID], true)) {
                $newStatus = Invoice::STATUS_ISSUED;
            }
        } elseif ($paid >= $total - 0.005) {
            $newStatus = Invoice::STATUS_PAID;
        } else {
            $newStatus = Invoice::STATUS_PARTIAL;
        }

        // Cek overdue (kalau due_date lewat & belum paid)
        if ($newStatus !== Invoice::STATUS_PAID
            && $invoice->due_date
            && $invoice->due_date->isPast()) {
            $newStatus = Invoice::STATUS_OVERDUE;
        }

        $invoice->update([
            'paid_amount' => $paid,
            'status'      => $newStatus,
        ]);
    }
}

<?php

namespace App\Services;

use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use Illuminate\Support\Facades\DB;

class PurchaseOrderService
{
    public function __construct(private readonly StockMovementService $movements) {}

    /**
     * Create PO Draft untuk raw material dari supplier.
     * Jasa Bersih & Pembelian Lain-lain ada di menu terpisah.
     */
    public function createDraft(array $payload): PurchaseOrder
    {
        return DB::transaction(function () use ($payload) {
            $poNumber = $this->movements->nextDocNumber('PO');

            $po = PurchaseOrder::create([
                'po_number'             => $poNumber,
                'supplier_id'           => $payload['supplier_id'],
                'warehouse_id'          => $payload['warehouse_id'],
                'po_date'               => $payload['po_date'],
                'expected_date'         => $payload['expected_date'] ?? null,
                'status'                => PurchaseOrder::STATUS_DRAFT,
                'subtotal'              => 0,
                'tax_amount'            => 0,
                'additional_cost_total' => 0,
                'total_amount'          => 0,
                'notes'                 => $payload['notes'] ?? null,
                'created_by'            => $payload['created_by'],
            ]);

            $this->saveItems($po, $payload['items']);
            $this->recalcTotals($po);

            return $po->fresh(['items']);
        });
    }

    public function updateDraft(PurchaseOrder $po, array $payload): PurchaseOrder
    {
        if (! $po->isEditable()) {
            throw new \RuntimeException('PO yang sudah submitted tidak bisa diedit. Cancel dulu kalau perlu revisi.');
        }

        return DB::transaction(function () use ($po, $payload) {
            $po->update([
                'supplier_id'   => $payload['supplier_id'],
                'warehouse_id'  => $payload['warehouse_id'],
                'po_date'       => $payload['po_date'],
                'expected_date' => $payload['expected_date'] ?? null,
                'notes'         => $payload['notes'] ?? null,
            ]);

            $po->items()->delete();
            $this->saveItems($po, $payload['items']);
            $this->recalcTotals($po);

            return $po->fresh(['items']);
        });
    }

    public function submit(PurchaseOrder $po, int $userId): PurchaseOrder
    {
        if (! $po->isSubmittable()) {
            throw new \RuntimeException("PO ini tidak bisa di-submit pada status: {$po->status_label}.");
        }
        $po->update([
            'status'      => PurchaseOrder::STATUS_SUBMITTED,
            'approved_by' => $userId,
        ]);
        return $po;
    }

    public function cancel(PurchaseOrder $po): PurchaseOrder
    {
        if (! $po->isCancellable()) {
            throw new \RuntimeException("PO ini tidak bisa di-cancel pada status: {$po->status_label}.");
        }
        $po->update(['status' => PurchaseOrder::STATUS_CANCELLED]);
        return $po;
    }

    private function saveItems(PurchaseOrder $po, array $items): void
    {
        foreach ($items as $row) {
            $qtyGram    = (float) $row['qty_gram'];
            $pricePerKg = (float) $row['price_per_kg'];
            $subtotal   = round($qtyGram * $pricePerKg / 1000, 2);

            PurchaseOrderItem::create([
                'po_id'        => $po->id,
                'category_id'  => $row['category_id'],
                'qty_gram'     => $qtyGram,
                'price_per_kg' => $pricePerKg,
                'subtotal'     => $subtotal,
                'notes'        => $row['notes'] ?? null,
            ]);
        }
    }

    public function recalcTotals(PurchaseOrder $po): void
    {
        $itemSubtotal = (float) $po->items()->sum('subtotal');
        $tax          = (float) $po->tax_amount;
        $total        = $itemSubtotal + $tax;

        $po->update([
            'subtotal'              => $itemSubtotal,
            'additional_cost_total' => 0,
            'total_amount'          => $total,
        ]);
    }
}

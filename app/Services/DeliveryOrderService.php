<?php

namespace App\Services;

use App\Models\DeliveryOrder;
use App\Models\DeliveryOrderItem;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DeliveryOrderService
{
    public function __construct(private readonly StockMovementService $movements) {}

    /**
     * Create DO Draft dari SO confirmed/partial.
     *
     * @param array $payload {
     *   so_id: int,
     *   delivery_date: string,
     *   driver_name?: ?string,
     *   vehicle_no?: ?string,
     *   notes?: ?string,
     *   created_by: int,
     *   items: array<int, array{
     *     so_item_id: int,
     *     batch_id?: ?int,
     *     quantity: float,
     *   }>
     * }
     */
    public function createFromSO(array $payload): DeliveryOrder
    {
        return DB::transaction(function () use ($payload) {
            $so = SalesOrder::with('items')->lockForUpdate()->findOrFail($payload['so_id']);

            if (! in_array($so->status, [SalesOrder::STATUS_CONFIRMED, SalesOrder::STATUS_PARTIAL], true)) {
                throw new \RuntimeException("SO harus berstatus Confirmed/Partial. Status sekarang: {$so->status_label}.");
            }

            // Validate setiap item: qty <= outstanding (so.quantity - delivered_quantity)
            $itemsBySoItemId = $so->items->keyBy('id');
            foreach ($payload['items'] as $line) {
                $soItem = $itemsBySoItemId->get($line['so_item_id']);
                if (! $soItem) {
                    throw new \RuntimeException("SO item ID {$line['so_item_id']} tidak ditemukan di SO ini.");
                }
                $outstanding = (float) $soItem->quantity - (float) $soItem->delivered_quantity;
                if ((float) $line['quantity'] > $outstanding) {
                    throw new \RuntimeException(sprintf(
                        "Qty kirim untuk produk ID %d (%s) melebihi outstanding. Outstanding: %s, kirim: %s.",
                        $soItem->product_id,
                        $soItem->product?->name ?? '-',
                        number_format($outstanding, 3),
                        number_format((float) $line['quantity'], 3)
                    ));
                }
            }

            // Create DO header
            $doNumber = $this->movements->nextDocNumber('DO');
            $do = DeliveryOrder::create([
                'do_number'    => $doNumber,
                'so_id'        => $so->id,
                'customer_id'  => $so->customer_id,
                'warehouse_id' => $so->warehouse_id,
                'delivery_date' => $payload['delivery_date'],
                'driver_name'  => $payload['driver_name'] ?? null,
                'vehicle_no'   => $payload['vehicle_no']  ?? null,
                'notes'        => $payload['notes']       ?? null,
                'status'       => DeliveryOrder::STATUS_DRAFT,
                'created_by'   => $payload['created_by'],
            ]);

            // Create items
            foreach ($payload['items'] as $line) {
                $soItem = $itemsBySoItemId->get($line['so_item_id']);
                DeliveryOrderItem::create([
                    'do_id'      => $do->id,
                    'so_item_id' => $soItem->id,
                    'product_id' => $soItem->product_id,
                    'batch_id'   => $line['batch_id'] ?? null,
                    'quantity'   => $line['quantity'],
                    'uom_id'     => $soItem->uom_id,
                    'unit_price' => $soItem->unit_price,
                ]);
            }

            return $do->fresh(['items']);
        });
    }

    /**
     * Ship DO → release reserved stock, apply out_sale movements, update SO status.
     */
    public function ship(DeliveryOrder $do, int $userId, ?string $receivedByName = null): DeliveryOrder
    {
        if (! $do->isShippable()) {
            throw new \RuntimeException("DO ini tidak bisa di-ship pada status: {$do->status_label}.");
        }

        return DB::transaction(function () use ($do, $userId, $receivedByName) {
            $do->load('items.product');
            $so = $do->so_id ? SalesOrder::with('items')->lockForUpdate()->find($do->so_id) : null;

            foreach ($do->items as $doItem) {
                $product   = $doItem->product;
                $qty       = (float) $doItem->quantity;
                $warehouse = (int) $do->warehouse_id;

                // 1. Release reserved (kurangi reserved_quantity)
                //    Strategi: pakai batch_id yg dipilih kalau ada, kalau tidak agregat
                $this->releaseReserved($product->id, $warehouse, $doItem->batch_id ? (int) $doItem->batch_id : null, $qty);

                // 2. Insert StockMovement out_sale (quantity negatif)
                $docNumber = $this->movements->nextDocNumber('DO');
                $this->movements->createMovement([
                    'movement_number' => $docNumber,
                    'product_id'      => $product->id,
                    'warehouse_id'    => $warehouse,
                    'batch_id'        => $doItem->batch_id ? (int) $doItem->batch_id : null,
                    'movement_type'   => StockMovement::TYPE_OUT_SALE,
                    'reference_type'  => 'DO',
                    'reference_id'    => $do->id,
                    'quantity'        => -1 * $qty, // negatif untuk keluar
                    'uom_id'          => $doItem->uom_id,
                    'cost_price'      => (float) $product->default_cost_price,
                    'notes'           => "DO {$do->do_number}",
                    'created_by'      => $userId,
                ]);

                // 3. Update SO item delivered_quantity
                if ($so && $doItem->so_item_id) {
                    DB::table('tbr_sales_order_items')
                        ->where('id', $doItem->so_item_id)
                        ->update([
                            'delivered_quantity' => DB::raw("delivered_quantity + {$qty}"),
                        ]);
                }
            }

            // Update DO status
            $do->update([
                'status'           => DeliveryOrder::STATUS_SHIPPED,
                'delivered_at'     => Carbon::now(),
                'received_by_name' => $receivedByName,
            ]);

            // Update SO status (partial / delivered)
            if ($so) {
                $so->refresh();
                $so->load('items');
                $allDelivered = $so->items->every(fn ($i) =>
                    (float) $i->delivered_quantity >= (float) $i->quantity
                );
                $anyDelivered = $so->items->some(fn ($i) =>
                    (float) $i->delivered_quantity > 0
                );

                if ($allDelivered) {
                    $so->update(['status' => SalesOrder::STATUS_DELIVERED]);
                } elseif ($anyDelivered) {
                    $so->update(['status' => SalesOrder::STATUS_PARTIAL]);
                }
            }

            return $do->fresh(['items']);
        });
    }

    /**
     * Cancel DO Draft (belum shipped) — tidak ada efek stock.
     */
    public function cancel(DeliveryOrder $do): DeliveryOrder
    {
        if (! $do->isCancellable()) {
            throw new \RuntimeException("DO ini tidak bisa di-cancel pada status: {$do->status_label}.");
        }
        $do->update(['status' => DeliveryOrder::STATUS_CANCELLED]);
        return $do;
    }

    /* =================== Helpers =================== */

    /**
     * Release reserved_quantity untuk produk+warehouse.
     * Kalau batch_id specified: kurangi reserved di balance dengan batch tsb saja.
     * Kalau null: kurangi reserved di balance manapun yang punya reserved (FIFO by id).
     */
    private function releaseReserved(int $productId, int $warehouseId, ?int $batchId, float $qty): void
    {
        $remaining = $qty;
        $query = DB::table('tbs_stock_balances')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->where('reserved_quantity', '>', 0);

        if ($batchId !== null) {
            $query->where('batch_id', $batchId);
        }

        $balances = $query->orderBy('id')->lockForUpdate()->select('id', 'reserved_quantity')->get();

        foreach ($balances as $bal) {
            if ($remaining <= 0) break;
            $reserved = (float) $bal->reserved_quantity;
            $release  = min($remaining, $reserved);
            DB::table('tbs_stock_balances')
                ->where('id', $bal->id)
                ->update([
                    'reserved_quantity' => DB::raw('reserved_quantity - ' . $release),
                    'last_updated_date' => now(),
                ]);
            $remaining -= $release;
        }

        if ($remaining > 0.001) {
            throw new \RuntimeException(
                "Reserved stock tidak mencukupi untuk release. Sisa belum ter-release: " . number_format($remaining, 3)
            );
        }
    }
}

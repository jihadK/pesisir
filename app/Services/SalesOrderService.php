<?php

namespace App\Services;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Support\Facades\DB;

class SalesOrderService
{
    public function __construct(private readonly StockMovementService $movements) {}

    /**
     * Create SO baru (status draft).
     *
     * @param array $payload {
     *   customer_id: int, warehouse_id: int, sales_user_id: ?int,
     *   order_date: string, delivery_date: ?string,
     *   payment_terms_days: ?int, shipping_cost: ?float, discount_amount: ?float,
     *   notes: ?string, created_by: int,
     *   items: array<int, array{
     *     product_id:int, quantity:float, unit_price:float,
     *     discount_pct?:float, notes?:?string
     *   }>
     * }
     */
    public function createDraft(array $payload): SalesOrder
    {
        return DB::transaction(function () use ($payload) {
            $soNumber = $this->movements->nextDocNumber('SO');

            $so = SalesOrder::create([
                'so_number'          => $soNumber,
                'customer_id'        => $payload['customer_id'],
                'warehouse_id'       => $payload['warehouse_id'],
                'sales_user_id'      => $payload['sales_user_id'] ?? $payload['created_by'],
                'order_date'         => $payload['order_date'],
                'delivery_date'      => $payload['delivery_date'] ?? null,
                'status'             => SalesOrder::STATUS_DRAFT,
                'payment_terms_days' => $payload['payment_terms_days'] ?? 0,
                'payment_method_id'  => $payload['payment_method_id'] ?? null,
                'shipping_cost'      => $payload['shipping_cost'] ?? 0,
                'discount_amount'    => $payload['discount_amount'] ?? 0,
                'tax_amount'         => 0,
                'notes'              => $payload['notes'] ?? null,
                'created_by'         => $payload['created_by'],
            ]);

            $this->saveItems($so, $payload['items']);
            $this->recalcTotals($so);

            return $so->fresh(['items']);
        });
    }

    /**
     * Update SO draft.
     */
    public function updateDraft(SalesOrder $so, array $payload): SalesOrder
    {
        if (! $so->isEditable()) {
            throw new \RuntimeException('SO yang sudah confirmed tidak bisa diedit. Cancel dulu kalau perlu revisi.');
        }

        return DB::transaction(function () use ($so, $payload) {
            $so->update([
                'customer_id'        => $payload['customer_id'],
                'warehouse_id'       => $payload['warehouse_id'],
                'sales_user_id'      => $payload['sales_user_id'] ?? $so->sales_user_id,
                'order_date'         => $payload['order_date'],
                'delivery_date'      => $payload['delivery_date'] ?? null,
                'payment_terms_days' => $payload['payment_terms_days'] ?? 0,
                'payment_method_id'  => $payload['payment_method_id'] ?? null,
                'shipping_cost'      => $payload['shipping_cost'] ?? 0,
                'discount_amount'    => $payload['discount_amount'] ?? 0,
                'notes'              => $payload['notes'] ?? null,
            ]);

            $so->items()->delete();
            $this->saveItems($so, $payload['items']);
            $this->recalcTotals($so);

            return $so->fresh(['items']);
        });
    }

    /**
     * Confirm SO → reserve stock di tbs_stock_balances.
     */
    public function confirm(SalesOrder $so, int $userId): SalesOrder
    {
        if (! $so->isConfirmable()) {
            throw new \RuntimeException('SO ini tidak bisa di-confirm pada status saat ini.');
        }

        return DB::transaction(function () use ($so, $userId) {
            $so->load('items');

            // Validate stock availability per item (sebelum reserve apapun)
            foreach ($so->items as $item) {
                $available = $this->getAvailable((int) $item->product_id, (int) $so->warehouse_id);
                if ((float) $item->quantity > $available) {
                    throw new \RuntimeException(sprintf(
                        "Stock tidak cukup untuk produk ID %d. Tersedia: %s, dibutuhkan: %s.",
                        $item->product_id,
                        number_format($available, 3),
                        number_format((float) $item->quantity, 3)
                    ));
                }
            }

            // Reserve qty per produk (per warehouse, agregat semua batch).
            // Strategi: tambah reserved_quantity pada baris tbs_stock_balances mana saja yang
            // ada quantity untuk produk+warehouse, urut FEFO (expiry naik) sampai tertutup.
            foreach ($so->items as $item) {
                $remaining = (float) $item->quantity;

                $balances = DB::table('tbs_stock_balances as sb')
                    ->leftJoin('tbm_product_batches as b', 'b.id', '=', 'sb.batch_id')
                    ->where('sb.product_id', $item->product_id)
                    ->where('sb.warehouse_id', $so->warehouse_id)
                    ->where('sb.quantity', '>', DB::raw('sb.reserved_quantity'))
                    ->orderByRaw('b.expiry_date IS NULL, b.expiry_date ASC')
                    ->orderBy('sb.id')
                    ->select('sb.id', 'sb.quantity', 'sb.reserved_quantity')
                    ->lock('FOR UPDATE OF sb')  // Postgres: lock hanya tabel utama (sb), bukan nullable join
                    ->get();

                foreach ($balances as $bal) {
                    if ($remaining <= 0) break;
                    $availableHere = (float) $bal->quantity - (float) $bal->reserved_quantity;
                    if ($availableHere <= 0) continue;
                    $take = min($remaining, $availableHere);
                    DB::table('tbs_stock_balances')
                        ->where('id', $bal->id)
                        ->update([
                            'reserved_quantity' => DB::raw('reserved_quantity + ' . $take),
                            'last_updated_date' => now(),
                        ]);
                    $remaining -= $take;
                }

                if ($remaining > 0.001) {
                    throw new \RuntimeException("Reserve gagal: stock berubah saat proses (race condition). Coba lagi.");
                }
            }

            $so->update([
                'status'      => SalesOrder::STATUS_CONFIRMED,
                'approved_by' => $userId,
            ]);

            return $so;
        });
    }

    /**
     * Cancel SO → release reserved stock kalau status confirmed.
     */
    public function cancel(SalesOrder $so): SalesOrder
    {
        if (! $so->isCancellable()) {
            throw new \RuntimeException('SO ini tidak bisa di-cancel pada status saat ini.');
        }

        return DB::transaction(function () use ($so) {
            // Release reservations kalau status confirmed
            if ($so->status === SalesOrder::STATUS_CONFIRMED) {
                $so->load('items');
                foreach ($so->items as $item) {
                    $remaining = (float) $item->quantity;
                    $balances = DB::table('tbs_stock_balances as sb')
                        ->leftJoin('tbm_product_batches as b', 'b.id', '=', 'sb.batch_id')
                        ->where('sb.product_id', $item->product_id)
                        ->where('sb.warehouse_id', $so->warehouse_id)
                        ->where('sb.reserved_quantity', '>', 0)
                        ->orderByRaw('b.expiry_date IS NULL, b.expiry_date ASC')
                        ->orderBy('sb.id')
                        ->select('sb.id', 'sb.reserved_quantity')
                        ->lock('FOR UPDATE OF sb')
                        ->get();

                    foreach ($balances as $bal) {
                        if ($remaining <= 0) break;
                        $reserved = (float) $bal->reserved_quantity;
                        $release = min($remaining, $reserved);
                        DB::table('tbs_stock_balances')
                            ->where('id', $bal->id)
                            ->update([
                                'reserved_quantity' => DB::raw('reserved_quantity - ' . $release),
                                'last_updated_date' => now(),
                            ]);
                        $remaining -= $release;
                    }
                }
            }

            $so->update(['status' => SalesOrder::STATUS_CANCELLED]);
            return $so;
        });
    }

    /* ============================== Helpers ============================== */

    private function saveItems(SalesOrder $so, array $items): void
    {
        foreach ($items as $row) {
            $product = Product::with('baseUom')->findOrFail($row['product_id']);
            $qty       = (float) $row['quantity'];
            $price     = (float) ($row['unit_price'] ?? $product->default_sell_price);
            $discPct   = (float) ($row['discount_pct'] ?? 0);
            $discAmt   = round($qty * $price * $discPct / 100, 2);
            $subtotal  = round($qty * $price - $discAmt, 2);

            SalesOrderItem::create([
                'so_id'           => $so->id,
                'product_id'      => $product->id,
                'uom_id'          => $product->base_uom_id,
                'quantity'        => $qty,
                'unit_price'      => $price,
                'discount_pct'    => $discPct,
                'discount_amount' => $discAmt,
                'subtotal'        => $subtotal,
                'notes'           => $row['notes'] ?? null,
            ]);
        }
    }

    public function recalcTotals(SalesOrder $so): void
    {
        $items    = $so->items()->get();
        $subtotal = (float) $items->sum('subtotal');
        $disc     = (float) $so->discount_amount;
        $tax      = (float) $so->tax_amount;
        $shipping = (float) $so->shipping_cost;
        $total    = max(0, $subtotal - $disc + $tax + $shipping);

        $so->update([
            'subtotal'     => $subtotal,
            'total_amount' => $total,
        ]);
    }

    private function getAvailable(int $productId, int $warehouseId): float
    {
        $row = DB::table('tbs_stock_balances')
            ->selectRaw('COALESCE(SUM(quantity - reserved_quantity), 0) AS avail')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->first();
        return (float) ($row->avail ?? 0);
    }
}

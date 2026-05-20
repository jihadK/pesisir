<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Quick stock update untuk modal di halaman Produk.
 * Class baru — sengaja terpisah dari StockAdjustmentService supaya tidak
 * terjebak opcache class lama. Logic FEFO untuk deduct lintas batches.
 */
class QuickStockUpdateService
{
    public const REASONS = [
        'count_in'  => 'Koreksi (Lebih)',
        'count_out' => 'Koreksi (Kurang)',
    ];

    public function __construct(private readonly StockMovementService $movements) {}

    public function apply(array $payload): StockMovement
    {
        return DB::transaction(function () use ($payload) {
            Log::info('[QuickStock] apply', $payload);

            $product     = Product::with('baseUom')->findOrFail($payload['product_id']);
            $direction   = $payload['direction'] === 'in' ? 1 : -1;
            $qtyAbs      = abs((float) $payload['quantity']);
            $warehouseId = (int) $payload['warehouse_id'];
            $reasonKey   = $payload['direction'] === 'in' ? 'count_in' : 'count_out';
            $reasonLabel = self::REASONS[$reasonKey];
            $notes       = "[{$reasonLabel}] " . ($payload['notes'] ?? '');

            // === IN (Tambah) ===
            if ($direction > 0) {
                return $this->movements->createMovement([
                    'movement_number' => $this->movements->nextDocNumber('ADJ'),
                    'product_id'      => $product->id,
                    'warehouse_id'    => $warehouseId,
                    'batch_id'        => null,
                    'movement_type'   => StockMovement::TYPE_IN_ADJUSTMENT,
                    'reference_type'  => StockMovement::REF_ADJUSTMENT,
                    'quantity'        => $qtyAbs,
                    'uom_id'          => $product->base_uom_id,
                    'cost_price'      => 0,
                    'notes'           => $notes,
                    'created_by'      => $payload['created_by'],
                ]);
            }

            // === OUT (Kurang) — FEFO across all balance rows ===
            $balances = DB::table('tbs_stock_balances as sb')
                ->leftJoin('tbm_product_batches as b', 'b.id', '=', 'sb.batch_id')
                ->where('sb.product_id', $product->id)
                ->where('sb.warehouse_id', $warehouseId)
                ->orderByRaw('b.expiry_date IS NULL, b.expiry_date ASC')
                ->orderBy('sb.id')
                ->select('sb.id', 'sb.batch_id', 'sb.quantity', 'sb.reserved_quantity')
                ->lock('FOR UPDATE OF sb')
                ->get();

            $totalAvail = (float) $balances->sum(fn($r) => max(0, (float)$r->quantity - (float)$r->reserved_quantity));

            Log::info('[QuickStock] balances scanned', [
                'product_id' => $product->id,
                'warehouse_id' => $warehouseId,
                'rows' => $balances->map(fn($r) => [
                    'id' => $r->id, 'batch_id' => $r->batch_id,
                    'qty' => $r->quantity, 'reserved' => $r->reserved_quantity,
                ])->all(),
                'total_available' => $totalAvail,
                'requested' => $qtyAbs,
            ]);

            if ($qtyAbs > $totalAvail) {
                throw new \RuntimeException(sprintf(
                    'Stock tidak cukup. Tersedia: %s, diminta: %s.',
                    number_format($totalAvail, 3),
                    number_format($qtyAbs, 3)
                ));
            }

            $remaining = $qtyAbs;
            $firstMovement = null;
            foreach ($balances as $bal) {
                if ($remaining <= 0) break;
                $availHere = max(0, (float)$bal->quantity - (float)$bal->reserved_quantity);
                if ($availHere <= 0) continue;
                $take = min($remaining, $availHere);

                $mv = $this->movements->createMovement([
                    'movement_number' => $this->movements->nextDocNumber('ADJ'),
                    'product_id'      => $product->id,
                    'warehouse_id'    => $warehouseId,
                    'batch_id'        => $bal->batch_id, // per baris balance
                    'movement_type'   => StockMovement::TYPE_OUT_ADJUSTMENT,
                    'reference_type'  => StockMovement::REF_ADJUSTMENT,
                    'quantity'        => -$take,
                    'uom_id'          => $product->base_uom_id,
                    'cost_price'      => 0,
                    'notes'           => $notes,
                    'created_by'      => $payload['created_by'],
                ]);
                if ($firstMovement === null) $firstMovement = $mv;
                $remaining -= $take;
            }

            if ($firstMovement === null) {
                throw new \RuntimeException('Tidak ada stock yang bisa dideduct.');
            }

            return $firstMovement;
        });
    }
}

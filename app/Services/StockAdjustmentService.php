<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Facades\DB;

class StockAdjustmentService
{
    public const REASONS = [
        'damaged'   => 'Rusak',
        'expired'   => 'Expired',
        'lost'      => 'Hilang',
        'count_in'  => 'Koreksi (Lebih)',
        'count_out' => 'Koreksi (Kurang)',
        'other'     => 'Lainnya',
    ];

    public function __construct(private readonly StockMovementService $movements) {}

    /**
     * Apply single-line adjustment.
     *
     * @param array $payload {
     *   warehouse_id: int,
     *   product_id: int,
     *   batch_id: ?int,
     *   direction: 'in'|'out',
     *   reason: string (key dari REASONS),
     *   quantity: float (selalu positif),
     *   notes: ?string,
     *   created_by: int,
     * }
     */
    public function applyAdjustment(array $payload): StockMovement
    {
        return DB::transaction(function () use ($payload) {
            $product = Product::with('baseUom')->findOrFail($payload['product_id']);
            $direction = $payload['direction'] === 'in' ? 1 : -1;
            $qtyAbs = abs((float) $payload['quantity']);
            $reasonLabel = self::REASONS[$payload['reason']] ?? $payload['reason'];
            $combinedNotes = "[{$reasonLabel}]" . (! empty($payload['notes']) ? ' ' . $payload['notes'] : '');
            $batchId = $payload['batch_id'] ?? null;
            $warehouseId = (int) $payload['warehouse_id'];

            // === ADJUSTMENT OUT ===
            if ($direction < 0) {
                // Lock semua baris stock balance relevant (product + warehouse).
                // Kalau batch_id diisi, hanya 1 baris itu. Kalau null, lock & deduct FEFO
                // dari semua baris yang ada stok (urut expiry naik, null terakhir).
                $balQ = DB::table('tbs_stock_balances as sb')
                    ->leftJoin('tbm_product_batches as b', 'b.id', '=', 'sb.batch_id')
                    ->where('sb.product_id', $product->id)
                    ->where('sb.warehouse_id', $warehouseId);
                if ($batchId !== null) {
                    $balQ->where('sb.batch_id', (int) $batchId);
                }
                $balances = $balQ
                    ->orderByRaw('b.expiry_date IS NULL, b.expiry_date ASC')
                    ->orderBy('sb.id')
                    ->select('sb.id', 'sb.batch_id', 'sb.quantity', 'sb.reserved_quantity')
                    ->lock('FOR UPDATE OF sb')
                    ->get();

                $totalAvailable = (float) $balances->sum(fn($r) => max(0, (float)$r->quantity - (float)$r->reserved_quantity));
                if ($qtyAbs > $totalAvailable) {
                    throw new \RuntimeException(sprintf(
                        'Stock tidak cukup (tersedia: %s, diminta: %s).',
                        number_format($totalAvailable, 3),
                        number_format($qtyAbs, 3)
                    ));
                }

                $movementType = in_array($payload['reason'], ['damaged','expired'], true)
                    ? StockMovement::TYPE_OUT_WASTE
                    : StockMovement::TYPE_OUT_ADJUSTMENT;

                // Deduct FEFO — split per baris balance. Movement pertama jadi return value.
                $remaining = $qtyAbs;
                $firstMovement = null;
                foreach ($balances as $bal) {
                    if ($remaining <= 0) break;
                    $availHere = max(0, (float)$bal->quantity - (float)$bal->reserved_quantity);
                    if ($availHere <= 0) continue;
                    $take = min($remaining, $availHere);

                    $docNumber = $this->movements->nextDocNumber('ADJ');
                    $mv = $this->movements->createMovement([
                        'movement_number' => $docNumber,
                        'product_id'      => $product->id,
                        'warehouse_id'    => $warehouseId,
                        'batch_id'        => $bal->batch_id, // ikut baris yang dideduct
                        'movement_type'   => $movementType,
                        'reference_type'  => StockMovement::REF_ADJUSTMENT,
                        'quantity'        => -$take,
                        'uom_id'          => $product->base_uom_id,
                        'cost_price'      => 0,
                        'notes'           => $combinedNotes,
                        'created_by'      => $payload['created_by'],
                    ]);
                    if ($firstMovement === null) $firstMovement = $mv;
                    $remaining -= $take;
                }
                if ($remaining > 0.001 || $firstMovement === null) {
                    throw new \RuntimeException('Gagal deduct stock — race condition. Coba lagi.');
                }
                return $firstMovement;
            }

            // === ADJUSTMENT IN ===
            $docNumber = $this->movements->nextDocNumber('ADJ');
            return $this->movements->createMovement([
                'movement_number' => $docNumber,
                'product_id'      => $product->id,
                'warehouse_id'    => $warehouseId,
                'batch_id'        => $batchId,
                'movement_type'   => StockMovement::TYPE_IN_ADJUSTMENT,
                'reference_type'  => StockMovement::REF_ADJUSTMENT,
                'quantity'        => $qtyAbs,
                'uom_id'          => $product->base_uom_id,
                'cost_price'      => 0,
                'notes'           => $combinedNotes,
                'created_by'      => $payload['created_by'],
            ]);
        });
    }

    public function listHistory(array $filters)
    {
        return StockMovement::query()
            ->with(['product:id,sku,name', 'warehouse:id,code,name', 'batch:id,batch_number', 'createdBy:id,full_name'])
            ->where('reference_type', StockMovement::REF_ADJUSTMENT)
            ->ofProduct($filters['product_id'] ?? null)
            ->ofWarehouse($filters['warehouse_id'] ?? null)
            ->betweenDates($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->orderByDesc('created_date')
            ->paginate(50)
            ->withQueryString();
    }
}

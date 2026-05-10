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
            $qty = abs((float) $payload['quantity']) * $direction;

            // Validasi: jangan biarkan stock jadi negatif untuk adjustment OUT
            if ($direction < 0) {
                $current = $this->movements->getCurrentBalance(
                    $product->id,
                    (int) $payload['warehouse_id'],
                    isset($payload['batch_id']) ? (int) $payload['batch_id'] : null
                );
                if (abs($qty) > $current) {
                    throw new \RuntimeException(sprintf(
                        "Qty melebihi stock saat ini (%s). Saldo saat ini: %s.",
                        number_format(abs($qty), 3),
                        number_format($current, 3)
                    ));
                }
            }

            // Pilih movement_type: out_waste untuk damaged/expired, sisanya pakai *_adjustment
            $movementType = match (true) {
                $direction < 0 && in_array($payload['reason'], ['damaged','expired'], true) => StockMovement::TYPE_OUT_WASTE,
                $direction < 0 => StockMovement::TYPE_OUT_ADJUSTMENT,
                default        => StockMovement::TYPE_IN_ADJUSTMENT,
            };

            $reasonLabel = self::REASONS[$payload['reason']] ?? $payload['reason'];
            $combinedNotes = "[{$reasonLabel}]" . (! empty($payload['notes']) ? ' ' . $payload['notes'] : '');

            $docNumber = $this->movements->nextDocNumber('ADJ');
            return $this->movements->createMovement([
                'movement_number' => $docNumber,
                'product_id'      => $product->id,
                'warehouse_id'    => $payload['warehouse_id'],
                'batch_id'        => $payload['batch_id'] ?? null,
                'movement_type'   => $movementType,
                'reference_type'  => StockMovement::REF_ADJUSTMENT,
                'quantity'        => $qty,
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

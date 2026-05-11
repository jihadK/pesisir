<?php

namespace App\Services;

use App\Models\Product;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockOpeningService
{
    public function __construct(private readonly StockMovementService $movements) {}

    /**
     * Apply stock opening untuk satu warehouse, multiple item.
     *
     * @param array $payload {
     *   warehouse_id: int,
     *   notes: ?string,
     *   created_by: int,
     *   items: array<int, array{
     *     product_id: int,
     *     quantity: float,
     *     cost_price: float,
     *     production_date?: ?string,
     *     expiry_date?: ?string,
     *     catch_date?: ?string,
     *     catch_location?: ?string,
     *   }>
     * }
     *
     * @return array{count:int, doc_numbers:array<int,string>}
     */
    public function applyOpening(array $payload): array
    {
        return DB::transaction(function () use ($payload) {
            $warehouseId = (int) $payload['warehouse_id'];
            $notes       = $payload['notes'] ?? null;
            $createdBy   = $payload['created_by'];
            $docNumbers  = [];
            $count       = 0;

            foreach ($payload['items'] as $item) {
                if (! ($item['quantity'] ?? 0)) continue;

                $product = Product::with('baseUom')->findOrFail($item['product_id']);

                // Guard: tidak boleh opening kalau sudah ada movement
                if ($this->movements->hasAnyMovement($product->id, $warehouseId)) {
                    throw new \RuntimeException(
                        "Produk '{$product->name}' (SKU: {$product->sku}) sudah punya gerakan stock di gudang ini — opening tidak diperbolehkan. Pakai Stock Adjustment untuk koreksi."
                    );
                }

                $batchId = null;
                if ($product->is_perishable) {
                    // Buat batch otomatis (placeholder bila tanggal kosong)
                    $expiry = $item['expiry_date'] ?? null;
                    if (! $expiry && $product->shelf_life_days) {
                        $expiry = Carbon::now()->addDays((int) $product->shelf_life_days)->toDateString();
                    }

                    $batch = $this->movements->createBatch([
                        'product_id'       => $product->id,
                        'received_date'    => Carbon::now()->toDateString(),
                        'production_date'  => $item['production_date']  ?? null,
                        'expiry_date'      => $expiry,
                        'catch_date'       => $item['catch_date']       ?? null,
                        'catch_location'   => $item['catch_location']   ?? null,
                        'cost_price'       => $item['cost_price']       ?? 0,
                        'initial_quantity' => $item['quantity'],
                        'quality_status'   => 'fresh',
                        'notes'            => 'Stock Opening',
                        '_ref_prefix'      => 'OPENING',
                    ]);
                    $batchId = $batch->id;
                }

                $docNumber = $this->movements->nextDocNumber('OPN');
                $this->movements->createMovement([
                    'movement_number' => $docNumber,
                    'product_id'      => $product->id,
                    'warehouse_id'    => $warehouseId,
                    'batch_id'        => $batchId,
                    'movement_type'   => StockMovement::TYPE_IN_ADJUSTMENT,
                    'reference_type'  => StockMovement::REF_OPENING,
                    'quantity'        => abs((float) $item['quantity']),
                    'uom_id'          => $product->base_uom_id,
                    'cost_price'      => $item['cost_price'] ?? 0,
                    'notes'           => $notes ?: 'Stock Opening',
                    'created_by'      => $createdBy,
                ]);

                $docNumbers[] = $docNumber;
                $count++;
            }

            if ($count === 0) {
                throw new \RuntimeException('Tidak ada item dengan qty > 0 untuk diproses.');
            }

            return ['count' => $count, 'doc_numbers' => $docNumbers];
        });
    }

    /**
     * List riwayat opening dengan filter & pagination.
     */
    public function listHistory(array $filters)
    {
        return StockMovement::query()
            ->with([
                'product:id,sku,name,base_uom_id,pack_content_type,pack_content_min,pack_content_max,pack_weight_min_g,pack_weight_max_g',
                'product.baseUom:id,code',
                'warehouse:id,code,name',
                'batch:id,batch_number,expiry_date',
                'createdBy:id,full_name',
            ])
            ->where('reference_type', StockMovement::REF_OPENING)
            ->ofProduct($filters['product_id'] ?? null)
            ->ofWarehouse($filters['warehouse_id'] ?? null)
            ->betweenDates($filters['date_from'] ?? null, $filters['date_to'] ?? null)
            ->orderByDesc('created_date')
            ->paginate(50)
            ->withQueryString();
    }
}

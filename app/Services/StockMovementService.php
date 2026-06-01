<?php

namespace App\Services;

use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class StockMovementService
{
    /**
     * Generate document number atomik dari tbs_document_sequences.
     * Format: {prefix}{YYYY}/{NNNNN}  (mis. OPN/2026/00001)
     *
     * Reset rules:
     *   - 'yearly'  : reset 1 Januari setiap tahun
     *   - 'monthly' : reset 1 setiap bulan
     *   - 'never'   : tidak reset
     */
    /**
     * Peta doc_type → (table, kolom nomor). Dipakai untuk self-healing: kalau
     * row sequence tidak sinkron (mis. di-reseed tapi data lama masih ada),
     * kandidat nomor di-naikkan ke max(existing)+1 supaya tidak bentrok.
     */
    private const DOC_TARGETS = [
        'SM'  => ['tbh_stock_movements', 'movement_number'],
        'SO'  => ['tbr_sales_orders',    'so_number'],
        'PO'  => ['tbr_purchase_orders', 'po_number'],
        'DO'  => ['tbr_delivery_orders', 'do_number'],
        'INV' => ['tbr_invoices',        'invoice_number'],
        'PAY' => ['tbr_payments',        'payment_number'],
    ];

    public function nextDocNumber(string $docType): string
    {
        return DB::transaction(function () use ($docType) {
            $row = DB::table('tbs_document_sequences')
                ->where('doc_type', $docType)
                ->lockForUpdate()
                ->first();

            if (! $row) {
                throw new \RuntimeException("Document sequence '{$docType}' belum di-seed.");
            }

            $today = Carbon::now();
            $needsReset = false;
            $lastReset  = $row->last_reset_at ? Carbon::parse($row->last_reset_at) : null;

            if ($row->reset_period === 'yearly') {
                $needsReset = ! $lastReset || $lastReset->year !== $today->year;
            } elseif ($row->reset_period === 'monthly') {
                $needsReset = ! $lastReset
                    || $lastReset->year !== $today->year
                    || $lastReset->month !== $today->month;
            }

            $nextNumber = $needsReset ? 1 : ((int) $row->current_number + 1);

            // Self-healing: hindari bentrok dengan nomor yang SUDAH ada di tabel
            // tujuan (sequence row bisa stale karena seed/restore). Ambil max
            // numerik dari tail nomor untuk prefix+tahun, naikkan kalau perlu.
            if (isset(self::DOC_TARGETS[$docType])) {
                [$targetTable, $targetCol] = self::DOC_TARGETS[$docType];
                $prefixYear = sprintf('%s%d/', $row->prefix, $today->year);
                $maxTail = (int) DB::table($targetTable)
                    ->where($targetCol, 'like', $prefixYear . '%')
                    ->selectRaw("COALESCE(MAX(CAST(SUBSTRING({$targetCol} FROM '[0-9]+$') AS INTEGER)), 0) AS tail")
                    ->value('tail');
                if ($maxTail >= $nextNumber) {
                    $nextNumber = $maxTail + 1;
                }
            }

            DB::table('tbs_document_sequences')
                ->where('id', $row->id)
                ->update([
                    'current_number' => $nextNumber,
                    'last_reset_at'  => $today->toDateString(),
                    'updated_date'   => $today,
                ]);

            return sprintf('%s%d/%05d', $row->prefix, $today->year, $nextNumber);
        });
    }

    /**
     * Insert stock movement. Trigger DB akan auto-update tbs_stock_balances
     * & tbm_product_batches.remaining_quantity, plus mengisi balance_after.
     *
     * @param array{
     *   product_id:int, warehouse_id:int, movement_type:string,
     *   quantity:float, uom_id:int, batch_id?:int|null,
     *   reference_type?:string|null, reference_id?:int|null,
     *   cost_price?:float, notes?:string|null, created_by?:int|null,
     *   movement_number?:string|null
     * } $data
     */
    public function createMovement(array $data): StockMovement
    {
        if (! isset($data['movement_number']) || ! $data['movement_number']) {
            throw new \InvalidArgumentException('movement_number wajib diisi (panggil nextDocNumber dulu).');
        }
        if (! ($data['quantity'] ?? 0)) {
            throw new \InvalidArgumentException('quantity tidak boleh 0.');
        }

        \Illuminate\Support\Facades\Log::info('[StockMovement] createMovement', $data);
        // Defensive guard: kalau movement negatif (OUT), pastikan saldo baris
        // (product, warehouse, batch_id) tidak akan jadi negatif. Cek baris
        // SPESIFIK — bukan sum semua batch — sesuai unique key trigger DB
        // (product_id, warehouse_id, COALESCE(batch_id, 0)).
        $qty = (float) $data['quantity'];
        if ($qty < 0) {
            $batchId = $data['batch_id'] ?? null;
            $balQ = DB::table('tbs_stock_balances')
                ->where('product_id', $data['product_id'])
                ->where('warehouse_id', $data['warehouse_id']);
            if ($batchId !== null) {
                $balQ->where('batch_id', (int) $batchId);
            } else {
                $balQ->whereNull('batch_id');
            }
            $rows = $balQ->lockForUpdate()->get(['quantity']);
            $current = (float) $rows->sum('quantity');
            if (abs($qty) > $current) {
                throw new \RuntimeException(sprintf(
                    'Stock tidak cukup pada baris ini (saldo: %s, diminta: %s).',
                    number_format($current, 3),
                    number_format(abs($qty), 3)
                ));
            }
        }

        return StockMovement::create([
            'movement_number' => $data['movement_number'],
            'product_id'      => $data['product_id'],
            'warehouse_id'    => $data['warehouse_id'],
            'batch_id'        => $data['batch_id'] ?? null,
            'movement_type'   => $data['movement_type'],
            'reference_type'  => $data['reference_type'] ?? null,
            'reference_id'    => $data['reference_id'] ?? null,
            'quantity'        => $data['quantity'],
            'uom_id'          => $data['uom_id'],
            'cost_price'      => $data['cost_price'] ?? 0,
            'notes'           => $data['notes'] ?? null,
            'created_by'      => $data['created_by'] ?? null,
        ]);
    }

    /**
     * Buat batch baru — dipakai saat opening / GRN untuk produk perishable
     * yang belum punya batch.
     *
     * Auto-generate batch_number kalau tidak diisi:
     *   {refType}-{YYYYMMDD}-{NNN} → mis. OPENING-20260510-001
     */
    public function createBatch(array $data): ProductBatch
    {
        if (empty($data['batch_number'])) {
            $prefix = strtoupper($data['_ref_prefix'] ?? 'BATCH');
            $today  = Carbon::now()->format('Ymd');
            $latest = DB::table('tbm_product_batches')
                ->where('batch_number', 'like', "{$prefix}-{$today}-%")
                ->where('product_id', $data['product_id'])
                ->orderByDesc('batch_number')
                ->value('batch_number');

            $next = 1;
            if ($latest) {
                $tail = (int) substr($latest, strrpos($latest, '-') + 1);
                $next = $tail + 1;
            }
            $data['batch_number'] = sprintf('%s-%s-%03d', $prefix, $today, $next);
        }
        unset($data['_ref_prefix']);

        // Untuk batch baru: remaining_quantity diset awal sama dengan initial_quantity.
        // Trigger movement akan menambah lagi remaining saat insert movement, jadi
        // di sini kita set remaining = 0 supaya hasil akhir = qty movement.
        $data['initial_quantity']   = $data['initial_quantity'] ?? 0;
        $data['remaining_quantity'] = 0;

        return ProductBatch::create($data);
    }

    /**
     * Cek apakah produk sudah pernah punya movement di warehouse tertentu.
     * Dipakai untuk validasi: stock opening hanya boleh dilakukan kalau belum
     * ada gerakan stock sama sekali untuk kombinasi tersebut.
     */
    public function hasAnyMovement(int $productId, int $warehouseId): bool
    {
        return DB::table('tbh_stock_movements')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->exists();
    }

    /**
     * Total qty stock saat ini untuk kombinasi produk+warehouse (semua batch).
     */
    /**
     * Saldo stock untuk baris (product, warehouse, batch_id) tertentu.
     * Penting: kalau $batchId === null, harus filter WHERE batch_id IS NULL —
     * BUKAN sum semua batch — karena trigger update baris keyed by
     * (product_id, warehouse_id, COALESCE(batch_id, 0)). Sum-all akan menipu
     * validasi & menyebabkan baris null jadi negatif (chk_sb_qty_nonneg).
     */
    public function getCurrentBalance(int $productId, int $warehouseId, ?int $batchId = null): float
    {
        $q = DB::table('tbs_stock_balances')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId);
        if ($batchId !== null) {
            $q->where('batch_id', $batchId);
        } else {
            $q->whereNull('batch_id');
        }
        return (float) $q->sum('quantity');
    }
}

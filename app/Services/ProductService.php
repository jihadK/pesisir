<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductGrade;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductService
{
    /**
     * Generate suggested SKU dari kategori + grade + running number.
     * Format: FISH-{CATEGORY-SLUG}-{GRADE-CODE}-{NNN}
     * Mis. FISH-IKAN-LAUT-A-001
     */
    public function suggestSku(?int $categoryId = null, ?int $gradeId = null): string
    {
        $catSlug = 'PROD';
        if ($categoryId && ($cat = Category::find($categoryId))) {
            $catSlug = strtoupper(str_replace('-', '', Str::limit(Str::slug($cat->name), 8, '')));
        }

        $gradeCode = '';
        if ($gradeId && ($g = ProductGrade::find($gradeId))) {
            $gradeCode = '-' . strtoupper($g->code);
        }

        $prefix = "FISH-{$catSlug}{$gradeCode}";

        // Cari running number berikutnya
        $lastSku = DB::table('tbm_products')
            ->where('sku', 'ilike', $prefix . '-%')
            ->orderByDesc('sku')
            ->value('sku');

        $next = 1;
        if ($lastSku) {
            $parts = explode('-', $lastSku);
            $tail = end($parts);
            if (is_numeric($tail)) {
                $next = (int) $tail + 1;
            }
        }

        return sprintf('%s-%03d', $prefix, $next);
    }

    /**
     * Tidak boleh hapus kalau:
     *  - Masih ada stock balance > 0
     *  - Masih ada PO/SO aktif (draft/submitted/confirmed/partial)
     */
    public function canDelete(Product $p): array
    {
        $stockQty = (float) DB::table('tbs_stock_balances')
            ->where('product_id', $p->id)
            ->sum('quantity');
        if ($stockQty > 0) {
            return [
                'allowed' => false,
                'reason'  => "Produk masih punya stock {$stockQty} unit. Pindahkan/habiskan dulu.",
            ];
        }

        $activePO = DB::table('tbr_purchase_order_items as poi')
            ->join('tbr_purchase_orders as po', 'po.id', '=', 'poi.po_id')
            ->where('poi.product_id', $p->id)
            ->whereIn('po.status', ['draft', 'submitted', 'partial'])
            ->count();
        if ($activePO > 0) {
            return [
                'allowed' => false,
                'reason'  => "Produk masih ada di {$activePO} PO aktif.",
            ];
        }

        $activeSO = DB::table('tbr_sales_order_items as soi')
            ->join('tbr_sales_orders as so', 'so.id', '=', 'soi.so_id')
            ->where('soi.product_id', $p->id)
            ->whereIn('so.status', ['draft', 'confirmed', 'partial'])
            ->count();
        if ($activeSO > 0) {
            return [
                'allowed' => false,
                'reason'  => "Produk masih ada di {$activeSO} SO aktif.",
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    public function delete(Product $p): array
    {
        $check = $this->canDelete($p);
        if (! $check['allowed']) {
            return ['success' => false, 'message' => $check['reason']];
        }
        $name = $p->name;
        $p->delete();
        return ['success' => true, 'message' => "Produk '{$name}' berhasil dihapus."];
    }

    /**
     * Upload image ke storage/app/public/products/
     * Return relative URL (mis. /storage/products/abc-123.jpg) atau null kalau gagal.
     */
    public function uploadImage(UploadedFile $file, string $sku): ?string
    {
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension());
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }
        $filename = Str::slug($sku) . '-' . Str::random(6) . '.' . $ext;
        $path = $file->storeAs('public/products', $filename);
        // storeAs ke public disk return path relative ke storage. Kita return URL public
        return $path ? '/storage/products/' . $filename : null;
    }

    /**
     * Hapus file image lama (kalau di local storage kita).
     */
    public function deleteImage(?string $imageUrl): void
    {
        if (! $imageUrl) return;
        if (! str_starts_with($imageUrl, '/storage/products/')) return;
        $relative = 'public/' . substr($imageUrl, strlen('/storage/'));
        if (Storage::exists($relative)) {
            Storage::delete($relative);
        }
    }

    /* ===================================================================
     | METHODS UNTUK DETAIL/SHOW PAGE
     | =================================================================== */

    public function getDetailStats(Product $p): array
    {
        $stockTotal = (float) DB::table('tbs_stock_balances')
            ->where('product_id', $p->id)->sum('quantity');

        $stockReserved = (float) DB::table('tbs_stock_balances')
            ->where('product_id', $p->id)->sum('reserved_quantity');

        $activeBatchCount = (int) DB::table('tbm_product_batches')
            ->where('product_id', $p->id)
            ->where('remaining_quantity', '>', 0)->count();

        $totalSold = (float) DB::table('tbr_sales_order_items as soi')
            ->join('tbr_sales_orders as so', 'so.id', '=', 'soi.so_id')
            ->where('soi.product_id', $p->id)
            ->whereNotIn('so.status', ['draft', 'cancelled'])
            ->sum('soi.delivered_quantity');

        $lastReceived = DB::table('tbm_product_batches')
            ->where('product_id', $p->id)
            ->max('received_date');

        $lastSold = DB::table('tbr_sales_order_items as soi')
            ->join('tbr_sales_orders as so', 'so.id', '=', 'soi.so_id')
            ->where('soi.product_id', $p->id)
            ->whereNotIn('so.status', ['draft', 'cancelled'])
            ->max('so.order_date');

        return [
            'stock_total'     => $stockTotal,
            'stock_reserved'  => $stockReserved,
            'stock_available' => $stockTotal - $stockReserved,
            'active_batches'  => $activeBatchCount,
            'total_sold'      => $totalSold,
            'last_received'   => $lastReceived,
            'last_sold'       => $lastSold,
        ];
    }

    public function getStockByWarehouse(Product $p)
    {
        return DB::table('tbs_stock_balances as sb')
            ->join('tbm_warehouses as w', 'w.id', '=', 'sb.warehouse_id')
            ->select(
                'w.id', 'w.code', 'w.name', 'w.type',
                DB::raw('SUM(sb.quantity) AS total_qty'),
                DB::raw('SUM(sb.reserved_quantity) AS reserved_qty'),
                DB::raw('SUM(sb.available_quantity) AS available_qty')
            )
            ->where('sb.product_id', $p->id)
            ->groupBy('w.id', 'w.code', 'w.name', 'w.type')
            ->orderBy('w.code')
            ->get();
    }

    public function getActiveBatches(Product $p)
    {
        return DB::table('tbm_product_batches as b')
            ->leftJoin('tbm_suppliers as s', 's.id', '=', 'b.supplier_id')
            ->select(
                'b.id', 'b.batch_number', 'b.received_date', 'b.production_date',
                'b.expiry_date', 'b.catch_date', 'b.catch_location',
                'b.cost_price', 'b.initial_quantity', 'b.remaining_quantity',
                'b.quality_status', 's.name as supplier_name'
            )
            ->where('b.product_id', $p->id)
            ->where('b.remaining_quantity', '>', 0)
            ->orderByRaw('b.expiry_date IS NULL, b.expiry_date ASC')
            ->limit(50)
            ->get();
    }

    public function getRecentMovements(Product $p)
    {
        return DB::table('tbh_stock_movements as sm')
            ->leftJoin('tbm_warehouses as w', 'w.id', '=', 'sm.warehouse_id')
            ->leftJoin('tbm_product_batches as b', 'b.id', '=', 'sm.batch_id')
            ->leftJoin('tbm_users as u', 'u.id', '=', 'sm.created_by')
            ->select(
                'sm.id', 'sm.movement_number', 'sm.movement_type',
                'sm.quantity', 'sm.balance_after', 'sm.created_date',
                'sm.reference_type', 'sm.reference_id', 'sm.notes',
                'w.code as warehouse_code', 'w.name as warehouse_name',
                'b.batch_number',
                'u.full_name as created_by_name'
            )
            ->where('sm.product_id', $p->id)
            ->orderByDesc('sm.created_date')
            ->limit(30)
            ->get();
    }

    public function getPurchaseHistory(Product $p)
    {
        return DB::table('tbr_purchase_order_items as poi')
            ->join('tbr_purchase_orders as po', 'po.id', '=', 'poi.po_id')
            ->join('tbm_suppliers as s', 's.id', '=', 'po.supplier_id')
            ->select(
                'po.id', 'po.po_number', 'po.po_date', 'po.status',
                'poi.quantity', 'poi.received_quantity', 'poi.unit_price',
                's.name as supplier_name', 's.code as supplier_code'
            )
            ->where('poi.product_id', $p->id)
            ->orderByDesc('po.po_date')
            ->limit(20)
            ->get();
    }

    public function getSalesHistory(Product $p)
    {
        return DB::table('tbr_sales_order_items as soi')
            ->join('tbr_sales_orders as so', 'so.id', '=', 'soi.so_id')
            ->join('tbm_customers as c', 'c.id', '=', 'so.customer_id')
            ->select(
                'so.id', 'so.so_number', 'so.order_date', 'so.status',
                'soi.quantity', 'soi.delivered_quantity', 'soi.unit_price',
                'c.name as customer_name', 'c.code as customer_code'
            )
            ->where('soi.product_id', $p->id)
            ->orderByDesc('so.order_date')
            ->limit(20)
            ->get();
    }

    public function getPricesPerTier(Product $p)
    {
        return DB::table('tbm_product_prices as pp')
            ->join('tbm_price_tiers as t', 't.id', '=', 'pp.price_tier_id')
            ->select(
                'pp.id', 'pp.price', 'pp.min_quantity',
                'pp.effective_from', 'pp.effective_to',
                't.id as tier_id', 't.name as tier_name'
            )
            ->where('pp.product_id', $p->id)
            ->orderBy('t.name')
            ->orderByDesc('pp.effective_from')
            ->get();
    }
}

<?php

namespace App\Services;

use App\Models\Supplier;
use Illuminate\Support\Facades\DB;

class SupplierService
{
    /**
     * Cek apakah supplier boleh dihapus.
     * Tidak boleh kalau masih punya PO aktif (draft/submitted/partial).
     */
    public function canDelete(Supplier $supplier): array
    {
        $activePO = DB::table('tbr_purchase_orders')
            ->where('supplier_id', $supplier->id)
            ->whereIn('status', ['draft', 'submitted', 'partial'])
            ->count();

        if ($activePO > 0) {
            return [
                'allowed' => false,
                'reason'  => "Supplier masih memiliki {$activePO} Purchase Order aktif. Selesaikan/batalkan dulu PO tersebut.",
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Soft delete + return result.
     */
    public function delete(Supplier $supplier): array
    {
        $check = $this->canDelete($supplier);
        if (! $check['allowed']) {
            return ['success' => false, 'message' => $check['reason']];
        }

        $name = $supplier->name;
        $supplier->delete();

        return [
            'success' => true,
            'message' => "Supplier '{$name}' berhasil dihapus.",
        ];
    }

    /**
     * Statistik untuk halaman detail.
     */
    public function getDetailStats(Supplier $supplier): array
    {
        return [
            'total_po' => (int) DB::table('tbr_purchase_orders')
                ->where('supplier_id', $supplier->id)->count(),
            'active_po' => $supplier->getActivePOCount(),
            'total_purchase' => $supplier->getTotalPurchase(),
            'last_po_date' => DB::table('tbr_purchase_orders')
                ->where('supplier_id', $supplier->id)
                ->max('po_date'),
            'distinct_products' => DB::table('tbm_product_batches')
                ->where('supplier_id', $supplier->id)
                ->distinct('product_id')->count('product_id'),
            'active_batches' => DB::table('tbm_product_batches')
                ->where('supplier_id', $supplier->id)
                ->where('remaining_quantity', '>', 0)->count(),
        ];
    }

    /**
     * Distinct cities untuk dropdown filter.
     */
    public function distinctCities(): array
    {
        return DB::table('tbm_suppliers')
            ->whereNotNull('city')
            ->where('city', '!=', '')
            ->whereNull('deleted_date')
            ->distinct()
            ->orderBy('city')
            ->pluck('city')
            ->toArray();
    }
}

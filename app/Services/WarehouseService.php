<?php

namespace App\Services;

use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;

class WarehouseService
{
    /**
     * Cek apakah warehouse boleh dinonaktifkan.
     * Tidak boleh kalau masih ada stock balance > 0.
     */
    public function canDeactivate(Warehouse $warehouse): array
    {
        $totalStock = DB::table('tbs_stock_balances')
            ->where('warehouse_id', $warehouse->id)
            ->sum('quantity');

        if ($totalStock > 0) {
            return [
                'allowed' => false,
                'reason'  => "Gudang masih memiliki saldo stock {$totalStock} unit. Pindahkan atau habiskan dulu.",
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    /**
     * Toggle status aktif. Return array status + message.
     */
    public function toggleStatus(Warehouse $warehouse): array
    {
        // Kalau mau di-nonaktifkan, cek dulu
        if ($warehouse->is_active) {
            $check = $this->canDeactivate($warehouse);
            if (! $check['allowed']) {
                return [
                    'success' => false,
                    'message' => $check['reason'],
                ];
            }
        }

        $warehouse->update(['is_active' => ! $warehouse->is_active]);

        return [
            'success' => true,
            'message' => $warehouse->is_active
                ? "Gudang '{$warehouse->name}' diaktifkan."
                : "Gudang '{$warehouse->name}' dinonaktifkan.",
        ];
    }

    /**
     * Statistik untuk halaman detail.
     */
    public function getDetailStats(Warehouse $warehouse): array
    {
        return [
            'total_products' => DB::table('tbs_stock_balances')
                ->where('warehouse_id', $warehouse->id)
                ->where('quantity', '>', 0)
                ->distinct('product_id')
                ->count('product_id'),
            'total_qty' => (float) DB::table('tbs_stock_balances')
                ->where('warehouse_id', $warehouse->id)
                ->sum('quantity'),
            'total_users' => DB::table('tbm_user_warehouses')
                ->where('warehouse_id', $warehouse->id)
                ->count(),
            'total_movements_30d' => DB::table('tbh_stock_movements')
                ->where('warehouse_id', $warehouse->id)
                ->where('created_date', '>=', now()->subDays(30))
                ->count(),
        ];
    }
}

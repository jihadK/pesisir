<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;

class CustomerService
{
    /**
     * Tidak boleh hapus kalau:
     *  - Punya SO aktif (status: draft, confirmed, partial)
     *  - Punya outstanding invoice (issued, partial, overdue)
     */
    public function canDelete(Customer $c): array
    {
        $activeSO = DB::table('tbr_sales_orders')
            ->where('customer_id', $c->id)
            ->whereIn('status', ['draft', 'confirmed', 'partial'])
            ->count();

        if ($activeSO > 0) {
            return [
                'allowed' => false,
                'reason'  => "Customer masih memiliki {$activeSO} Sales Order aktif. Selesaikan/batalkan dulu SO tersebut.",
            ];
        }

        $outstanding = (float) DB::table('tbr_invoices')
            ->where('customer_id', $c->id)
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->sum('outstanding_amount');

        if ($outstanding > 0) {
            return [
                'allowed' => false,
                'reason'  => 'Customer masih memiliki piutang Rp ' . number_format($outstanding, 0, ',', '.') . '. Selesaikan dulu pembayaran.',
            ];
        }

        return ['allowed' => true, 'reason' => null];
    }

    public function delete(Customer $c): array
    {
        $check = $this->canDelete($c);
        if (! $check['allowed']) {
            return ['success' => false, 'message' => $check['reason']];
        }
        $name = $c->name;
        $c->delete();
        return ['success' => true, 'message' => "Customer '{$name}' berhasil dihapus."];
    }

    /**
     * Statistik untuk halaman detail.
     */
    public function getDetailStats(Customer $c): array
    {
        $today = now()->toDateString();

        // AR aging buckets
        $aging = DB::table('tbr_invoices')
            ->select(
                DB::raw("COALESCE(SUM(CASE WHEN due_date >= ? THEN outstanding_amount ELSE 0 END), 0) as not_due"),
                DB::raw("COALESCE(SUM(CASE WHEN due_date <  ? AND ?::date - due_date BETWEEN 1 AND 30 THEN outstanding_amount ELSE 0 END), 0) as d_1_30"),
                DB::raw("COALESCE(SUM(CASE WHEN ?::date - due_date BETWEEN 31 AND 60 THEN outstanding_amount ELSE 0 END), 0) as d_31_60"),
                DB::raw("COALESCE(SUM(CASE WHEN ?::date - due_date BETWEEN 61 AND 90 THEN outstanding_amount ELSE 0 END), 0) as d_61_90"),
                DB::raw("COALESCE(SUM(CASE WHEN ?::date - due_date > 90 THEN outstanding_amount ELSE 0 END), 0) as d_over_90"),
            )
            ->addBinding([$today, $today, $today, $today, $today, $today], 'select')
            ->where('customer_id', $c->id)
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->first();

        return [
            'total_so'        => (int) DB::table('tbr_sales_orders')->where('customer_id', $c->id)->count(),
            'active_so'       => (int) DB::table('tbr_sales_orders')->where('customer_id', $c->id)
                                    ->whereIn('status', ['draft','confirmed','partial'])->count(),
            'total_sales'     => (float) DB::table('tbr_sales_orders')->where('customer_id', $c->id)
                                    ->whereNotIn('status', ['draft','cancelled'])->sum('total_amount'),
            'outstanding_ar'  => $c->getOutstandingAR(),
            'credit_util'     => $c->getCreditUtilization(),
            'last_order_date' => DB::table('tbr_sales_orders')->where('customer_id', $c->id)->max('order_date'),
            'aging' => [
                'not_due'   => (float) ($aging->not_due ?? 0),
                'd_1_30'    => (float) ($aging->d_1_30 ?? 0),
                'd_31_60'   => (float) ($aging->d_31_60 ?? 0),
                'd_61_90'   => (float) ($aging->d_61_90 ?? 0),
                'd_over_90' => (float) ($aging->d_over_90 ?? 0),
            ],
        ];
    }

    public function distinctCities(): array
    {
        return DB::table('tbm_customers')
            ->whereNotNull('city')->where('city', '!=', '')
            ->whereNull('deleted_date')
            ->distinct()->orderBy('city')
            ->pluck('city')->toArray();
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $stats = [
            'products_active'   => (int) DB::table('tbm_products')->where('is_active', true)->whereNull('deleted_date')->count(),
            'warehouses_active' => (int) DB::table('tbm_warehouses')->where('is_active', true)->count(),
            'stock_low'         => (int) DB::table('v_stock_low')->count(),
            'so_today'          => (int) DB::table('tbr_sales_orders')->whereDate('order_date', today())->count(),
            'ar_outstanding'    => (float) DB::table('tbr_invoices')
                                    ->whereIn('status', ['issued', 'partial', 'overdue'])
                                    ->sum('outstanding_amount'),
        ];

        $recentLogins = DB::table('tbh_login_attempts')
            ->select('email', 'ip_address', 'success', 'failure_reason', 'attempted_at')
            ->orderByDesc('attempted_at')
            ->limit(8)
            ->get();

        return view('dashboard', compact('stats', 'recentLogins'));
    }
}

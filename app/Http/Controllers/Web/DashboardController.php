<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $period = $request->string('period')->toString() ?: 'weekly';
        if (! in_array($period, ['daily', 'weekly', 'monthly'], true)) {
            $period = 'weekly';
        }

        // ===== Period summary (HPP / Sales / Profit) untuk periode aktif =====
        $periodSummary = $this->computeSummary($this->periodStart($period), now());

        // ===== Lifetime summary (semua waktu) =====
        $lifetimeSummary = $this->computeSummary(null, null);

        // ===== Chart data: time series untuk 12 titik terakhir =====
        $chartData = $this->buildChartData($period);

        // ===== Stock di bawah minimum =====
        $stockLowCount = (int) DB::table('v_stock_low')->count();

        // ===== Unpaid orders (Draft) =====
        $unpaidOrders = SalesOrder::query()
            ->with(['customer:id,code,name'])
            ->where('status', SalesOrder::STATUS_DRAFT)
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->limit(10)
            ->get();
        $unpaidTotal = (float) SalesOrder::where('status', SalesOrder::STATUS_DRAFT)->sum('total_amount');
        $unpaidCount = (int) SalesOrder::where('status', SalesOrder::STATUS_DRAFT)->count();

        // ===== Piutang (AR) =====
        $today = \Carbon\Carbon::today();
        $arBase = SalesOrder::where('status', SalesOrder::STATUS_FULFILLED);
        $arWidgets = [
            'outstanding'   => (float) $arBase->clone()->sum('total_amount'),
            'count'         => (int)   $arBase->clone()->count(),
            'overdue'       => (float) $arBase->clone()->whereDate('due_date', '<', $today)->sum('total_amount'),
            'overdue_count' => (int)   $arBase->clone()->whereDate('due_date', '<', $today)->count(),
            'due7'          => (float) $arBase->clone()->whereBetween('due_date', [$today, $today->copy()->addDays(6)])->sum('total_amount'),
            'due7_count'    => (int)   $arBase->clone()->whereBetween('due_date', [$today, $today->copy()->addDays(6)])->count(),
        ];

        return view('dashboard', [
            'period'          => $period,
            'periodSummary'   => $periodSummary,
            'lifetimeSummary' => $lifetimeSummary,
            'chart'           => $chartData,
            'stockLowCount'   => $stockLowCount,
            'unpaidOrders'    => $unpaidOrders,
            'unpaidTotal'     => $unpaidTotal,
            'unpaidCount'     => $unpaidCount,
            'arWidgets'       => $arWidgets,
        ]);
    }

    /**
     * Compute HPP / Sales / Profit untuk semua SO Paid antara 2 tanggal.
     * $from / $to null = all time.
     */
    private function computeSummary(?Carbon $from, ?Carbon $to): array
    {
        $q = SalesOrder::query()->where('status', SalesOrder::STATUS_PAID);
        if ($from) $q->where('order_date', '>=', $from->toDateString());
        if ($to)   $q->where('order_date', '<=', $to->toDateString());
        $ids = $q->pluck('id');

        $sales = (float) SalesOrder::whereIn('id', $ids)->sum('total_amount');
        $hpp = (float) DB::table('tbr_sales_order_items as soi')
            ->join('tbm_products as p', 'p.id', '=', 'soi.product_id')
            ->whereIn('soi.so_id', $ids)
            ->sum(DB::raw('soi.quantity * COALESCE(p.default_cost_price, 0)'));
        $profit = $sales - $hpp;

        return [
            'count'      => $ids->count(),
            'sales'      => $sales,
            'hpp'        => $hpp,
            'profit'     => $profit,
            'margin_pct' => $sales > 0 ? ($profit / $sales * 100) : 0,
        ];
    }

    /**
     * Bangun data chart time-series sesuai periode.
     * Returns: ['labels'=>[...], 'sales'=>[...], 'hpp'=>[...], 'profit'=>[...]]
     */
    private function buildChartData(string $period): array
    {
        // Range & step
        [$buckets, $labelFormat, $groupExpr] = match ($period) {
            'daily'   => [$this->dailyBuckets(30), 'd M', "to_char(order_date, 'YYYY-MM-DD')"],
            'weekly'  => [$this->weeklyBuckets(12), 'd M', "to_char(date_trunc('week', order_date), 'YYYY-MM-DD')"],
            'monthly' => [$this->monthlyBuckets(12), 'M Y', "to_char(date_trunc('month', order_date), 'YYYY-MM-DD')"],
        };

        // Query agregat per bucket
        $rangeFrom = collect($buckets)->first()['key'];
        $rangeTo   = now()->endOfDay()->toDateString();

        $rows = DB::table('tbr_sales_orders as so')
            ->where('so.status', SalesOrder::STATUS_PAID)
            ->where('so.order_date', '>=', $rangeFrom)
            ->where('so.order_date', '<=', $rangeTo)
            ->select(
                DB::raw($groupExpr . ' AS bucket'),
                DB::raw('SUM(so.total_amount) AS sales')
            )
            ->groupBy(DB::raw($groupExpr))
            ->get();

        // HPP per bucket — query terpisah, agregat via JOIN ke items.
        // Group expr untuk join (kolom order_date dari so harus disebut eksplisit)
        $groupExprSO = str_replace('order_date', 'so.order_date', $groupExpr);
        $hppRows = DB::table('tbr_sales_orders as so')
            ->join('tbr_sales_order_items as soi', 'soi.so_id', '=', 'so.id')
            ->join('tbm_products as p', 'p.id', '=', 'soi.product_id')
            ->where('so.status', SalesOrder::STATUS_PAID)
            ->where('so.order_date', '>=', $rangeFrom)
            ->where('so.order_date', '<=', $rangeTo)
            ->select(
                DB::raw($groupExprSO . ' AS bucket'),
                DB::raw('SUM(soi.quantity * COALESCE(p.default_cost_price, 0)) AS hpp')
            )
            ->groupBy(DB::raw($groupExprSO))
            ->get()
            ->keyBy('bucket');

        $salesByBucket = $rows->mapWithKeys(fn($r) => [$r->bucket => (float)$r->sales]);

        $labels = []; $sales = []; $hpps = []; $profits = [];
        foreach ($buckets as $b) {
            $k = $b['key'];
            $s = (float) ($salesByBucket[$k] ?? 0);
            $h = (float) ($hppRows[$k]->hpp ?? 0);
            $labels[]  = $b['label'];
            $sales[]   = $s;
            $hpps[]    = $h;
            $profits[] = $s - $h;
        }

        return [
            'labels'  => $labels,
            'sales'   => $sales,
            'hpp'     => $hpps,
            'profit'  => $profits,
        ];
    }

    private function dailyBuckets(int $n): array
    {
        $out = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            $d = now()->subDays($i);
            $out[] = ['key' => $d->toDateString(), 'label' => $d->format('d M')];
        }
        return $out;
    }

    private function weeklyBuckets(int $n): array
    {
        $out = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            $w = now()->startOfWeek()->subWeeks($i);
            $out[] = ['key' => $w->toDateString(), 'label' => $w->format('d M')];
        }
        return $out;
    }

    private function monthlyBuckets(int $n): array
    {
        $out = [];
        for ($i = $n - 1; $i >= 0; $i--) {
            $m = now()->startOfMonth()->subMonths($i);
            $out[] = ['key' => $m->toDateString(), 'label' => $m->format('M Y')];
        }
        return $out;
    }

    private function periodStart(string $period): Carbon
    {
        return match ($period) {
            'daily'   => now()->startOfDay(),
            'weekly'  => now()->startOfWeek(),
            'monthly' => now()->startOfMonth(),
        };
    }
}

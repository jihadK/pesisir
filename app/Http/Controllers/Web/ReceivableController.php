<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\SalesOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ReceivableController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'q'      => $request->string('q')->toString(),
            'aging'  => $request->string('aging')->toString(), // current|due7|due14|overdue
        ];

        $today = Carbon::today();

        $base = SalesOrder::query()
            ->with(['customer:id,code,name,phone', 'salesUser:id,full_name'])
            ->where('status', SalesOrder::STATUS_FULFILLED);

        if ($filters['q']) {
            $base->where(function ($q) use ($filters) {
                $q->where('so_number', 'ilike', "%{$filters['q']}%")
                  ->orWhereHas('customer', fn ($c) => $c->where('name','ilike',"%{$filters['q']}%")->orWhere('code','ilike',"%{$filters['q']}%"));
            });
        }

        // Aging filter
        if ($filters['aging'] === 'current') {
            $base->whereDate('due_date', '>=', $today)->whereDate('due_date', '<=', $today->copy()->addDays(6));
        } elseif ($filters['aging'] === 'due7') {
            $base->whereDate('due_date', '>', $today->copy()->addDays(6))->whereDate('due_date', '<=', $today->copy()->addDays(14));
        } elseif ($filters['aging'] === 'due14') {
            $base->whereDate('due_date', '>', $today->copy()->addDays(14))->whereDate('due_date', '<=', $today->copy()->addDays(30));
        } elseif ($filters['aging'] === 'overdue') {
            $base->whereDate('due_date', '<', $today);
        }

        $orders = $base->orderBy('due_date')->paginate(50)->withQueryString();

        // Aging buckets summary (semua, tanpa filter aging)
        $bucketBase = SalesOrder::where('status', SalesOrder::STATUS_FULFILLED);
        $summary = [
            'total_outstanding' => (float) $bucketBase->clone()->sum('total_amount'),
            'count'             => (int) $bucketBase->clone()->count(),
            'overdue'           => (float) $bucketBase->clone()->whereDate('due_date', '<', $today)->sum('total_amount'),
            'overdue_count'     => (int)   $bucketBase->clone()->whereDate('due_date', '<', $today)->count(),
            'due7'              => (float) $bucketBase->clone()->whereBetween('due_date', [$today, $today->copy()->addDays(6)])->sum('total_amount'),
            'due14'             => (float) $bucketBase->clone()->whereBetween('due_date', [$today->copy()->addDays(7), $today->copy()->addDays(14)])->sum('total_amount'),
            'due30'             => (float) $bucketBase->clone()->whereBetween('due_date', [$today->copy()->addDays(15), $today->copy()->addDays(30)])->sum('total_amount'),
        ];

        return view('receivables.index', [
            'orders'  => $orders,
            'filters' => $filters,
            'summary' => $summary,
            'today'   => $today,
        ]);
    }
}

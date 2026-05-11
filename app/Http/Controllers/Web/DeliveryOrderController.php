<?php

namespace App\Http\Controllers\Web;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryOrder\StoreDeliveryOrderRequest;
use App\Models\DeliveryOrder;
use App\Models\SalesOrder;
use App\Services\DeliveryOrderService;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DeliveryOrderController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly DeliveryOrderService $service) {}

    public function index(Request $request): View
    {
        $filters = [
            'q'         => $request->string('q')->toString(),
            'status'    => $request->string('status')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to'   => $request->string('date_to')->toString(),
        ];

        $orders = DeliveryOrder::query()
            ->with(['customer:id,code,name', 'warehouse:id,code,name', 'salesOrder:id,so_number', 'createdBy:id,full_name'])
            ->search($filters['q'] ?: null)
            ->ofStatus($filters['status'] ?: null)
            ->betweenDates($filters['date_from'] ?: null, $filters['date_to'] ?: null)
            ->orderByDesc('delivery_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('delivery_orders.index', [
            'orders'   => $orders,
            'filters'  => $filters,
            'statuses' => DeliveryOrder::statusLabels(),
        ]);
    }

    public function create(Request $request): View
    {
        // List SO confirmed/partial yang masih punya outstanding qty
        $eligibleSO = SalesOrder::query()
            ->with(['customer:id,code,name', 'warehouse:id,code,name'])
            ->whereIn('status', [SalesOrder::STATUS_CONFIRMED, SalesOrder::STATUS_PARTIAL])
            ->orderByDesc('order_date')
            ->get();

        $preselectedSO = null;
        if ($soId = $request->integer('so_id')) {
            $preselectedSO = SalesOrder::with(['items.product:id,sku,name', 'items.uom:id,code', 'customer', 'warehouse'])
                ->find($soId);
        }

        return view('delivery_orders.create', [
            'eligibleSO'    => $eligibleSO,
            'preselectedSO' => $preselectedSO,
        ]);
    }

    public function store(StoreDeliveryOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        try {
            $do = $this->service->createFromSO($data);
        } catch (\Throwable $e) {
            return back()->withInput()
                ->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Menyimpan'));
        }

        return redirect()
            ->route('delivery_orders.show', $do)
            ->with('flash', Flash::ok("DO {$do->do_number} berhasil dibuat (draft).", 'Berhasil Disimpan'));
    }

    public function show(DeliveryOrder $deliveryOrder): View
    {
        $deliveryOrder->load([
            'items.product:id,sku,name', 'items.uom:id,code', 'items.batch:id,batch_number,expiry_date',
            'customer', 'warehouse', 'salesOrder:id,so_number,status', 'createdBy:id,full_name',
        ]);

        return view('delivery_orders.show', ['do' => $deliveryOrder]);
    }

    public function ship(Request $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        if (! $request->user()?->hasPermission('delivery_order.ship')) {
            return back()->with('flash', Flash::err('Tidak punya akses ship DO.', ResponseCode::FORBIDDEN));
        }

        try {
            $this->service->ship(
                $deliveryOrder,
                $request->user()->id,
                $request->string('received_by_name')->toString() ?: null
            );
        } catch (\Throwable $e) {
            return back()->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Ship'));
        }

        return back()->with('flash', Flash::ok(
            "DO {$deliveryOrder->do_number} berhasil di-ship. Stock sudah dikurangi.",
            'Shipped'
        ));
    }

    public function cancel(Request $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        if (! $request->user()?->hasPermission('delivery_order.cancel')) {
            return back()->with('flash', Flash::err('Tidak punya akses cancel DO.', ResponseCode::FORBIDDEN));
        }

        try {
            $this->service->cancel($deliveryOrder);
        } catch (\Throwable $e) {
            return back()->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Cancel'));
        }

        return back()->with('flash', Flash::ok("DO {$deliveryOrder->do_number} dibatalkan.", 'Cancelled'));
    }

    public function print(DeliveryOrder $deliveryOrder): View
    {
        $deliveryOrder->load([
            'items.product', 'items.uom:id,code', 'items.batch:id,batch_number',
            'customer', 'warehouse', 'salesOrder:id,so_number',
        ]);
        return view('delivery_orders.print', ['do' => $deliveryOrder]);
    }

    /**
     * AJAX: ambil items SO + outstanding qty + batch options per item.
     * Dipakai saat pilih SO di form create DO.
     */
    public function soItems(Request $request)
    {
        $soId = $request->integer('so_id');
        if (! $soId) return $this->ok(['items' => []]);

        $so = SalesOrder::with(['items.product:id,sku,name,is_perishable', 'items.uom:id,code'])
            ->find($soId);
        if (! $so) return $this->ok(['items' => []]);

        $warehouseId = $so->warehouse_id;
        $items = $so->items->map(function ($it) use ($warehouseId) {
            $outstanding = (float) $it->quantity - (float) $it->delivered_quantity;

            // Batches available untuk produk perishable
            $batches = [];
            if ($it->product->is_perishable) {
                $batches = DB::table('tbs_stock_balances as sb')
                    ->leftJoin('tbm_product_batches as b', 'b.id', '=', 'sb.batch_id')
                    ->where('sb.product_id', $it->product_id)
                    ->where('sb.warehouse_id', $warehouseId)
                    ->where('sb.quantity', '>', 0)
                    ->orderByRaw('b.expiry_date IS NULL, b.expiry_date ASC')
                    ->select(
                        'sb.batch_id',
                        'b.batch_number',
                        DB::raw("TO_CHAR(b.expiry_date, 'DD Mon YYYY') as expiry_date"),
                        DB::raw('(sb.quantity - sb.reserved_quantity) as available')
                    )
                    ->get()
                    ->map(fn($b) => [
                        'batch_id'     => $b->batch_id,
                        'batch_number' => $b->batch_number,
                        'expiry_date'  => $b->expiry_date,
                        'available'    => (float) $b->available,
                    ])
                    ->toArray();
            }

            return [
                'so_item_id'    => $it->id,
                'product_id'    => $it->product_id,
                'sku'           => $it->product->sku,
                'name'          => $it->product->name,
                'is_perishable' => (bool) $it->product->is_perishable,
                'uom_code'      => $it->uom->code,
                'qty_total'     => (float) $it->quantity,
                'qty_delivered' => (float) $it->delivered_quantity,
                'outstanding'   => $outstanding,
                'unit_price'    => (float) $it->unit_price,
                'batches'       => $batches,
            ];
        })->filter(fn ($x) => $x['outstanding'] > 0)->values();

        return $this->ok([
            'so' => [
                'id'          => $so->id,
                'so_number'   => $so->so_number,
                'customer'    => $so->customer?->name,
                'warehouse'   => $so->warehouse?->name,
                'status'      => $so->status,
            ],
            'items' => $items,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\PurchaseOrder\StorePurchaseOrderRequest;
use App\Http\Requests\PurchaseOrder\UpdatePurchaseOrderRequest;
use App\Models\Category;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use App\Services\PurchaseOrderService;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PurchaseOrderController extends Controller
{
    public function __construct(private readonly PurchaseOrderService $service) {}

    public function index(Request $request): View
    {
        $filters = [
            'q'         => $request->string('q')->toString(),
            'status'    => $request->string('status')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to'   => $request->string('date_to')->toString(),
        ];

        $orders = PurchaseOrder::query()
            ->with(['supplier:id,code,name', 'warehouse:id,code,name', 'createdBy:id,full_name'])
            ->search($filters['q'] ?: null)
            ->ofStatus($filters['status'] ?: null)
            ->betweenDates($filters['date_from'] ?: null, $filters['date_to'] ?: null)
            ->orderByDesc('po_date')->orderByDesc('id')
            ->paginate(50)->withQueryString();

        return view('purchase_orders.index', [
            'orders'   => $orders,
            'filters'  => $filters,
            'statuses' => PurchaseOrder::statusLabels(),
        ]);
    }

    public function create(): View
    {
        return view('purchase_orders.create', [
            'po'         => new PurchaseOrder(['po_date' => now()->toDateString()]),
            'suppliers'  => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('code')->get(),
            'categories' => Category::whereNotNull('parent_id')->with('parent:id,name')->orderBy('name')->get(['id', 'name', 'parent_id']),
        ]);
    }

    public function store(StorePurchaseOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        try {
            $po = $this->service->createDraft($data);
        } catch (\Throwable $e) {
            return back()->withInput()
                ->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Menyimpan'));
        }

        return redirect()->route('purchase_orders.show', $po)
            ->with('flash', Flash::ok("PO {$po->po_number} berhasil dibuat (status draft).", 'Berhasil Disimpan'));
    }

    public function show(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load([
            'items.category:id,name,parent_id',
            'items.category.parent:id,name',
            'supplier', 'warehouse', 'createdBy:id,full_name', 'approvedBy:id,full_name',
        ]);
        return view('purchase_orders.show', ['po' => $purchaseOrder]);
    }

    public function edit(PurchaseOrder $purchaseOrder): View
    {
        if (! $purchaseOrder->isEditable()) {
            return redirect()->route('purchase_orders.show', $purchaseOrder)
                ->with('flash', Flash::err('PO yang sudah submitted tidak bisa diedit.', ResponseCode::BUSINESS_RULE_FAILED));
        }

        $purchaseOrder->load(['items.category']);

        return view('purchase_orders.edit', [
            'po'         => $purchaseOrder,
            'suppliers'  => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'warehouses' => Warehouse::where('is_active', true)->orderBy('code')->get(),
            'categories' => Category::whereNotNull('parent_id')->with('parent:id,name')->orderBy('name')->get(['id', 'name', 'parent_id']),
        ]);
    }

    public function update(UpdatePurchaseOrderRequest $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        try {
            $this->service->updateDraft($purchaseOrder, $request->validated());
        } catch (\Throwable $e) {
            return back()->withInput()
                ->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Update'));
        }
        return redirect()->route('purchase_orders.show', $purchaseOrder)
            ->with('flash', Flash::ok("PO {$purchaseOrder->po_number} berhasil diperbarui.", 'Berhasil'));
    }

    public function submit(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! $request->user()?->hasPermission('purchase_order.submit')) {
            return back()->with('flash', Flash::err('Tidak punya akses submit PO.', ResponseCode::FORBIDDEN));
        }
        try {
            $this->service->submit($purchaseOrder, $request->user()->id);
        } catch (\Throwable $e) {
            return back()->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Submit'));
        }
        return back()->with('flash', Flash::ok("PO {$purchaseOrder->po_number} disubmit.", 'Submitted'));
    }

    public function cancel(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! $request->user()?->hasPermission('purchase_order.cancel')) {
            return back()->with('flash', Flash::err('Tidak punya akses cancel PO.', ResponseCode::FORBIDDEN));
        }
        try {
            $this->service->cancel($purchaseOrder);
        } catch (\Throwable $e) {
            return back()->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Cancel'));
        }
        return back()->with('flash', Flash::ok("PO {$purchaseOrder->po_number} dibatalkan.", 'Cancelled'));
    }

    public function markPaid(Request $request, PurchaseOrder $purchaseOrder): RedirectResponse
    {
        if (! $request->user()?->hasPermission('purchase_order.mark_paid')) {
            return back()->with('flash', Flash::err('Tidak punya akses tandai terbayar.', ResponseCode::FORBIDDEN));
        }
        try {
            $this->service->markAsPaid($purchaseOrder);
        } catch (\Throwable $e) {
            return back()->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Update'));
        }
        return back()->with('flash', Flash::ok("PO {$purchaseOrder->po_number} ditandai Terbayar.", 'Paid'));
    }

    public function print(PurchaseOrder $purchaseOrder): View
    {
        $purchaseOrder->load([
            'items.category:id,name,parent_id',
            'items.category.parent:id,name',
            'supplier', 'warehouse', 'createdBy:id,full_name', 'approvedBy:id,full_name',
        ]);
        return view('purchase_orders.print', ['po' => $purchaseOrder]);
    }
}

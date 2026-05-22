<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\SalesOrder\StoreSalesOrderRequest;
use App\Http\Requests\SalesOrder\UpdateSalesOrderRequest;
use App\Http\Concerns\ApiResponse;
use App\Models\Customer;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Services\SalesOrderService;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SalesOrderController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SalesOrderService $service) {}

    public function index(Request $request): View
    {
        $filters = [
            'q'         => $request->string('q')->toString(),
            'status'    => $request->string('status')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to'   => $request->string('date_to')->toString(),
        ];

        $orders = SalesOrder::query()
            ->with(['customer:id,code,name', 'warehouse:id,code,name', 'salesUser:id,full_name'])
            ->search($filters['q'] ?: null)
            ->ofStatus($filters['status'] ?: null)
            ->betweenDates($filters['date_from'] ?: null, $filters['date_to'] ?: null)
            ->orderByDesc('order_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        // Summary HPP / Penjualan / Laba — hanya untuk view "Daftar Invoice" (status=paid).
        // Hitung berdasarkan SEMUA SO yang match filter (bukan halaman saja).
        $summary = null;
        if (($filters['status'] ?? '') === 'paid') {
            $matchingIds = SalesOrder::query()
                ->search($filters['q'] ?: null)
                ->ofStatus($filters['status'])
                ->betweenDates($filters['date_from'] ?: null, $filters['date_to'] ?: null)
                ->pluck('id');

            $totalSales = (float) SalesOrder::whereIn('id', $matchingIds)->sum('total_amount');
            $totalHpp = (float) DB::table('tbr_sales_order_items as soi')
                ->join('tbm_products as p', 'p.id', '=', 'soi.product_id')
                ->whereIn('soi.so_id', $matchingIds)
                ->sum(DB::raw('soi.quantity * COALESCE(p.default_cost_price, 0)'));
            $laba = $totalSales - $totalHpp;

            $summary = [
                'count'       => $matchingIds->count(),
                'total_sales' => $totalSales,
                'total_hpp'   => $totalHpp,
                'laba'        => $laba,
                'margin_pct'  => $totalSales > 0 ? ($laba / $totalSales * 100) : 0,
            ];
        }

        return view('sales_orders.index', [
            'orders'   => $orders,
            'filters'  => $filters,
            'statuses' => SalesOrder::statusLabels(),
            'summary'  => $summary,
        ]);
    }

    public function create(): View
    {
        return view('sales_orders.create', [
            'so'             => new SalesOrder(['order_date' => now()->toDateString()]),
            'customers'      => Customer::orderBy('name')->get(['id', 'code', 'name', 'price_tier_id', 'payment_terms_days']),
            'warehouses'     => Warehouse::where('is_active', true)->orderBy('code')->get(),
            'products'       => Product::active()->with('baseUom:id,code')->orderBy('sku')->get(),
            'paymentMethods' => PaymentMethod::active()->ordered()->get(),
        ]);
    }

    public function store(StoreSalesOrderRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        try {
            $so = $this->service->createDraft($data);
        } catch (\Throwable $e) {
            return back()->withInput()
                ->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Menyimpan'));
        }

        return redirect()
            ->route('sales_orders.show', $so)
            ->with('flash', Flash::ok("Sales Order {$so->so_number} berhasil dibuat (status draft).", 'Berhasil Disimpan'));
    }

    public function show(SalesOrder $salesOrder): View
    {
        $salesOrder->load([
            'items.product:id,sku,name,pack_content_type,pack_content_min,pack_content_max,pack_weight_min_g,pack_weight_max_g',
            'items.uom:id,code',
            'customer', 'warehouse', 'salesUser', 'createdBy', 'paymentMethod',
        ]);

        return view('sales_orders.show', [
            'so'              => $salesOrder,
            'paymentMethods'  => PaymentMethod::active()->ordered()->get(),
            'products'        => Product::active()->with('baseUom:id,code')->orderBy('sku')->get(),
        ]);
    }

    public function edit(SalesOrder $salesOrder): View
    {
        if (! $salesOrder->isEditable()) {
            return redirect()->route('sales_orders.show', $salesOrder)
                ->with('flash', Flash::err('SO yang sudah confirmed tidak bisa diedit.', ResponseCode::BUSINESS_RULE_FAILED));
        }

        $salesOrder->load(['items.product:id,sku,name,pack_content_type,pack_content_min,pack_content_max,pack_weight_min_g,pack_weight_max_g']);

        return view('sales_orders.edit', [
            'so'             => $salesOrder,
            'customers'      => Customer::orderBy('name')->get(['id', 'code', 'name', 'price_tier_id', 'payment_terms_days']),
            'warehouses'     => Warehouse::where('is_active', true)->orderBy('code')->get(),
            'products'       => Product::active()->with('baseUom:id,code')->orderBy('sku')->get(),
            'paymentMethods' => PaymentMethod::active()->ordered()->get(),
        ]);
    }

    /**
     * AJAX: Update payment method untuk SO yang sudah ada (admin/sales bisa ganti
     * metode setelah customer minta perubahan, terlepas dari status SO).
     */
    public function updatePaymentMethod(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method_id' => ['nullable', 'integer', \Illuminate\Validation\Rule::exists('tbm_payment_methods', 'id')],
        ]);
        $salesOrder->update(['payment_method_id' => $validated['payment_method_id'] ?? null]);
        return back()->with('flash', Flash::ok('Metode pembayaran SO berhasil diperbarui.', 'Berhasil'));
    }

    /**
     * AJAX: Available stock per product untuk warehouse tertentu.
     * Return: [{ product_id, available }] — qty tersedia (quantity - reserved_quantity, agregat semua batch).
     */
    public function availableStock(Request $request)
    {
        $warehouseId = $request->integer('warehouse_id');
        if (! $warehouseId) {
            return $this->ok(['stocks' => []]);
        }

        $stocks = DB::table('tbs_stock_balances')
            ->select(
                'product_id',
                DB::raw('SUM(quantity - reserved_quantity) AS available')
            )
            ->where('warehouse_id', $warehouseId)
            ->groupBy('product_id')
            ->havingRaw('SUM(quantity - reserved_quantity) > 0')
            ->get();

        return $this->ok([
            'stocks' => $stocks->map(fn($s) => [
                'product_id' => (int) $s->product_id,
                'available'  => (float) $s->available,
            ]),
        ]);
    }

    public function update(UpdateSalesOrderRequest $request, SalesOrder $salesOrder): RedirectResponse
    {
        try {
            $this->service->updateDraft($salesOrder, $request->validated());
        } catch (\Throwable $e) {
            return back()->withInput()
                ->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Update'));
        }

        return redirect()
            ->route('sales_orders.show', $salesOrder)
            ->with('flash', Flash::ok("Sales Order {$salesOrder->so_number} berhasil diperbarui.", 'Berhasil Diperbarui'));
    }

    public function confirm(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        if (! $request->user()?->hasPermission('sales_order.confirm')) {
            return back()->with('flash', Flash::err('Tidak punya akses confirm.', ResponseCode::FORBIDDEN));
        }

        try {
            $this->service->confirm($salesOrder, $request->user()->id);
        } catch (\Throwable $e) {
            return back()->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Confirm'));
        }

        return back()->with('flash', Flash::ok(
            "SO {$salesOrder->so_number} dikonfirmasi & stock telah di-reserve.",
            'Confirmed'
        ));
    }

    public function cancel(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        if (! $request->user()?->hasPermission('sales_order.cancel')) {
            return back()->with('flash', Flash::err('Tidak punya akses cancel.', ResponseCode::FORBIDDEN));
        }

        try {
            $this->service->cancel($salesOrder);
        } catch (\Throwable $e) {
            return back()->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Cancel'));
        }

        return back()->with('flash', Flash::ok(
            "SO {$salesOrder->so_number} berhasil dibatalkan.",
            'Cancelled'
        ));
    }

    public function appendItem(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        if (! $request->user()?->hasPermission('sales_order.update')) {
            return back()->with('flash', Flash::err('Tidak punya akses tambah item.', ResponseCode::FORBIDDEN));
        }

        $data = $request->validate([
            'product_id'   => ['required', 'integer', \Illuminate\Validation\Rule::exists('tbm_products', 'id')->whereNull('deleted_date')],
            'quantity'     => ['required', 'numeric', 'min:0.001'],
            'unit_price'   => ['required', 'string'],
            'discount_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes'        => ['nullable', 'string', 'max:255'],
        ]);
        // clean rupiah (id-ID format)
        $data['unit_price'] = (float) preg_replace('/[^0-9]/', '', $data['unit_price']);

        try {
            $this->service->appendItemToConfirmed($salesOrder, $data);
        } catch (\Throwable $e) {
            return back()->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Tambah Item'));
        }

        return back()->with('flash', Flash::ok('Item berhasil ditambahkan ke order.', 'Item Ditambahkan'));
    }

    public function markPaid(Request $request, SalesOrder $salesOrder): RedirectResponse
    {
        if (! $request->user()?->hasPermission('sales_order.mark_paid')) {
            return back()->with('flash', Flash::err('Tidak punya akses Mark Paid.', ResponseCode::FORBIDDEN));
        }

        try {
            $this->service->markAsPaid($salesOrder);
        } catch (\Throwable $e) {
            return back()->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Mark Paid'));
        }

        return back()->with('flash', Flash::ok(
            "Order {$salesOrder->so_number} ditandai sudah dibayar.",
            'Paid'
        ));
    }

    public function publicPrint(SalesOrder $salesOrder): View
    {
        return $this->print($salesOrder);
    }

    public function print(SalesOrder $salesOrder): View
    {
        $salesOrder->load([
            'items.product', 'items.uom:id,code',
            'customer', 'warehouse', 'salesUser', 'createdBy', 'paymentMethod',
        ]);

        // Selalu tampilkan semua metode Bank Transfer yang aktif sebagai pilihan.
        // Kalau SO punya pilihan customer (selain transfer), tambahkan ke list dan
        // tandai di view (highlight). Pilihan transfer customer otomatis tergabung
        // karena sudah di-list semua transfer.
        $transfers = PaymentMethod::active()
            ->where('type', PaymentMethod::TYPE_TRANSFER)
            ->ordered()
            ->get();

        $paymentMethods = $transfers;
        if ($salesOrder->paymentMethod && $salesOrder->paymentMethod->type !== PaymentMethod::TYPE_TRANSFER) {
            // Sisipkan pilihan customer di paling atas
            $paymentMethods = collect([$salesOrder->paymentMethod])->merge($transfers);
        }

        return view('sales_orders.print', [
            'so'             => $salesOrder,
            'paymentMethods' => $paymentMethods,
        ]);
    }
}

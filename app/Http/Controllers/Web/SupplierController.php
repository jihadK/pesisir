<?php

namespace App\Http\Controllers\Web;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Models\Supplier;
use App\Services\SupplierService;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class SupplierController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly SupplierService $service) {}

    public function index(Request $request): View
    {
        $filters = [
            'q'      => $request->string('q')->toString(),
            'city'   => $request->string('city')->toString(),
            'status' => $request->string('status')->toString(), // active|inactive|''
            'trash'  => $request->boolean('trash'),             // tampilkan soft-deleted
        ];

        $query = Supplier::query()
            ->search($filters['q'] ?: null)
            ->ofCity($filters['city'] ?: null)
            ->when($filters['status'] === 'active',   fn ($q) => $q->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($filters['trash'], fn ($q) => $q->onlyTrashed())
            ->orderBy('code');

        $suppliers = $query->get();

        return view('suppliers.index', [
            'suppliers' => $suppliers,
            'filters'   => $filters,
            'cities'    => $this->service->distinctCities(),
        ]);
    }

    public function create(): View
    {
        return view('suppliers.create', [
            'supplier' => new Supplier([
                'is_active'          => true,
                'payment_terms_days' => 30,
            ]),
        ]);
    }

    public function store(StoreSupplierRequest $request): RedirectResponse
    {
        $supplier = Supplier::create($request->validated());

        return redirect()
            ->route('suppliers.index')
            ->with('flash', Flash::ok("Supplier '{$supplier->name}' berhasil ditambahkan.", 'Berhasil Disimpan'));
    }

    public function show(Supplier $supplier): View
    {
        $stats = $this->service->getDetailStats($supplier);

        $recentPOs = DB::table('tbr_purchase_orders')
            ->select('id', 'po_number', 'po_date', 'status', 'total_amount')
            ->where('supplier_id', $supplier->id)
            ->orderByDesc('po_date')
            ->limit(10)
            ->get();

        $products = DB::table('tbm_product_batches as b')
            ->join('tbm_products as p', 'p.id', '=', 'b.product_id')
            ->select('p.id', 'p.sku', 'p.name', DB::raw('COUNT(b.id) as batch_count'), DB::raw('SUM(b.remaining_quantity) as total_qty'))
            ->where('b.supplier_id', $supplier->id)
            ->groupBy('p.id', 'p.sku', 'p.name')
            ->orderBy('p.name')
            ->limit(10)
            ->get();

        return view('suppliers.show', compact('supplier', 'stats', 'recentPOs', 'products'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier): RedirectResponse
    {
        $supplier->update($request->validated());

        return redirect()
            ->route('suppliers.index')
            ->with('flash', Flash::ok("Supplier '{$supplier->name}' berhasil diperbarui.", 'Berhasil Diperbarui'));
    }

    /**
     * Soft delete dengan business rule check.
     */
    public function destroy(Request $request, Supplier $supplier): mixed
    {
        if (! $request->user()?->hasPermission('suppliers.delete')) {
            return $request->expectsJson()
                ? $this->failForbidden()
                : back()->with('flash', Flash::err('Anda tidak punya akses menghapus supplier.', ResponseCode::FORBIDDEN));
        }

        $result = $this->service->delete($supplier);

        if ($request->expectsJson()) {
            return $result['success']
                ? $this->ok(null, $result['message'])
                : $this->failBusinessRule($result['message']);
        }

        return back()->with(
            'flash',
            $result['success']
                ? Flash::ok($result['message'], 'Berhasil Dihapus')
                : Flash::err($result['message'], ResponseCode::BUSINESS_RULE_FAILED, 'Tidak Bisa Menghapus')
        );
    }

    /**
     * Restore soft-deleted supplier.
     */
    public function restore(Request $request, int $id): mixed
    {
        if (! $request->user()?->hasPermission('suppliers.delete')) {
            return $request->expectsJson()
                ? $this->failForbidden()
                : back()->with('flash', Flash::err('Anda tidak punya akses.', ResponseCode::FORBIDDEN));
        }

        $supplier = Supplier::onlyTrashed()->findOrFail($id);
        $supplier->restore();

        $msg = "Supplier '{$supplier->name}' berhasil dipulihkan.";

        return $request->expectsJson()
            ? $this->ok(null, $msg)
            : redirect()->route('suppliers.index')->with('flash', Flash::ok($msg, 'Dipulihkan'));
    }
}

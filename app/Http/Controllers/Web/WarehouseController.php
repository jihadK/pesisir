<?php

namespace App\Http\Controllers\Web;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class WarehouseController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly WarehouseService $service) {}

    public function index(Request $request): View
    {
        $filters = [
            'q'      => $request->string('q')->toString(),
            'type'   => $request->string('type')->toString(),
            'status' => $request->string('status')->toString(),
        ];

        $warehouses = Warehouse::query()
            ->with('picUser:id,full_name,username')
            ->search($filters['q'] ?: null)
            ->when($filters['type'], fn ($q, $t) => $q->ofType($t))
            ->when($filters['status'] === 'active',   fn ($q) => $q->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn ($q) => $q->where('is_active', false))
            ->orderBy('code')
            ->get();

        return view('warehouses.index', [
            'warehouses' => $warehouses,
            'filters'    => $filters,
            'types'      => Warehouse::TYPES,
        ]);
    }

    public function create(): View
    {
        return view('warehouses.create', [
            'warehouse' => new Warehouse(['is_active' => true, 'type' => 'cold_storage']),
            'types'     => Warehouse::TYPES,
            'picUsers'  => $this->picOptions(),
        ]);
    }

    public function store(StoreWarehouseRequest $request): RedirectResponse
    {
        $warehouse = Warehouse::create($request->validated());

        return redirect()
            ->route('warehouses.index')
            ->with('flash', Flash::ok("Gudang '{$warehouse->name}' berhasil ditambahkan.", 'Berhasil Disimpan'));
    }

    public function show(Warehouse $warehouse): View
    {
        $warehouse->load('picUser', 'users');

        $stats = $this->service->getDetailStats($warehouse);

        $recentMovements = DB::table('tbh_stock_movements as sm')
            ->leftJoin('tbm_products as p', 'p.id', '=', 'sm.product_id')
            ->select('sm.movement_number', 'sm.movement_type', 'sm.quantity', 'sm.created_date', 'p.name as product_name', 'p.sku')
            ->where('sm.warehouse_id', $warehouse->id)
            ->orderByDesc('sm.created_date')
            ->limit(10)
            ->get();

        return view('warehouses.show', compact('warehouse', 'stats', 'recentMovements'));
    }

    public function edit(Warehouse $warehouse): View
    {
        return view('warehouses.edit', [
            'warehouse' => $warehouse,
            'types'     => Warehouse::TYPES,
            'picUsers'  => $this->picOptions($warehouse->pic_user_id),
        ]);
    }

    public function update(UpdateWarehouseRequest $request, Warehouse $warehouse): RedirectResponse
    {
        $warehouse->update($request->validated());

        return redirect()
            ->route('warehouses.index')
            ->with('flash', Flash::ok("Gudang '{$warehouse->name}' berhasil diperbarui.", 'Berhasil Diperbarui'));
    }

    /**
     * Toggle aktif/non-aktif. Return JSON kalau request expects JSON,
     * kalau tidak redirect dengan flash.
     */
    public function toggle(Request $request, Warehouse $warehouse): mixed
    {
        $result = $this->service->toggleStatus($warehouse);

        // AJAX/JSON path
        if ($request->expectsJson()) {
            return $result['success']
                ? $this->ok(['warehouse' => $warehouse->only(['id','code','name','is_active'])], $result['message'])
                : $this->failBusinessRule($result['message']);
        }

        // Web redirect path
        return back()->with(
            'flash',
            $result['success']
                ? Flash::ok($result['message'], 'Status Diubah')
                : Flash::err($result['message'], ResponseCode::BUSINESS_RULE_FAILED, 'Tidak Bisa Mengubah Status')
        );
    }

    private function picOptions(?int $currentPicId = null)
    {
        return User::query()
            ->where(function ($q) use ($currentPicId) {
                $q->where('is_active', true)->whereNull('deleted_date');
                if ($currentPicId) $q->orWhere('id', $currentPicId);
            })
            ->orderBy('full_name')
            ->get(['id', 'username', 'full_name', 'email']);
    }
}

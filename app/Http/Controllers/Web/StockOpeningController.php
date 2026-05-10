<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\StockOpening\StoreStockOpeningRequest;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockOpeningService;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class StockOpeningController extends Controller
{
    public function __construct(private readonly StockOpeningService $service) {}

    public function index(Request $request): View
    {
        $filters = [
            'product_id'   => $request->string('product_id')->toString(),
            'warehouse_id' => $request->string('warehouse_id')->toString(),
            'date_from'    => $request->string('date_from')->toString(),
            'date_to'      => $request->string('date_to')->toString(),
        ];

        $movements = $this->service->listHistory($filters);

        return view('stock_openings.index', [
            'movements'  => $movements,
            'filters'    => $filters,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('code')->get(),
            'products'   => Product::active()->orderBy('sku')->get(['id', 'sku', 'name']),
        ]);
    }

    public function create(Request $request): View
    {
        return view('stock_openings.create', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('code')->get(),
            'products'   => Product::active()
                ->with(['baseUom:id,code', 'category:id,name', 'grade:id,code'])
                ->orderBy('sku')
                ->get(),
        ]);
    }

    public function store(StoreStockOpeningRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        try {
            $result = $this->service->applyOpening($data);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Menyimpan'));
        }

        $count = $result['count'];
        return redirect()
            ->route('stock_openings.index')
            ->with('flash', Flash::ok(
                "Stock opening berhasil dibuat untuk {$count} produk.",
                'Berhasil Disimpan'
            ));
    }

    public function show(StockMovement $stockOpening): View
    {
        $stockOpening->load(['product', 'warehouse', 'batch', 'uom', 'createdBy:id,full_name']);

        if ($stockOpening->reference_type !== StockMovement::REF_OPENING) {
            abort(404);
        }

        return view('stock_openings.show', ['movement' => $stockOpening]);
    }
}

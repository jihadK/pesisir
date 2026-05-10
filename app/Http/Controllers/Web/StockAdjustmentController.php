<?php

namespace App\Http\Controllers\Web;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\StockAdjustment\StoreStockAdjustmentRequest;
use App\Models\Product;
use App\Models\ProductBatch;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockAdjustmentService;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockAdjustmentController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly StockAdjustmentService $service) {}

    public function index(Request $request): View
    {
        $filters = [
            'product_id'   => $request->string('product_id')->toString(),
            'warehouse_id' => $request->string('warehouse_id')->toString(),
            'date_from'    => $request->string('date_from')->toString(),
            'date_to'      => $request->string('date_to')->toString(),
        ];

        $movements = $this->service->listHistory($filters);

        return view('stock_adjustments.index', [
            'movements'  => $movements,
            'filters'    => $filters,
            'warehouses' => Warehouse::where('is_active', true)->orderBy('code')->get(),
            'products'   => Product::active()->orderBy('sku')->get(['id', 'sku', 'name']),
            'reasons'    => StockAdjustmentService::REASONS,
        ]);
    }

    public function create(Request $request): View
    {
        return view('stock_adjustments.create', [
            'warehouses' => Warehouse::where('is_active', true)->orderBy('code')->get(),
            'products'   => Product::active()->with('baseUom:id,code')->orderBy('sku')->get(),
            'reasons'    => StockAdjustmentService::REASONS,
        ]);
    }

    public function store(StoreStockAdjustmentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        try {
            $movement = $this->service->applyAdjustment($data);
        } catch (\Throwable $e) {
            return back()
                ->withInput()
                ->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Menyimpan'));
        }

        return redirect()
            ->route('stock_adjustments.show', $movement)
            ->with('flash', Flash::ok(
                "Stock adjustment {$movement->movement_number} berhasil dibuat.",
                'Berhasil Disimpan'
            ));
    }

    public function show(StockMovement $stockAdjustment): View
    {
        $stockAdjustment->load(['product', 'warehouse', 'batch', 'uom', 'createdBy:id,full_name']);
        if ($stockAdjustment->reference_type !== StockMovement::REF_ADJUSTMENT) {
            abort(404);
        }
        return view('stock_adjustments.show', [
            'movement' => $stockAdjustment,
            'reasons'  => StockAdjustmentService::REASONS,
        ]);
    }

    /**
     * AJAX: ambil batch tersedia + saldo per batch untuk produk+warehouse.
     */
    public function batches(Request $request)
    {
        $productId   = $request->integer('product_id');
        $warehouseId = $request->integer('warehouse_id');

        if (! $productId || ! $warehouseId) {
            return $this->ok(['batches' => [], 'total' => 0]);
        }

        $rows = DB::table('tbs_stock_balances as sb')
            ->leftJoin('tbm_product_batches as b', 'b.id', '=', 'sb.batch_id')
            ->select(
                'sb.batch_id', 'b.batch_number', 'b.expiry_date',
                DB::raw('sb.quantity - sb.reserved_quantity AS available')
            )
            ->where('sb.product_id', $productId)
            ->where('sb.warehouse_id', $warehouseId)
            ->where('sb.quantity', '>', 0)
            ->orderByRaw('b.expiry_date IS NULL, b.expiry_date ASC')
            ->get();

        $total = (float) DB::table('tbs_stock_balances')
            ->where('product_id', $productId)
            ->where('warehouse_id', $warehouseId)
            ->sum('quantity');

        return $this->ok([
            'batches' => $rows,
            'total'   => $total,
        ]);
    }
}

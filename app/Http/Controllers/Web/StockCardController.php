<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class StockCardController extends Controller
{
    /**
     * Index: pilih produk dulu untuk lihat kartu stok-nya.
     */
    public function index(Request $request): View
    {
        $q = $request->string('q')->toString();

        $products = Product::query()
            ->with(['category:id,name', 'baseUom:id,code'])
            ->when($q, fn ($qq) => $qq->search($q))
            ->orderBy('sku')
            ->paginate(30)
            ->withQueryString();

        // Compute current total stock per product (untuk kolom saldo)
        $stockMap = DB::table('tbs_stock_balances')
            ->select('product_id', DB::raw('SUM(quantity) AS total_qty'))
            ->whereIn('product_id', $products->pluck('id'))
            ->groupBy('product_id')
            ->pluck('total_qty', 'product_id');

        $products->each(fn ($p) => $p->total_stock = (float) ($stockMap[$p->id] ?? 0));

        return view('stock_cards.index', [
            'products' => $products,
            'q'        => $q,
        ]);
    }

    /**
     * Show: kartu stok per produk dengan filter periode + warehouse.
     */
    public function show(Product $stockCard, Request $request): View
    {
        $product = $stockCard;

        $filters = [
            'warehouse_id' => $request->string('warehouse_id')->toString(),
            'date_from'    => $request->string('date_from')->toString(),
            'date_to'      => $request->string('date_to')->toString(),
        ];

        $movements = StockMovement::query()
            ->with(['warehouse:id,code,name', 'batch:id,batch_number', 'createdBy:id,full_name'])
            ->where('product_id', $product->id)
            ->ofWarehouse($filters['warehouse_id'] ?: null)
            ->betweenDates($filters['date_from'] ?: null, $filters['date_to'] ?: null)
            ->orderByDesc('created_date')
            ->paginate(50)
            ->withQueryString();

        // Saldo per warehouse saat ini
        $balancesByWh = DB::table('tbs_stock_balances as sb')
            ->join('tbm_warehouses as w', 'w.id', '=', 'sb.warehouse_id')
            ->select(
                'w.id', 'w.code', 'w.name',
                DB::raw('SUM(sb.quantity) AS total_qty'),
                DB::raw('SUM(sb.reserved_quantity) AS reserved_qty')
            )
            ->where('sb.product_id', $product->id)
            ->groupBy('w.id', 'w.code', 'w.name')
            ->orderBy('w.code')
            ->get();

        // Summary in/out
        $summary = DB::table('tbh_stock_movements')
            ->where('product_id', $product->id)
            ->when($filters['warehouse_id'], fn ($qq, $v) => $qq->where('warehouse_id', $v))
            ->when($filters['date_from'], fn ($qq, $v) => $qq->whereDate('created_date', '>=', $v))
            ->when($filters['date_to'],   fn ($qq, $v) => $qq->whereDate('created_date', '<=', $v))
            ->selectRaw('
                SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) AS total_in,
                SUM(CASE WHEN quantity < 0 THEN -quantity ELSE 0 END) AS total_out,
                COUNT(*) AS movement_count
            ')
            ->first();

        return view('stock_cards.show', [
            'product'      => $product,
            'movements'    => $movements,
            'filters'      => $filters,
            'warehouses'   => Warehouse::where('is_active', true)->orderBy('code')->get(),
            'balancesByWh' => $balancesByWh,
            'summary'      => $summary,
        ]);
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductGrade;
use App\Models\UnitOfMeasure;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProductController extends Controller
{
    use ApiResponse;

    public function __construct(
        private readonly ProductService $service,
        private readonly CategoryService $categoryService,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'q'           => $request->string('q')->toString(),
            'category_id' => $request->string('category_id')->toString(),
            'grade_id'    => $request->string('grade_id')->toString(),
            'uom_id'      => $request->string('uom_id')->toString(),
            'perishable'  => $request->string('perishable')->toString(), // ''|'1'|'0'
            'status'      => $request->string('status')->toString(),     // ''|'active'|'inactive'
            'stock_low'   => $request->boolean('stock_low'),
            'trash'       => $request->boolean('trash'),
            'view'        => $request->string('view')->toString() ?: 'table', // table|grid
        ];

        $products = Product::query()
            ->with(['category:id,name', 'grade:id,code,name,color', 'baseUom:id,code'])
            ->search($filters['q'] ?: null)
            ->ofCategory($filters['category_id'] ?: null)
            ->ofGrade($filters['grade_id'] ?: null)
            ->when($filters['uom_id'], fn ($q, $v) => $q->where('base_uom_id', $v))
            ->perishable($filters['perishable'] === '' ? null : (bool) $filters['perishable'])
            ->when($filters['status'] === 'active',   fn ($q) => $q->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($filters['stock_low'], fn ($q) => $q->stockLow())
            ->when($filters['trash'], fn ($q) => $q->onlyTrashed())
            ->orderBy('sku')
            ->get();

        // Compute total stock per product (1 query, untuk performance)
        $stockMap = DB::table('tbs_stock_balances')
            ->select('product_id', DB::raw('SUM(quantity) AS total_qty'))
            ->whereIn('product_id', $products->pluck('id'))
            ->groupBy('product_id')
            ->pluck('total_qty', 'product_id');

        $products->each(fn ($p) => $p->total_stock = (float) ($stockMap[$p->id] ?? 0));

        return view('products.index', [
            'products'    => $products,
            'filters'     => $filters,
            'categories'  => $this->categoryService->flatTreeForDropdown(),
            'grades'      => ProductGrade::orderBy('code')->get(),
            'uoms'        => UnitOfMeasure::orderBy('code')->get(),
        ]);
    }

    public function create(Request $request): View
    {
        return view('products.create', [
            'product'    => new Product([
                'is_active'     => true,
                'is_perishable' => true,
            ]),
            'categories' => $this->categoryService->flatTreeForDropdown(),
            'grades'     => ProductGrade::orderBy('code')->get(),
            'uoms'       => UnitOfMeasure::orderBy('code')->get(),
            'suggestedSku' => null,
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        // Image upload
        if ($request->hasFile('image')) {
            $url = $this->service->uploadImage($request->file('image'), $data['sku']);
            if ($url) $data['image_url'] = $url;
        }
        unset($data['image']);

        $product = Product::create($data);

        return redirect()
            ->route('products.index')
            ->with('flash', Flash::ok("Produk '{$product->name}' (SKU: {$product->sku}) berhasil ditambahkan.", 'Berhasil Disimpan'));
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'grade', 'baseUom', 'createdBy:id,full_name,username']);

        return view('products.show', [
            'product'         => $product,
            'stats'           => $this->service->getDetailStats($product),
            'stockByWh'       => $this->service->getStockByWarehouse($product),
            'activeBatches'   => $this->service->getActiveBatches($product),
            'recentMovements' => $this->service->getRecentMovements($product),
            'purchaseHistory' => $this->service->getPurchaseHistory($product),
            'salesHistory'    => $this->service->getSalesHistory($product),
            'pricesPerTier'   => $this->service->getPricesPerTier($product),
        ]);
    }

    public function edit(Product $product): View
    {
        return view('products.edit', [
            'product'    => $product,
            'categories' => $this->categoryService->flatTreeForDropdown(),
            'grades'     => ProductGrade::orderBy('code')->get(),
            'uoms'       => UnitOfMeasure::orderBy('code')->get(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        // Handle image
        if ($request->boolean('remove_image')) {
            $this->service->deleteImage($product->image_url);
            $data['image_url'] = null;
        }
        if ($request->hasFile('image')) {
            $url = $this->service->uploadImage($request->file('image'), $product->sku);
            if ($url) {
                $this->service->deleteImage($product->image_url);
                $data['image_url'] = $url;
            }
        }
        unset($data['image'], $data['remove_image']);

        $product->update($data);

        return redirect()
            ->route('products.index')
            ->with('flash', Flash::ok("Produk '{$product->name}' berhasil diperbarui.", 'Berhasil Diperbarui'));
    }

    public function destroy(Request $request, Product $product): mixed
    {
        if (! $request->user()?->hasPermission('products.delete')) {
            return $request->expectsJson()
                ? $this->failForbidden()
                : back()->with('flash', Flash::err('Tidak punya akses.', ResponseCode::FORBIDDEN));
        }

        $result = $this->service->delete($product);

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

    public function restore(Request $request, int $id): mixed
    {
        if (! $request->user()?->hasPermission('products.delete')) {
            return $request->expectsJson()
                ? $this->failForbidden()
                : back()->with('flash', Flash::err('Tidak punya akses.', ResponseCode::FORBIDDEN));
        }

        $product = Product::onlyTrashed()->findOrFail($id);
        $product->restore();
        $msg = "Produk '{$product->name}' berhasil dipulihkan.";

        return $request->expectsJson()
            ? $this->ok(null, $msg)
            : redirect()->route('products.index')->with('flash', Flash::ok($msg, 'Dipulihkan'));
    }

    /**
     * JSON endpoint: suggest SKU dari kategori + grade.
     */
    public function suggestSku(Request $request)
    {
        $categoryId = $request->integer('category_id') ?: null;
        $gradeId    = $request->integer('grade_id') ?: null;
        $sku        = $this->service->suggestSku($categoryId, $gradeId);
        return $this->ok(['sku' => $sku]);
    }
}

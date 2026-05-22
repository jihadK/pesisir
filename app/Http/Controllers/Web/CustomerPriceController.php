<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerProductPrice;
use App\Models\Product;
use App\Support\Flash;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class CustomerPriceController extends Controller
{
    public function index(Request $request): View
    {
        $filters = [
            'customer_id' => $request->string('customer_id')->toString(),
            'product_id'  => $request->string('product_id')->toString(),
            'q'           => $request->string('q')->toString(),
            'active'      => $request->string('active')->toString(),
        ];

        $rows = CustomerProductPrice::query()
            ->with(['customer:id,code,name', 'product:id,sku,name'])
            ->when($filters['customer_id'], fn ($q, $v) => $q->where('customer_id', $v))
            ->when($filters['product_id'],  fn ($q, $v) => $q->where('product_id', $v))
            ->when($filters['q'], function ($q, $v) {
                $q->where(function ($qq) use ($v) {
                    $qq->whereHas('customer', fn ($c) => $c->where('name','ilike',"%$v%")->orWhere('code','ilike',"%$v%"))
                       ->orWhereHas('product', fn ($p) => $p->where('name','ilike',"%$v%")->orWhere('sku','ilike',"%$v%"));
                });
            })
            ->when($filters['active'] === '1', fn ($q) => $q->where('is_active', true))
            ->when($filters['active'] === '0', fn ($q) => $q->where('is_active', false))
            ->orderByDesc('effective_from')
            ->orderBy('id')
            ->paginate(50)
            ->withQueryString();

        return view('customer_prices.index', [
            'rows'      => $rows,
            'filters'   => $filters,
            'customers' => Customer::orderBy('name')->get(['id', 'code', 'name']),
            'products'  => Product::active()->orderBy('sku')->get(['id', 'sku', 'name']),
        ]);
    }

    public function create(): View
    {
        return view('customer_prices.create', [
            'row'       => new CustomerProductPrice([
                'effective_from' => now()->toDateString(),
                'is_active'      => true,
                'min_quantity'   => 0,
            ]),
            'customers' => $this->nonRetailCustomers(),
            'products'  => $this->productsWithPackInfo(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validateData($request);
        $data['created_by'] = $request->user()->id;
        try {
            CustomerProductPrice::create($data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('flash', Flash::err($e->getMessage(), '05', 'Gagal Simpan'));
        }
        return redirect()->route('customer_prices.index')
            ->with('flash', Flash::ok('Kontrak harga berhasil disimpan.', 'Tersimpan'));
    }

    public function edit(CustomerProductPrice $customerPrice): View
    {
        return view('customer_prices.edit', [
            'row'       => $customerPrice,
            'customers' => $this->nonRetailCustomers(),
            'products'  => $this->productsWithPackInfo(),
        ]);
    }

    /**
     * Customer non-retail = customer yang price_tier_id-nya BUKAN tier 'Retail'.
     * Customer tanpa tier ikut termasuk (asumsinya bukan retail standar).
     */
    private function nonRetailCustomers()
    {
        $retailTierId = DB::table('tbm_price_tiers')->whereRaw('LOWER(name) = ?', ['retail'])->value('id');
        return Customer::query()
            ->when($retailTierId, fn ($q) => $q->where(function ($qq) use ($retailTierId) {
                $qq->whereNull('price_tier_id')->orWhere('price_tier_id', '!=', $retailTierId);
            }))
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'price_tier_id']);
    }

    private function productsWithPackInfo()
    {
        return Product::active()
            ->orderBy('sku')
            ->get(['id', 'sku', 'name', 'default_sell_price',
                   'pack_content_type', 'pack_content_min', 'pack_content_max',
                   'pack_weight_min_g', 'pack_weight_max_g']);
    }

    public function update(Request $request, CustomerProductPrice $customerPrice): RedirectResponse
    {
        $data = $this->validateData($request, $customerPrice->id);
        $data['updated_date'] = now();
        try {
            $customerPrice->update($data);
        } catch (\Throwable $e) {
            return back()->withInput()->with('flash', Flash::err($e->getMessage(), '05', 'Gagal Update'));
        }
        return redirect()->route('customer_prices.index')
            ->with('flash', Flash::ok('Kontrak harga berhasil diupdate.', 'Tersimpan'));
    }

    public function destroy(CustomerProductPrice $customerPrice): RedirectResponse
    {
        $customerPrice->delete();
        return redirect()->route('customer_prices.index')
            ->with('flash', Flash::ok('Kontrak harga dihapus.', 'Terhapus'));
    }

    private function validateData(Request $request, ?int $excludeId = null): array
    {
        $rules = [
            'customer_id'    => ['required', 'integer', Rule::exists('tbm_customers', 'id')->whereNull('deleted_date')],
            'product_id'     => ['required', 'integer', Rule::exists('tbm_products', 'id')->whereNull('deleted_date')],
            'price'          => ['required', 'string'],
            'min_quantity'   => ['nullable', 'numeric', 'min:0'],
            'effective_from' => ['required', 'date'],
            'effective_to'   => ['nullable', 'date', 'after_or_equal:effective_from'],
            'notes'          => ['nullable', 'string', 'max:255'],
            'is_active'      => ['nullable', 'boolean'],
        ];
        $data = $request->validate($rules);
        $data['price'] = (float) preg_replace('/[^0-9]/', '', $data['price']);
        $data['min_quantity'] = (float) ($data['min_quantity'] ?? 0);
        $data['is_active']    = $request->boolean('is_active', true);

        // Pastikan tidak duplikat (customer, product, effective_from)
        $exists = CustomerProductPrice::where('customer_id', $data['customer_id'])
            ->where('product_id', $data['product_id'])
            ->where('effective_from', $data['effective_from'])
            ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
            ->exists();
        if ($exists) {
            abort(422, 'Kontrak untuk pasangan customer × produk × tanggal mulai ini sudah ada.');
        }

        return $data;
    }
}

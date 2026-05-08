<?php

namespace App\Http\Controllers\Web;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\PriceTier\StorePriceTierRequest;
use App\Http\Requests\PriceTier\UpdatePriceTierRequest;
use App\Models\PriceTier;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PriceTierController extends Controller
{
    use ApiResponse;

    public function index(Request $request): View
    {
        $q = $request->string('q')->toString();
        $status = $request->string('status')->toString();

        $tiers = PriceTier::query()
            ->search($q ?: null)
            ->when($status === 'active',   fn ($qq) => $qq->where('is_active', true))
            ->when($status === 'inactive', fn ($qq) => $qq->where('is_active', false))
            ->orderBy('id')
            ->get()
            ->each(fn ($t) => $t->customer_count = $t->getCustomerCount());

        return view('price_tiers.index', ['tiers' => $tiers, 'filters' => compact('q', 'status')]);
    }

    public function create(): View
    {
        return view('price_tiers.create', ['tier' => new PriceTier(['is_active' => true])]);
    }

    public function store(StorePriceTierRequest $request): RedirectResponse
    {
        $t = PriceTier::create($request->validated());
        return redirect()->route('price_tiers.index')->with('flash', Flash::ok("Tier '{$t->name}' berhasil ditambahkan."));
    }

    public function edit(PriceTier $price_tier): View
    {
        return view('price_tiers.edit', ['tier' => $price_tier]);
    }

    public function update(UpdatePriceTierRequest $request, PriceTier $price_tier): RedirectResponse
    {
        $price_tier->update($request->validated());
        return redirect()->route('price_tiers.index')->with('flash', Flash::ok("Tier '{$price_tier->name}' berhasil diperbarui."));
    }

    public function destroy(Request $request, PriceTier $price_tier): mixed
    {
        if (! $request->user()?->hasPermission('price_tiers.delete')) {
            return $request->expectsJson() ? $this->failForbidden() : back()->with('flash', Flash::err('Tidak punya akses.', ResponseCode::FORBIDDEN));
        }

        $custUsing = DB::table('tbm_customers')->where('price_tier_id', $price_tier->id)->whereNull('deleted_date')->count();
        $priceUsing = DB::table('tbm_product_prices')->where('price_tier_id', $price_tier->id)->count();

        if ($custUsing > 0 || $priceUsing > 0) {
            $msg = "Tier '{$price_tier->name}' masih dipakai di {$custUsing} customer dan {$priceUsing} harga produk.";
            return $request->expectsJson() ? $this->failBusinessRule($msg) : back()->with('flash', Flash::err($msg, ResponseCode::BUSINESS_RULE_FAILED));
        }

        $name = $price_tier->name;
        $price_tier->delete();
        $msg = "Tier '{$name}' berhasil dihapus.";
        return $request->expectsJson() ? $this->ok(null, $msg) : back()->with('flash', Flash::ok($msg));
    }
}

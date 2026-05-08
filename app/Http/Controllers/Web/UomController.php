<?php

namespace App\Http\Controllers\Web;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Uom\StoreUomRequest;
use App\Http\Requests\Uom\UpdateUomRequest;
use App\Models\UnitOfMeasure;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UomController extends Controller
{
    use ApiResponse;

    public function index(Request $request): View
    {
        $q = $request->string('q')->toString();
        $uoms = UnitOfMeasure::query()->search($q ?: null)->orderBy('code')->get();
        return view('uoms.index', ['uoms' => $uoms, 'filters' => ['q' => $q]]);
    }

    public function create(): View
    {
        return view('uoms.create', ['uom' => new UnitOfMeasure()]);
    }

    public function store(StoreUomRequest $request): RedirectResponse
    {
        $u = UnitOfMeasure::create($request->validated());
        return redirect()->route('uoms.index')->with('flash', Flash::ok("Satuan '{$u->code}' berhasil ditambahkan."));
    }

    public function edit(UnitOfMeasure $uom): View
    {
        return view('uoms.edit', ['uom' => $uom]);
    }

    public function update(UpdateUomRequest $request, UnitOfMeasure $uom): RedirectResponse
    {
        $uom->update($request->validated());
        return redirect()->route('uoms.index')->with('flash', Flash::ok("Satuan '{$uom->code}' berhasil diperbarui."));
    }

    public function destroy(Request $request, UnitOfMeasure $uom): mixed
    {
        if (! $request->user()?->hasPermission('uom.delete')) {
            return $request->expectsJson() ? $this->failForbidden() : back()->with('flash', Flash::err('Tidak punya akses.', ResponseCode::FORBIDDEN));
        }

        // Cek dipakai di mana
        $productUsing = DB::table('tbm_products')->where('base_uom_id', $uom->id)->whereNull('deleted_date')->count();
        if ($productUsing > 0) {
            $msg = "Satuan '{$uom->code}' masih dipakai di {$productUsing} produk. Tidak bisa dihapus.";
            return $request->expectsJson() ? $this->failBusinessRule($msg) : back()->with('flash', Flash::err($msg, ResponseCode::BUSINESS_RULE_FAILED));
        }

        $code = $uom->code;
        $uom->delete();
        $msg = "Satuan '{$code}' berhasil dihapus.";
        return $request->expectsJson() ? $this->ok(null, $msg) : back()->with('flash', Flash::ok($msg));
    }
}

<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceRate\StoreServiceRateRequest;
use App\Http\Requests\ServiceRate\UpdateServiceRateRequest;
use App\Models\Category;
use App\Models\ServiceRate;
use App\Support\Flash;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ServiceRateController extends Controller
{
    public function index(Request $request): View
    {
        return view('service_rates.index', [
            'rates' => ServiceRate::query()
                ->with('category:id,name')
                ->search($request->string('q')->toString() ?: null)
                ->orderBy('name')->paginate(50)->withQueryString(),
            'q' => $request->string('q')->toString(),
        ]);
    }

    public function create(): View
    {
        return view('service_rates.create', [
            'rate'       => new ServiceRate(['is_active' => true]),
            'categories' => Category::whereNotNull('parent_id')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(StoreServiceRateRequest $request): RedirectResponse
    {
        $r = ServiceRate::create($request->validated());
        return redirect()->route('service_rates.index')
            ->with('flash', Flash::ok("Tarif jasa '{$r->name}' berhasil ditambahkan.", 'Berhasil'));
    }

    public function edit(ServiceRate $serviceRate): View
    {
        return view('service_rates.edit', [
            'rate'       => $serviceRate,
            'categories' => Category::whereNotNull('parent_id')->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function update(UpdateServiceRateRequest $request, ServiceRate $serviceRate): RedirectResponse
    {
        $serviceRate->update($request->validated());
        return redirect()->route('service_rates.index')
            ->with('flash', Flash::ok("Tarif jasa '{$serviceRate->name}' berhasil diperbarui.", 'Berhasil'));
    }

    public function destroy(ServiceRate $serviceRate): RedirectResponse
    {
        $name = $serviceRate->name;
        $serviceRate->delete();
        return redirect()->route('service_rates.index')
            ->with('flash', Flash::ok("Tarif '{$name}' dihapus.", 'Berhasil'));
    }
}

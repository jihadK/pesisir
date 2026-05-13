<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\CleaningService\StoreCleaningServiceRequest;
use App\Http\Requests\CleaningService\UpdateCleaningServiceRequest;
use App\Models\Category;
use App\Models\CleaningService;
use App\Models\Employee;
use App\Models\ServiceRate;
use App\Services\StockMovementService;
use App\Support\Flash;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class CleaningServiceController extends Controller
{
    public function __construct(private readonly StockMovementService $movements) {}

    public function index(Request $request): View
    {
        $filters = [
            'q'         => $request->string('q')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to'   => $request->string('date_to')->toString(),
        ];

        $services = CleaningService::query()
            ->with(['employee:id,code,name', 'category:id,name,parent_id', 'category.parent:id,name', 'createdBy:id,full_name'])
            ->search($filters['q'] ?: null)
            ->betweenDates($filters['date_from'] ?: null, $filters['date_to'] ?: null)
            ->orderByDesc('service_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('cleaning_services.index', [
            'services' => $services,
            'filters'  => $filters,
        ]);
    }

    public function create(): View
    {
        return view('cleaning_services.create', [
            'service'      => new CleaningService(['service_date' => now()->toDateString()]),
            'employees'    => Employee::active()->orderBy('name')->get(['id', 'code', 'name']),
            'categories'   => Category::whereNotNull('parent_id')->with('parent:id,name')->orderBy('name')->get(['id', 'name', 'parent_id']),
            'serviceRates' => ServiceRate::active()->orderBy('name')->get(),
        ]);
    }

    public function store(StoreCleaningServiceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['service_no']   = $this->movements->nextDocNumber('CS');
        $data['subtotal']     = round((float) $data['qty_kg'] * (float) $data['rate_per_kg'], 2);
        $data['created_by']   = $request->user()->id;

        $cs = CleaningService::create($data);
        return redirect()->route('cleaning_services.index')
            ->with('flash', Flash::ok("Jasa bersih {$cs->service_no} berhasil dicatat.", 'Berhasil'));
    }

    public function edit(CleaningService $cleaningService): View
    {
        return view('cleaning_services.edit', [
            'service'      => $cleaningService,
            'employees'    => Employee::active()->orderBy('name')->get(['id', 'code', 'name']),
            'categories'   => Category::whereNotNull('parent_id')->with('parent:id,name')->orderBy('name')->get(['id', 'name', 'parent_id']),
            'serviceRates' => ServiceRate::active()->orderBy('name')->get(),
        ]);
    }

    public function update(UpdateCleaningServiceRequest $request, CleaningService $cleaningService): RedirectResponse
    {
        $data = $request->validated();
        $data['subtotal'] = round((float) $data['qty_kg'] * (float) $data['rate_per_kg'], 2);
        $cleaningService->update($data);

        return redirect()->route('cleaning_services.index')
            ->with('flash', Flash::ok("Jasa bersih {$cleaningService->service_no} berhasil diperbarui.", 'Berhasil'));
    }

    public function destroy(CleaningService $cleaningService): RedirectResponse
    {
        $no = $cleaningService->service_no;
        $cleaningService->delete();
        return redirect()->route('cleaning_services.index')
            ->with('flash', Flash::ok("Jasa bersih {$no} dihapus.", 'Berhasil'));
    }
}

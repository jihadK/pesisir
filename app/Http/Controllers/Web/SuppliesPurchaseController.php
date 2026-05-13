<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuppliesPurchase\StoreSuppliesPurchaseRequest;
use App\Http\Requests\SuppliesPurchase\UpdateSuppliesPurchaseRequest;
use App\Models\Supplier;
use App\Models\SuppliesPurchase;
use App\Services\StockMovementService;
use App\Support\Flash;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class SuppliesPurchaseController extends Controller
{
    public function __construct(private readonly StockMovementService $movements) {}

    public function index(Request $request): View
    {
        $filters = [
            'q'         => $request->string('q')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to'   => $request->string('date_to')->toString(),
        ];

        $purchases = SuppliesPurchase::query()
            ->with(['supplier:id,code,name', 'createdBy:id,full_name'])
            ->search($filters['q'] ?: null)
            ->betweenDates($filters['date_from'] ?: null, $filters['date_to'] ?: null)
            ->orderByDesc('purchase_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('supplies_purchases.index', [
            'purchases' => $purchases,
            'filters'   => $filters,
        ]);
    }

    public function create(): View
    {
        return view('supplies_purchases.create', [
            'purchase'  => new SuppliesPurchase(['purchase_date' => now()->toDateString(), 'unit' => 'pcs']),
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function store(StoreSuppliesPurchaseRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['purchase_no'] = $this->movements->nextDocNumber('SP');
        $data['subtotal']    = round((float) $data['qty'] * (float) $data['unit_price'], 2);
        $data['created_by']  = $request->user()->id;

        $sp = SuppliesPurchase::create($data);
        return redirect()->route('supplies_purchases.index')
            ->with('flash', Flash::ok("Pembelian {$sp->purchase_no} berhasil dicatat.", 'Berhasil'));
    }

    public function edit(SuppliesPurchase $suppliesPurchase): View
    {
        return view('supplies_purchases.edit', [
            'purchase'  => $suppliesPurchase,
            'suppliers' => Supplier::where('is_active', true)->orderBy('name')->get(['id', 'code', 'name']),
        ]);
    }

    public function update(UpdateSuppliesPurchaseRequest $request, SuppliesPurchase $suppliesPurchase): RedirectResponse
    {
        $data = $request->validated();
        $data['subtotal'] = round((float) $data['qty'] * (float) $data['unit_price'], 2);
        $suppliesPurchase->update($data);

        return redirect()->route('supplies_purchases.index')
            ->with('flash', Flash::ok("Pembelian {$suppliesPurchase->purchase_no} berhasil diperbarui.", 'Berhasil'));
    }

    public function destroy(SuppliesPurchase $suppliesPurchase): RedirectResponse
    {
        $no = $suppliesPurchase->purchase_no;
        $suppliesPurchase->delete();
        return redirect()->route('supplies_purchases.index')
            ->with('flash', Flash::ok("Pembelian {$no} dihapus.", 'Berhasil'));
    }
}

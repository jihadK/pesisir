<?php

namespace App\Http\Controllers\Web;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerRequest;
use App\Http\Requests\Customer\UpdateCustomerRequest;
use App\Models\Customer;
use App\Models\PriceTier;
use App\Services\CustomerService;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class CustomerController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly CustomerService $service) {}

    public function index(Request $request): View
    {
        $filters = [
            'q'      => $request->string('q')->toString(),
            'type'   => $request->string('type')->toString(),
            'tier'   => $request->string('tier')->toString(),
            'city'   => $request->string('city')->toString(),
            'status' => $request->string('status')->toString(),
            'trash'  => $request->boolean('trash'),
        ];

        $customers = Customer::query()
            ->with('priceTier:id,name')
            ->search($filters['q'] ?: null)
            ->ofType($filters['type'] ?: null)
            ->ofTier($filters['tier'] ?: null)
            ->ofCity($filters['city'] ?: null)
            ->when($filters['status'] === 'active',   fn ($q) => $q->where('is_active', true))
            ->when($filters['status'] === 'inactive', fn ($q) => $q->where('is_active', false))
            ->when($filters['trash'], fn ($q) => $q->onlyTrashed())
            ->orderBy('code')
            ->get();

        return view('customers.index', [
            'customers' => $customers,
            'filters'   => $filters,
            'types'     => Customer::TYPES,
            'tiers'     => PriceTier::orderBy('id')->get(['id', 'name']),
            'cities'    => $this->service->distinctCities(),
        ]);
    }

    public function create(): View
    {
        return view('customers.create', [
            'customer' => new Customer([
                'is_active'          => true,
                'customer_type'      => 'individu',
                'credit_limit'       => 0,
                'payment_terms_days' => 0,
                'code'               => $this->generateCustomerCode(),
            ]),
            'types'         => Customer::TYPES,
            'tiers'         => PriceTier::where('is_active', true)->orderBy('id')->get(['id', 'name']),
            'typeToTier'    => Customer::TYPE_TO_TIER,
        ]);
    }

    /**
     * Auto-generate kode customer: CUST-001, CUST-002, dst.
     * Cari max sequence dari existing code yang match pola CUST-NNN, +1.
     */
    private function generateCustomerCode(): string
    {
        $last = \App\Models\Customer::query()
            ->withTrashed()
            ->where('code', 'like', 'CUST-%')
            ->orderByDesc('code')
            ->value('code');

        $next = 1;
        if ($last) {
            $parts = explode('-', $last);
            $tail  = end($parts);
            if (is_numeric($tail)) $next = (int) $tail + 1;
        }

        return sprintf('CUST-%03d', $next);
    }

    public function store(StoreCustomerRequest $request): RedirectResponse
    {
        $customer = Customer::create($request->validated());

        return redirect()
            ->route('customers.index')
            ->with('flash', Flash::ok("Customer '{$customer->name}' berhasil ditambahkan.", 'Berhasil Disimpan'));
    }

    public function show(Customer $customer): View
    {
        $customer->load('priceTier');

        $stats = $this->service->getDetailStats($customer);

        $recentSO = DB::table('tbr_sales_orders')
            ->select('id', 'so_number', 'order_date', 'status', 'total_amount')
            ->where('customer_id', $customer->id)
            ->orderByDesc('order_date')
            ->limit(10)
            ->get();

        $outstandingInvoices = DB::table('tbr_invoices')
            ->select('id', 'invoice_number', 'invoice_date', 'due_date', 'total_amount', 'paid_amount', 'outstanding_amount', 'status')
            ->where('customer_id', $customer->id)
            ->whereIn('status', ['issued', 'partial', 'overdue'])
            ->orderBy('due_date')
            ->limit(15)
            ->get();

        return view('customers.show', compact('customer', 'stats', 'recentSO', 'outstandingInvoices'));
    }

    public function edit(Customer $customer): View
    {
        return view('customers.edit', [
            'customer'   => $customer,
            'types'      => Customer::TYPES,
            'tiers'      => PriceTier::where('is_active', true)->orderBy('id')->get(['id', 'name']),
            'typeToTier' => Customer::TYPE_TO_TIER,
        ]);
    }

    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $customer->update($request->validated());

        return redirect()
            ->route('customers.index')
            ->with('flash', Flash::ok("Customer '{$customer->name}' berhasil diperbarui.", 'Berhasil Diperbarui'));
    }

    public function destroy(Request $request, Customer $customer): mixed
    {
        if (! $request->user()?->hasPermission('customers.delete')) {
            return $request->expectsJson()
                ? $this->failForbidden()
                : back()->with('flash', Flash::err('Anda tidak punya akses menghapus customer.', ResponseCode::FORBIDDEN));
        }

        $result = $this->service->delete($customer);

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
        if (! $request->user()?->hasPermission('customers.delete')) {
            return $request->expectsJson()
                ? $this->failForbidden()
                : back()->with('flash', Flash::err('Anda tidak punya akses.', ResponseCode::FORBIDDEN));
        }

        $customer = Customer::onlyTrashed()->findOrFail($id);
        $customer->restore();

        $msg = "Customer '{$customer->name}' berhasil dipulihkan.";

        return $request->expectsJson()
            ? $this->ok(null, $msg)
            : redirect()->route('customers.index')->with('flash', Flash::ok($msg, 'Dipulihkan'));
    }
}

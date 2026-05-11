<?php

namespace App\Http\Controllers\Web;

use App\Http\Concerns\ApiResponse;
use App\Http\Controllers\Controller;
use App\Http\Requests\Payment\StorePaymentRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\PaymentService;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PaymentController extends Controller
{
    use ApiResponse;

    public function __construct(private readonly PaymentService $service) {}

    public function index(Request $request): View
    {
        $filters = [
            'q'         => $request->string('q')->toString(),
            'status'    => $request->string('status')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to'   => $request->string('date_to')->toString(),
        ];

        $payments = Payment::query()
            ->with(['customer:id,code,name', 'paymentMethod:id,code,name', 'createdBy:id,full_name'])
            ->search($filters['q'] ?: null)
            ->ofStatus($filters['status'] ?: null)
            ->betweenDates($filters['date_from'] ?: null, $filters['date_to'] ?: null)
            ->orderByDesc('payment_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'filters'  => $filters,
            'statuses' => Payment::statusLabels(),
        ]);
    }

    public function create(Request $request): View
    {
        $preInvoice = null;
        if ($invoiceId = $request->integer('invoice_id')) {
            $preInvoice = Invoice::with('customer')->find($invoiceId);
        }

        return view('payments.create', [
            'preInvoice'     => $preInvoice,
            'customers'      => Customer::orderBy('name')->get(['id', 'code', 'name']),
            'paymentMethods' => PaymentMethod::active()->ordered()->get(),
        ]);
    }

    public function store(StorePaymentRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['created_by'] = $request->user()->id;

        try {
            $payment = $this->service->record($data);
        } catch (\Throwable $e) {
            return back()->withInput()
                ->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Catat Payment'));
        }

        return redirect()
            ->route('payments.show', $payment)
            ->with('flash', Flash::ok("Pembayaran {$payment->payment_number} berhasil dicatat.", 'Berhasil'));
    }

    public function show(Payment $payment): View
    {
        $payment->load([
            'customer', 'paymentMethod', 'createdBy:id,full_name',
            'invoices' => fn($q) => $q->select('tbr_invoices.id', 'invoice_number', 'invoice_date', 'total_amount'),
        ]);
        return view('payments.show', ['payment' => $payment]);
    }

    public function cancel(Request $request, Payment $payment): RedirectResponse
    {
        if (! $request->user()?->hasPermission('payment.cancel')) {
            return back()->with('flash', Flash::err('Tidak punya akses cancel payment.', ResponseCode::FORBIDDEN));
        }

        try {
            $this->service->cancel($payment);
        } catch (\Throwable $e) {
            return back()->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Cancel'));
        }

        return back()->with('flash', Flash::ok("Payment {$payment->payment_number} dibatalkan.", 'Cancelled'));
    }

    /**
     * AJAX: list outstanding invoices untuk customer (dropdown alokasi).
     */
    public function outstandingInvoices(Request $request)
    {
        $customerId = $request->integer('customer_id');
        if (! $customerId) return $this->ok(['invoices' => []]);

        $invoices = $this->service->outstandingInvoicesForCustomer($customerId);

        return $this->ok([
            'invoices' => $invoices->map(fn($i) => [
                'id'             => $i->id,
                'invoice_number' => $i->invoice_number,
                'invoice_date'   => $i->invoice_date?->format('d M Y'),
                'due_date'       => $i->due_date?->format('d M Y'),
                'total_amount'   => (float) $i->total_amount,
                'paid_amount'    => (float) $i->paid_amount,
                'outstanding'    => (float) $i->total_amount - (float) $i->paid_amount,
            ]),
        ]);
    }
}

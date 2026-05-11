<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\DeliveryOrder;
use App\Models\Invoice;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Services\InvoiceService;
use App\Services\PaymentService;
use App\Support\Flash;
use App\Support\ResponseCode;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct(
        private readonly InvoiceService $service,
        private readonly PaymentService $payments,
    ) {}

    public function index(Request $request): View
    {
        $filters = [
            'q'         => $request->string('q')->toString(),
            'status'    => $request->string('status')->toString(),
            'date_from' => $request->string('date_from')->toString(),
            'date_to'   => $request->string('date_to')->toString(),
        ];

        $invoices = Invoice::query()
            ->with(['customer:id,code,name', 'salesOrder:id,so_number', 'deliveryOrder:id,do_number'])
            ->search($filters['q'] ?: null)
            ->ofStatus($filters['status'] ?: null)
            ->betweenDates($filters['date_from'] ?: null, $filters['date_to'] ?: null)
            ->orderByDesc('invoice_date')
            ->orderByDesc('id')
            ->paginate(50)
            ->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'filters'  => $filters,
            'statuses' => Invoice::statusLabels(),
        ]);
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load([
            'items.product:id,sku,name', 'items.uom:id,code',
            'customer', 'salesOrder:id,so_number,payment_method_id', 'deliveryOrder:id,do_number',
            'createdBy:id,full_name',
            'payments' => fn ($q) => $q->where('tbr_payments.status', '!=', \App\Models\Payment::STATUS_CANCELLED)
                ->with('paymentMethod:id,code,name'),
        ]);

        return view('invoices.show', [
            'invoice'        => $invoice,
            'paymentMethods' => PaymentMethod::active()->ordered()->get(),
        ]);
    }

    public function createFromDO(Request $request, DeliveryOrder $deliveryOrder): RedirectResponse
    {
        if (! $request->user()?->hasPermission('invoice.create')) {
            return back()->with('flash', Flash::err('Tidak punya akses create invoice.', ResponseCode::FORBIDDEN));
        }

        try {
            $invoice = $this->service->createFromDO($deliveryOrder, $request->user()->id);
        } catch (\Throwable $e) {
            return back()->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Generate Invoice'));
        }

        return redirect()
            ->route('invoices.show', $invoice)
            ->with('flash', Flash::ok("Invoice {$invoice->invoice_number} berhasil dibuat.", 'Berhasil'));
    }

    public function cancel(Request $request, Invoice $invoice): RedirectResponse
    {
        if (! $request->user()?->hasPermission('invoice.cancel')) {
            return back()->with('flash', Flash::err('Tidak punya akses cancel invoice.', ResponseCode::FORBIDDEN));
        }

        try {
            $this->service->cancel($invoice);
        } catch (\Throwable $e) {
            return back()->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Cancel'));
        }

        return back()->with('flash', Flash::ok("Invoice {$invoice->invoice_number} dibatalkan.", 'Cancelled'));
    }

    /**
     * Quick-Pay: mark invoice as fully paid dengan 1 klik.
     * Generate Payment record otomatis dengan amount = outstanding,
     * alokasi penuh ke invoice ini.
     */
    public function quickPay(Request $request, Invoice $invoice): RedirectResponse
    {
        if (! $request->user()?->hasPermission('payment.create')) {
            return back()->with('flash', Flash::err('Tidak punya akses catat payment.', ResponseCode::FORBIDDEN));
        }

        if (! $invoice->isReceivable()) {
            return back()->with('flash', Flash::err('Invoice tidak dalam status menerima pembayaran.', ResponseCode::BUSINESS_RULE_FAILED));
        }

        $outstanding = (float) $invoice->total_amount - (float) $invoice->paid_amount;
        if ($outstanding <= 0) {
            return back()->with('flash', Flash::err('Invoice sudah lunas.', ResponseCode::BUSINESS_RULE_FAILED));
        }

        $validated = $request->validate([
            'payment_method_id' => ['required', 'integer', Rule::exists('tbm_payment_methods', 'id')],
            'payment_date'      => ['required', 'date'],
            'reference_no'      => ['nullable', 'string', 'max:50'],
            'notes'             => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $payment = $this->payments->record([
                'customer_id'       => $invoice->customer_id,
                'payment_method_id' => $validated['payment_method_id'],
                'payment_date'      => $validated['payment_date'],
                'amount'            => $outstanding,
                'reference_no'      => $validated['reference_no'] ?? null,
                'notes'             => $validated['notes'] ?? "Quick-pay untuk invoice {$invoice->invoice_number}",
                'status'            => Payment::STATUS_CLEARED,
                'created_by'        => $request->user()->id,
                'allocations'       => [
                    ['invoice_id' => $invoice->id, 'amount' => $outstanding],
                ],
            ]);
        } catch (\Throwable $e) {
            return back()->with('flash', Flash::err($e->getMessage(), ResponseCode::BUSINESS_RULE_FAILED, 'Gagal Quick-Pay'));
        }

        return back()->with('flash', Flash::ok(
            "Invoice {$invoice->invoice_number} ditandai LUNAS via {$payment->payment_number}.",
            'Berhasil'
        ));
    }

    public function print(Invoice $invoice): View
    {
        $invoice->load([
            'items.product', 'items.uom:id,code',
            'customer', 'salesOrder:id,so_number', 'deliveryOrder:id,do_number',
            'payments' => fn ($q) => $q->where('tbr_payments.status', '!=', \App\Models\Payment::STATUS_CANCELLED)
                ->with('paymentMethod:id,code,name'),
        ]);

        return view('invoices.print', ['invoice' => $invoice]);
    }
}

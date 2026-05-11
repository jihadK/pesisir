@extends('layouts.app')

@section('title', 'Invoice ' . $invoice->invoice_number)
@section('page_title', 'Detail Invoice')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Invoicing</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('invoices.index') }}" class="text-muted">Invoice</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $invoice->invoice_number }}</li>
@endsection

@section('content')
@php
    $outstanding = (float) $invoice->total_amount - (float) $invoice->paid_amount;
@endphp
<div class="row">
    <div class="col-md-8">
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">{{ $invoice->invoice_number }}</h3>
                <div class="card-toolbar">
                    <span class="badge {{ $invoice->status_badge }} fs-6">{{ $invoice->status_label }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3"><div class="col-3 text-muted">Tanggal Invoice</div><div class="col-9 fw-bold">{{ $invoice->invoice_date?->format('d M Y') }}</div></div>
                <div class="row mb-3"><div class="col-3 text-muted">Jatuh Tempo</div><div class="col-9 fw-bold">{{ $invoice->due_date?->format('d M Y') }} <span class="text-muted fs-7">({{ $invoice->payment_terms_days }} hari)</span></div></div>
                <div class="row mb-3"><div class="col-3 text-muted">Customer</div><div class="col-9 fw-bold">{{ $invoice->customer->code }} — {{ $invoice->customer->name }}</div></div>
                <div class="row mb-3">
                    <div class="col-3 text-muted">Referensi</div>
                    <div class="col-9 fs-7">
                        @if($invoice->salesOrder)
                            SO: <a href="{{ route('sales_orders.show', $invoice->salesOrder) }}" class="fw-bold">{{ $invoice->salesOrder->so_number }}</a>
                        @endif
                        @if($invoice->deliveryOrder)
                            · DO: <a href="{{ route('delivery_orders.show', $invoice->deliveryOrder) }}" class="fw-bold">{{ $invoice->deliveryOrder->do_number }}</a>
                        @endif
                    </div>
                </div>
                @if($invoice->notes)
                    <div class="row"><div class="col-3 text-muted">Catatan</div><div class="col-9">{{ $invoice->notes }}</div></div>
                @endif
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Items</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-2">
                        <thead>
                            <tr class="fw-bold text-muted bg-light fs-7">
                                <th class="ps-4">Item</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end pe-4">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($invoice->items as $item)
                            @php $q = (float)$item->quantity; $qF = floor($q)==$q ? number_format($q,0,',','.') : number_format($q,3,',','.'); @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $item->product->sku }}</div>
                                    <div class="text-muted fs-7">{{ $item->description ?? $item->product->name }}</div>
                                </td>
                                <td class="text-end">{{ $qF }} {{ $item->uom->code }}</td>
                                <td class="text-end">Rp {{ number_format((float)$item->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end pe-4 fw-bold">Rp {{ number_format((float)$item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="fw-bold">
                            <tr><td colspan="3" class="text-end">Subtotal</td><td class="text-end pe-4">Rp {{ number_format((float)$invoice->subtotal, 0, ',', '.') }}</td></tr>
                            @if((float)$invoice->discount_amount > 0)
                                <tr><td colspan="3" class="text-end">Diskon</td><td class="text-end pe-4 text-danger">−Rp {{ number_format((float)$invoice->discount_amount, 0, ',', '.') }}</td></tr>
                            @endif
                            @if((float)$invoice->shipping_cost > 0)
                                <tr><td colspan="3" class="text-end">Ongkir</td><td class="text-end pe-4">Rp {{ number_format((float)$invoice->shipping_cost, 0, ',', '.') }}</td></tr>
                            @endif
                            <tr><td colspan="3" class="text-end fs-5">TOTAL</td><td class="text-end pe-4 fs-5 text-primary">Rp {{ number_format((float)$invoice->total_amount, 0, ',', '.') }}</td></tr>
                            <tr><td colspan="3" class="text-end text-success">Sudah Dibayar</td><td class="text-end pe-4 text-success">Rp {{ number_format((float)$invoice->paid_amount, 0, ',', '.') }}</td></tr>
                            <tr><td colspan="3" class="text-end fs-5 {{ $outstanding > 0 ? 'text-danger' : 'text-muted' }}">SISA</td><td class="text-end pe-4 fs-5 {{ $outstanding > 0 ? 'text-danger' : 'text-muted' }}">Rp {{ number_format($outstanding, 0, ',', '.') }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        @if($invoice->payments->isNotEmpty())
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Riwayat Pembayaran</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-2">
                        <thead>
                            <tr class="fw-bold text-muted bg-light fs-7">
                                <th class="ps-4">Tanggal</th>
                                <th>No. Pembayaran</th>
                                <th>Metode</th>
                                <th>Ref</th>
                                <th class="text-center">Status</th>
                                <th class="text-end pe-4">Alokasi</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($invoice->payments as $pay)
                            <tr>
                                <td class="ps-4 fs-7">{{ \Illuminate\Support\Carbon::parse($pay->payment_date)->format('d M Y') }}</td>
                                <td><a href="{{ route('payments.show', $pay) }}" class="fw-bold text-primary">{{ $pay->payment_number }}</a></td>
                                <td class="fs-7">{{ $pay->paymentMethod->name }}</td>
                                <td class="fs-7">{{ $pay->reference_no ?? '—' }}</td>
                                <td class="text-center"><span class="badge {{ $pay->status_badge }} fs-8">{{ $pay->status_label }}</span></td>
                                <td class="text-end pe-4 fw-bold text-success">Rp {{ number_format((float)$pay->pivot->allocated_amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @endif
    </div>

    <div class="col-md-4">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Status Pembayaran</h3></div>
            <div class="card-body">
                <div class="d-flex flex-stack mb-3">
                    <span class="text-muted">Total Invoice:</span>
                    <span class="fw-bold">Rp {{ number_format((float)$invoice->total_amount, 0, ',', '.') }}</span>
                </div>
                <div class="d-flex flex-stack mb-3">
                    <span class="text-muted">Sudah Dibayar:</span>
                    <span class="fw-bold text-success">Rp {{ number_format((float)$invoice->paid_amount, 0, ',', '.') }}</span>
                </div>
                <div class="separator my-3"></div>
                <div class="d-flex flex-stack">
                    <span class="text-muted">SISA:</span>
                    <span class="fw-bolder fs-3 {{ $outstanding > 0 ? 'text-danger' : 'text-success' }}">
                        Rp {{ number_format($outstanding, 0, ',', '.') }}
                    </span>
                </div>
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Aksi</h3></div>
            <div class="card-body d-flex flex-column gap-2">
                @if(auth()->user()?->hasPermission('invoice.print'))
                    <a href="{{ route('invoices.print', $invoice) }}" target="_blank" class="btn btn-light-primary">
                        <i class="ki-outline ki-printer fs-2"></i> Cetak Invoice
                    </a>
                @endif

                @if($invoice->isReceivable() && auth()->user()?->hasPermission('payment.create'))
                    {{-- Quick Pay: 1 klik mark as lunas dengan modal kecil --}}
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal_quick_pay">
                        <i class="ki-outline ki-check-circle fs-2"></i> Tandai Lunas (Quick Pay)
                    </button>

                    {{-- Catat Pembayaran (full form, untuk parsial/multi-invoice) --}}
                    <a href="{{ route('payments.create', ['invoice_id' => $invoice->id]) }}" class="btn btn-light-success">
                        <i class="ki-outline ki-wallet fs-2"></i> Catat Pembayaran (Parsial / Lain)
                    </a>
                @endif

                @if($invoice->isCancellable() && auth()->user()?->hasPermission('invoice.cancel'))
                    <form method="POST" action="{{ route('invoices.cancel', $invoice) }}" onsubmit="return confirm('Cancel invoice ini?')">
                        @csrf
                        <button type="submit" class="btn btn-light-danger w-100">
                            <i class="ki-outline ki-cross-circle fs-2"></i> Cancel Invoice
                        </button>
                    </form>
                @endif

                <a href="{{ route('invoices.index') }}" class="btn btn-light">
                    <i class="ki-outline ki-arrow-left fs-2"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

@if($invoice->isReceivable() && auth()->user()?->hasPermission('payment.create'))
{{-- Modal: Quick Pay --}}
<div class="modal fade" id="modal_quick_pay" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('invoices.quick-pay', $invoice) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="ki-outline ki-check-circle text-success me-2"></i>
                    Tandai Lunas — {{ $invoice->invoice_number }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light-success py-3 mb-4">
                    <div class="d-flex flex-stack mb-1 fs-7">
                        <span class="text-muted">Customer:</span>
                        <span class="fw-bold">{{ $invoice->customer->name }}</span>
                    </div>
                    <div class="d-flex flex-stack mb-1 fs-7">
                        <span class="text-muted">Total Invoice:</span>
                        <span class="fw-bold">Rp {{ number_format((float)$invoice->total_amount, 0, ',', '.') }}</span>
                    </div>
                    @if((float)$invoice->paid_amount > 0)
                        <div class="d-flex flex-stack mb-1 fs-7">
                            <span class="text-muted">Sudah Dibayar:</span>
                            <span class="fw-bold text-success">Rp {{ number_format((float)$invoice->paid_amount, 0, ',', '.') }}</span>
                        </div>
                    @endif
                    <div class="separator my-2"></div>
                    <div class="d-flex flex-stack">
                        <span class="text-muted">Yang akan dibayar:</span>
                        <span class="fw-bolder fs-3 text-success">Rp {{ number_format($outstanding, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-4 fw-semibold required">Tanggal Bayar</label>
                    <div class="col-md-8">
                        <input type="date" name="payment_date" value="{{ now()->toDateString() }}"
                               class="form-control form-control-solid" required />
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-4 fw-semibold required">Metode</label>
                    <div class="col-md-8">
                        <select name="payment_method_id" class="form-select form-select-solid" required>
                            @php $defaultPmId = $invoice->salesOrder?->payment_method_id; @endphp
                            <option value="">Pilih metode...</option>
                            @foreach($paymentMethods as $pm)
                                <option value="{{ $pm->id }}" @selected($defaultPmId == $pm->id)>
                                    {{ $pm->name }}@if($pm->bank_name) ({{ $pm->bank_name }} {{ $pm->account_no }})@endif
                                </option>
                            @endforeach
                        </select>
                        @if($defaultPmId)
                            <div class="form-text fs-8">Auto-pilih dari metode di SO. Boleh diubah.</div>
                        @endif
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-4 fw-semibold">No. Referensi</label>
                    <div class="col-md-8">
                        <input type="text" name="reference_no" maxlength="50"
                               class="form-control form-control-solid"
                               placeholder="Mis. nomor mutasi rekening / ID QRIS" />
                    </div>
                </div>

                <div class="row">
                    <label class="col-form-label col-md-4 fw-semibold">Catatan</label>
                    <div class="col-md-8">
                        <textarea name="notes" rows="2" class="form-control form-control-solid" maxlength="500"
                                  placeholder="(Opsional)"></textarea>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">
                    <i class="ki-outline ki-check fs-2"></i> Tandai Lunas Sekarang
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

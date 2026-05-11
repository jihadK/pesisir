@extends('layouts.app')

@section('title', 'Payment ' . $payment->payment_number)
@section('page_title', 'Detail Pembayaran')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Invoicing</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('payments.index') }}" class="text-muted">Payment</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $payment->payment_number }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">{{ $payment->payment_number }}</h3>
                <div class="card-toolbar">
                    <span class="badge {{ $payment->status_badge }} fs-6">{{ $payment->status_label }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3"><div class="col-3 text-muted">Tanggal</div><div class="col-9 fw-bold">{{ $payment->payment_date?->format('d M Y') }}</div></div>
                <div class="row mb-3"><div class="col-3 text-muted">Customer</div><div class="col-9 fw-bold">{{ $payment->customer->code }} — {{ $payment->customer->name }}</div></div>
                <div class="row mb-3"><div class="col-3 text-muted">Metode</div><div class="col-9 fw-bold">{{ $payment->paymentMethod->name }}</div></div>
                @if($payment->reference_no)
                    <div class="row mb-3"><div class="col-3 text-muted">Ref No.</div><div class="col-9">{{ $payment->reference_no }}</div></div>
                @endif
                <div class="row mb-3"><div class="col-3 text-muted">Jumlah</div><div class="col-9 fw-bold fs-3 text-success">Rp {{ number_format((float)$payment->amount, 0, ',', '.') }}</div></div>
                @if($payment->notes)
                    <div class="row"><div class="col-3 text-muted">Catatan</div><div class="col-9">{{ $payment->notes }}</div></div>
                @endif
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Alokasi ke Invoice</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-2">
                        <thead>
                            <tr class="fw-bold text-muted bg-light fs-7">
                                <th class="ps-4">No. Invoice</th>
                                <th>Tgl Invoice</th>
                                <th class="text-end">Total Invoice</th>
                                <th class="text-end pe-4">Alokasi Pembayaran</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($payment->invoices as $inv)
                            <tr>
                                <td class="ps-4"><a href="{{ route('invoices.show', $inv) }}" class="fw-bold text-primary">{{ $inv->invoice_number }}</a></td>
                                <td class="fs-7">{{ \Illuminate\Support\Carbon::parse($inv->invoice_date)->format('d M Y') }}</td>
                                <td class="text-end">Rp {{ number_format((float)$inv->total_amount, 0, ',', '.') }}</td>
                                <td class="text-end pe-4 fw-bold text-success">Rp {{ number_format((float)$inv->pivot->allocated_amount, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Aksi</h3></div>
            <div class="card-body d-flex flex-column gap-2">
                @if($payment->status !== \App\Models\Payment::STATUS_CANCELLED && auth()->user()?->hasPermission('payment.cancel'))
                    <form method="POST" action="{{ route('payments.cancel', $payment) }}" onsubmit="return confirm('Cancel pembayaran ini? Invoice yang ter-alokasi akan dikembalikan ke status sebelumnya.')">
                        @csrf
                        <button type="submit" class="btn btn-light-danger w-100">
                            <i class="ki-outline ki-cross-circle fs-2"></i> Cancel Pembayaran
                        </button>
                    </form>
                @endif
                <a href="{{ route('payments.index') }}" class="btn btn-light">
                    <i class="ki-outline ki-arrow-left fs-2"></i> Kembali
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Info</h3></div>
            <div class="card-body fs-7">
                <div class="d-flex flex-stack mb-2"><span class="text-muted">Dicatat oleh:</span><span class="fw-bold">{{ $payment->createdBy?->full_name ?? '—' }}</span></div>
                <div class="d-flex flex-stack"><span class="text-muted">Tgl Dicatat:</span><span class="fw-bold">{{ $payment->created_date?->format('d M Y H:i') }}</span></div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Payment')
@section('page_title', 'Payment')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Invoicing</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Payment</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title flex-column">
            <h2 class="mb-1">Daftar Pembayaran</h2>
            <span class="text-muted fs-7">Catat pembayaran customer, alokasikan ke 1+ invoice.</span>
        </div>
        <div class="card-toolbar">
            @if(auth()->user()?->hasPermission('payment.create'))
                <a href="{{ route('payments.create') }}" class="btn btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> Catat Pembayaran
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-5">
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm form-control-solid" placeholder="No. Pembayaran / Ref / customer..." />
            </div>
            <div class="col-md-2">
                <label class="form-label fs-7 fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm form-select-solid" data-control="select2" data-placeholder="Semua">
                    <option value=""></option>
                    @foreach($statuses as $k => $v)<option value="{{ $k }}" @selected($filters['status']==$k)>{{ $v }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label fs-7 fw-semibold">Dari</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control form-control-sm form-control-solid" />
            </div>
            <div class="col-md-2">
                <label class="form-label fs-7 fw-semibold">Sampai</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control form-control-sm form-control-solid" />
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ki-outline ki-filter fs-3"></i> Filter</button>
                @if(array_filter($filters))<a href="{{ route('payments.index') }}" class="btn btn-sm btn-light">Reset</a>@endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-3">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4">Tanggal</th>
                        <th>No. Pembayaran</th>
                        <th>Customer</th>
                        <th>Metode</th>
                        <th>Ref No.</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Jumlah</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($payments as $p)
                    <tr>
                        <td class="ps-4 fs-7">{{ $p->payment_date?->format('d M Y') }}</td>
                        <td><span class="fw-bold text-primary">{{ $p->payment_number }}</span></td>
                        <td>{{ $p->customer->name }}</td>
                        <td class="fs-7">{{ $p->paymentMethod->name }}</td>
                        <td class="fs-7">{{ $p->reference_no ?? '—' }}</td>
                        <td class="text-center"><span class="badge {{ $p->status_badge }} fs-7">{{ $p->status_label }}</span></td>
                        <td class="text-end fw-bold text-success">Rp {{ number_format((float)$p->amount, 0, ',', '.') }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('payments.show', $p) }}" class="btn btn-sm btn-light-info"><i class="ki-outline ki-eye fs-3"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-10">Belum ada pembayaran.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $payments->links() }}</div>
    </div>
</div>
@endsection

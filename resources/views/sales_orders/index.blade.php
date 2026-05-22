@extends('layouts.app')

@php $isPaidView = ($filters['status'] ?? '') === 'paid'; @endphp
@section('title', $isPaidView ? 'Daftar Invoice' : 'Booking Order')
@section('page_title', $isPaidView ? 'Daftar Invoice' : 'Booking Order')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Penjualan</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $isPaidView ? 'Daftar Invoice' : 'Booking Order' }}</li>
@endsection

@section('content')

@if($isPaidView && $summary)
<div class="row g-4 mb-5">
    <div class="col-md-4">
        <div class="card card-flush h-100 shadow-sm" style="background: linear-gradient(135deg,#fff5f0,#ffd5b8); border:0;">
            <div class="card-body d-flex align-items-center">
                <div class="symbol symbol-50px me-4">
                    <span class="symbol-label bg-danger bg-opacity-25 text-danger">
                        <i class="ki-outline ki-purchase fs-2x text-danger"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="text-muted fs-7 fw-semibold text-uppercase">Total HPP</div>
                    <div class="fs-2 fw-bolder text-dark">Rp {{ number_format((float)$summary['total_hpp'], 0, ',', '.') }}</div>
                    <div class="fs-8 text-muted">Modal pembelian produk terjual</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card card-flush h-100 shadow-sm" style="background: linear-gradient(135deg,#e7f4ff,#bcd8ff); border:0;">
            <div class="card-body d-flex align-items-center">
                <div class="symbol symbol-50px me-4">
                    <span class="symbol-label bg-primary bg-opacity-25 text-primary">
                        <i class="ki-outline ki-handcart fs-2x text-primary"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="text-muted fs-7 fw-semibold text-uppercase">Total Penjualan</div>
                    <div class="fs-2 fw-bolder text-dark">Rp {{ number_format((float)$summary['total_sales'], 0, ',', '.') }}</div>
                    <div class="fs-8 text-muted">Dari {{ number_format($summary['count'], 0, ',', '.') }} order terbayar</div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        @php
            $labaPositif = $summary['laba'] >= 0;
            $gradient = $labaPositif ? 'linear-gradient(135deg,#e8fbef,#a4e7bb)' : 'linear-gradient(135deg,#fdecea,#f7b8b1)';
            $iconBg   = $labaPositif ? 'bg-success' : 'bg-danger';
            $iconCol  = $labaPositif ? 'text-success' : 'text-danger';
        @endphp
        <div class="card card-flush h-100 shadow-sm" style="background: {{ $gradient }}; border:0;">
            <div class="card-body d-flex align-items-center">
                <div class="symbol symbol-50px me-4">
                    <span class="symbol-label {{ $iconBg }} bg-opacity-25 {{ $iconCol }}">
                        <i class="ki-outline ki-chart-line-up fs-2x {{ $iconCol }}"></i>
                    </span>
                </div>
                <div class="flex-grow-1">
                    <div class="text-muted fs-7 fw-semibold text-uppercase">Laba Bersih</div>
                    <div class="fs-2 fw-bolder text-dark">Rp {{ number_format((float)$summary['laba'], 0, ',', '.') }}</div>
                    <div class="fs-8 text-muted">
                        Margin <span class="fw-bold {{ $iconCol }}">{{ number_format((float)$summary['margin_pct'], 1, ',', '.') }}%</span>
                        @if(! $labaPositif) <span class="badge badge-light-danger ms-1">Rugi</span> @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title flex-column">
            <h2 class="mb-1">{{ $isPaidView ? 'Daftar Invoice (Sudah Terbayar)' : 'Daftar Booking Order' }}</h2>
            <span class="text-muted fs-7">{{ $isPaidView ? 'Order yang sudah lunas / paid.' : 'Order customer (Draft → Paid).' }}</span>
        </div>
        <div class="card-toolbar">
            @if(! $isPaidView && auth()->user()?->hasPermission('sales_order.create'))
                <a href="{{ route('sales_orders.create') }}" class="btn btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> Order Baru
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-5">
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm form-control-solid" placeholder="No. SO / customer..." />
            </div>
            <div class="col-md-2">
                <label class="form-label fs-7 fw-semibold">Status</label>
                <select name="status" class="form-select form-select-sm form-select-solid" data-control="select2" data-placeholder="Semua">
                    <option value=""></option>
                    @foreach($statuses as $key => $label)<option value="{{ $key }}" @selected($filters['status']==$key)>{{ $label }}</option>@endforeach
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
                @if(array_filter($filters))<a href="{{ route('sales_orders.index') }}" class="btn btn-sm btn-light">Reset</a>@endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-3">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4">Tanggal</th>
                        <th>No. Order</th>
                        <th>Customer</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Total</th>
                        <th>Sales</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $o)
                    <tr>
                        <td class="ps-4 fs-7">{{ $o->order_date?->format('d M Y') }}</td>
                        <td><span class="fw-bold text-primary">{{ $o->so_number }}</span></td>
                        <td>
                            <div class="fw-bold">{{ $o->customer->name }}</div>
                            <div class="text-muted fs-8">{{ $o->customer->code }}</div>
                        </td>
                        <td class="text-center"><span class="badge {{ $o->status_badge }} fs-7">{{ $o->status_label }}</span></td>
                        <td class="text-end fw-bold">{{ number_format((float)$o->total_amount, 0, ',', '.') }}</td>
                        <td class="fs-7">{{ $o->salesUser?->full_name ?? '—' }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('sales_orders.show', $o) }}" class="btn btn-sm btn-light-info"><i class="ki-outline ki-eye fs-3"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-10">Belum ada booking order.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
    </div>
</div>
@endsection

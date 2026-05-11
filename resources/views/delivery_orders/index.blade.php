@extends('layouts.app')

@section('title', 'Delivery Order')
@section('page_title', 'Delivery Order')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Penjualan</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Delivery Order</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title flex-column">
            <h2 class="mb-1">Daftar Delivery Order</h2>
            <span class="text-muted fs-7">Surat jalan pengiriman ke customer. Saat shipped, stok auto-kurang.</span>
        </div>
        <div class="card-toolbar">
            @if(auth()->user()?->hasPermission('delivery_order.create'))
                <a href="{{ route('delivery_orders.create') }}" class="btn btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> DO Baru
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-5">
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm form-control-solid" placeholder="No. DO / SO / customer..." />
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
                @if(array_filter($filters))<a href="{{ route('delivery_orders.index') }}" class="btn btn-sm btn-light">Reset</a>@endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-3">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4">Tgl Kirim</th>
                        <th>No. DO</th>
                        <th>Ref SO</th>
                        <th>Customer</th>
                        <th>Warehouse</th>
                        <th class="text-center">Status</th>
                        <th>Driver / Kendaraan</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $o)
                    <tr>
                        <td class="ps-4 fs-7">{{ $o->delivery_date?->format('d M Y') }}</td>
                        <td><span class="fw-bold text-primary">{{ $o->do_number }}</span></td>
                        <td class="fs-7">{{ $o->salesOrder?->so_number ?? '—' }}</td>
                        <td>
                            <div class="fw-bold">{{ $o->customer->name }}</div>
                            <div class="text-muted fs-8">{{ $o->customer->code }}</div>
                        </td>
                        <td class="fs-7">{{ $o->warehouse->code }}</td>
                        <td class="text-center"><span class="badge {{ $o->status_badge }} fs-7">{{ $o->status_label }}</span></td>
                        <td class="fs-7">
                            @if($o->driver_name || $o->vehicle_no)
                                <div>{{ $o->driver_name ?? '—' }}</div>
                                <div class="text-muted fs-8">{{ $o->vehicle_no ?? '' }}</div>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('delivery_orders.show', $o) }}" class="btn btn-sm btn-light-info"><i class="ki-outline ki-eye fs-3"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-10">Belum ada DO.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
    </div>
</div>
@endsection

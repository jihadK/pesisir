@extends('layouts.app')

@section('title', 'Purchase Order')
@section('page_title', 'Purchase Order')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Pembelian</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Purchase Order</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title flex-column">
            <h2 class="mb-1">Daftar Purchase Order</h2>
            <span class="text-muted fs-7">Order pembelian ke supplier. Submit → kirim ke supplier → terima via GRN.</span>
        </div>
        <div class="card-toolbar">
            @if(auth()->user()?->hasPermission('purchase_order.create'))
                <a href="{{ route('purchase_orders.create') }}" class="btn btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> PO Baru
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-5">
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm form-control-solid" placeholder="No. PO / supplier..." />
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
                @if(array_filter($filters))<a href="{{ route('purchase_orders.index') }}" class="btn btn-sm btn-light">Reset</a>@endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-3">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4">Tanggal</th>
                        <th>No. PO</th>
                        <th>Supplier</th>
                        <th>Warehouse</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Total</th>
                        <th>Expected</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $o)
                    <tr>
                        <td class="ps-4 fs-7">{{ $o->po_date?->format('d M Y') }}</td>
                        <td><span class="fw-bold text-primary">{{ $o->po_number }}</span></td>
                        <td>
                            <div class="fw-bold">{{ $o->supplier->name }}</div>
                            <div class="text-muted fs-8">{{ $o->supplier->code }}</div>
                        </td>
                        <td class="fs-7">{{ $o->warehouse->code }}</td>
                        <td class="text-center"><span class="badge {{ $o->status_badge }} fs-7">{{ $o->status_label }}</span></td>
                        <td class="text-end fw-bold">{{ number_format((float)$o->total_amount, 0, ',', '.') }}</td>
                        <td class="fs-7">{{ $o->expected_date?->format('d M Y') ?? '—' }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('purchase_orders.show', $o) }}" class="btn btn-sm btn-light-info"><i class="ki-outline ki-eye fs-3"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-10">Belum ada PO.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
    </div>
</div>
@endsection

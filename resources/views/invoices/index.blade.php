@extends('layouts.app')

@section('title', 'Invoice')
@section('page_title', 'Invoice')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Invoicing</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Invoice</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title flex-column">
            <h2 class="mb-1">Daftar Invoice</h2>
            <span class="text-muted fs-7">Faktur yang sudah di-issue ke customer.</span>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-5">
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm form-control-solid" placeholder="No. Invoice / customer..." />
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
                @if(array_filter($filters))<a href="{{ route('invoices.index') }}" class="btn btn-sm btn-light">Reset</a>@endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-3">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4">Tanggal</th>
                        <th>No. Invoice</th>
                        <th>Customer</th>
                        <th>Ref</th>
                        <th class="text-center">Status</th>
                        <th class="text-end">Total</th>
                        <th class="text-end">Sudah Dibayar</th>
                        <th class="text-end">Sisa</th>
                        <th>Jatuh Tempo</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($invoices as $inv)
                    @php
                        $outstanding = (float) $inv->total_amount - (float) $inv->paid_amount;
                        $isOverdue = $inv->due_date && $inv->due_date->isPast() && $outstanding > 0;
                    @endphp
                    <tr>
                        <td class="ps-4 fs-7">{{ $inv->invoice_date?->format('d M Y') }}</td>
                        <td><span class="fw-bold text-primary">{{ $inv->invoice_number }}</span></td>
                        <td>{{ $inv->customer->name }}</td>
                        <td class="fs-7 text-muted">
                            {{ $inv->salesOrder?->so_number ?? '' }}
                            @if($inv->deliveryOrder)<br>{{ $inv->deliveryOrder->do_number }}@endif
                        </td>
                        <td class="text-center"><span class="badge {{ $inv->status_badge }} fs-7">{{ $inv->status_label }}</span></td>
                        <td class="text-end fw-bold">Rp {{ number_format((float)$inv->total_amount, 0, ',', '.') }}</td>
                        <td class="text-end text-success">Rp {{ number_format((float)$inv->paid_amount, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold {{ $outstanding > 0 ? 'text-danger' : 'text-muted' }}">
                            Rp {{ number_format($outstanding, 0, ',', '.') }}
                        </td>
                        <td class="fs-7 {{ $isOverdue ? 'text-danger fw-bold' : '' }}">
                            {{ $inv->due_date?->format('d M Y') }}
                            @if($isOverdue)<div class="fs-8">⚠ Overdue</div>@endif
                        </td>
                        <td class="text-end pe-4">
                            <a href="{{ route('invoices.show', $inv) }}" class="btn btn-sm btn-light-info"><i class="ki-outline ki-eye fs-3"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" class="text-center text-muted py-10">Belum ada invoice.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $invoices->links() }}</div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Detail Customer')
@section('page_title', $customer->name)
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('customers.index') }}" class="text-muted text-hover-primary">Customer</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $customer->code }}</li>
@endsection

@php
    $util = $stats['credit_util'];
    $utilColor = $util === null ? 'secondary' : ($util < 70 ? 'success' : ($util < 90 ? 'warning' : 'danger'));

    $aging = $stats['aging'];
    $agingTotal = max(1, array_sum($aging)); // hindari div by 0
@endphp

@section('content')
<div class="row g-5 mb-5">
    {{-- Profile Card --}}
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="me-3">{{ $customer->name }}</span>
                    <span class="badge {{ $customer->type_badge_class }} me-1">{{ $customer->type_label }}</span>
                    <span class="badge {{ $customer->status_badge }}">{{ $customer->status_label }}</span>
                </h3>
                <div class="card-toolbar">
                    <a href="{{ route('customers.edit', $customer) }}" class="btn btn-sm btn-light-primary">
                        <i class="ki-outline ki-pencil fs-3"></i> Edit
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Kode</div><div class="col-md-9 fw-bold">{{ $customer->code }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Tipe</div><div class="col-md-9">{{ $customer->type_label }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Tier Harga</div><div class="col-md-9">{{ $customer->priceTier?->name ?? '—' }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Contact Person</div><div class="col-md-9">{{ $customer->contact_person ?? '—' }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Phone</div><div class="col-md-9">{{ $customer->phone ?? '—' }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Email</div><div class="col-md-9">{{ $customer->email ?? '—' }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Alamat</div><div class="col-md-9">{{ $customer->address ?? '—' }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Kota</div><div class="col-md-9">{{ $customer->city ?? '—' }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">NPWP</div><div class="col-md-9">{{ $customer->npwp ?? '—' }}</div></div>
                <div class="separator my-4"></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Credit Limit</div><div class="col-md-9 fw-bold">
                    @if($customer->credit_limit > 0)
                        Rp {{ number_format($customer->credit_limit, 0, ',', '.') }}
                    @else
                        <span class="text-muted">COD (Cash on Delivery)</span>
                    @endif
                </div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">TOP</div><div class="col-md-9"><span class="badge badge-light-info">{{ $customer->payment_terms_days }} hari</span></div></div>
                <div class="separator my-4"></div>
                <div class="row text-muted fs-7">
                    <div class="col-md-6">Dibuat: {{ $customer->created_date?->format('d M Y H:i') ?? '—' }}</div>
                    <div class="col-md-6 text-md-end">Diubah: {{ $customer->updated_date?->format('d M Y H:i') ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistik + Credit Util --}}
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Statistik</h3></div>
            <div class="card-body">
                {{-- Credit Utilization --}}
                <div class="mb-7">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted fs-7 text-uppercase">Pemakaian Kredit</span>
                        <span class="fw-bold fs-7">
                            @if($util === null)
                                <span class="text-muted">N/A (COD)</span>
                            @else
                                <span class="text-{{ $utilColor }}">{{ $util }}%</span>
                            @endif
                        </span>
                    </div>
                    <div class="progress h-8px">
                        <div class="progress-bar bg-{{ $utilColor }}"
                             role="progressbar"
                             style="width: {{ min($util ?? 0, 100) }}%"></div>
                    </div>
                    <div class="d-flex justify-content-between mt-2 fs-8 text-muted">
                        <span>Rp {{ number_format($stats['outstanding_ar'], 0, ',', '.') }}</span>
                        <span>/ Rp {{ number_format($customer->credit_limit, 0, ',', '.') }}</span>
                    </div>
                </div>

                <div class="d-flex flex-stack mb-4">
                    <div>
                        <div class="text-muted fs-7 text-uppercase">Total SO</div>
                        <div class="fs-3 fw-bolder">{{ number_format($stats['total_so']) }}</div>
                    </div>
                    <i class="ki-outline ki-basket fs-3hx text-gray-300"></i>
                </div>
                <div class="d-flex flex-stack mb-4">
                    <div>
                        <div class="text-muted fs-7 text-uppercase">SO Aktif</div>
                        <div class="fs-3 fw-bolder">{{ number_format($stats['active_so']) }}</div>
                    </div>
                    <i class="ki-outline ki-time fs-3hx text-gray-300"></i>
                </div>
                <div class="d-flex flex-stack mb-4">
                    <div>
                        <div class="text-muted fs-7 text-uppercase">Total Penjualan</div>
                        <div class="fs-3 fw-bolder">Rp {{ number_format($stats['total_sales'], 0, ',', '.') }}</div>
                    </div>
                    <i class="ki-outline ki-dollar fs-3hx text-gray-300"></i>
                </div>
                <div class="d-flex flex-stack">
                    <div>
                        <div class="text-muted fs-7 text-uppercase">Order Terakhir</div>
                        <div class="fs-3 fw-bolder">{{ $stats['last_order_date'] ? \Carbon\Carbon::parse($stats['last_order_date'])->format('d M Y') : '—' }}</div>
                    </div>
                    <i class="ki-outline ki-calendar fs-3hx text-gray-300"></i>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- AR Aging Bar --}}
<div class="card mb-5">
    <div class="card-header">
        <h3 class="card-title">AR Aging — Total Outstanding: Rp {{ number_format($stats['outstanding_ar'], 0, ',', '.') }}</h3>
    </div>
    <div class="card-body">
        @php
            $buckets = [
                ['Belum jatuh tempo', $aging['not_due'],   'success'],
                ['1 - 30 hari',       $aging['d_1_30'],    'info'],
                ['31 - 60 hari',      $aging['d_31_60'],   'warning'],
                ['61 - 90 hari',      $aging['d_61_90'],   'orange'],
                ['> 90 hari',         $aging['d_over_90'], 'danger'],
            ];
        @endphp

        {{-- Stacked horizontal bar --}}
        <div class="progress h-30px mb-4" style="background:#f1f1f4">
            @foreach($buckets as [$label, $amt, $color])
                @if($amt > 0)
                    <div class="progress-bar bg-{{ $color }}" role="progressbar"
                         style="width: {{ ($amt / $agingTotal) * 100 }}%"
                         data-bs-toggle="tooltip" title="{{ $label }}: Rp {{ number_format($amt, 0, ',', '.') }}">
                        {{ ($amt / $agingTotal) * 100 >= 8 ? 'Rp '.number_format($amt, 0, ',', '.') : '' }}
                    </div>
                @endif
            @endforeach
        </div>

        {{-- Legend --}}
        <div class="row g-3">
            @foreach($buckets as [$label, $amt, $color])
                <div class="col-md-2 col-6">
                    <div class="border border-gray-300 border-dashed rounded p-3">
                        <div class="d-flex align-items-center mb-1">
                            <span class="bullet bullet-vertical bg-{{ $color }} me-2"></span>
                            <span class="fs-8 text-muted text-uppercase">{{ $label }}</span>
                        </div>
                        <div class="fw-bold fs-7">Rp {{ number_format($amt, 0, ',', '.') }}</div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<div class="row g-5">
    {{-- Recent SO --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">10 Sales Order Terakhir</h3></div>
            <div class="card-body">
                <table class="table table-row-bordered table-row-gray-200 align-middle gy-3 gs-0">
                    <thead><tr class="text-gray-500 fw-bold fs-7 text-uppercase">
                        <th>No. SO</th><th>Tanggal</th><th>Status</th><th class="text-end">Total</th>
                    </tr></thead>
                    <tbody class="fs-7">
                        @forelse($recentSO as $so)
                            <tr>
                                <td class="fw-bold">{{ $so->so_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($so->order_date)->format('d M Y') }}</td>
                                <td><span class="badge badge-light fs-8">{{ $so->status }}</span></td>
                                <td class="text-end fw-bold">Rp {{ number_format($so->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-5">Belum ada SO.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Outstanding Invoices --}}
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Invoice Belum Lunas</h3></div>
            <div class="card-body">
                <table class="table table-row-bordered table-row-gray-200 align-middle gy-3 gs-0">
                    <thead><tr class="text-gray-500 fw-bold fs-7 text-uppercase">
                        <th>No. Invoice</th><th>Jatuh Tempo</th><th>Status</th><th class="text-end">Outstanding</th>
                    </tr></thead>
                    <tbody class="fs-7">
                        @forelse($outstandingInvoices as $inv)
                            @php
                                $dueDate = \Carbon\Carbon::parse($inv->due_date);
                                $overdue = $dueDate->isPast();
                                $daysOverdue = $overdue ? $dueDate->diffInDays(now()) : null;
                            @endphp
                            <tr>
                                <td class="fw-bold">{{ $inv->invoice_number }}</td>
                                <td>
                                    {{ $dueDate->format('d M Y') }}
                                    @if($overdue)<span class="badge badge-light-danger ms-1 fs-9">{{ $daysOverdue }}h lewat</span>@endif
                                </td>
                                <td><span class="badge badge-light-{{ $inv->status === 'overdue' ? 'danger' : ($inv->status === 'partial' ? 'warning' : 'info') }} fs-8">{{ $inv->status }}</span></td>
                                <td class="text-end fw-bold {{ $overdue ? 'text-danger' : '' }}">Rp {{ number_format($inv->outstanding_amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-success py-5"><i class="ki-outline ki-check-circle fs-2 me-1"></i> Tidak ada piutang.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

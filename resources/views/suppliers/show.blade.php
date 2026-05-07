@extends('layouts.app')

@section('title', 'Detail Supplier')
@section('page_title', $supplier->name)
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('suppliers.index') }}" class="text-muted text-hover-primary">Supplier</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $supplier->code }}</li>
@endsection

@section('content')
<div class="row g-5 mb-5">
    {{-- Profile --}}
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="me-3">{{ $supplier->name }}</span>
                    <span class="badge {{ $supplier->status_badge }}">{{ $supplier->status_label }}</span>
                </h3>
                <div class="card-toolbar">
                    <a href="{{ route('suppliers.edit', $supplier) }}" class="btn btn-sm btn-light-primary">
                        <i class="ki-outline ki-pencil fs-3"></i> Edit
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Kode</div><div class="col-md-9 fw-bold">{{ $supplier->code }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Contact Person</div><div class="col-md-9">{{ $supplier->contact_person ?? '—' }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Phone</div><div class="col-md-9">{{ $supplier->phone ?? '—' }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Email</div><div class="col-md-9">{{ $supplier->email ?? '—' }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Alamat</div><div class="col-md-9">{{ $supplier->address ?? '—' }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Kota</div><div class="col-md-9">{{ $supplier->city ?? '—' }}</div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">NPWP</div><div class="col-md-9">{{ $supplier->npwp ?? '—' }}</div></div>
                <div class="separator my-4"></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">Bank</div><div class="col-md-9">{{ $supplier->bank_name ?? '—' }} <span class="text-muted">{{ $supplier->bank_account }}</span></div></div>
                <div class="row mb-4"><div class="col-md-3 text-muted fw-semibold">TOP</div><div class="col-md-9"><span class="badge badge-light-info">{{ $supplier->payment_terms_days }} hari</span></div></div>
                <div class="separator my-4"></div>
                <div class="row text-muted fs-7">
                    <div class="col-md-6">Dibuat: {{ $supplier->created_date?->format('d M Y H:i') ?? '—' }}</div>
                    <div class="col-md-6 text-md-end">Diubah: {{ $supplier->updated_date?->format('d M Y H:i') ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Statistik --}}
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Statistik</h3></div>
            <div class="card-body">
                <div class="d-flex flex-stack mb-5">
                    <div>
                        <div class="text-muted fs-7 text-uppercase">Total PO</div>
                        <div class="fs-2 fw-bolder">{{ number_format($stats['total_po']) }}</div>
                    </div>
                    <i class="ki-outline ki-handcart fs-3hx text-gray-300"></i>
                </div>
                <div class="d-flex flex-stack mb-5">
                    <div>
                        <div class="text-muted fs-7 text-uppercase">PO Aktif</div>
                        <div class="fs-2 fw-bolder">{{ number_format($stats['active_po']) }}</div>
                    </div>
                    <i class="ki-outline ki-time fs-3hx text-gray-300"></i>
                </div>
                <div class="d-flex flex-stack mb-5">
                    <div>
                        <div class="text-muted fs-7 text-uppercase">Total Pembelian</div>
                        <div class="fs-2 fw-bolder">Rp {{ number_format($stats['total_purchase'], 0, ',', '.') }}</div>
                    </div>
                    <i class="ki-outline ki-dollar fs-3hx text-gray-300"></i>
                </div>
                <div class="d-flex flex-stack mb-5">
                    <div>
                        <div class="text-muted fs-7 text-uppercase">PO Terakhir</div>
                        <div class="fs-2 fw-bolder">{{ $stats['last_po_date'] ? \Carbon\Carbon::parse($stats['last_po_date'])->format('d M Y') : '—' }}</div>
                    </div>
                    <i class="ki-outline ki-calendar fs-3hx text-gray-300"></i>
                </div>
                <div class="d-flex flex-stack mb-5">
                    <div>
                        <div class="text-muted fs-7 text-uppercase">Produk Disuplai</div>
                        <div class="fs-2 fw-bolder">{{ number_format($stats['distinct_products']) }}</div>
                    </div>
                    <i class="ki-outline ki-package fs-3hx text-gray-300"></i>
                </div>
                <div class="d-flex flex-stack">
                    <div>
                        <div class="text-muted fs-7 text-uppercase">Batch Aktif</div>
                        <div class="fs-2 fw-bolder">{{ number_format($stats['active_batches']) }}</div>
                    </div>
                    <i class="ki-outline ki-element-7 fs-3hx text-gray-300"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-5">
    {{-- Recent POs --}}
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">10 PO Terakhir</h3></div>
            <div class="card-body">
                <table class="table table-row-bordered table-row-gray-200 align-middle gy-3 gs-0">
                    <thead>
                        <tr class="text-gray-500 fw-bold fs-7 text-uppercase">
                            <th>No. PO</th>
                            <th>Tanggal</th>
                            <th>Status</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        @forelse($recentPOs as $po)
                            <tr>
                                <td class="fw-bold">{{ $po->po_number }}</td>
                                <td>{{ \Carbon\Carbon::parse($po->po_date)->format('d M Y') }}</td>
                                <td><span class="badge badge-light fs-8">{{ $po->status }}</span></td>
                                <td class="text-end fw-bold">Rp {{ number_format($po->total_amount, 0, ',', '.') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted py-5">Belum ada PO dari supplier ini.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Produk yang disuplai --}}
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header"><h3 class="card-title">Produk Disuplai</h3></div>
            <div class="card-body">
                @forelse($products as $p)
                    <div class="d-flex align-items-center mb-4">
                        <div class="symbol symbol-40px me-3">
                            <span class="symbol-label bg-light-warning"><i class="ki-outline ki-fish fs-2 text-warning"></i></span>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">{{ $p->name }}</div>
                            <div class="text-muted fs-7">{{ $p->sku }} &middot; {{ $p->batch_count }} batch</div>
                        </div>
                        <span class="badge badge-light-success fw-bold">{{ number_format($p->total_qty, 1) }}</span>
                    </div>
                @empty
                    <div class="text-muted">Belum ada produk yang disuplai.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection

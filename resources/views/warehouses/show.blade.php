@extends('layouts.app')

@section('title', 'Detail Gudang')
@section('page_title', $warehouse->name)
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('warehouses.index') }}" class="text-muted text-hover-primary">Gudang</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $warehouse->code }}</li>
@endsection

@section('content')
<x-flash-messages />

<div class="row g-5 mb-5">
    {{-- ===== Profile Card ===== --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <span class="me-3">{{ $warehouse->name }}</span>
                    <span class="badge {{ $warehouse->type_badge_class }} fw-bold">{{ $warehouse->type_label }}</span>
                    @if(!$warehouse->is_active)
                        <span class="badge badge-light-danger ms-2">Non-aktif</span>
                    @endif
                </h3>
                <div class="card-toolbar">
                    <a href="{{ route('warehouses.edit', $warehouse) }}" class="btn btn-sm btn-light-primary">
                        <i class="ki-outline ki-pencil fs-3"></i> Edit
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-3 text-muted fw-semibold">Kode</div>
                    <div class="col-md-9 fw-bold">{{ $warehouse->code }}</div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-3 text-muted fw-semibold">Tipe</div>
                    <div class="col-md-9">{{ $warehouse->type_label }}</div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-3 text-muted fw-semibold">Suhu</div>
                    <div class="col-md-9">
                        @if($warehouse->temperature_min !== null || $warehouse->temperature_max !== null)
                            {{ $warehouse->temperature_min ?? '—' }} °C ~ {{ $warehouse->temperature_max ?? '—' }} °C
                        @else
                            <span class="text-muted">Tidak diset</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-3 text-muted fw-semibold">Alamat</div>
                    <div class="col-md-9">{{ $warehouse->address ?? '—' }}</div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-3 text-muted fw-semibold">PIC</div>
                    <div class="col-md-9">
                        @if($warehouse->picUser)
                            <span class="fw-bold">{{ $warehouse->picUser->full_name }}</span>
                            <span class="text-muted">({{ $warehouse->picUser->username }})</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-4">
                    <div class="col-md-3 text-muted fw-semibold">Status</div>
                    <div class="col-md-9">
                        @if($warehouse->is_active)
                            <span class="badge badge-light-success">Aktif</span>
                        @else
                            <span class="badge badge-light-danger">Non-aktif</span>
                        @endif
                    </div>
                </div>
                <div class="separator my-4"></div>
                <div class="row text-muted fs-7">
                    <div class="col-md-6">Dibuat: {{ $warehouse->created_date?->format('d M Y H:i') ?? '—' }}</div>
                    <div class="col-md-6 text-md-end">Diubah: {{ $warehouse->updated_date?->format('d M Y H:i') ?? '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Statistik ===== --}}
    <div class="col-md-5">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Statistik</h3>
            </div>
            <div class="card-body">
                <div class="d-flex flex-stack mb-5">
                    <div>
                        <div class="text-muted fs-7 text-uppercase">Total Produk</div>
                        <div class="fs-2 fw-bolder">{{ number_format($stats['total_products']) }}</div>
                    </div>
                    <i class="ki-outline ki-package fs-3hx text-gray-300"></i>
                </div>
                <div class="d-flex flex-stack mb-5">
                    <div>
                        <div class="text-muted fs-7 text-uppercase">Total Qty Stock</div>
                        <div class="fs-2 fw-bolder">{{ number_format($stats['total_qty'], 3) }}</div>
                    </div>
                    <i class="ki-outline ki-fish fs-3hx text-gray-300"></i>
                </div>
                <div class="d-flex flex-stack mb-5">
                    <div>
                        <div class="text-muted fs-7 text-uppercase">User Akses</div>
                        <div class="fs-2 fw-bolder">{{ number_format($stats['total_users']) }}</div>
                    </div>
                    <i class="ki-outline ki-people fs-3hx text-gray-300"></i>
                </div>
                <div class="d-flex flex-stack">
                    <div>
                        <div class="text-muted fs-7 text-uppercase">Mutasi 30 Hari</div>
                        <div class="fs-2 fw-bolder">{{ number_format($stats['total_movements_30d']) }}</div>
                    </div>
                    <i class="ki-outline ki-arrow-right-left fs-3hx text-gray-300"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-5">
    {{-- ===== User Akses ===== --}}
    <div class="col-md-5">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">User dengan Akses</h3>
            </div>
            <div class="card-body">
                @forelse($warehouse->users as $u)
                    <div class="d-flex align-items-center mb-4">
                        <div class="symbol symbol-40px me-3">
                            <div class="symbol-label fs-5 bg-light-primary text-primary fw-bold">
                                {{ strtoupper(mb_substr($u->full_name, 0, 1)) }}
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">{{ $u->full_name }}</div>
                            <div class="text-muted fs-7">{{ $u->username }}</div>
                        </div>
                        @if($u->pivot->is_default)
                            <span class="badge badge-light-success me-2">Default</span>
                        @endif
                        <span class="badge badge-light-{{ $u->pivot->access_level === 'admin' ? 'danger' : ($u->pivot->access_level === 'write' ? 'warning' : 'info') }} fw-bold">
                            {{ strtoupper($u->pivot->access_level) }}
                        </span>
                    </div>
                @empty
                    <div class="text-muted">Belum ada user yang ter-assign ke gudang ini.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- ===== Mutasi Terbaru ===== --}}
    <div class="col-md-7">
        <div class="card h-100">
            <div class="card-header">
                <h3 class="card-title">10 Mutasi Stock Terakhir</h3>
            </div>
            <div class="card-body">
                <table class="table table-row-bordered table-row-gray-200 align-middle gy-3 gs-0">
                    <thead>
                        <tr class="text-gray-500 fw-bold fs-7 text-uppercase">
                            <th>No.</th>
                            <th>Produk</th>
                            <th>Tipe</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="fs-7">
                        @forelse($recentMovements as $m)
                            <tr>
                                <td><span class="text-muted">{{ $m->movement_number }}</span></td>
                                <td>
                                    <div class="fw-bold">{{ $m->product_name ?? '—' }}</div>
                                    <div class="text-muted fs-8">{{ $m->sku }}</div>
                                </td>
                                <td><span class="badge badge-light fs-8">{{ $m->movement_type }}</span></td>
                                <td class="text-end fw-bold {{ $m->quantity < 0 ? 'text-danger' : 'text-success' }}">
                                    {{ number_format($m->quantity, 3) }}
                                </td>
                                <td class="text-end text-muted">
                                    {{ \Carbon\Carbon::parse($m->created_date)->format('d M Y H:i') }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="text-center text-muted py-5">Belum ada mutasi stock.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

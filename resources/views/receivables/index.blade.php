@extends('layouts.app')

@section('title', 'Piutang')
@section('page_title', 'Piutang (Outstanding AR)')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Penjualan</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Piutang</li>
@endsection

@section('content')

{{-- ===== Aging buckets ===== --}}
<div class="row g-4 mb-5">
    <div class="col-md-3">
        <div class="card card-flush h-100 shadow-sm" style="background: linear-gradient(135deg,#e7f4ff,#bcd8ff); border:0;">
            <div class="card-body">
                <div class="text-muted fs-7 fw-semibold text-uppercase mb-1">Total Outstanding</div>
                <div class="fs-2 fw-bolder text-dark">Rp {{ number_format($summary['total_outstanding'], 0, ',', '.') }}</div>
                <div class="fs-8 text-muted">{{ number_format($summary['count']) }} order belum dibayar</div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <a href="?aging=overdue" class="text-decoration-none">
            <div class="card card-flush h-100 shadow-sm" style="background: linear-gradient(135deg,#fdecea,#f7b8b1); border:0; cursor:pointer;">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold text-uppercase mb-1">Overdue</div>
                    <div class="fs-2 fw-bolder text-danger">Rp {{ number_format($summary['overdue'], 0, ',', '.') }}</div>
                    <div class="fs-8 text-muted">{{ number_format($summary['overdue_count']) }} order telat bayar</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?aging=current" class="text-decoration-none">
            <div class="card card-flush h-100 shadow-sm" style="background: linear-gradient(135deg,#fff9e6,#ffe9a8); border:0; cursor:pointer;">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold text-uppercase mb-1">Jatuh Tempo 7 Hari</div>
                    <div class="fs-2 fw-bolder text-warning">Rp {{ number_format($summary['due7'], 0, ',', '.') }}</div>
                    <div class="fs-8 text-muted">Akan jatuh tempo dalam 1 minggu</div>
                </div>
            </div>
        </a>
    </div>
    <div class="col-md-3">
        <a href="?aging=due14" class="text-decoration-none">
            <div class="card card-flush h-100 shadow-sm" style="background: linear-gradient(135deg,#e8fbef,#a4e7bb); border:0; cursor:pointer;">
                <div class="card-body">
                    <div class="text-muted fs-7 fw-semibold text-uppercase mb-1">8 – 14 Hari Lagi</div>
                    <div class="fs-2 fw-bolder text-success">Rp {{ number_format($summary['due14'], 0, ',', '.') }}</div>
                    <div class="fs-8 text-muted">Jatuh tempo minggu depan</div>
                </div>
            </div>
        </a>
    </div>
</div>

{{-- ===== List ===== --}}
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title flex-column">
            <h2 class="mb-1">Daftar Piutang</h2>
            <span class="text-muted fs-7">SO yang sudah Fulfilled (barang kirim) tapi belum dibayar.</span>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-5">
            <div class="col-md-4">
                <label class="form-label fs-7 fw-semibold">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm form-control-solid" placeholder="No. Order / customer..." />
            </div>
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Aging</label>
                <select name="aging" class="form-select form-select-sm form-select-solid">
                    <option value="">Semua</option>
                    <option value="overdue" @selected($filters['aging']=='overdue')>Overdue (telat)</option>
                    <option value="current" @selected($filters['aging']=='current')>0–7 hari</option>
                    <option value="due7"    @selected($filters['aging']=='due7')>8–14 hari</option>
                    <option value="due14"   @selected($filters['aging']=='due14')>15–30 hari</option>
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ki-outline ki-filter fs-3"></i> Filter</button>
                @if(array_filter($filters))<a href="{{ route('receivables.index') }}" class="btn btn-sm btn-light">Reset</a>@endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-3">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4">Tanggal Order</th>
                        <th>No. Order</th>
                        <th>Customer</th>
                        <th>Jatuh Tempo</th>
                        <th class="text-center">Aging</th>
                        <th class="text-end">Outstanding</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $o)
                    @php
                        $days = $o->due_date ? $today->diffInDays($o->due_date, false) : null;
                        $overdue = $days !== null && $days < 0;
                    @endphp
                    <tr class="{{ $overdue ? 'bg-light-danger' : '' }}">
                        <td class="ps-4 fs-7">{{ $o->order_date?->format('d M Y') }}</td>
                        <td><a href="{{ route('sales_orders.show', $o) }}" class="fw-bold text-primary">{{ $o->so_number }}</a></td>
                        <td>
                            <div class="fw-bold">{{ $o->customer->name }}</div>
                            <div class="text-muted fs-8">{{ $o->customer->code }} @if($o->customer->phone)· 📞 {{ $o->customer->phone }}@endif</div>
                        </td>
                        <td class="fs-7 fw-bold {{ $overdue ? 'text-danger' : '' }}">{{ $o->due_date?->format('d M Y') ?? '—' }}</td>
                        <td class="text-center">
                            @if($days === null) <span class="text-muted">—</span>
                            @elseif($overdue) <span class="badge badge-light-danger">Telat {{ abs($days) }} hari</span>
                            @elseif($days <= 7) <span class="badge badge-light-warning">{{ $days }} hari lagi</span>
                            @else <span class="badge badge-light-success">{{ $days }} hari lagi</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold">Rp {{ number_format((float)$o->total_amount, 0, ',', '.') }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('sales_orders.show', $o) }}" class="btn btn-sm btn-light-info" title="Detail"><i class="ki-outline ki-eye fs-3"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-10">Tidak ada piutang 🎉</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $orders->links() }}</div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Jasa Bersih Ikan')
@section('page_title', 'Jasa Bersih Ikan')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Pembelian</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Jasa Bersih Ikan</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title flex-column">
            <h2 class="mb-1">Daftar Jasa Bersih</h2>
            <span class="text-muted fs-7">Catatan jasa pembersihan ikan per pegawai.</span>
        </div>
        <div class="card-toolbar">
            @if(auth()->user()?->hasPermission('cleaning_service.create'))
                <a href="{{ route('cleaning_services.create') }}" class="btn btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> Catat Jasa Bersih
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-5">
            <div class="col-md-4">
                <label class="form-label fs-7 fw-semibold">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm form-control-solid" placeholder="No. / pegawai / kategori..." />
            </div>
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Dari</label>
                <input type="date" name="date_from" value="{{ $filters['date_from'] }}" class="form-control form-control-sm form-control-solid" />
            </div>
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Sampai</label>
                <input type="date" name="date_to" value="{{ $filters['date_to'] }}" class="form-control form-control-sm form-control-solid" />
            </div>
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-sm btn-primary">Filter</button>
                @if(array_filter($filters))<a href="{{ route('cleaning_services.index') }}" class="btn btn-sm btn-light">Reset</a>@endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-3">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4">Tanggal</th>
                        <th>No. Jasa</th>
                        <th>Pegawai</th>
                        <th>Sub-Kategori</th>
                        <th class="text-end">Qty (kg)</th>
                        <th class="text-end">Tarif/Kg</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($services as $s)
                    @php $q = (float)$s->qty_kg; $qF = floor($q)==$q ? number_format($q,0,',','.') : rtrim(rtrim(number_format($q,3,',','.'),'0'),','); @endphp
                    <tr>
                        <td class="ps-4 fs-7">{{ $s->service_date?->format('d M Y') }}</td>
                        <td><span class="fw-bold text-primary">{{ $s->service_no }}</span></td>
                        <td>{{ $s->employee?->name ?? '—' }}</td>
                        <td class="fs-7">{{ $s->category?->parent?->name }}{{ $s->category?->parent ? ' › ' : '' }}{{ $s->category?->name }}</td>
                        <td class="text-end fw-bold">{{ $qF }} kg</td>
                        <td class="text-end">{{ number_format((float)$s->rate_per_kg, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold">{{ number_format((float)$s->subtotal, 0, ',', '.') }}</td>
                        <td class="text-end pe-4">
                            @if(auth()->user()?->hasPermission('cleaning_service.update'))
                                <a href="{{ route('cleaning_services.edit', $s) }}" class="btn btn-sm btn-light-warning"><i class="ki-outline ki-pencil fs-3"></i></a>
                            @endif
                            @if(auth()->user()?->hasPermission('cleaning_service.delete'))
                                <form method="POST" action="{{ route('cleaning_services.destroy', $s) }}" class="d-inline" onsubmit="return confirm('Hapus catatan {{ $s->service_no }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light-danger"><i class="ki-outline ki-trash fs-3"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-10">Belum ada catatan jasa bersih.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $services->links() }}</div>
    </div>
</div>
@endsection

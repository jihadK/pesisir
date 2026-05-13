@extends('layouts.app')

@section('title', 'Tarif Jasa')
@section('page_title', 'Tarif Jasa')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Konfigurasi</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Tarif Jasa</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <form method="GET" class="d-flex align-items-center position-relative">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                <input type="text" name="q" value="{{ $q }}" class="form-control form-control-solid w-300px ps-13" placeholder="Cari nama / kategori..." />
            </form>
        </div>
        <div class="card-toolbar">
            @if(auth()->user()?->hasPermission('service_rate.create'))
                <a href="{{ route('service_rates.create') }}" class="btn btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> Tambah Tarif
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-3">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4">Nama Jasa</th>
                        <th>Sub-Kategori</th>
                        <th class="text-end">Tarif / Kg</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rates as $r)
                    <tr>
                        <td class="ps-4 fw-bold">{{ $r->name }}</td>
                        <td>{{ $r->category?->name ?? '— Semua —' }}</td>
                        <td class="text-end fw-bold">Rp {{ number_format((float)$r->rate_per_kg, 0, ',', '.') }}</td>
                        <td class="text-center">
                            @if($r->is_active)<span class="badge badge-light-success">Aktif</span>
                            @else<span class="badge badge-light-secondary">Non-aktif</span>@endif
                        </td>
                        <td class="text-end pe-4">
                            @if(auth()->user()?->hasPermission('service_rate.update'))
                                <a href="{{ route('service_rates.edit', $r) }}" class="btn btn-sm btn-light-warning"><i class="ki-outline ki-pencil fs-3"></i></a>
                            @endif
                            @if(auth()->user()?->hasPermission('service_rate.delete'))
                                <form method="POST" action="{{ route('service_rates.destroy', $r) }}" class="d-inline" onsubmit="return confirm('Hapus tarif {{ $r->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light-danger"><i class="ki-outline ki-trash fs-3"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="text-center text-muted py-10">Belum ada tarif jasa.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $rates->links() }}</div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Metode Pembayaran')
@section('page_title', 'Metode Pembayaran')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Konfigurasi</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Metode Pembayaran</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title flex-column">
            <h2 class="mb-1">Metode Pembayaran</h2>
            <span class="text-muted fs-7">Daftar rekening, QRIS, dan COD yang ditampilkan di Proforma/Invoice.</span>
        </div>
        <div class="card-toolbar">
            @if(auth()->user()?->hasPermission('payment_method.create'))
                <a href="{{ route('payment_methods.create') }}" class="btn btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> Tambah Metode
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-3">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4" style="width:60px;">Urut</th>
                        <th>Kode</th>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th>Detail</th>
                        <th class="text-center">QRIS</th>
                        <th class="text-center">Aktif</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($methods as $m)
                    <tr>
                        <td class="ps-4 text-muted">{{ $m->display_order }}</td>
                        <td><code>{{ $m->code }}</code></td>
                        <td class="fw-bold">{{ $m->name }}</td>
                        <td><span class="badge badge-light-info">{{ $m->type_label }}</span></td>
                        <td class="fs-7">
                            @if($m->bank_name || $m->account_no)
                                <div><strong>{{ $m->bank_name }}</strong> {{ $m->account_no }}</div>
                                <div class="text-muted">a.n. {{ $m->account_holder ?: '—' }}</div>
                            @elseif($m->description)
                                <span class="text-muted">{{ $m->description }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($m->qris_image_display_url)
                                <a href="{{ $m->qris_image_display_url }}" target="_blank">
                                    <img src="{{ $m->qris_image_display_url }}" alt="QRIS" style="height:40px;border-radius:4px" />
                                </a>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-center">
                            @if($m->is_active)
                                <span class="badge badge-light-success">Aktif</span>
                            @else
                                <span class="badge badge-light-secondary">Non-aktif</span>
                            @endif
                        </td>
                        <td class="text-end pe-4">
                            @if(auth()->user()?->hasPermission('payment_method.update'))
                                <a href="{{ route('payment_methods.edit', $m) }}" class="btn btn-sm btn-light-warning">
                                    <i class="ki-outline ki-pencil fs-3"></i>
                                </a>
                            @endif
                            @if(auth()->user()?->hasPermission('payment_method.delete'))
                                <form method="POST" action="{{ route('payment_methods.destroy', $m) }}" class="d-inline" onsubmit="return confirm('Hapus metode {{ $m->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light-danger"><i class="ki-outline ki-trash fs-3"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center text-muted py-10">Belum ada metode pembayaran.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection

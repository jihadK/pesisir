@extends('layouts.app')

@section('title', 'Pembelian Lain-lain')
@section('page_title', 'Pembelian Lain-lain')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Pembelian</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Pembelian Lain-lain</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title flex-column">
            <h2 class="mb-1">Pembelian Lain-lain</h2>
            <span class="text-muted fs-7">Plastik, box, timba, dan supplies operasional lainnya.</span>
        </div>
        <div class="card-toolbar">
            @if(auth()->user()?->hasPermission('supplies_purchase.create'))
                <a href="{{ route('supplies_purchases.create') }}" class="btn btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> Catat Pembelian
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-5">
            <div class="col-md-4">
                <label class="form-label fs-7 fw-semibold">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm form-control-solid" placeholder="No. / item / supplier..." />
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
                @if(array_filter($filters))<a href="{{ route('supplies_purchases.index') }}" class="btn btn-sm btn-light">Reset</a>@endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-3">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4">Tanggal</th>
                        <th>No. Pembelian</th>
                        <th>Item</th>
                        <th>Supplier</th>
                        <th class="text-end">Qty</th>
                        <th>Satuan</th>
                        <th class="text-end">Harga</th>
                        <th class="text-end">Subtotal</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($purchases as $p)
                    @php $q = (float)$p->qty; $qF = floor($q)==$q ? number_format($q,0,',','.') : rtrim(rtrim(number_format($q,3,',','.'),'0'),','); @endphp
                    <tr>
                        <td class="ps-4 fs-7">{{ $p->purchase_date?->format('d M Y') }}</td>
                        <td><span class="fw-bold text-primary">{{ $p->purchase_no }}</span></td>
                        <td class="fw-bold">{{ $p->description }}</td>
                        <td class="fs-7">{{ $p->supplier?->name ?? '—' }}</td>
                        <td class="text-end">{{ $qF }}</td>
                        <td class="fs-7">{{ $p->unit }}</td>
                        <td class="text-end">{{ number_format((float)$p->unit_price, 0, ',', '.') }}</td>
                        <td class="text-end fw-bold">{{ number_format((float)$p->subtotal, 0, ',', '.') }}</td>
                        <td class="text-end pe-4">
                            @if(auth()->user()?->hasPermission('supplies_purchase.update'))
                                <a href="{{ route('supplies_purchases.edit', $p) }}" class="btn btn-sm btn-light-warning"><i class="ki-outline ki-pencil fs-3"></i></a>
                            @endif
                            @if(auth()->user()?->hasPermission('supplies_purchase.delete'))
                                <form method="POST" action="{{ route('supplies_purchases.destroy', $p) }}" class="d-inline" onsubmit="return confirm('Hapus {{ $p->purchase_no }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light-danger"><i class="ki-outline ki-trash fs-3"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-10">Belum ada catatan pembelian.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $purchases->links() }}</div>
    </div>
</div>
@endsection

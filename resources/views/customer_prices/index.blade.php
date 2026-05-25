@extends('layouts.app')

@section('title', 'Kontrak Harga Customer')
@section('page_title', 'Kontrak Harga Customer')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Penjualan</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Kontrak Harga Customer</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title flex-column">
            <h2 class="mb-1">Kontrak Harga Customer</h2>
            <span class="text-muted fs-7">Harga khusus per customer × produk. Override default sell price.</span>
        </div>
        <div class="card-toolbar">
            @if(auth()->user()?->hasPermission('customer_price.create'))
                <a href="{{ route('customer_prices.create') }}" class="btn btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> Kontrak Baru
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <form method="GET" class="row g-3 mb-5">
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Cari</label>
                <input type="text" name="q" value="{{ $filters['q'] }}" class="form-control form-control-sm form-control-solid" placeholder="Customer / produk..." />
            </div>
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Customer</label>
                <select name="customer_id" class="form-select form-select-sm form-select-solid" data-control="select2" data-placeholder="Semua">
                    <option value=""></option>
                    @foreach($customers as $c)<option value="{{ $c->id }}" @selected($filters['customer_id']==$c->id)>{{ $c->code }} — {{ $c->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Produk</label>
                <select name="product_id" class="form-select form-select-sm form-select-solid" data-control="select2" data-placeholder="Semua">
                    <option value=""></option>
                    @foreach($products as $p)<option value="{{ $p->id }}" @selected($filters['product_id']==$p->id)>{{ $p->sku }} — {{ $p->name }}</option>@endforeach
                </select>
            </div>
            <div class="col-md-3 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ki-outline ki-filter fs-3"></i> Filter</button>
                @if(array_filter($filters))<a href="{{ route('customer_prices.index') }}" class="btn btn-sm btn-light">Reset</a>@endif
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-3">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4">Customer</th>
                        <th>Produk</th>
                        <th class="text-end">Harga Kontrak</th>
                        <th class="text-center">Min Qty</th>
                        <th>Berlaku</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rows as $r)
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold">{{ $r->customer?->name ?? '—' }}</div>
                            <div class="text-muted fs-8">{{ $r->customer?->code ?? 'Customer dihapus' }}</div>
                        </td>
                        <td>
                            <div class="fw-bold">{{ $r->product?->name ?? '—' }}</div>
                            <div class="text-muted fs-8">{{ $r->product?->sku ?? 'Produk dihapus' }}</div>
                        </td>
                        <td class="text-end fw-bold text-primary">Rp {{ number_format((float)$r->price, 0, ',', '.') }}</td>
                        <td class="text-center fs-7">{{ rtrim(rtrim(number_format((float)$r->min_quantity, 3, ',', '.'), '0'), ',') }}</td>
                        <td class="fs-7">
                            {{ $r->effective_from?->format('d M Y') }}
                            @if($r->effective_to) – {{ $r->effective_to->format('d M Y') }} @else <span class="text-muted">– seterusnya</span> @endif
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $r->is_active ? 'badge-light-success' : 'badge-light-secondary' }}">
                                {{ $r->is_active ? 'Aktif' : 'Nonaktif' }}
                            </span>
                        </td>
                        <td class="text-end pe-4">
                            @if(auth()->user()?->hasPermission('customer_price.update'))
                                <a href="{{ route('customer_prices.edit', $r) }}" class="btn btn-sm btn-light-warning"><i class="ki-outline ki-pencil fs-3"></i></a>
                            @endif
                            @if(auth()->user()?->hasPermission('customer_price.delete'))
                                <form method="POST" action="{{ route('customer_prices.destroy', $r) }}" class="d-inline" onsubmit="return confirm('Hapus kontrak ini?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light-danger"><i class="ki-outline ki-trash fs-3"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="text-center text-muted py-10">Belum ada kontrak harga.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $rows->links() }}</div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Stock Opening')
@section('page_title', 'Stock Opening')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Inventory</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Stock Opening</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title flex-column">
            <h2 class="mb-1">Riwayat Stock Opening</h2>
            <span class="text-muted fs-7">Input saldo awal produk per warehouse — sekali pakai saat go-live.</span>
        </div>
        <div class="card-toolbar">
            @if(auth()->user()?->hasPermission('stock_opening.create'))
                <a href="{{ route('stock_openings.create') }}" class="btn btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> Stock Opening Baru
                </a>
            @endif
        </div>
    </div>

    <div class="card-body">
        {{-- Filter --}}
        <form method="GET" action="{{ route('stock_openings.index') }}" class="row g-3 mb-5">
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Warehouse</label>
                <select name="warehouse_id" class="form-select form-select-sm form-select-solid" data-control="select2" data-placeholder="Semua">
                    <option value=""></option>
                    @foreach($warehouses as $w)
                        <option value="{{ $w->id }}" @selected($filters['warehouse_id']==$w->id)>{{ $w->code }} — {{ $w->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label fs-7 fw-semibold">Produk</label>
                <select name="product_id" class="form-select form-select-sm form-select-solid" data-control="select2" data-placeholder="Semua">
                    <option value=""></option>
                    @foreach($products as $p)
                        <option value="{{ $p->id }}" @selected($filters['product_id']==$p->id)>{{ $p->sku }} — {{ $p->name }}</option>
                    @endforeach
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
            <div class="col-md-2 d-flex align-items-end gap-2">
                <button type="submit" class="btn btn-sm btn-primary"><i class="ki-outline ki-filter fs-3"></i> Filter</button>
                @if(array_filter($filters))<a href="{{ route('stock_openings.index') }}" class="btn btn-sm btn-light">Reset</a>@endif
            </div>
        </form>

        {{-- Table --}}
        <div class="table-responsive">
            <table class="table table-row-bordered table-row-gray-100 align-middle gs-0 gy-3">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4">Tanggal</th>
                        <th>No. Dokumen</th>
                        <th>Warehouse</th>
                        <th>Produk</th>
                        <th>Batch</th>
                        <th class="text-end">Qty</th>
                        <th class="text-end">Cost</th>
                        <th>Oleh</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($movements as $m)
                        <tr>
                            <td class="ps-4">{{ $m->created_date?->format('d M Y H:i') }}</td>
                            <td><span class="fw-bold text-primary">{{ $m->movement_number }}</span></td>
                            <td>{{ $m->warehouse->code }} — {{ $m->warehouse->name }}</td>
                            <td>
                                <div class="fw-bold">{{ $m->product->sku }}</div>
                                <div class="text-muted fs-7">{{ $m->product->name }}</div>
                            </td>
                            <td>
                                @if($m->batch)
                                    <div class="fs-7">{{ $m->batch->batch_number }}</div>
                                    @if($m->batch->expiry_date)
                                        <div class="text-muted fs-8">Exp: {{ \Illuminate\Support\Carbon::parse($m->batch->expiry_date)->format('d M Y') }}</div>
                                    @endif
                                @else
                                    <span class="text-muted">—</span>
                                @endif
                            </td>
                            @php $q = (float)$m->quantity; $qFmt = floor($q)==$q ? number_format($q,0,',','.') : number_format($q,3,',','.'); @endphp
                            <td class="text-end fw-bold text-success">+{{ $qFmt }} <span class="text-muted fs-8">{{ $m->product->baseUom?->code ?? '' }}</span></td>
                            <td class="text-end">Rp {{ number_format((float)$m->cost_price, 0, ',', '.') }}</td>
                            <td class="fs-7">{{ $m->createdBy?->full_name ?? '—' }}</td>
                            <td class="text-end pe-4">
                                <a href="{{ route('stock_openings.show', $m) }}" class="btn btn-sm btn-light-info">
                                    <i class="ki-outline ki-eye fs-3"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="9" class="text-center text-muted py-10">Belum ada data stock opening.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">{{ $movements->links() }}</div>
    </div>
</div>
@endsection

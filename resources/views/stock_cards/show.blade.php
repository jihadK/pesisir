@extends('layouts.app')

@section('title', 'Kartu Stok — '.$product->sku)
@section('page_title', 'Kartu Stok')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Inventory</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('stock_cards.index') }}" class="text-muted">Kartu Stok</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $product->sku }}</li>
@endsection

@php
    // Helper format: kalau bilangan bulat → tanpa desimal; kalau pecahan → 3 desimal
    $fmt = function ($v) {
        $f = (float) $v;
        return floor($f) == $f
            ? number_format($f, 0, ',', '.')
            : number_format($f, 3, ',', '.');
    };
@endphp
@section('content')
<div class="row">
    <div class="col-md-4">
        {{-- Info Produk --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">{{ $product->sku }}</h3></div>
            <div class="card-body fs-7">
                <div class="d-flex flex-stack mb-2"><span class="text-muted">Nama:</span><span class="fw-bold text-end">{{ $product->name }}</span></div>
                <div class="d-flex flex-stack mb-2"><span class="text-muted">Kategori:</span><span class="fw-bold">{{ $product->category?->name ?? '—' }}</span></div>
                <div class="d-flex flex-stack mb-2"><span class="text-muted">Grade:</span><span class="fw-bold">{{ $product->grade?->code ?? '—' }}</span></div>
                <div class="d-flex flex-stack"><span class="text-muted">UoM:</span><span class="fw-bold">{{ $product->baseUom?->code ?? '—' }}</span></div>
            </div>
        </div>

        {{-- Saldo per Warehouse --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Saldo per Warehouse</h3></div>
            <div class="card-body">
                @if($balancesByWh->isEmpty())
                    <div class="text-center text-muted py-3">Belum ada saldo.</div>
                @else
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gy-2">
                            <thead><tr class="text-muted fs-7"><th>Warehouse</th><th class="text-end">Total</th><th class="text-end">Tersedia</th></tr></thead>
                            <tbody>
                            @foreach($balancesByWh as $b)
                                <tr>
                                    <td class="fs-7">{{ $b->code }}</td>
                                    <td class="text-end fw-bold">{{ $fmt($b->total_qty) }}</td>
                                    <td class="text-end text-success fw-bold">{{ $fmt((float)$b->total_qty - (float)$b->reserved_qty) }}</td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>

        {{-- Summary periode --}}
        <div class="card">
            <div class="card-header"><h3 class="card-title">Ringkasan Periode</h3></div>
            <div class="card-body">
                @php
                    $totalIn  = (float) ($summary->total_in  ?? 0);
                    $totalOut = (float) ($summary->total_out ?? 0);
                    $net      = $totalIn - $totalOut;
                @endphp
                <div class="d-flex flex-stack mb-3">
                    <span class="text-muted">Total Masuk:</span>
                    <span class="fw-bold text-success">+{{ $fmt($totalIn) }}</span>
                </div>
                <div class="d-flex flex-stack mb-3">
                    <span class="text-muted">Total Keluar:</span>
                    <span class="fw-bold text-danger">−{{ $fmt($totalOut) }}</span>
                </div>
                <div class="separator my-3"></div>
                <div class="d-flex flex-stack mb-3">
                    <span class="text-muted">Net:</span>
                    <span class="fw-bolder fs-3 {{ $net >= 0 ? 'text-success' : 'text-danger' }}">
                        {{ $net >= 0 ? '+' : '−' }}{{ $fmt(abs($net)) }}
                    </span>
                </div>
                <div class="d-flex flex-stack">
                    <span class="text-muted">Jumlah Mutasi:</span>
                    <span class="fw-bold">{{ $summary->movement_count ?? 0 }}</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-8">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Riwayat Mutasi</h3></div>
            <div class="card-body">
                {{-- Filter --}}
                <form method="GET" class="row g-3 mb-5">
                    <div class="col-md-4">
                        <label class="form-label fs-7 fw-semibold">Warehouse</label>
                        <select name="warehouse_id" class="form-select form-select-sm form-select-solid" data-control="select2" data-placeholder="Semua">
                            <option value=""></option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}" @selected($filters['warehouse_id']==$w->id)>{{ $w->code }} — {{ $w->name }}</option>
                            @endforeach
                        </select>
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
                        @if(array_filter($filters))<a href="{{ route('stock_cards.show', $product) }}" class="btn btn-sm btn-light">Reset</a>@endif
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-3">
                        <thead>
                            <tr class="fw-bold text-muted bg-light fs-7">
                                <th class="ps-4">Tanggal</th>
                                <th>No. Dokumen</th>
                                <th>Warehouse</th>
                                <th>Tipe</th>
                                <th>Batch</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Saldo</th>
                                <th>Catatan</th>
                            </tr>
                        </thead>
                        <tbody>
                        @forelse($movements as $m)
                            @php $isIn = (float)$m->quantity > 0; @endphp
                            <tr>
                                <td class="ps-4 fs-7">{{ $m->created_date?->format('d M Y H:i') }}</td>
                                <td><span class="fw-bold text-primary fs-7">{{ $m->movement_number }}</span></td>
                                <td class="fs-7">{{ $m->warehouse->code }}</td>
                                <td><span class="badge {{ $isIn ? 'badge-light-success' : 'badge-light-danger' }} fs-8">{{ $m->type_label }}</span></td>
                                <td class="fs-8 text-muted">{{ $m->batch?->batch_number ?? '—' }}</td>
                                @php
                                    $q = abs((float)$m->quantity);
                                    $b = (float)$m->balance_after;
                                    $qF = floor($q)==$q ? number_format($q,0,',','.') : number_format($q,3,',','.');
                                    $bF = floor($b)==$b ? number_format($b,0,',','.') : number_format($b,3,',','.');
                                @endphp
                                <td class="text-end fw-bold {{ $isIn ? 'text-success' : 'text-danger' }}">{{ $isIn ? '+' : '−' }}{{ $qF }}</td>
                                <td class="text-end fw-bold">{{ $bF }}</td>
                                <td class="fs-8 text-muted" style="max-width:200px">{{ \Illuminate\Support\Str::limit($m->notes, 50) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="text-center text-muted py-10">Belum ada mutasi.</td></tr>
                        @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">{{ $movements->links() }}</div>
            </div>
        </div>
    </div>
</div>
@endsection

@extends('layouts.app')

@section('title', 'Detail Stock Opening')
@section('page_title', 'Detail Stock Opening')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Inventory</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('stock_openings.index') }}" class="text-muted">Stock Opening</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $movement->movement_number }}</li>
@endsection

@php
    $qty       = (float) $movement->quantity;
    $balance   = (float) $movement->balance_after;
    $isInteger = floor($qty) == $qty;
    $isBalInt  = floor($balance) == $balance;
    $qtyFmt    = $isInteger ? number_format($qty, 0, ',', '.') : number_format($qty, 3, ',', '.');
    $balFmt    = $isBalInt  ? number_format($balance, 0, ',', '.') : number_format($balance, 3, ',', '.');
    $product   = $movement->product;
@endphp
@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">{{ $movement->movement_number }}</h3>
                <div class="card-toolbar">
                    <span class="badge badge-light-success">Stock Opening</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3"><div class="col-4 text-muted">Tanggal</div><div class="col-8 fw-bold">{{ $movement->created_date?->format('d M Y H:i') }}</div></div>
                <div class="row mb-3"><div class="col-4 text-muted">Warehouse</div><div class="col-8 fw-bold">{{ $movement->warehouse->code }} — {{ $movement->warehouse->name }}</div></div>
                <div class="row mb-3"><div class="col-4 text-muted">Produk</div><div class="col-8 fw-bold">{{ $product->sku }} — {{ $product->name }}</div></div>
                <div class="row mb-3">
                    <div class="col-4 text-muted">Qty</div>
                    <div class="col-8 fw-bold text-success fs-3">+{{ $qtyFmt }} {{ $movement->uom->code }}</div>
                </div>
                <div class="row mb-3"><div class="col-4 text-muted">Cost / pack</div><div class="col-8 fw-bold">Rp {{ number_format((float)$movement->cost_price, 0, ',', '.') }}</div></div>
                <div class="row mb-3"><div class="col-4 text-muted">Subtotal</div><div class="col-8 fw-bold">Rp {{ number_format((float)$movement->cost_price * $qty, 0, ',', '.') }}</div></div>
                <div class="row mb-3"><div class="col-4 text-muted">Saldo Setelahnya</div><div class="col-8 fw-bold">{{ $balFmt }} {{ $movement->uom->code }}</div></div>
                @if($movement->notes)
                    <div class="row mb-3"><div class="col-4 text-muted">Catatan</div><div class="col-8">{{ $movement->notes }}</div></div>
                @endif
                <div class="row"><div class="col-4 text-muted">Dibuat oleh</div><div class="col-8">{{ $movement->createdBy?->full_name ?? '—' }}</div></div>
            </div>
        </div>

        {{-- Spesifikasi Pack --}}
        @if($product->pack_content_label || $product->pack_weight_label)
            @php
                $totalContentMin = $product->pack_content_min ? (int)($qty * $product->pack_content_min) : null;
                $totalContentMax = $product->pack_content_max ? (int)($qty * $product->pack_content_max) : null;
                $totalWeightMin  = $product->pack_weight_min_g ? $qty * (float)$product->pack_weight_min_g : null;
                $totalWeightMax  = $product->pack_weight_max_g ? $qty * (float)$product->pack_weight_max_g : null;
            @endphp
            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Spesifikasi Pack</h3></div>
                <div class="card-body">
                    <div class="row mb-3"><div class="col-4 text-muted">Per Pack</div><div class="col-8">
                        @if($product->pack_content_label)
                            <span class="badge badge-light-info me-2">{{ $product->pack_content_label }}</span>
                        @endif
                        @if($product->pack_weight_label)
                            <span class="badge badge-light-warning">{{ $product->pack_weight_label }}</span>
                        @endif
                    </div></div>
                    @if($totalContentMin)
                        <div class="row mb-3">
                            <div class="col-4 text-muted">Total Isi (estimasi)</div>
                            <div class="col-8 fw-bold">
                                {{ $totalContentMin == $totalContentMax ? number_format($totalContentMin,0,',','.') : number_format($totalContentMin,0,',','.').'–'.number_format($totalContentMax,0,',','.') }}
                                {{ $product->pack_content_type }}
                            </div>
                        </div>
                    @endif
                    @if($totalWeightMin)
                        @php
                            $minKg = $totalWeightMin / 1000;
                            $maxKg = $totalWeightMax / 1000;
                        @endphp
                        <div class="row">
                            <div class="col-4 text-muted">Total Berat (estimasi)</div>
                            <div class="col-8 fw-bold">
                                @if($totalWeightMin == $totalWeightMax)
                                    {{ number_format($totalWeightMin, 0, ',', '.') }} g
                                    <span class="text-muted ms-2">({{ rtrim(rtrim(number_format($minKg, 3, ',', '.'), '0'), ',') }} kg)</span>
                                @else
                                    {{ number_format($totalWeightMin, 0, ',', '.') }}–{{ number_format($totalWeightMax, 0, ',', '.') }} g
                                    <span class="text-muted ms-2">({{ rtrim(rtrim(number_format($minKg, 3, ',', '.'), '0'), ',') }}–{{ rtrim(rtrim(number_format($maxKg, 3, ',', '.'), '0'), ',') }} kg)</span>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif
    </div>

    <div class="col-md-4">
        @if($movement->batch)
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Batch Info</h3></div>
            <div class="card-body fs-7">
                <div class="d-flex flex-stack mb-2"><span class="text-muted">Batch No:</span><span class="fw-bold">{{ $movement->batch->batch_number }}</span></div>
                <div class="d-flex flex-stack mb-2"><span class="text-muted">Received:</span><span class="fw-bold">{{ $movement->batch->received_date?->format('d M Y') }}</span></div>
                @if($movement->batch->production_date)<div class="d-flex flex-stack mb-2"><span class="text-muted">Production:</span><span class="fw-bold">{{ $movement->batch->production_date?->format('d M Y') }}</span></div>@endif
                @if($movement->batch->expiry_date)<div class="d-flex flex-stack mb-2"><span class="text-muted">Expiry:</span><span class="fw-bold">{{ $movement->batch->expiry_date?->format('d M Y') }}</span></div>@endif
                <div class="d-flex flex-stack"><span class="text-muted">Status:</span><span class="badge badge-light-info">{{ $movement->batch->quality_status }}</span></div>
            </div>
        </div>
        @endif

        <div class="card">
            <div class="card-header"><h3 class="card-title">Aksi</h3></div>
            <div class="card-body">
                <a href="{{ route('stock_openings.index') }}" class="btn btn-light w-100"><i class="ki-outline ki-arrow-left fs-2"></i> Kembali</a>
            </div>
        </div>
    </div>
</div>
@endsection

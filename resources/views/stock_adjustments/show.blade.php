@extends('layouts.app')

@section('title', 'Detail Adjustment')
@section('page_title', 'Detail Adjustment')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Inventory</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('stock_adjustments.index') }}" class="text-muted">Stock Adjustment</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $movement->movement_number }}</li>
@endsection

@section('content')
@php
    $qtyAbs    = abs((float) $movement->quantity);
    $balance   = (float) $movement->balance_after;
    $isIn      = (float)$movement->quantity > 0;
    $isInteger = floor($qtyAbs) == $qtyAbs;
    $isBalInt  = floor($balance) == $balance;
    $qtyFmt    = $isInteger ? number_format($qtyAbs, 0, ',', '.') : number_format($qtyAbs, 3, ',', '.');
    $balFmt    = $isBalInt  ? number_format($balance, 0, ',', '.') : number_format($balance, 3, ',', '.');
    $product   = $movement->product;
@endphp
<div class="row">
    <div class="col-md-8">
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">{{ $movement->movement_number }}</h3>
                <div class="card-toolbar">
                    <span class="badge {{ $isIn ? 'badge-light-success' : 'badge-light-danger' }} fs-7">{{ $movement->type_label }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3"><div class="col-4 text-muted">Tanggal</div><div class="col-8 fw-bold">{{ $movement->created_date?->format('d M Y H:i') }}</div></div>
                <div class="row mb-3"><div class="col-4 text-muted">Warehouse</div><div class="col-8 fw-bold">{{ $movement->warehouse->code }} — {{ $movement->warehouse->name }}</div></div>
                <div class="row mb-3"><div class="col-4 text-muted">Produk</div><div class="col-8 fw-bold">{{ $movement->product->sku }} — {{ $movement->product->name }}</div></div>
                @if($movement->batch)
                    <div class="row mb-3"><div class="col-4 text-muted">Batch</div><div class="col-8 fw-bold">{{ $movement->batch->batch_number }}</div></div>
                @endif
                <div class="row mb-3">
                    <div class="col-4 text-muted">Qty</div>
                    <div class="col-8 fw-bold {{ $isIn ? 'text-success' : 'text-danger' }} fs-3">
                        {{ $isIn ? '+' : '−' }}{{ $qtyFmt }} {{ $movement->uom->code }}
                    </div>
                </div>
                @if($product->pack_content_label || $product->pack_weight_label)
                    <div class="row mb-3">
                        <div class="col-4 text-muted">Spesifikasi / Pack</div>
                        <div class="col-8">
                            @if($product->pack_content_label)<span class="badge badge-light-info me-2">{{ $product->pack_content_label }}</span>@endif
                            @if($product->pack_weight_label)<span class="badge badge-light-warning">{{ $product->pack_weight_label }}</span>@endif
                        </div>
                    </div>
                @endif
                <div class="row mb-3"><div class="col-4 text-muted">Saldo Setelahnya</div><div class="col-8 fw-bold">{{ $balFmt }} {{ $movement->uom->code }}</div></div>
                <div class="row mb-3"><div class="col-4 text-muted">Catatan</div><div class="col-8">{{ $movement->notes }}</div></div>
                <div class="row"><div class="col-4 text-muted">Dibuat oleh</div><div class="col-8">{{ $movement->createdBy?->full_name ?? '—' }}</div></div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Aksi</h3></div>
            <div class="card-body d-flex flex-column gap-2">
                <a href="{{ route('stock_adjustments.index') }}" class="btn btn-light"><i class="ki-outline ki-arrow-left fs-2"></i> Kembali</a>
                <a href="{{ route('stock_cards.show', $movement->product) }}?warehouse_id={{ $movement->warehouse_id }}" class="btn btn-light-info">
                    <i class="ki-outline ki-questionnaire-tablet fs-2"></i> Lihat Kartu Stok Produk
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

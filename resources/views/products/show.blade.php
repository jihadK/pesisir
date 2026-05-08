@extends('layouts.app')

@section('title', 'Detail Produk')
@section('page_title', $product->name)
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('products.index') }}" class="text-muted text-hover-primary">Produk</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $product->sku }}</li>
@endsection

@php
    $movementBadge = function ($type) {
        return match (true) {
            str_starts_with($type, 'in_')  => 'badge-light-success',
            str_starts_with($type, 'out_') => 'badge-light-danger',
            default                        => 'badge-light',
        };
    };
    $statusBadge = function ($status) {
        return match ($status) {
            'received', 'invoiced', 'delivered'  => 'badge-light-success',
            'partial'                             => 'badge-light-warning',
            'submitted', 'confirmed'              => 'badge-light-info',
            'cancelled', 'rejected'               => 'badge-light-danger',
            'draft'                               => 'badge-light',
            default                               => 'badge-light-secondary',
        };
    };
@endphp

@section('content')

{{-- ========== PROFILE CARD ========== --}}
<div class="card mb-5">
    <div class="card-body py-5">
        <div class="d-flex flex-wrap flex-sm-nowrap">
            {{-- Image --}}
            <div class="me-7 mb-4">
                <div class="symbol symbol-200px symbol-fixed">
                    <img src="{{ $product->image_display_url }}" alt="{{ $product->name }}" class="object-fit-cover rounded" style="width:200px;height:200px" />
                </div>
            </div>

            {{-- Info --}}
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-3">
                    <div>
                        <div class="d-flex align-items-center mb-2">
                            <span class="text-gray-900 fs-2 fw-bold me-3">{{ $product->name }}</span>
                            @if($product->grade)
                                <span class="badge fw-bold me-2" style="background:{{ $product->grade->color ?? '#6c757d' }};color:#fff">Grade {{ $product->grade->code }}</span>
                            @endif
                            @if($product->is_perishable)
                                <span class="badge badge-light-warning me-2"><i class="ki-outline ki-time fs-7"></i> Perishable</span>
                            @endif
                            <span class="badge badge-light-{{ $product->is_active ? 'success' : 'danger' }}">
                                {{ $product->is_active ? 'Aktif' : 'Non-aktif' }}
                            </span>
                        </div>
                        @if($product->scientific_name)
                            <div class="text-gray-600 fst-italic fs-6 mb-2">{{ $product->scientific_name }}</div>
                        @endif
                        <div class="d-flex flex-wrap fs-7 text-muted gap-3 mb-3">
                            <span><i class="ki-outline ki-barcode fs-6 me-1"></i><strong>{{ $product->sku }}</strong></span>
                            @if($product->barcode)<span><i class="ki-outline ki-scan-barcode fs-6 me-1"></i>{{ $product->barcode }}</span>@endif
                            @if($product->category)<span><i class="ki-outline ki-folder fs-6 me-1"></i>{{ $product->category->name }}</span>@endif
                            @if($product->baseUom)<span><i class="ki-outline ki-element-equal fs-6 me-1"></i>UoM: {{ $product->baseUom->code }}</span>@endif
                            @if($product->origin)<span><i class="ki-outline ki-geolocation fs-6 me-1"></i>{{ $product->origin }}</span>@endif
                        </div>
                    </div>
                    <a href="{{ route('products.edit', $product) }}" class="btn btn-light-primary btn-sm">
                        <i class="ki-outline ki-pencil fs-3"></i> Edit Produk
                    </a>
                </div>

                {{-- Stat boxes --}}
                <div class="d-flex flex-wrap gap-3 mb-3">
                    <div class="border border-gray-300 border-dashed rounded min-w-130px py-3 px-4">
                        <div class="fs-2 fw-bolder">{{ number_format($stats['stock_total'], 3) }}</div>
                        <div class="text-muted fs-7">Total Stock ({{ $product->baseUom?->code }})</div>
                    </div>
                    <div class="border border-gray-300 border-dashed rounded min-w-130px py-3 px-4">
                        <div class="fs-2 fw-bolder text-success">{{ number_format($stats['stock_available'], 3) }}</div>
                        <div class="text-muted fs-7">Available</div>
                    </div>
                    <div class="border border-gray-300 border-dashed rounded min-w-130px py-3 px-4">
                        <div class="fs-2 fw-bolder text-warning">{{ number_format($stats['stock_reserved'], 3) }}</div>
                        <div class="text-muted fs-7">Reserved</div>
                    </div>
                    <div class="border border-gray-300 border-dashed rounded min-w-130px py-3 px-4">
                        <div class="fs-2 fw-bolder">{{ $stats['active_batches'] }}</div>
                        <div class="text-muted fs-7">Batch Aktif</div>
                    </div>
                    <div class="border border-gray-300 border-dashed rounded min-w-130px py-3 px-4">
                        <div class="fs-2 fw-bolder">{{ number_format($stats['total_sold'], 3) }}</div>
                        <div class="text-muted fs-7">Total Terjual</div>
                    </div>
                    <div class="border border-gray-300 border-dashed rounded min-w-130px py-3 px-4">
                        <div class="fs-3 fw-bold">{{ $stats['last_received'] ? \Carbon\Carbon::parse($stats['last_received'])->format('d M Y') : '—' }}</div>
                        <div class="text-muted fs-7">Diterima Terakhir</div>
                    </div>
                </div>

                @if($product->description)
                    <div class="text-gray-700 fs-7">{{ $product->description }}</div>
                @endif
            </div>
        </div>
    </div>
</div>

{{-- ========== INFO TAMBAHAN ========== --}}
<div class="row g-5 mb-5">
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted fs-7 text-uppercase mb-2">Penyimpanan</div>
                <div class="d-flex flex-stack mb-1"><span>Suhu:</span><strong>
                    @if($product->storage_temp_min !== null || $product->storage_temp_max !== null)
                        {{ $product->storage_temp_min ?? '—' }}°C ~ {{ $product->storage_temp_max ?? '—' }}°C
                    @else <span class="text-muted">N/A</span> @endif
                </strong></div>
                <div class="d-flex flex-stack"><span>Umur Simpan:</span><strong>{{ $product->shelf_life_days ? $product->shelf_life_days.' hari' : '—' }}</strong></div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted fs-7 text-uppercase mb-2">Stock Level</div>
                <div class="d-flex flex-stack mb-1"><span>Min:</span><strong>{{ $product->min_stock_level ? number_format($product->min_stock_level, 3) : '—' }}</strong></div>
                <div class="d-flex flex-stack mb-1"><span>Max:</span><strong>{{ $product->max_stock_level ? number_format($product->max_stock_level, 3) : '—' }}</strong></div>
                <div class="d-flex flex-stack">
                    <span>Status:</span>
                    @if($product->is_stock_low)
                        <span class="badge badge-light-danger fw-bold"><i class="ki-outline ki-warning"></i> Stock Low</span>
                    @else
                        <span class="badge badge-light-success">OK</span>
                    @endif
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="text-muted fs-7 text-uppercase mb-2">Harga Default</div>
                <div class="d-flex flex-stack mb-1"><span>Cost:</span><strong>Rp {{ number_format((float)$product->default_cost_price, 0, ',', '.') }}</strong></div>
                <div class="d-flex flex-stack mb-1"><span>Sell:</span><strong>Rp {{ number_format((float)$product->default_sell_price, 0, ',', '.') }}</strong></div>
                <div class="d-flex flex-stack">
                    <span>Margin:</span>
                    <strong class="text-{{ ($product->margin_percent ?? 0) > 0 ? 'success' : 'muted' }}">
                        {{ $product->margin_percent !== null ? $product->margin_percent.'%' : '—' }}
                    </strong>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ========== TAB INTERFACE ========== --}}
<div class="card">
    <div class="card-header card-header-stretchy">
        <ul class="nav nav-tabs nav-line-tabs nav-line-tabs-2x border-transparent fs-6 fw-bold flex-nowrap text-nowrap" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-stock"><i class="ki-outline ki-package fs-3 me-1"></i> Stock per Gudang ({{ $stockByWh->count() }})</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-batch"><i class="ki-outline ki-element-7 fs-3 me-1"></i> Batch Aktif ({{ $activeBatches->count() }})</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-card"><i class="ki-outline ki-arrow-right-left fs-3 me-1"></i> Kartu Stock</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-price"><i class="ki-outline ki-tag fs-3 me-1"></i> Harga per Tier</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-history"><i class="ki-outline ki-handcart fs-3 me-1"></i> Riwayat PO/SO</a></li>
        </ul>
    </div>
    <div class="card-body">
        <div class="tab-content">

            {{-- ===== TAB 1: Stock per Gudang ===== --}}
            <div class="tab-pane fade show active" id="tab-stock" role="tabpanel">
                @if($stockByWh->isEmpty())
                    <div class="text-center text-muted py-10">
                        <i class="ki-outline ki-package fs-3hx text-gray-300"></i>
                        <div class="mt-3">Produk ini belum punya stock di gudang manapun.</div>
                    </div>
                @else
                    <table class="table table-row-bordered table-row-gray-200 align-middle gy-3 gs-0">
                        <thead><tr class="text-gray-500 fw-bold fs-7 text-uppercase">
                            <th>Kode</th>
                            <th>Nama Gudang</th>
                            <th>Tipe</th>
                            <th class="text-end">Total</th>
                            <th class="text-end">Reserved</th>
                            <th class="text-end">Available</th>
                        </tr></thead>
                        <tbody>
                            @foreach($stockByWh as $wh)
                                <tr>
                                    <td><span class="fw-bold text-gray-900">{{ $wh->code }}</span></td>
                                    <td>{{ $wh->name }}</td>
                                    <td><span class="badge badge-light fs-8">{{ $wh->type }}</span></td>
                                    <td class="text-end fw-bold">{{ number_format($wh->total_qty, 3) }}</td>
                                    <td class="text-end text-warning">{{ number_format($wh->reserved_qty, 3) }}</td>
                                    <td class="text-end text-success fw-bold">{{ number_format($wh->available_qty, 3) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="border-top">
                            <tr class="fw-bold fs-6">
                                <td colspan="3" class="text-end">TOTAL</td>
                                <td class="text-end">{{ number_format($stockByWh->sum('total_qty'), 3) }}</td>
                                <td class="text-end text-warning">{{ number_format($stockByWh->sum('reserved_qty'), 3) }}</td>
                                <td class="text-end text-success">{{ number_format($stockByWh->sum('available_qty'), 3) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                @endif
            </div>

            {{-- ===== TAB 2: Batch Aktif ===== --}}
            <div class="tab-pane fade" id="tab-batch" role="tabpanel">
                @if($activeBatches->isEmpty())
                    <div class="text-center text-muted py-10">
                        <i class="ki-outline ki-element-7 fs-3hx text-gray-300"></i>
                        <div class="mt-3">Belum ada batch aktif.</div>
                    </div>
                @else
                    <div class="alert alert-light-info py-2 mb-3 fs-7"><i class="ki-outline ki-information-5 fs-3 me-1"></i>
                        Diurutkan FEFO (First Expired First Out) — yang paling cepat expire dipakai dulu.
                    </div>
                    <table class="table table-row-bordered table-row-gray-200 align-middle gy-3 gs-0">
                        <thead><tr class="text-gray-500 fw-bold fs-7 text-uppercase">
                            <th>Batch #</th>
                            <th>Supplier</th>
                            <th>Diterima</th>
                            <th>Expiry</th>
                            <th class="text-end">Initial</th>
                            <th class="text-end">Sisa</th>
                            <th>Quality</th>
                            <th class="text-end">Cost</th>
                        </tr></thead>
                        <tbody>
                            @foreach($activeBatches as $b)
                                @php
                                    $expDays = $b->expiry_date ? \Carbon\Carbon::today()->diffInDays(\Carbon\Carbon::parse($b->expiry_date), false) : null;
                                    $expColor = $expDays === null ? '' : ($expDays < 0 ? 'text-danger' : ($expDays <= 7 ? 'text-warning' : ($expDays <= 30 ? 'text-info' : 'text-muted')));
                                @endphp
                                <tr>
                                    <td><span class="fw-bold text-gray-900">{{ $b->batch_number }}</span></td>
                                    <td class="fs-7">{{ $b->supplier_name ?? '—' }}</td>
                                    <td class="fs-7">{{ \Carbon\Carbon::parse($b->received_date)->format('d M Y') }}</td>
                                    <td class="{{ $expColor }} fs-7">
                                        @if($b->expiry_date)
                                            {{ \Carbon\Carbon::parse($b->expiry_date)->format('d M Y') }}
                                            @if($expDays < 0)<span class="badge badge-light-danger ms-1 fs-9">Expired {{ abs($expDays) }}h</span>
                                            @elseif($expDays <= 7)<span class="badge badge-light-warning ms-1 fs-9">{{ $expDays }}h lagi</span>
                                            @elseif($expDays <= 30)<span class="badge badge-light-info ms-1 fs-9">{{ $expDays }}h</span>
                                            @endif
                                        @else <span class="text-muted">—</span> @endif
                                    </td>
                                    <td class="text-end fs-7">{{ number_format($b->initial_quantity, 3) }}</td>
                                    <td class="text-end fw-bold">{{ number_format($b->remaining_quantity, 3) }}</td>
                                    <td>
                                        @php $qBadge = match($b->quality_status) { 'fresh' => 'success', 'good' => 'info', 'near_expiry' => 'warning', 'expired', 'damaged' => 'danger', default => 'secondary' }; @endphp
                                        <span class="badge badge-light-{{ $qBadge }} fs-8">{{ $b->quality_status }}</span>
                                    </td>
                                    <td class="text-end fs-7">Rp {{ number_format((float)$b->cost_price, 0, ',', '.') }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- ===== TAB 3: Kartu Stock ===== --}}
            <div class="tab-pane fade" id="tab-card" role="tabpanel">
                @if($recentMovements->isEmpty())
                    <div class="text-center text-muted py-10">
                        <i class="ki-outline ki-arrow-right-left fs-3hx text-gray-300"></i>
                        <div class="mt-3">Belum ada pergerakan stock.</div>
                    </div>
                @else
                    <table class="table table-row-bordered table-row-gray-200 align-middle gy-3 gs-0">
                        <thead><tr class="text-gray-500 fw-bold fs-7 text-uppercase">
                            <th>No. Mutasi</th>
                            <th>Tanggal</th>
                            <th>Tipe</th>
                            <th>Gudang</th>
                            <th>Batch</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Saldo</th>
                            <th>User</th>
                        </tr></thead>
                        <tbody>
                            @foreach($recentMovements as $m)
                                <tr>
                                    <td class="fs-7"><span class="text-muted">{{ $m->movement_number }}</span></td>
                                    <td class="fs-7">{{ \Carbon\Carbon::parse($m->created_date)->format('d M Y H:i') }}</td>
                                    <td><span class="badge {{ $movementBadge($m->movement_type) }} fs-8">{{ $m->movement_type }}</span></td>
                                    <td class="fs-7">{{ $m->warehouse_code }}</td>
                                    <td class="fs-7">{{ $m->batch_number ?? '—' }}</td>
                                    <td class="text-end fw-bold {{ $m->quantity < 0 ? 'text-danger' : 'text-success' }}">
                                        {{ ($m->quantity > 0 ? '+' : '') . number_format($m->quantity, 3) }}
                                    </td>
                                    <td class="text-end fs-7">{{ $m->balance_after ? number_format($m->balance_after, 3) : '—' }}</td>
                                    <td class="fs-7 text-muted">{{ $m->created_by_name ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- ===== TAB 4: Harga per Tier ===== --}}
            <div class="tab-pane fade" id="tab-price" role="tabpanel">
                @if($pricesPerTier->isEmpty())
                    <div class="text-center text-muted py-10">
                        <i class="ki-outline ki-tag fs-3hx text-gray-300"></i>
                        <div class="mt-3">
                            Belum ada harga khusus per tier untuk produk ini.<br>
                            <span class="fs-7">Harga jual default: <strong>Rp {{ number_format((float)$product->default_sell_price, 0, ',', '.') }}</strong> akan dipakai untuk semua tier.</span>
                        </div>
                        <div class="text-muted fs-8 mt-3">Modul Pricing per Tier akan dibangun di Sub-phase 3.3.</div>
                    </div>
                @else
                    <table class="table table-row-bordered table-row-gray-200 align-middle gy-3 gs-0">
                        <thead><tr class="text-gray-500 fw-bold fs-7 text-uppercase">
                            <th>Tier</th>
                            <th class="text-end">Harga</th>
                            <th class="text-end">Min Qty</th>
                            <th>Berlaku Mulai</th>
                            <th>Berlaku Sampai</th>
                        </tr></thead>
                        <tbody>
                            @foreach($pricesPerTier as $pp)
                                <tr>
                                    <td><span class="badge badge-light-primary">{{ $pp->tier_name }}</span></td>
                                    <td class="text-end fw-bold">Rp {{ number_format((float)$pp->price, 0, ',', '.') }}</td>
                                    <td class="text-end fs-7">{{ number_format($pp->min_quantity, 3) }}</td>
                                    <td class="fs-7">{{ \Carbon\Carbon::parse($pp->effective_from)->format('d M Y') }}</td>
                                    <td class="fs-7">{{ $pp->effective_to ? \Carbon\Carbon::parse($pp->effective_to)->format('d M Y') : '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
            </div>

            {{-- ===== TAB 5: Riwayat PO/SO ===== --}}
            <div class="tab-pane fade" id="tab-history" role="tabpanel">
                <div class="row g-5">
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="ki-outline ki-handcart fs-3 me-1"></i> Pembelian (PO)</h5>
                        @if($purchaseHistory->isEmpty())
                            <div class="text-center text-muted py-10 border border-dashed rounded">
                                <i class="ki-outline ki-handcart fs-3x text-gray-300"></i>
                                <div class="mt-2 fs-7">Belum ada pembelian.</div>
                            </div>
                        @else
                            <table class="table table-row-bordered table-row-gray-200 align-middle gy-3 gs-0">
                                <thead><tr class="text-gray-500 fw-bold fs-8 text-uppercase">
                                    <th>No. PO</th><th>Supplier</th><th>Tgl</th>
                                    <th class="text-end">Qty</th><th>Status</th>
                                </tr></thead>
                                <tbody class="fs-7">
                                    @foreach($purchaseHistory as $po)
                                        <tr>
                                            <td class="fw-bold">{{ $po->po_number }}</td>
                                            <td>{{ $po->supplier_name }}</td>
                                            <td>{{ \Carbon\Carbon::parse($po->po_date)->format('d M Y') }}</td>
                                            <td class="text-end">{{ number_format($po->received_quantity, 0) }} / {{ number_format($po->quantity, 0) }}</td>
                                            <td><span class="badge {{ $statusBadge($po->status) }} fs-8">{{ $po->status }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <h5 class="mb-3"><i class="ki-outline ki-basket fs-3 me-1"></i> Penjualan (SO)</h5>
                        @if($salesHistory->isEmpty())
                            <div class="text-center text-muted py-10 border border-dashed rounded">
                                <i class="ki-outline ki-basket fs-3x text-gray-300"></i>
                                <div class="mt-2 fs-7">Belum ada penjualan.</div>
                            </div>
                        @else
                            <table class="table table-row-bordered table-row-gray-200 align-middle gy-3 gs-0">
                                <thead><tr class="text-gray-500 fw-bold fs-8 text-uppercase">
                                    <th>No. SO</th><th>Customer</th><th>Tgl</th>
                                    <th class="text-end">Qty</th><th>Status</th>
                                </tr></thead>
                                <tbody class="fs-7">
                                    @foreach($salesHistory as $so)
                                        <tr>
                                            <td class="fw-bold">{{ $so->so_number }}</td>
                                            <td>{{ $so->customer_name }}</td>
                                            <td>{{ \Carbon\Carbon::parse($so->order_date)->format('d M Y') }}</td>
                                            <td class="text-end">{{ number_format($so->delivered_quantity, 0) }} / {{ number_format($so->quantity, 0) }}</td>
                                            <td><span class="badge {{ $statusBadge($so->status) }} fs-8">{{ $so->status }}</span></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection

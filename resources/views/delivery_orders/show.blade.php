@extends('layouts.app')

@section('title', 'Detail DO ' . $do->do_number)
@section('page_title', 'Detail Delivery Order')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Penjualan</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('delivery_orders.index') }}" class="text-muted">Delivery Order</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $do->do_number }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">{{ $do->do_number }}</h3>
                <div class="card-toolbar">
                    <span class="badge {{ $do->status_badge }} fs-6">{{ $do->status_label }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3"><div class="col-3 text-muted">Tanggal Kirim</div><div class="col-9 fw-bold">{{ $do->delivery_date?->format('d M Y') }}</div></div>
                <div class="row mb-3">
                    <div class="col-3 text-muted">Sales Order</div>
                    <div class="col-9">
                        @if($do->salesOrder)
                            <a href="{{ route('sales_orders.show', $do->salesOrder) }}" class="fw-bold">{{ $do->salesOrder->so_number }}</a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                </div>
                <div class="row mb-3"><div class="col-3 text-muted">Customer</div><div class="col-9 fw-bold">{{ $do->customer->code }} — {{ $do->customer->name }}</div></div>
                <div class="row mb-3"><div class="col-3 text-muted">Warehouse Asal</div><div class="col-9 fw-bold">{{ $do->warehouse->code }} — {{ $do->warehouse->name }}</div></div>
                <div class="row mb-3"><div class="col-3 text-muted">Driver / Kendaraan</div><div class="col-9">{{ $do->driver_name ?? '—' }} {{ $do->vehicle_no ? '· '.$do->vehicle_no : '' }}</div></div>
                @if($do->delivered_at)
                    <div class="row mb-3"><div class="col-3 text-muted">Shipped pada</div><div class="col-9 fw-bold">{{ $do->delivered_at->format('d M Y H:i') }}</div></div>
                    <div class="row mb-3"><div class="col-3 text-muted">Diterima oleh</div><div class="col-9">{{ $do->received_by_name ?? '—' }}</div></div>
                @endif
                @if($do->notes)
                    <div class="row"><div class="col-3 text-muted">Catatan</div><div class="col-9">{{ $do->notes }}</div></div>
                @endif
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Item</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-2">
                        <thead>
                            <tr class="fw-bold text-muted bg-light fs-7">
                                <th class="ps-4">Produk</th>
                                <th>Batch</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end pe-4">Harga</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($do->items as $item)
                            @php $q = (float)$item->quantity; $qF = floor($q)==$q ? number_format($q,0,',','.') : number_format($q,3,',','.'); @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $item->product->sku }}</div>
                                    <div class="text-muted fs-7">{{ $item->product->name }}</div>
                                </td>
                                <td class="fs-7">
                                    @if($item->batch)
                                        {{ $item->batch->batch_number }}
                                        @if($item->batch->expiry_date)<div class="text-muted fs-8">Exp: {{ \Illuminate\Support\Carbon::parse($item->batch->expiry_date)->format('d M Y') }}</div>@endif
                                    @else
                                        <span class="text-muted">—</span>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">{{ $qF }} {{ $item->uom->code }}</td>
                                <td class="text-end pe-4">Rp {{ number_format((float)$item->unit_price, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Aksi</h3></div>
            <div class="card-body d-flex flex-column gap-2">
                @if(auth()->user()?->hasPermission('delivery_order.print'))
                    <a href="{{ route('delivery_orders.print', $do) }}" target="_blank" class="btn btn-light-primary">
                        <i class="ki-outline ki-printer fs-2"></i> Cetak Surat Jalan
                    </a>
                @endif

                @if($do->isShippable() && auth()->user()?->hasPermission('delivery_order.ship'))
                    <button type="button" class="btn btn-success" data-bs-toggle="modal" data-bs-target="#modal_ship">
                        <i class="ki-outline ki-check-circle fs-2"></i> Ship (Konfirmasi Kirim)
                    </button>
                @endif

                @if(in_array($do->status, [\App\Models\DeliveryOrder::STATUS_SHIPPED, \App\Models\DeliveryOrder::STATUS_DELIVERED]) && auth()->user()?->hasPermission('invoice.create'))
                    @php
                        $existingInvoice = \App\Models\Invoice::where('do_id', $do->id)
                            ->whereNotIn('status', [\App\Models\Invoice::STATUS_CANCELLED, \App\Models\Invoice::STATUS_VOID])
                            ->first();
                    @endphp
                    @if($existingInvoice)
                        <a href="{{ route('invoices.show', $existingInvoice) }}" class="btn btn-light-success">
                            <i class="ki-outline ki-bill fs-2"></i> Lihat Invoice ({{ $existingInvoice->invoice_number }})
                        </a>
                    @else
                        <form method="POST" action="{{ route('invoices.create-from-do', $do) }}">
                            @csrf
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="ki-outline ki-bill fs-2"></i> Generate Invoice
                            </button>
                        </form>
                    @endif
                @endif

                @if($do->isCancellable() && auth()->user()?->hasPermission('delivery_order.cancel'))
                    <form method="POST" action="{{ route('delivery_orders.cancel', $do) }}" onsubmit="return confirm('Batalkan DO ini?')">
                        @csrf
                        <button type="submit" class="btn btn-light-danger w-100">
                            <i class="ki-outline ki-cross-circle fs-2"></i> Cancel
                        </button>
                    </form>
                @endif

                <a href="{{ route('delivery_orders.index') }}" class="btn btn-light">
                    <i class="ki-outline ki-arrow-left fs-2"></i> Kembali
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Info</h3></div>
            <div class="card-body fs-7">
                <div class="d-flex flex-stack mb-2"><span class="text-muted">Dibuat oleh:</span><span class="fw-bold">{{ $do->createdBy?->full_name ?? '—' }}</span></div>
                <div class="d-flex flex-stack mb-2"><span class="text-muted">Tgl Dibuat:</span><span class="fw-bold">{{ $do->created_date?->format('d M Y H:i') }}</span></div>
                @if($do->updated_date)
                    <div class="d-flex flex-stack"><span class="text-muted">Tgl Update:</span><span class="fw-bold">{{ $do->updated_date?->format('d M Y H:i') }}</span></div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($do->isShippable() && auth()->user()?->hasPermission('delivery_order.ship'))
<div class="modal fade" id="modal_ship" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('delivery_orders.ship', $do) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Konfirmasi Ship DO</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-light-warning fs-7">
                    <i class="ki-outline ki-information fs-3 me-1"></i>
                    Setelah klik <strong>Ship</strong>: stok akan dikurangi permanen dari gudang, status SO terupdate (partial/delivered). Aksi ini <strong>tidak bisa di-undo</strong>.
                </div>
                <label class="form-label fw-semibold">Diterima oleh (opsional)</label>
                <input type="text" name="received_by_name" class="form-control form-control-solid"
                       placeholder="Nama penerima di lokasi customer" maxlength="100" />
                <div class="form-text">Bisa diisi nanti setelah customer konfirmasi terima.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success">
                    <i class="ki-outline ki-check fs-2"></i> Ship Sekarang
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

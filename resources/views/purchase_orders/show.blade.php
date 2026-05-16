@extends('layouts.app')

@section('title', 'Detail PO ' . $po->po_number)
@section('page_title', 'Detail Purchase Order')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Pembelian</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('purchase_orders.index') }}" class="text-muted">Purchase Order</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $po->po_number }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">{{ $po->po_number }}</h3>
                <div class="card-toolbar"><span class="badge {{ $po->status_badge }} fs-6">{{ $po->status_label }}</span></div>
            </div>
            <div class="card-body">
                <div class="row mb-3"><div class="col-3 text-muted">Tanggal Pembelian</div><div class="col-9 fw-bold">{{ $po->po_date?->format('d M Y') }}</div></div>
                <div class="row mb-3"><div class="col-3 text-muted">Rencana Belanja</div><div class="col-9 fw-bold">{{ $po->expected_date?->format('d M Y') ?? '—' }}</div></div>
                <div class="row mb-3"><div class="col-3 text-muted">Supplier</div><div class="col-9 fw-bold">{{ $po->supplier->code }} — {{ $po->supplier->name }}</div></div>
                @if($po->notes)
                    <div class="row"><div class="col-3 text-muted">Catatan</div><div class="col-9">{{ $po->notes }}</div></div>
                @endif
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Item Raw dari Supplier</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-2" style="min-width:680px">
                        <thead><tr class="fw-bold text-muted bg-light fs-7">
                            <th class="ps-4">Sub-Kategori</th>
                            <th class="text-end">Qty (gram)</th>
                            <th class="text-end">Qty (kg)</th>
                            <th class="text-end">Harga/Kg</th>
                            <th class="text-end">Diskon</th>
                            <th class="text-end pe-4">Subtotal</th>
                        </tr></thead>
                        <tbody>
                        @foreach($po->items as $item)
                            @php $kg = (float)$item->qty_gram / 1000; @endphp
                            <tr>
                                <td class="ps-4 fw-bold">{{ $item->category->parent?->name }}{{ $item->category->parent ? ' › ' : '' }}{{ $item->category->name }}</td>
                                <td class="text-end">{{ number_format((float)$item->qty_gram, 0, ',', '.') }} g</td>
                                <td class="text-end fw-bold">{{ rtrim(rtrim(number_format($kg, 3, ',', '.'), '0'), ',') }} kg</td>
                                <td class="text-end">{{ number_format((float)$item->price_per_kg, 0, ',', '.') }}</td>
                                <td class="text-end">{{ number_format((float)($item->discount_amount ?? 0), 0, ',', '.') }}</td>
                                <td class="text-end pe-4 fw-bold">{{ number_format((float)$item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="fw-bold">
                            <tr><td colspan="5" class="text-end fs-4">TOTAL</td><td class="text-end pe-4 fs-4 text-primary">Rp {{ number_format((float)$po->total_amount, 0, ',', '.') }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <div class="alert alert-light-info fs-7">
            <i class="ki-outline ki-information fs-3 me-1"></i>
            Jasa bersih ikan & pembelian lain-lain dicatat terpisah di menu
            <a href="{{ route('cleaning_services.index') }}">Jasa Bersih Ikan</a> dan
            <a href="{{ route('supplies_purchases.index') }}">Pembelian Lain-lain</a>.
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Aksi</h3></div>
            <div class="card-body d-flex flex-column gap-2">
                @if(auth()->user()?->hasPermission('purchase_order.print'))
                    <a href="{{ route('purchase_orders.print', $po) }}" target="_blank" class="btn btn-light-primary"><i class="ki-outline ki-printer fs-2"></i> Cetak PO</a>
                @endif
                @if($po->isEditable() && auth()->user()?->hasPermission('purchase_order.update'))
                    <a href="{{ route('purchase_orders.edit', $po) }}" class="btn btn-light-warning"><i class="ki-outline ki-pencil fs-2"></i> Edit</a>
                @endif
                @if($po->isMarkPaidable() && auth()->user()?->hasPermission('purchase_order.mark_paid'))
                    <form method="POST" action="{{ route('purchase_orders.mark-paid', $po) }}" onsubmit="return confirm('Tandai Belanja ini sebagai Terbayar? Barang sudah diterima &amp; dibayar ke supplier.')">@csrf
                        <button type="submit" class="btn btn-success w-100"><i class="ki-outline ki-wallet fs-2"></i> Tandai Terbayar</button>
                    </form>
                @endif
                @if($po->isCancellable() && auth()->user()?->hasPermission('purchase_order.cancel'))
                    <form method="POST" action="{{ route('purchase_orders.cancel', $po) }}" onsubmit="return confirm('Batalkan Belanja ini?')">@csrf
                        <button type="submit" class="btn btn-light-danger w-100"><i class="ki-outline ki-cross-circle fs-2"></i> Cancel</button>
                    </form>
                @endif
                <a href="{{ route('purchase_orders.index') }}" class="btn btn-light"><i class="ki-outline ki-arrow-left fs-2"></i> Kembali</a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Info</h3></div>
            <div class="card-body fs-7">
                <div class="d-flex flex-stack mb-2"><span class="text-muted">Dibuat:</span><span class="fw-bold">{{ $po->createdBy?->full_name ?? '—' }}</span></div>
                <div class="d-flex flex-stack mb-2"><span class="text-muted">Tgl Buat:</span><span class="fw-bold">{{ $po->created_date?->format('d M Y H:i') }}</span></div>
                @if($po->approvedBy)
                    <div class="d-flex flex-stack"><span class="text-muted">Disubmit oleh:</span><span class="fw-bold">{{ $po->approvedBy->full_name }}</span></div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection

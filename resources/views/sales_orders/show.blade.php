@extends('layouts.app')

@section('title', 'Detail SO ' . $so->so_number)
@section('page_title', 'Detail Sales Order')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Penjualan</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('sales_orders.index') }}" class="text-muted">Sales Order</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $so->so_number }}</li>
@endsection

@section('content')
<div class="row">
    <div class="col-md-8">
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">{{ $so->so_number }}</h3>
                <div class="card-toolbar">
                    <span class="badge {{ $so->status_badge }} fs-6">{{ $so->status_label }}</span>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3"><div class="col-3 text-muted">Tanggal Order</div><div class="col-9 fw-bold">{{ $so->order_date?->format('d M Y') }}</div></div>
                <div class="row mb-3"><div class="col-3 text-muted">Tanggal Kirim</div><div class="col-9 fw-bold">{{ $so->delivery_date?->format('d M Y') ?? '—' }}</div></div>
                <div class="row mb-3"><div class="col-3 text-muted">Customer</div><div class="col-9 fw-bold">{{ $so->customer->code }} — {{ $so->customer->name }}</div></div>
                <div class="row mb-3"><div class="col-3 text-muted">Warehouse</div><div class="col-9 fw-bold">{{ $so->warehouse->code }} — {{ $so->warehouse->name }}</div></div>
                <div class="row mb-3"><div class="col-3 text-muted">Sales</div><div class="col-9">{{ $so->salesUser?->full_name ?? '—' }}</div></div>
                <div class="row mb-3"><div class="col-3 text-muted">Term Pembayaran</div><div class="col-9">{{ $so->payment_terms_days }} hari</div></div>
                <div class="row mb-3">
                    <div class="col-3 text-muted">Metode Pembayaran</div>
                    <div class="col-9">
                        @if($so->paymentMethod)
                            <span class="fw-bold">{{ $so->paymentMethod->name }}</span>
                            @if($so->paymentMethod->bank_name)
                                <span class="text-muted">— {{ $so->paymentMethod->bank_name }} {{ $so->paymentMethod->account_no }} a.n. {{ $so->paymentMethod->account_holder }}</span>
                            @endif
                        @else
                            <span class="text-muted">Belum ditentukan</span>
                        @endif
                        @if(auth()->user()?->hasPermission('sales_order.update'))
                            <button type="button" class="btn btn-sm btn-light-warning ms-2" data-bs-toggle="modal" data-bs-target="#modal_change_pm">
                                <i class="ki-outline ki-pencil fs-3"></i> Ganti
                            </button>
                        @endif
                    </div>
                </div>
                @if($so->notes)
                    <div class="row"><div class="col-3 text-muted">Catatan</div><div class="col-9">{{ $so->notes }}</div></div>
                @endif
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Items</h3></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-2">
                        <thead>
                            <tr class="fw-bold text-muted bg-light fs-7">
                                <th class="ps-4">Produk</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Harga</th>
                                <th class="text-end">Disc%</th>
                                <th class="text-end pe-4">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($so->items as $item)
                            @php $q = (float)$item->quantity; $qF = floor($q)==$q ? number_format($q,0,',','.') : number_format($q,3,',','.'); @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $item->product->sku }}</div>
                                    <div class="text-muted fs-7">{{ $item->product->name }}</div>
                                </td>
                                <td class="text-end fw-bold">{{ $qF }} <span class="text-muted fs-8">{{ $item->uom->code }}</span></td>
                                <td class="text-end">Rp {{ number_format((float)$item->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end">{{ rtrim(rtrim(number_format((float)$item->discount_pct, 2, ',', '.'), '0'), ',') }}%</td>
                                <td class="text-end pe-4 fw-bold">Rp {{ number_format((float)$item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="fw-bold">
                            <tr><td colspan="4" class="text-end">Subtotal</td><td class="text-end pe-4">Rp {{ number_format((float)$so->subtotal, 0, ',', '.') }}</td></tr>
                            @if((float)$so->discount_amount > 0)
                                <tr><td colspan="4" class="text-end">Diskon</td><td class="text-end pe-4 text-danger">−Rp {{ number_format((float)$so->discount_amount, 0, ',', '.') }}</td></tr>
                            @endif
                            @if((float)$so->shipping_cost > 0)
                                <tr><td colspan="4" class="text-end">Ongkir</td><td class="text-end pe-4">Rp {{ number_format((float)$so->shipping_cost, 0, ',', '.') }}</td></tr>
                            @endif
                            <tr><td colspan="4" class="text-end fs-4">TOTAL</td><td class="text-end pe-4 fs-4 text-primary">Rp {{ number_format((float)$so->total_amount, 0, ',', '.') }}</td></tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Aksi</h3></div>
            <div class="card-body d-flex flex-column gap-2">
                @if(auth()->user()?->hasPermission('sales_order.print'))
                    <a href="{{ route('sales_orders.print', $so) }}" target="_blank" class="btn btn-light-primary">
                        <i class="ki-outline ki-printer fs-2"></i> Cetak / Proforma
                    </a>
                @endif

                @if($so->isEditable() && auth()->user()?->hasPermission('sales_order.update'))
                    <a href="{{ route('sales_orders.edit', $so) }}" class="btn btn-light-warning">
                        <i class="ki-outline ki-pencil fs-2"></i> Edit
                    </a>
                @endif

                @if($so->isConfirmable() && auth()->user()?->hasPermission('sales_order.confirm'))
                    <form method="POST" action="{{ route('sales_orders.confirm', $so) }}" onsubmit="return confirm('Confirm SO ini? Stock akan di-reserve dan tidak bisa diedit lagi.')">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="ki-outline ki-check-circle fs-2"></i> Confirm SO
                        </button>
                    </form>
                @endif

                @if(in_array($so->status, [\App\Models\SalesOrder::STATUS_CONFIRMED, \App\Models\SalesOrder::STATUS_PARTIAL]) && auth()->user()?->hasPermission('delivery_order.create'))
                    <a href="{{ route('delivery_orders.create', ['so_id' => $so->id]) }}" class="btn btn-primary">
                        <i class="ki-outline ki-truck fs-2"></i> Buat Delivery Order
                    </a>
                @endif

                @if($so->isCancellable() && auth()->user()?->hasPermission('sales_order.cancel'))
                    <form method="POST" action="{{ route('sales_orders.cancel', $so) }}" onsubmit="return confirm('Batalkan SO ini? Reserved stock akan dikembalikan.')">
                        @csrf
                        <button type="submit" class="btn btn-light-danger w-100">
                            <i class="ki-outline ki-cross-circle fs-2"></i> Cancel
                        </button>
                    </form>
                @endif

                <a href="{{ route('sales_orders.index') }}" class="btn btn-light">
                    <i class="ki-outline ki-arrow-left fs-2"></i> Kembali
                </a>
            </div>
        </div>

        <div class="card">
            <div class="card-header"><h3 class="card-title">Info</h3></div>
            <div class="card-body fs-7">
                <div class="d-flex flex-stack mb-2"><span class="text-muted">Dibuat oleh:</span><span class="fw-bold">{{ $so->createdBy?->full_name ?? '—' }}</span></div>
                <div class="d-flex flex-stack mb-2"><span class="text-muted">Tgl Dibuat:</span><span class="fw-bold">{{ $so->created_date?->format('d M Y H:i') }}</span></div>
                @if($so->updated_date)
                    <div class="d-flex flex-stack"><span class="text-muted">Tgl Update:</span><span class="fw-bold">{{ $so->updated_date?->format('d M Y H:i') }}</span></div>
                @endif
            </div>
        </div>
    </div>
</div>

@if(auth()->user()?->hasPermission('sales_order.update'))
{{-- Modal: Ganti Metode Pembayaran --}}
<div class="modal fade" id="modal_change_pm" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('sales_orders.payment-method.update', $so) }}" class="modal-content">
            @csrf @method('PATCH')
            <div class="modal-header">
                <h5 class="modal-title">Ganti Metode Pembayaran</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <label class="form-label fw-semibold">Metode Pembayaran</label>
                <select name="payment_method_id" class="form-select form-select-solid">
                    <option value="">— Belum ditentukan —</option>
                    @foreach($paymentMethods as $pm)
                        <option value="{{ $pm->id }}" @selected($so->payment_method_id==$pm->id)>
                            {{ $pm->name }}@if($pm->bank_name) ({{ $pm->bank_name }} {{ $pm->account_no }})@endif
                        </option>
                    @endforeach
                </select>
                <div class="form-text">Customer minta ganti metode? Update di sini.</div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Update</button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

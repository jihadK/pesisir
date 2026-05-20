@extends('layouts.app')

@section('title', 'Detail Order ' . $so->so_number)
@section('page_title', 'Detail Booking Order')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Penjualan</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('sales_orders.index') }}" class="text-muted">Booking Order</a></li>
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
                <div class="row mb-3"><div class="col-3 text-muted">Sales</div><div class="col-9">{{ $so->salesUser?->full_name ?? '—' }}</div></div>
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
            <div class="card-header">
                <h3 class="card-title">Items</h3>
                @if(in_array($so->status, [\App\Models\SalesOrder::STATUS_CONFIRMED, \App\Models\SalesOrder::STATUS_PARTIAL]) && auth()->user()?->hasPermission('sales_order.update'))
                    <div class="card-toolbar">
                        <button type="button" class="btn btn-sm btn-light-primary" data-bs-toggle="modal" data-bs-target="#modal_add_item">
                            <i class="ki-outline ki-plus fs-3"></i> Tambah Item
                        </button>
                    </div>
                @endif
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-2" style="min-width:760px">
                        <thead>
                            <tr class="fw-bold text-muted bg-light fs-7">
                                <th class="ps-4" style="min-width:240px">Produk</th>
                                <th class="text-end" style="min-width:110px">Qty</th>
                                <th class="text-end" style="min-width:120px">Harga</th>
                                <th class="text-end" style="min-width:70px">Disc%</th>
                                <th class="text-end pe-4" style="min-width:140px">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        @php
                            $totalContentMin = 0; $totalContentMax = 0; $totalWeightMin = 0; $totalWeightMax = 0; $sameUnit = null;
                        @endphp
                        @foreach($so->items as $item)
                            @php
                                $q = (float)$item->quantity;
                                $qF = floor($q)==$q ? number_format($q,0,',','.') : number_format($q,3,',','.');
                                $p = $item->product;
                                if ($p->pack_content_min) {
                                    $totalContentMin += $q * (int)$p->pack_content_min;
                                    $totalContentMax += $q * (int)($p->pack_content_max ?: $p->pack_content_min);
                                    $sameUnit = $sameUnit === null ? $p->pack_content_type : ($sameUnit === $p->pack_content_type ? $sameUnit : false);
                                }
                                if ($p->pack_weight_min_g) {
                                    $totalWeightMin += $q * (float)$p->pack_weight_min_g;
                                    $totalWeightMax += $q * (float)($p->pack_weight_max_g ?: $p->pack_weight_min_g);
                                }
                            @endphp
                            <tr>
                                <td class="ps-4">
                                    <div class="fw-bold">{{ $p->sku }}</div>
                                    <div class="text-muted fs-7">{{ $p->name }}</div>
                                    @if($p->pack_content_label || $p->pack_weight_label)
                                        <div class="fs-8 mt-1">
                                            @if($p->pack_content_label)<span class="badge badge-light-info me-1">{{ $p->pack_content_label }}</span>@endif
                                            @if($p->pack_weight_label)<span class="badge badge-light-warning">{{ $p->pack_weight_label }}</span>@endif
                                        </div>
                                    @endif
                                </td>
                                <td class="text-end fw-bold">{{ $qF }} <span class="text-muted fs-8">{{ $item->uom->code }}</span></td>
                                <td class="text-end">{{ number_format((float)$item->unit_price, 0, ',', '.') }}</td>
                                <td class="text-end">{{ rtrim(rtrim(number_format((float)$item->discount_pct, 2, ',', '.'), '0'), ',') }}%</td>
                                <td class="text-end pe-4 fw-bold">{{ number_format((float)$item->subtotal, 0, ',', '.') }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="fw-bold">
                            @if($totalContentMin > 0 || $totalWeightMin > 0)
                                <tr class="fs-8">
                                    <td colspan="5" class="ps-4 pe-4 py-2 bg-light-info">
                                        <i class="ki-outline ki-element-equal-1 fs-3 me-1 text-info"></i>
                                        <strong>Estimasi total:</strong>
                                        @if($totalContentMin > 0)
                                            {{ $totalContentMin == $totalContentMax ? number_format($totalContentMin,0,',','.') : number_format($totalContentMin,0,',','.').'–'.number_format($totalContentMax,0,',','.') }}
                                            {{ $sameUnit ?: 'isi' }}
                                        @endif
                                        @if($totalContentMin > 0 && $totalWeightMin > 0) · @endif
                                        @if($totalWeightMin > 0)
                                            @php $kgMin = $totalWeightMin/1000; $kgMax = $totalWeightMax/1000; @endphp
                                            {{ $totalWeightMin == $totalWeightMax ? number_format($totalWeightMin,0,',','.') : number_format($totalWeightMin,0,',','.').'–'.number_format($totalWeightMax,0,',','.') }} g
                                            <span class="text-muted">({{ rtrim(rtrim(number_format($kgMin,3,',','.'),'0'),',') }}{{ $kgMin != $kgMax ? '–'.rtrim(rtrim(number_format($kgMax,3,',','.'),'0'),',') : '' }} kg)</span>
                                        @endif
                                    </td>
                                </tr>
                            @endif
                            <tr><td colspan="4" class="text-end">Subtotal</td><td class="text-end pe-4">{{ number_format((float)$so->subtotal, 0, ',', '.') }}</td></tr>
                            @if((float)$so->discount_amount > 0)
                                <tr><td colspan="4" class="text-end">Diskon</td><td class="text-end pe-4 text-danger">−{{ number_format((float)$so->discount_amount, 0, ',', '.') }}</td></tr>
                            @endif
                            @if((float)$so->shipping_cost > 0)
                                <tr><td colspan="4" class="text-end">Ongkir</td><td class="text-end pe-4">{{ number_format((float)$so->shipping_cost, 0, ',', '.') }}</td></tr>
                            @endif
                            @if((float)$so->packing_cost > 0)
                                <tr><td colspan="4" class="text-end">Biaya Packing</td><td class="text-end pe-4">{{ number_format((float)$so->packing_cost, 0, ',', '.') }}</td></tr>
                            @endif
                            @if((float)$so->other_cost_amount > 0)
                                <tr><td colspan="4" class="text-end">Biaya Lain-lain{{ $so->other_cost_desc ? ' ('.$so->other_cost_desc.')' : '' }}</td><td class="text-end pe-4">{{ number_format((float)$so->other_cost_amount, 0, ',', '.') }}</td></tr>
                            @endif
                            <tr><td colspan="4" class="text-end fs-4">TOTAL</td><td class="text-end pe-4 fs-4 text-primary">Rp {{ number_format((float)$so->total_amount, 0, ',', '.') }}</td></tr>
                        </tfoot>
                    </table>
                </div>
                <div class="d-md-none alert alert-light-info mt-3 fs-8 py-2 mb-0">
                    <i class="ki-outline ki-information fs-3 me-1"></i>
                    Tabel bisa di-<strong>geser ke samping</strong> untuk lihat semua kolom.
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
                        <i class="ki-outline ki-printer fs-2"></i> {{ $so->isPaid() ? 'Cetak Invoice' : 'Cetak / Proforma' }}
                    </a>
                @endif

                @if($so->isEditable() && auth()->user()?->hasPermission('sales_order.update'))
                    <a href="{{ route('sales_orders.edit', $so) }}" class="btn btn-light-warning">
                        <i class="ki-outline ki-pencil fs-2"></i> Edit
                    </a>
                @endif

                @if($so->isMarkPaidable() && auth()->user()?->hasPermission('sales_order.mark_paid'))
                    <form method="POST" action="{{ route('sales_orders.mark-paid', $so) }}" onsubmit="return confirm('Tandai Order ini sebagai sudah dibayar?')">
                        @csrf
                        <button type="submit" class="btn btn-success w-100">
                            <i class="ki-outline ki-wallet fs-2"></i> Paid (Terbayar)
                        </button>
                    </form>
                @endif

                @if($so->isCancellable() && auth()->user()?->hasPermission('sales_order.cancel'))
                    <form method="POST" action="{{ route('sales_orders.cancel', $so) }}" onsubmit="return confirm('Batalkan Order ini?')">
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

@if(in_array($so->status, [\App\Models\SalesOrder::STATUS_CONFIRMED, \App\Models\SalesOrder::STATUS_PARTIAL]) && auth()->user()?->hasPermission('sales_order.update'))
{{-- Modal: Tambah Item ke SO Confirmed --}}
<div class="modal fade" id="modal_add_item" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <form method="POST" action="{{ route('sales_orders.items.append', $so) }}" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Tambah Item ke Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Produk</label>
                    <div class="col-md-9">
                        <select name="product_id" id="add_item_product" class="form-select form-select-solid" required>
                            <option value="">— Pilih produk —</option>
                            @foreach($products as $p)
                                @php
                                    $packs = [];
                                    if ($p->pack_content_label) $packs[] = $p->pack_content_label;
                                    if ($p->pack_weight_label)  $packs[] = $p->pack_weight_label;
                                    $packStr = $packs ? ' (' . implode(', ', $packs) . ')' : '';
                                @endphp
                                <option value="{{ $p->id }}"
                                        data-price="{{ (float)$p->default_sell_price }}"
                                        data-pack-content="{{ $p->pack_content_label }}"
                                        data-pack-weight="{{ $p->pack_weight_label }}">
                                    {{ $p->sku }} — {{ $p->name }}{{ $packStr }}
                                </option>
                            @endforeach
                        </select>
                        <div id="add_item_pack_info" class="fs-7 mt-2"></div>
                    </div>
                </div>
                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Qty</label>
                    <div class="col-md-3">
                        <input type="number" step="0.001" min="0.001" name="quantity" value="1" class="form-control form-control-solid text-end" required />
                    </div>
                    <label class="col-form-label col-md-3 fw-semibold required">Harga</label>
                    <div class="col-md-3">
                        <div class="input-group"><span class="input-group-text">Rp</span>
                            <input type="text" name="unit_price" id="add_item_price" class="form-control form-control-solid text-end" required />
                        </div>
                    </div>
                </div>
                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold">Disc (%)</label>
                    <div class="col-md-3">
                        <input type="number" min="0" max="100" step="0.01" name="discount_pct" value="0" class="form-control form-control-solid text-end" />
                    </div>
                </div>
                <div class="row">
                    <label class="col-form-label col-md-3 fw-semibold">Catatan</label>
                    <div class="col-md-9">
                        <input type="text" name="notes" maxlength="255" class="form-control form-control-solid" />
                    </div>
                </div>
                <div class="alert alert-light-info mt-3 fs-7 mb-0">
                    <i class="ki-outline ki-information fs-3 me-1"></i>
                    Stok akan langsung di-reserve dari warehouse <strong>{{ $so->warehouse->code }}</strong>.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Tambah Item</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sel  = document.getElementById('add_item_product');
    const priceEl = document.getElementById('add_item_price');
    const packEl  = document.getElementById('add_item_pack_info');
    sel?.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        const price = parseFloat(opt?.dataset.price || 0);
        if (price && ! priceEl.value) priceEl.value = Math.round(price).toLocaleString('id-ID');

        const content = opt?.dataset.packContent || '';
        const weight  = opt?.dataset.packWeight  || '';
        const parts = [];
        if (content) parts.push(`<span class="badge badge-light-info me-1"><i class="ki-outline ki-element-equal-1 fs-8 me-1"></i>${content}</span>`);
        if (weight)  parts.push(`<span class="badge badge-light-warning"><i class="ki-outline ki-scale fs-8 me-1"></i>${weight}</span>`);
        packEl.innerHTML = parts.join(' ');
    });
});
</script>
@endpush
@endif

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

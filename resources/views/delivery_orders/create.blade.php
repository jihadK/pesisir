@extends('layouts.app')

@section('title', 'Delivery Order Baru')
@section('page_title', 'Delivery Order Baru')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Penjualan</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('delivery_orders.index') }}" class="text-muted">Delivery Order</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Baru</li>
@endsection

@section('content')
<form method="POST" action="{{ route('delivery_orders.store') }}">
    @csrf
    <div class="row">
        <div class="col-md-9">
            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Pilih Sales Order</h3></div>
                <div class="card-body">
                    <div class="row mb-4">
                        <label class="col-form-label col-md-3 fw-semibold required">Sales Order</label>
                        <div class="col-md-9">
                            <select name="so_id" id="do_so" class="form-select form-select-solid @error('so_id') is-invalid @enderror"
                                    data-control="select2" data-placeholder="Pilih SO..." required>
                                <option value=""></option>
                                @foreach($eligibleSO as $so)
                                    <option value="{{ $so->id }}" @selected(old('so_id', $preselectedSO?->id) == $so->id)>
                                        {{ $so->so_number }} — {{ $so->customer->name }} ({{ $so->warehouse->code }}) — {{ $so->status_label }}
                                    </option>
                                @endforeach
                            </select>
                            @error('so_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Hanya SO status Confirmed/Partial yang muncul. Pilih dari menu Sales Order kalau belum confirmed.</div>
                        </div>
                    </div>

                    <div id="so_summary" class="alert alert-light-info py-3 d-none">
                        <div class="d-flex gap-4 fs-7">
                            <span><strong>Customer:</strong> <span id="so_cust"></span></span>
                            <span><strong>Warehouse:</strong> <span id="so_wh"></span></span>
                            <span><strong>Status:</strong> <span id="so_status"></span></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Detail Pengiriman</h3></div>
                <div class="card-body">
                    <div class="row mb-4">
                        <label class="col-form-label col-md-3 fw-semibold required">Tanggal Kirim</label>
                        <div class="col-md-3">
                            <input type="date" name="delivery_date" value="{{ old('delivery_date', now()->toDateString()) }}"
                                   class="form-control form-control-solid @error('delivery_date') is-invalid @enderror" required />
                        </div>
                    </div>
                    <div class="row mb-4">
                        <label class="col-form-label col-md-3 fw-semibold">Driver</label>
                        <div class="col-md-4">
                            <input type="text" name="driver_name" value="{{ old('driver_name') }}"
                                   class="form-control form-control-solid" maxlength="100" placeholder="Nama supir/kurir" />
                        </div>
                        <label class="col-form-label col-md-2 fw-semibold">Kendaraan</label>
                        <div class="col-md-3">
                            <input type="text" name="vehicle_no" value="{{ old('vehicle_no') }}"
                                   class="form-control form-control-solid" maxlength="20" placeholder="Plat nomor" />
                        </div>
                    </div>
                    <div class="row">
                        <label class="col-form-label col-md-3 fw-semibold">Catatan</label>
                        <div class="col-md-9">
                            <textarea name="notes" rows="2" class="form-control form-control-solid" maxlength="1000">{{ old('notes') }}</textarea>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Item Pengiriman</h3></div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-row-bordered align-middle gy-3" id="tbl_items">
                            <thead>
                                <tr class="fw-bold text-muted bg-light fs-7">
                                    <th class="ps-4" style="width:30%">Produk</th>
                                    <th class="text-end" style="width:12%">Order</th>
                                    <th class="text-end" style="width:12%">Sudah Kirim</th>
                                    <th class="text-end" style="width:12%">Outstanding</th>
                                    <th style="width:22%">Batch (jika perishable)</th>
                                    <th class="text-end pe-4" style="width:12%">Qty Kirim</th>
                                </tr>
                            </thead>
                            <tbody id="items_body">
                                <tr><td colspan="6" class="text-center text-muted py-8">Pilih Sales Order dulu untuk menampilkan items.</td></tr>
                            </tbody>
                        </table>
                    </div>
                    @error('items')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card" style="position:sticky; top:80px">
                <div class="card-header"><h3 class="card-title">Aksi</h3></div>
                <div class="card-body d-flex flex-column gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="ki-outline ki-check fs-2"></i> Simpan DO (Draft)
                    </button>
                    <a href="{{ route('delivery_orders.index') }}" class="btn btn-light">Batal</a>
                </div>
            </div>

            <div class="alert alert-light-warning mt-5 fs-7">
                <i class="ki-outline ki-information fs-3 me-1"></i>
                DO disimpan sebagai <strong>Draft</strong> dulu. Klik <strong>Ship</strong> di halaman detail untuk konfirmasi pengiriman & kurangi stok.
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
const SO_ITEMS_URL = "{{ route('delivery_orders.so-items') }}";
const PRESELECT_ID = {{ $preselectedSO?->id ?? 'null' }};

function fmtQty(v) {
    const n = parseFloat(v) || 0;
    return Math.floor(n) === n ? n.toLocaleString('id-ID') : n.toLocaleString('id-ID', {minimumFractionDigits:3, maximumFractionDigits:3});
}

async function loadSOItems(soId) {
    const tbody = document.getElementById('items_body');
    const summary = document.getElementById('so_summary');

    if (! soId) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-8">Pilih Sales Order dulu untuk menampilkan items.</td></tr>';
        summary.classList.add('d-none');
        return;
    }

    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-8"><span class="spinner-border spinner-border-sm"></span> Loading...</td></tr>';

    const res = await fetch(`${SO_ITEMS_URL}?so_id=${soId}`, {
        headers: { 'Accept': 'application/json' }
    });
    const json = await res.json();
    if (json.resCode !== '00') {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger py-8">Gagal memuat data.</td></tr>';
        return;
    }

    const so = json.data.so;
    document.getElementById('so_cust').textContent   = so.customer ?? '—';
    document.getElementById('so_wh').textContent     = so.warehouse ?? '—';
    document.getElementById('so_status').textContent = so.status;
    summary.classList.remove('d-none');

    const items = json.data.items || [];
    if (! items.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-8">SO ini sudah delivered semua, tidak ada outstanding.</td></tr>';
        return;
    }

    tbody.innerHTML = items.map((it, idx) => {
        let batchSelect = '<span class="text-muted fs-8">—</span>';
        if (it.is_perishable && it.batches.length) {
            const opts = it.batches.map(b =>
                `<option value="${b.batch_id || ''}">${b.batch_number || '(no batch)'}${b.expiry_date ? ' · exp ' + b.expiry_date : ''} · saldo ${fmtQty(b.available)}</option>`
            ).join('');
            batchSelect = `
                <select name="items[${idx}][batch_id]" class="form-select form-select-sm form-select-solid">
                    <option value="">— Auto (FEFO) —</option>${opts}
                </select>`;
        } else if (it.is_perishable) {
            batchSelect = '<span class="text-danger fs-8">⚠ Tidak ada batch tersedia</span>';
        }

        return `
            <tr>
                <td class="ps-4">
                    <input type="hidden" name="items[${idx}][so_item_id]" value="${it.so_item_id}" />
                    <div class="fw-bold">${it.sku}</div>
                    <div class="text-muted fs-7">${it.name}</div>
                </td>
                <td class="text-end fs-7">${fmtQty(it.qty_total)} ${it.uom_code}</td>
                <td class="text-end fs-7 text-info">${fmtQty(it.qty_delivered)}</td>
                <td class="text-end fw-bold text-warning">${fmtQty(it.outstanding)}</td>
                <td>${batchSelect}</td>
                <td class="text-end pe-4">
                    <input type="number" step="0.001" min="0" max="${it.outstanding}"
                           name="items[${idx}][quantity]" value="${it.outstanding}"
                           class="form-control form-control-sm form-control-solid text-end" />
                </td>
            </tr>`;
    }).join('');
}

document.addEventListener('DOMContentLoaded', () => {
    const $ = window.jQuery;
    $('#do_so').on('change', function () { loadSOItems(this.value); });

    if (PRESELECT_ID) {
        $('#do_so').val(PRESELECT_ID).trigger('change');
    }
});
</script>
@endpush
@endsection

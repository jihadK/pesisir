@extends('layouts.app')

@section('title', 'Adjustment Baru')
@section('page_title', 'Stock Adjustment Baru')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Inventory</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('stock_adjustments.index') }}" class="text-muted">Stock Adjustment</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Baru</li>
@endsection

@section('content')
<form method="POST" action="{{ route('stock_adjustments.store') }}">
    @csrf
    <div class="row">
        <div class="col-md-8">
            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Detail Adjustment</h3></div>
                <div class="card-body">
                    <div class="row mb-5">
                        <label class="col-form-label col-md-3 fw-semibold required">Warehouse</label>
                        <div class="col-md-9">
                            <select name="warehouse_id" id="adj_warehouse" class="form-select form-select-solid @error('warehouse_id') is-invalid @enderror"
                                    data-control="select2" data-placeholder="Pilih warehouse..." required>
                                <option value=""></option>
                                @foreach($warehouses as $w)
                                    <option value="{{ $w->id }}" @selected(old('warehouse_id')==$w->id)>{{ $w->code }} — {{ $w->name }}</option>
                                @endforeach
                            </select>
                            @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row mb-5">
                        <label class="col-form-label col-md-3 fw-semibold required">Produk</label>
                        <div class="col-md-9">
                            <select name="product_id" id="adj_product" class="form-select form-select-solid @error('product_id') is-invalid @enderror"
                                    data-control="select2" data-placeholder="Pilih produk..." required>
                                <option value=""></option>
                                @foreach($products as $p)
                                    <option value="{{ $p->id }}" data-uom="{{ $p->baseUom?->code }}" @selected(old('product_id')==$p->id)>{{ $p->sku }} — {{ $p->name }}</option>
                                @endforeach
                            </select>
                            @error('product_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text" id="stock_info"></div>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <label class="col-form-label col-md-3 fw-semibold">Batch</label>
                        <div class="col-md-9">
                            <select name="batch_id" id="adj_batch" class="form-select form-select-solid"
                                    data-control="select2" data-placeholder="(Otomatis / pilih batch spesifik)">
                                <option value="">— Otomatis (semua batch) —</option>
                            </select>
                            <div class="form-text">Untuk perishable: pilih batch spesifik (misal yang rusak/expired). Untuk non-perishable: biarkan otomatis.</div>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <label class="col-form-label col-md-3 fw-semibold required">Tipe</label>
                        <div class="col-md-9">
                            <div class="d-flex gap-3">
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="radio" name="direction" value="in" @checked(old('direction','out')==='in') />
                                    <span class="form-check-label fw-semibold ms-2 text-success">Tambah (+)</span>
                                </label>
                                <label class="form-check form-check-custom form-check-solid">
                                    <input class="form-check-input" type="radio" name="direction" value="out" @checked(old('direction','out')==='out') />
                                    <span class="form-check-label fw-semibold ms-2 text-danger">Kurang (−)</span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="row mb-5">
                        <label class="col-form-label col-md-3 fw-semibold required">Alasan</label>
                        <div class="col-md-9">
                            <select name="reason" class="form-select form-select-solid @error('reason') is-invalid @enderror" required>
                                <option value="">Pilih alasan...</option>
                                @foreach($reasons as $key => $label)
                                    <option value="{{ $key }}" @selected(old('reason')==$key)>{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('reason')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row mb-5">
                        <label class="col-form-label col-md-3 fw-semibold required">Qty</label>
                        <div class="col-md-9">
                            <div class="input-group" style="max-width:300px">
                                <input type="number" step="0.001" min="0.001" name="quantity" id="adj_qty"
                                       value="{{ old('quantity') }}"
                                       class="form-control form-control-solid @error('quantity') is-invalid @enderror" required />
                                <span class="input-group-text" id="adj_uom">unit</span>
                            </div>
                            @error('quantity')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        </div>
                    </div>

                    <div class="row mb-5">
                        <label class="col-form-label col-md-3 fw-semibold required">Catatan</label>
                        <div class="col-md-9">
                            <textarea name="notes" rows="3" class="form-control form-control-solid @error('notes') is-invalid @enderror"
                                      placeholder="Mis. ditemukan rusak saat opname rutin tanggal 10 Mei" required>{{ old('notes') }}</textarea>
                            @error('notes')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text">Wajib diisi (audit trail). Min 5 karakter.</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <a href="{{ route('stock_adjustments.index') }}" class="btn btn-light">Batal</a>
                <button type="submit" class="btn btn-primary">
                    <i class="ki-outline ki-check fs-2"></i> Simpan Adjustment
                </button>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">Tips</h3></div>
                <div class="card-body fs-7 text-muted">
                    <ul class="ps-3 mb-0">
                        <li class="mb-2"><strong>Rusak / Expired</strong> → otomatis tercatat sebagai pemusnahan (waste).</li>
                        <li class="mb-2"><strong>Koreksi</strong> → hasil opname (selisih hitung fisik vs sistem).</li>
                        <li class="mb-2">Adjustment tidak bisa diedit/dihapus setelah dibuat — pastikan sebelum simpan.</li>
                        <li>Untuk produk perishable, pilih batch spesifik agar FEFO tetap akurat.</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</form>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const $ = window.jQuery;
    const wEl = $('#adj_warehouse');
    const pEl = $('#adj_product');
    const bEl = $('#adj_batch');
    const stockInfo = document.getElementById('stock_info');
    const uomLabel  = document.getElementById('adj_uom');

    function loadBatches() {
        const wId = wEl.val();
        const pId = pEl.val();
        bEl.html('<option value="">— Otomatis (semua batch) —</option>');
        stockInfo.textContent = '';
        if (! wId || ! pId) return;

        fetch(`{{ route('stock_adjustments.batches') }}?warehouse_id=${wId}&product_id=${pId}`, {
            headers: { 'Accept': 'application/json' }
        })
        .then(r => r.json())
        .then(res => {
            if (res.resCode !== '00') return;
            const total = res.data.total || 0;
            stockInfo.innerHTML = `Saldo saat ini di gudang ini: <strong>${total.toLocaleString('id-ID', {maximumFractionDigits:3})}</strong>`;

            (res.data.batches || []).forEach(b => {
                const exp = b.expiry_date ? ` (exp ${b.expiry_date})` : '';
                const opt = `<option value="${b.batch_id || ''}">${b.batch_number || '(no batch)'} · saldo ${parseFloat(b.available).toFixed(3)}${exp}</option>`;
                bEl.append(opt);
            });
            bEl.trigger('change.select2');
        });
    }

    wEl.on('change', loadBatches);
    pEl.on('change', function () {
        const uom = $(this).find(':selected').data('uom') || 'unit';
        uomLabel.textContent = uom;
        loadBatches();
    });

    // Init
    if (pEl.val()) pEl.trigger('change');
});
</script>
@endpush
@endsection

@php $isEdit = $isEdit ?? false; @endphp

<div class="row">
    <div class="col-md-8">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">{{ $isEdit ? 'Edit Pembelian' : 'Catat Pembelian Lain-lain' }}</h3></div>
            <div class="card-body">
                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Tanggal</label>
                    <div class="col-md-4">
                        <input type="date" name="purchase_date" value="{{ old('purchase_date', $purchase->purchase_date?->toDateString() ?? now()->toDateString()) }}"
                               class="form-control form-control-solid @error('purchase_date') is-invalid @enderror" required />
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold">Supplier</label>
                    <div class="col-md-9">
                        <select name="supplier_id" class="form-select form-select-solid" data-control="select2" data-placeholder="(Opsional)">
                            <option value="">— Tidak ditentukan —</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" @selected(old('supplier_id', $purchase->supplier_id)==$s->id)>{{ $s->code }} — {{ $s->name }}</option>
                            @endforeach
                        </select>
                        <div class="form-text fs-8">Kosongkan kalau beli dari toko biasa tanpa nama supplier tetap.</div>
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Item / Deskripsi</label>
                    <div class="col-md-9">
                        <input type="text" name="description" value="{{ old('description', $purchase->description) }}"
                               class="form-control form-control-solid @error('description') is-invalid @enderror"
                               maxlength="255" required placeholder="Mis. Plastik HD 30x40, Box styrofoam 10L, Timba 20L" />
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Qty</label>
                    <div class="col-md-3">
                        <input type="text" name="qty" id="sp_qty" value="{{ old('qty', $purchase->qty ? rtrim(rtrim(number_format((float)$purchase->qty, 3, ',', '.'), '0'), ',') : '') }}"
                               class="form-control form-control-solid text-end @error('qty') is-invalid @enderror" required />
                        @error('qty')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <label class="col-form-label col-md-2 fw-semibold required">Satuan</label>
                    <div class="col-md-3">
                        <input type="text" name="unit" value="{{ old('unit', $purchase->unit ?? 'pcs') }}"
                               class="form-control form-control-solid @error('unit') is-invalid @enderror" maxlength="20" placeholder="pcs / box / kg / pack" required />
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Harga / Satuan</label>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="unit_price" id="sp_price" value="{{ old('unit_price', $purchase->unit_price ? number_format((float)$purchase->unit_price, 0, ',', '.') : '') }}"
                                   class="form-control form-control-solid text-end @error('unit_price') is-invalid @enderror" required />
                        </div>
                        @error('unit_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold">Subtotal</label>
                    <div class="col-md-9">
                        <div class="fs-2 fw-bolder text-primary" id="sp_subtotal">Rp 0</div>
                    </div>
                </div>

                <div class="row">
                    <label class="col-form-label col-md-3 fw-semibold">Catatan</label>
                    <div class="col-md-9">
                        <textarea name="notes" rows="2" class="form-control form-control-solid" maxlength="1000">{{ old('notes', $purchase->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('supplies_purchases.index') }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="ki-outline ki-check fs-2"></i> {{ $isEdit ? 'Update' : 'Simpan' }}</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
function fmtRp(v) { return 'Rp ' + Math.round(parseFloat(v)||0).toLocaleString('id-ID'); }
function unmask(s) { return parseFloat((s||'0').toString().replace(/\./g,'').replace(',','.')) || 0; }

function recalc() {
    const qty = unmask(document.getElementById('sp_qty').value);
    const price = unmask(document.getElementById('sp_price').value);
    document.getElementById('sp_subtotal').textContent = fmtRp(qty * price);
}

document.addEventListener('DOMContentLoaded', function () {
    const qtyEl = document.getElementById('sp_qty');
    const priceEl = document.getElementById('sp_price');
    if (typeof Inputmask !== 'undefined') {
        Inputmask({ alias:'numeric', groupSeparator:'.', radixPoint:',', digits:3, allowMinus:false, removeMaskOnSubmit:false }).mask(qtyEl);
        Inputmask({ alias:'numeric', groupSeparator:'.', radixPoint:',', digits:0, allowMinus:false, removeMaskOnSubmit:false }).mask(priceEl);
    }
    qtyEl.addEventListener('input', recalc);
    priceEl.addEventListener('input', recalc);
    recalc();
});
</script>
@endpush

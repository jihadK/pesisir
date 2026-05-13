@php
    $isEdit = $isEdit ?? false;
    $existingItems = $isEdit ? $po->items->map(fn($i) => [
        'category_id'  => $i->category_id,
        'qty_gram'     => (float) $i->qty_gram,
        'price_per_kg' => (float) $i->price_per_kg,
        'notes'        => $i->notes,
    ])->values() : collect();
@endphp

<div class="row">
    <div class="col-md-9">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Header PO</h3></div>
            <div class="card-body">
                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Supplier</label>
                    <div class="col-md-9">
                        <select name="supplier_id" class="form-select form-select-solid @error('supplier_id') is-invalid @enderror" data-control="select2" required>
                            <option value=""></option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->id }}" @selected(old('supplier_id', $po->supplier_id)==$s->id)>{{ $s->code }} — {{ $s->name }}</option>
                            @endforeach
                        </select>
                        @error('supplier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Warehouse Tujuan</label>
                    <div class="col-md-9">
                        <select name="warehouse_id" class="form-select form-select-solid @error('warehouse_id') is-invalid @enderror" data-control="select2" required>
                            <option value=""></option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}" @selected(old('warehouse_id', $po->warehouse_id)==$w->id)>{{ $w->code }} — {{ $w->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Tanggal PO</label>
                    <div class="col-md-3">
                        <input type="date" name="po_date" value="{{ old('po_date', $po->po_date?->toDateString() ?? now()->toDateString()) }}" class="form-control form-control-solid" required />
                    </div>
                    <label class="col-form-label col-md-3 fw-semibold">Expected Delivery</label>
                    <div class="col-md-3">
                        <input type="date" name="expected_date" value="{{ old('expected_date', $po->expected_date?->toDateString()) }}" class="form-control form-control-solid" />
                    </div>
                </div>
                <div class="row">
                    <label class="col-form-label col-md-3 fw-semibold">Catatan</label>
                    <div class="col-md-9">
                        <textarea name="notes" rows="2" class="form-control form-control-solid" maxlength="1000">{{ old('notes', $po->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">Item Pembelian (Raw dari Supplier)</h3>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-light-primary btn-sm" id="btn_add_item">
                        <i class="ki-outline ki-plus fs-3"></i> Tambah Item
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-3" id="tbl_items" style="min-width:820px">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th style="min-width:240px">Sub-Kategori *</th>
                                <th class="text-end" style="min-width:130px">Qty (gram) *</th>
                                <th class="text-end" style="min-width:140px">Harga / Kg *</th>
                                <th class="text-end" style="min-width:140px">Subtotal</th>
                                <th style="min-width:60px"></th>
                            </tr>
                        </thead>
                        <tbody id="items_body"></tbody>
                        <tfoot class="fw-bold">
                            <tr><td colspan="3" class="text-end fs-4">TOTAL</td><td class="text-end fs-4 text-primary" id="ft_total">Rp 0</td><td></td></tr>
                        </tfoot>
                    </table>
                </div>
                <div class="d-md-none alert alert-light-info mt-3 fs-8 py-2 mb-0">
                    <i class="ki-outline ki-information fs-3 me-1"></i> Tabel bisa di-<strong>geser ke samping</strong>.
                </div>
                @error('items')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="alert alert-light-info fs-7">
            <i class="ki-outline ki-information fs-3 me-1"></i>
            <strong>Catatan:</strong> Jasa bersih ikan & pembelian lain-lain (plastik, box, dll) dicatat di menu terpisah:
            <strong>Pembelian → Jasa Bersih Ikan</strong> dan <strong>Pembelian → Pembelian Lain-lain</strong>.
        </div>
    </div>

    <div class="col-md-3">
        <div class="card" style="position:sticky; top:80px">
            <div class="card-header"><h3 class="card-title">Total PO</h3></div>
            <div class="card-body">
                <div class="d-flex flex-stack">
                    <span class="text-muted">TOTAL:</span>
                    <span class="fw-bolder fs-3 text-primary" id="sm_total">Rp 0</span>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column gap-2 mt-5">
            <button type="submit" class="btn btn-primary">
                <i class="ki-outline ki-check fs-2"></i> {{ $isEdit ? 'Update PO' : 'Simpan PO (Draft)' }}
            </button>
            <a href="{{ $isEdit ? route('purchase_orders.show', $po) : route('purchase_orders.index') }}" class="btn btn-light">Batal</a>
        </div>
    </div>
</div>

@php
    $categoryData = $categories->map(fn($c) => [
        'id'   => $c->id,
        'name' => $c->name,
        'group'=> $c->parent?->name ?? '',
    ])->values();
@endphp

@push('scripts')
<script>
const CATEGORIES = @json($categoryData);
const EXIST_ITEMS = @json($existingItems);

function fmtRp(v) { return 'Rp ' + Math.round(parseFloat(v)||0).toLocaleString('id-ID'); }
function unmask(s) { return parseFloat((s||'0').toString().replace(/\./g,'').replace(',','.')) || 0; }
function maskRupiah(el) {
    if (typeof Inputmask === 'undefined') return;
    Inputmask({ alias:'numeric', groupSeparator:'.', radixPoint:',', digits:0, allowMinus:false, removeMaskOnSubmit:false }).mask(el);
}

const CAT_OPTIONS = CATEGORIES.map(c => `<option value="${c.id}">${c.group ? c.group + ' › ' : ''}${c.name}</option>`).join('');

let itemIdx = 0;

function addItemRow(prefill = null) {
    const idx = itemIdx++;
    const tr = document.createElement('tr');
    tr.innerHTML = `
        <td>
            <select name="items[${idx}][category_id]" class="form-select form-select-sm form-select-solid cat-sel" data-control="select2" data-placeholder="Pilih sub-kategori..." style="width:100%">
                <option value=""></option>${CAT_OPTIONS}
            </select>
        </td>
        <td><div class="input-group input-group-sm"><input type="text" name="items[${idx}][qty_gram]" class="form-control form-control-sm form-control-solid text-end qty-gram" placeholder="0" /><span class="input-group-text">g</span></div></td>
        <td><div class="input-group input-group-sm"><span class="input-group-text">Rp</span><input type="text" name="items[${idx}][price_per_kg]" class="form-control form-control-sm form-control-solid text-end price-kg" placeholder="0" /></div></td>
        <td class="text-end fw-bold subtotal">Rp 0</td>
        <td class="text-end"><button type="button" class="btn btn-sm btn-light-danger btn-del"><i class="ki-outline ki-trash fs-3"></i></button></td>
    `;
    document.getElementById('items_body').appendChild(tr);

    const $tr = window.jQuery(tr);
    $tr.find('.cat-sel').select2({ width:'100%', dropdownParent: $tr.closest('.card') });
    maskRupiah($tr.find('.qty-gram')[0]);
    maskRupiah($tr.find('.price-kg')[0]);
    $tr.find('.qty-gram, .price-kg').on('input', () => recalcRow(tr));
    $tr.find('.btn-del').on('click', () => {
        tr.remove(); recalcTotals();
        if (! document.getElementById('items_body').children.length) addItemRow();
    });

    if (prefill) {
        $tr.find('.cat-sel').val(prefill.category_id).trigger('change');
        $tr.find('.qty-gram').val(Math.round(prefill.qty_gram).toLocaleString('id-ID'));
        $tr.find('.price-kg').val(Math.round(prefill.price_per_kg).toLocaleString('id-ID'));
        recalcRow(tr);
    }
}

function recalcRow(tr) {
    const qty = unmask(tr.querySelector('.qty-gram').value);
    const price = unmask(tr.querySelector('.price-kg').value);
    const sub = qty * price / 1000;
    tr.querySelector('.subtotal').textContent = fmtRp(sub);
    tr.dataset.subtotal = sub;
    recalcTotals();
}

function recalcTotals() {
    let total = 0;
    document.querySelectorAll('#items_body tr').forEach(tr => total += parseFloat(tr.dataset.subtotal||0));
    document.getElementById('ft_total').textContent = fmtRp(total);
    document.getElementById('sm_total').textContent = fmtRp(total);
}

document.addEventListener('DOMContentLoaded', () => {
    if (EXIST_ITEMS.length) EXIST_ITEMS.forEach(addItemRow);
    else addItemRow();
    document.getElementById('btn_add_item').addEventListener('click', () => addItemRow());
});
</script>
@endpush

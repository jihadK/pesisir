@php
    $isEdit = $isEdit ?? false;
    $existingItems = $isEdit ? $so->items->map(fn($i) => [
        'product_id'   => $i->product_id,
        'sku'          => $i->product->sku,
        'name'         => $i->product->name,
        'quantity'     => (float) $i->quantity,
        'unit_price'   => (float) $i->unit_price,
        'discount_pct' => (float) $i->discount_pct,
        'notes'        => $i->notes,
    ])->values() : collect();

    $sameDate = old('same_delivery_date',
        ($isEdit && $so->order_date && $so->delivery_date && $so->order_date->equalTo($so->delivery_date))
            || (! $isEdit && ! $so->delivery_date)
    );
@endphp

<div class="row">
    <div class="col-md-9">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Header Sales Order</h3></div>
            <div class="card-body">
                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Customer</label>
                    <div class="col-md-9">
                        <select name="customer_id" class="form-select form-select-solid @error('customer_id') is-invalid @enderror"
                                data-control="select2" data-placeholder="Pilih customer..." required>
                            <option value=""></option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @selected(old('customer_id', $so->customer_id)==$c->id)
                                        data-payment-terms="{{ $c->payment_terms_days }}">
                                    {{ $c->code }} — {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('customer_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Warehouse</label>
                    <div class="col-md-9">
                        <select name="warehouse_id" class="form-select form-select-solid @error('warehouse_id') is-invalid @enderror"
                                data-control="select2" data-placeholder="Pilih warehouse..." required>
                            <option value=""></option>
                            @foreach($warehouses as $w)
                                <option value="{{ $w->id }}" @selected(old('warehouse_id', $so->warehouse_id)==$w->id)>{{ $w->code }} — {{ $w->name }}</option>
                            @endforeach
                        </select>
                        @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Tanggal Order</label>
                    <div class="col-md-3">
                        <input type="date" name="order_date" id="so_order_date"
                               value="{{ old('order_date', $so->order_date?->toDateString() ?? now()->toDateString()) }}"
                               class="form-control form-control-solid @error('order_date') is-invalid @enderror" required />
                        @error('order_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <label class="col-form-label col-md-3 fw-semibold">Tanggal Kirim</label>
                    <div class="col-md-3">
                        <input type="date" name="delivery_date" id="so_delivery_date"
                               value="{{ old('delivery_date', $so->delivery_date?->toDateString()) }}"
                               class="form-control form-control-solid @error('delivery_date') is-invalid @enderror" />
                        <div class="form-check form-check-custom form-check-sm form-check-solid mt-2">
                            <input class="form-check-input" type="checkbox" id="chk_same_date" name="same_delivery_date" value="1" @checked($sameDate) />
                            <label class="form-check-label fs-8 ms-2" for="chk_same_date">Sama dengan tanggal order</label>
                        </div>
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold">Term Pembayaran</label>
                    <div class="col-md-3">
                        <div class="input-group">
                            <input type="number" name="payment_terms_days" min="0" max="365"
                                   value="{{ old('payment_terms_days', $so->payment_terms_days ?? 0) }}"
                                   class="form-control form-control-solid" />
                            <span class="input-group-text">hari</span>
                        </div>
                        <div class="form-text fs-8">0 = bayar sekarang/COD. Auto-fill dari customer.</div>
                    </div>
                    <label class="col-form-label col-md-3 fw-semibold">Metode Pembayaran</label>
                    <div class="col-md-3">
                        <select name="payment_method_id" class="form-select form-select-solid"
                                data-control="select2" data-placeholder="(Belum ditentukan)">
                            <option value="">— Belum ditentukan —</option>
                            @foreach($paymentMethods as $pm)
                                <option value="{{ $pm->id }}" @selected(old('payment_method_id', $so->payment_method_id)==$pm->id)>
                                    {{ $pm->name }}@if($pm->bank_name) ({{ $pm->bank_name }} {{ $pm->account_no }})@endif
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text fs-8">Bisa diubah customer minta ganti metode.</div>
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold">Catatan</label>
                    <div class="col-md-9">
                        <textarea name="notes" rows="2" class="form-control form-control-solid" maxlength="1000">{{ old('notes', $so->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">Item Pesanan</h3>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-light-primary btn-sm" id="btn_add_row">
                        <i class="ki-outline ki-plus fs-3"></i> Tambah Baris
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-row-bordered align-middle gy-3" id="tbl_items">
                        <thead>
                            <tr class="fw-bold text-muted bg-light">
                                <th style="width:32%">Produk *</th>
                                <th style="width:11%" class="text-end">Qty *</th>
                                <th style="width:14%" class="text-end">Harga *</th>
                                <th style="width:9%" class="text-end">Disc %</th>
                                <th style="width:18%" class="text-end">Subtotal</th>
                                <th style="width:8%"></th>
                            </tr>
                        </thead>
                        <tbody id="items_body"></tbody>
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="4" class="text-end">Subtotal</td>
                                <td class="text-end fs-5" id="ft_subtotal">Rp 0</td>
                                <td></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
                @error('items')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    <div class="col-md-3">
        <div class="card mb-5" style="position:sticky; top:80px">
            <div class="card-header"><h3 class="card-title">Total</h3></div>
            <div class="card-body">
                <div class="d-flex flex-stack mb-3 fs-7">
                    <span class="text-muted">Subtotal:</span>
                    <span class="fw-bold" id="sm_subtotal">Rp 0</span>
                </div>
                <div class="d-flex flex-stack mb-3 fs-7">
                    <span class="text-muted">Diskon:</span>
                    <div class="input-group input-group-sm" style="max-width:140px">
                        <span class="input-group-text">Rp</span>
                        <input type="text" name="discount_amount" id="sm_discount"
                               value="{{ old('discount_amount', $so->discount_amount ? number_format((float)$so->discount_amount, 0, ',', '.') : '0') }}"
                               class="form-control form-control-sm form-control-solid text-end" />
                    </div>
                </div>
                <div class="d-flex flex-stack mb-3 fs-7">
                    <span class="text-muted">Ongkir:</span>
                    <div class="input-group input-group-sm" style="max-width:140px">
                        <span class="input-group-text">Rp</span>
                        <input type="text" name="shipping_cost" id="sm_shipping"
                               value="{{ old('shipping_cost', $so->shipping_cost ? number_format((float)$so->shipping_cost, 0, ',', '.') : '0') }}"
                               class="form-control form-control-sm form-control-solid text-end" />
                    </div>
                </div>
                <div class="separator my-3"></div>
                <div class="d-flex flex-stack">
                    <span class="text-muted">TOTAL:</span>
                    <span class="fw-bolder fs-3 text-primary" id="sm_total">Rp 0</span>
                </div>
            </div>
        </div>

        <div class="d-flex flex-column gap-2">
            <button type="submit" class="btn btn-primary">
                <i class="ki-outline ki-check fs-2"></i> {{ $isEdit ? 'Update SO' : 'Simpan SO (Draft)' }}
            </button>
            <a href="{{ $isEdit ? route('sales_orders.show', $so) : route('sales_orders.index') }}" class="btn btn-light">Batal</a>
        </div>
    </div>
</div>

@php
    $productData = $products->map(fn($p) => [
        'id'           => $p->id,
        'sku'          => $p->sku,
        'name'         => $p->name,
        'uom'          => $p->baseUom?->code ?? '',
        'price'        => (float) $p->default_sell_price,
        'pack_content' => $p->pack_content_label,
        'pack_weight'  => $p->pack_weight_label,
    ])->values();
@endphp

@push('scripts')
<script>
const PRODUCTS = @json($productData);
const EXISTING = @json($existingItems);
const AVAILABLE_STOCK_URL = "{{ route('sales_orders.available-stock') }}";
const CSRF = document.querySelector('meta[name=csrf-token]')?.content;

// Build option label dengan pack info
function productOptionLabel(p) {
    let label = `${p.sku} — ${p.name}`;
    const packs = [];
    if (p.pack_content) packs.push(p.pack_content);
    if (p.pack_weight) packs.push(p.pack_weight);
    if (packs.length) label += ` (${packs.join(', ')})`;
    return label;
}
const PRODUCT_OPTIONS = PRODUCTS.map(p => `<option value="${p.id}">${productOptionLabel(p)}</option>`).join('');

// Cache stock available per warehouse {warehouseId: {productId: available}}
const STOCK_CACHE = {};
let currentWarehouseStocks = {};

function fmtQty(v) {
    const n = parseFloat(v) || 0;
    return Math.floor(n) === n ? n.toLocaleString('id-ID') : n.toLocaleString('id-ID', {minimumFractionDigits:3, maximumFractionDigits:3});
}

async function loadStocksForWarehouse(warehouseId) {
    if (! warehouseId) {
        currentWarehouseStocks = {};
        refreshAllStockInfo();
        return;
    }
    if (STOCK_CACHE[warehouseId]) {
        currentWarehouseStocks = STOCK_CACHE[warehouseId];
        refreshAllStockInfo();
        return;
    }
    try {
        const res = await fetch(`${AVAILABLE_STOCK_URL}?warehouse_id=${warehouseId}`, {
            headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': CSRF }
        });
        const json = await res.json();
        if (json.resCode === '00') {
            const map = {};
            (json.data.stocks || []).forEach(s => { map[s.product_id] = parseFloat(s.available); });
            STOCK_CACHE[warehouseId] = map;
            currentWarehouseStocks = map;
            refreshAllStockInfo();
        }
    } catch (e) { /* silent */ }
}

function refreshAllStockInfo() {
    document.querySelectorAll('#items_body tr').forEach(tr => updateStockInfoRow(tr));
}

function updateStockInfoRow(tr) {
    const prodId = tr.querySelector('.prod-sel')?.value;
    const stockInfo = tr.querySelector('.stock-info');
    const qtyEl = tr.querySelector('.qty');
    if (! stockInfo) return;

    if (! prodId) {
        stockInfo.innerHTML = '';
        qtyEl?.removeAttribute('max');
        return;
    }
    const avail = currentWarehouseStocks[prodId];
    if (avail === undefined || avail <= 0) {
        stockInfo.innerHTML = '<span class="text-danger fw-bold">⚠ Stock kosong</span>';
        qtyEl.setAttribute('max', '0');
        return;
    }
    qtyEl.setAttribute('max', String(avail));
    const qty = parseFloat(qtyEl.value) || 0;
    if (qty > avail) {
        stockInfo.innerHTML = `<span class="text-danger fw-bold">⚠ Tersedia: ${fmtQty(avail)} (qty melebihi!)</span>`;
        qtyEl.classList.add('is-invalid');
    } else {
        stockInfo.innerHTML = `<span class="text-success">Tersedia: ${fmtQty(avail)}</span>`;
        qtyEl.classList.remove('is-invalid');
    }
}

let rowIdx = 0;

function fmtRp(v) { return 'Rp ' + Math.round(v).toLocaleString('id-ID'); }
function unmask(s) { return parseFloat((s||'0').toString().replace(/\./g,'').replace(',','.')) || 0; }

function maskRupiah(el) {
    if (typeof Inputmask === 'undefined') return;
    Inputmask({ alias:'numeric', groupSeparator:'.', radixPoint:',', digits:0, allowMinus:false, removeMaskOnSubmit:false }).mask(el);
}

function recalcRow(tr) {
    const qty   = parseFloat(tr.querySelector('.qty').value) || 0;
    const price = unmask(tr.querySelector('.price').value);
    const disc  = parseFloat(tr.querySelector('.disc').value) || 0;
    const sub   = qty * price * (1 - disc/100);
    tr.querySelector('.subtotal').textContent = fmtRp(sub);
    tr.dataset.subtotal = sub;
    recalcTotals();
}

function recalcTotals() {
    let subtotal = 0;
    document.querySelectorAll('#items_body tr').forEach(tr => {
        subtotal += parseFloat(tr.dataset.subtotal || 0);
    });
    const disc     = unmask(document.getElementById('sm_discount').value);
    const shipping = unmask(document.getElementById('sm_shipping').value);
    const total    = Math.max(0, subtotal - disc + shipping);

    document.getElementById('ft_subtotal').textContent = fmtRp(subtotal);
    document.getElementById('sm_subtotal').textContent = fmtRp(subtotal);
    document.getElementById('sm_total').textContent    = fmtRp(total);
}

function addRow(prefill = null) {
    const idx = rowIdx++;
    const tr = document.createElement('tr');
    tr.dataset.idx = idx;
    tr.innerHTML = `
        <td>
            <select name="items[${idx}][product_id]" class="form-select form-select-sm form-select-solid prod-sel"
                    data-control="select2" data-placeholder="Pilih produk..." style="width:100%">
                <option value=""></option>
                ${PRODUCT_OPTIONS}
            </select>
            <div class="pack-info text-muted fs-8 mt-1"></div>
        </td>
        <td>
            <input type="number" step="0.001" min="0.001" name="items[${idx}][quantity]"
                   class="form-control form-control-sm form-control-solid text-end qty" placeholder="0" />
            <div class="stock-info fs-8 mt-1 text-end"></div>
        </td>
        <td>
            <input type="text" name="items[${idx}][unit_price]"
                   class="form-control form-control-sm form-control-solid text-end price" placeholder="0" />
        </td>
        <td>
            <input type="number" min="0" max="100" step="0.01" name="items[${idx}][discount_pct]" value="0"
                   class="form-control form-control-sm form-control-solid text-end disc" />
        </td>
        <td class="text-end fw-bold subtotal">Rp 0</td>
        <td class="text-end">
            <button type="button" class="btn btn-sm btn-light-danger btn-del">
                <i class="ki-outline ki-trash fs-3"></i>
            </button>
        </td>
    `;
    document.getElementById('items_body').appendChild(tr);

    const $tr = window.jQuery(tr);
    $tr.find('.prod-sel').select2({
        placeholder: 'Pilih produk...',
        width: '100%',
        dropdownParent: $tr.closest('.card')
    }).on('change', function () {
        const p = PRODUCTS.find(x => x.id == this.value);
        const packInfoEl = $tr.find('.pack-info')[0];
        if (p) {
            const priceEl = $tr.find('.price')[0];
            const qtyEl   = $tr.find('.qty')[0];
            if (! priceEl.value) {
                priceEl.value = p.price ? Math.round(p.price).toLocaleString('id-ID') : '';
            }
            if (! qtyEl.value) qtyEl.value = '1';
            // tampilkan pack info di bawah dropdown produk
            const packs = [];
            if (p.pack_content) packs.push(`<i class="ki-outline ki-element-equal-1 fs-8"></i> ${p.pack_content}`);
            if (p.pack_weight) packs.push(`<i class="ki-outline ki-scale fs-8"></i> ${p.pack_weight}`);
            packInfoEl.innerHTML = packs.join(' · ');
            recalcRow(tr);
            updateStockInfoRow(tr);
        } else {
            packInfoEl.innerHTML = '';
            updateStockInfoRow(tr);
        }
    });

    maskRupiah($tr.find('.price')[0]);
    $tr.find('.qty, .disc, .price').on('input', () => {
        recalcRow(tr);
        updateStockInfoRow(tr);
    });

    $tr.find('.btn-del').on('click', () => {
        tr.remove();
        recalcTotals();
        if (! document.getElementById('items_body').children.length) addRow();
    });

    // Prefill (edit mode)
    if (prefill) {
        $tr.find('.prod-sel').val(prefill.product_id).trigger('change.select2');
        $tr.find('.qty').val(prefill.quantity);
        $tr.find('.price').val(Math.round(prefill.unit_price).toLocaleString('id-ID'));
        $tr.find('.disc').val(prefill.discount_pct);
        recalcRow(tr);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    maskRupiah(document.getElementById('sm_discount'));
    maskRupiah(document.getElementById('sm_shipping'));
    document.getElementById('sm_discount').addEventListener('input', recalcTotals);
    document.getElementById('sm_shipping').addEventListener('input', recalcTotals);

    if (EXISTING.length) {
        EXISTING.forEach(addRow);
    } else {
        addRow();
    }

    document.getElementById('btn_add_row').addEventListener('click', () => addRow());

    // Auto-fill payment_terms dari customer
    const custEl = document.querySelector('select[name="customer_id"]');
    if (custEl) {
        window.jQuery(custEl).on('change', function () {
            const sel = this.options[this.selectedIndex];
            const days = sel.dataset.paymentTerms;
            if (days !== undefined && days !== '') {
                const ptEl = document.querySelector('input[name="payment_terms_days"]');
                if (ptEl) ptEl.value = days;
            }
        });
    }

    // Checkbox: tanggal kirim sama dengan tanggal order
    const orderEl    = document.getElementById('so_order_date');
    const deliveryEl = document.getElementById('so_delivery_date');
    const chkSame    = document.getElementById('chk_same_date');
    function applySameDate() {
        if (chkSame.checked) {
            deliveryEl.value = orderEl.value;
            deliveryEl.setAttribute('readonly', 'readonly');
            deliveryEl.classList.add('bg-light');
        } else {
            deliveryEl.removeAttribute('readonly');
            deliveryEl.classList.remove('bg-light');
        }
    }
    chkSame.addEventListener('change', applySameDate);
    orderEl.addEventListener('change', () => { if (chkSame.checked) deliveryEl.value = orderEl.value; });
    applySameDate();

    // Load stock saat warehouse berubah
    const whEl = document.querySelector('select[name="warehouse_id"]');
    if (whEl) {
        window.jQuery(whEl).on('change', function () {
            loadStocksForWarehouse(this.value);
        });
        // initial load (kalau ada nilai default)
        if (whEl.value) loadStocksForWarehouse(whEl.value);
    }
});
</script>
@endpush

@php $isEdit = $isEdit ?? false; @endphp

<div class="row">
    <div class="col-md-9">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">{{ $isEdit ? 'Edit Kontrak Harga' : 'Kontrak Harga Baru' }}</h3></div>
            <div class="card-body">
                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Customer</label>
                    <div class="col-md-9">
                        <select name="customer_id" class="form-select form-select-solid" data-control="select2" required>
                            <option value=""></option>
                            @foreach($customers as $c)
                                <option value="{{ $c->id }}" @selected(old('customer_id', $row->customer_id)==$c->id)>{{ $c->code }} — {{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Produk</label>
                    <div class="col-md-9">
                        <select name="product_id" id="prod_sel" class="form-select form-select-solid" data-control="select2" required>
                            <option value=""></option>
                            @foreach($products as $p)
                                @php
                                    $packs = [];
                                    if ($p->pack_content_label) $packs[] = $p->pack_content_label;
                                    if ($p->pack_weight_label)  $packs[] = $p->pack_weight_label;
                                    $packStr = $packs ? ' — ' . implode(', ', $packs) : '';
                                @endphp
                                <option value="{{ $p->id }}"
                                        data-default-price="{{ (float)$p->default_sell_price }}"
                                        data-pack-content="{{ $p->pack_content_label }}"
                                        data-pack-weight="{{ $p->pack_weight_label }}"
                                        @selected(old('product_id', $row->product_id)==$p->id)>
                                    {{ $p->sku }} — {{ $p->name }}{{ $packStr }} (default Rp {{ number_format((float)$p->default_sell_price, 0, ',', '.') }})
                                </option>
                            @endforeach
                        </select>
                        <div class="form-text fs-8" id="default_price_hint"></div>
                        <div id="pack_info" class="fs-7 mt-2"></div>
                    </div>
                </div>
                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Harga Kontrak</label>
                    <div class="col-md-9">
                        <div class="input-group" style="max-width:240px">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="price" id="price_input" value="{{ old('price', $row->price ? number_format((float)$row->price, 0, ',', '.') : '') }}" class="form-control form-control-solid text-end" required />
                        </div>
                    </div>
                </div>
                {{-- Min Qty di-hide sesuai UAT, tetap dikirim 0 ke server --}}
                <input type="hidden" name="min_quantity" value="0" />
                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Berlaku Mulai</label>
                    <div class="col-md-3">
                        <input type="date" name="effective_from" value="{{ old('effective_from', $row->effective_from?->toDateString() ?? now()->toDateString()) }}" class="form-control form-control-solid" required />
                    </div>
                    <label class="col-form-label col-md-3 fw-semibold">Sampai (opsional)</label>
                    <div class="col-md-3">
                        <input type="date" name="effective_to" value="{{ old('effective_to', $row->effective_to?->toDateString()) }}" class="form-control form-control-solid" />
                        <div class="form-text fs-8">Kosongkan = berlaku selamanya.</div>
                    </div>
                </div>
                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold">Catatan</label>
                    <div class="col-md-9">
                        <input type="text" name="notes" maxlength="255" value="{{ old('notes', $row->notes) }}" class="form-control form-control-solid" placeholder="Mis. Kontrak per Mei 2026, dll." />
                    </div>
                </div>
                <div class="row">
                    <label class="col-form-label col-md-3 fw-semibold">Status</label>
                    <div class="col-md-9">
                        <label class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $row->is_active ?? true))>
                            <span class="form-check-label fw-semibold">Aktif</span>
                        </label>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="d-flex flex-column gap-2" style="position:sticky; top:80px">
            <button type="submit" class="btn btn-primary"><i class="ki-outline ki-check fs-2"></i> {{ $isEdit ? 'Update' : 'Simpan' }}</button>
            <a href="{{ route('customer_prices.index') }}" class="btn btn-light">Batal</a>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const sel = document.getElementById('prod_sel');
    const hint = document.getElementById('default_price_hint');
    const priceEl = document.getElementById('price_input');
    const packInfo = document.getElementById('pack_info');
    function updateHint() {
        const opt = sel.options[sel.selectedIndex];
        const p = parseFloat(opt?.dataset.defaultPrice || 0);
        if (p) {
            hint.innerHTML = `Harga default Rp ${Math.round(p).toLocaleString('id-ID')} — kontrak akan override harga ini.`;
        } else hint.innerHTML = '';

        // Tampilkan pack info badge
        const packs = [];
        if (opt?.dataset.packContent) packs.push(`<span class="badge badge-light-info me-1">${opt.dataset.packContent}</span>`);
        if (opt?.dataset.packWeight)  packs.push(`<span class="badge badge-light-warning">${opt.dataset.packWeight}</span>`);
        packInfo.innerHTML = packs.join('');
    }
    sel?.addEventListener('change', updateHint);
    updateHint();
    if (typeof Inputmask !== 'undefined') {
        Inputmask({ alias:'numeric', groupSeparator:'.', radixPoint:',', digits:0, allowMinus:false, removeMaskOnSubmit:false }).mask(priceEl);
    }
});
</script>
@endpush

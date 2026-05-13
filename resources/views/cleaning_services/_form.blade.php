@php $isEdit = $isEdit ?? false; @endphp

<div class="row">
    <div class="col-md-8">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">{{ $isEdit ? 'Edit Jasa Bersih' : 'Catat Jasa Bersih' }}</h3></div>
            <div class="card-body">
                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Tanggal</label>
                    <div class="col-md-4">
                        <input type="date" name="service_date" value="{{ old('service_date', $service->service_date?->toDateString() ?? now()->toDateString()) }}"
                               class="form-control form-control-solid @error('service_date') is-invalid @enderror" required />
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Pegawai</label>
                    <div class="col-md-9">
                        <select name="employee_id" class="form-select form-select-solid @error('employee_id') is-invalid @enderror" data-control="select2" required>
                            <option value="">Pilih pegawai...</option>
                            @foreach($employees as $e)
                                <option value="{{ $e->id }}" @selected(old('employee_id', $service->employee_id)==$e->id)>{{ $e->code }} — {{ $e->name }}</option>
                            @endforeach
                        </select>
                        @error('employee_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Sub-Kategori</label>
                    <div class="col-md-9">
                        <select name="category_id" id="cs_category" class="form-select form-select-solid @error('category_id') is-invalid @enderror" data-control="select2" required>
                            <option value="">Pilih sub-kategori...</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" @selected(old('category_id', $service->category_id)==$c->id)>
                                    {{ $c->parent?->name ? $c->parent->name . ' › ' : '' }}{{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Qty (Kg)</label>
                    <div class="col-md-4">
                        <div class="input-group">
                            <input type="text" name="qty_kg" id="cs_qty" value="{{ old('qty_kg', $service->qty_kg ? rtrim(rtrim(number_format((float)$service->qty_kg, 3, ',', '.'), '0'), ',') : '') }}"
                                   class="form-control form-control-solid text-end @error('qty_kg') is-invalid @enderror" required />
                            <span class="input-group-text">kg</span>
                        </div>
                        @error('qty_kg')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold required">Tarif per Kg</label>
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="rate_per_kg" id="cs_rate" value="{{ old('rate_per_kg', $service->rate_per_kg ? number_format((float)$service->rate_per_kg, 0, ',', '.') : '') }}"
                                   class="form-control form-control-solid text-end @error('rate_per_kg') is-invalid @enderror" required />
                        </div>
                        @error('rate_per_kg')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="form-text fs-8">Auto-fill dari master Tarif Jasa kalau ada match dengan sub-kategori.</div>
                    </div>
                </div>

                <div class="row mb-4">
                    <label class="col-form-label col-md-3 fw-semibold">Subtotal</label>
                    <div class="col-md-9">
                        <div class="fs-2 fw-bolder text-primary" id="cs_subtotal">Rp 0</div>
                    </div>
                </div>

                <div class="row">
                    <label class="col-form-label col-md-3 fw-semibold">Catatan</label>
                    <div class="col-md-9">
                        <textarea name="notes" rows="2" class="form-control form-control-solid" maxlength="1000">{{ old('notes', $service->notes) }}</textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('cleaning_services.index') }}" class="btn btn-light">Batal</a>
            <button type="submit" class="btn btn-primary"><i class="ki-outline ki-check fs-2"></i> {{ $isEdit ? 'Update' : 'Simpan' }}</button>
        </div>
    </div>
</div>

@php
    $rateData = $serviceRates->map(fn($r) => [
        'id'          => $r->id,
        'category_id' => $r->category_id,
        'rate_per_kg' => (float) $r->rate_per_kg,
    ])->values();
@endphp

@push('scripts')
<script>
const RATES = @json($rateData);

function fmtRp(v) { return 'Rp ' + Math.round(parseFloat(v)||0).toLocaleString('id-ID'); }
function unmask(s) { return parseFloat((s||'0').toString().replace(/\./g,'').replace(',','.')) || 0; }

function recalc() {
    const qty  = unmask(document.getElementById('cs_qty').value);
    const rate = unmask(document.getElementById('cs_rate').value);
    document.getElementById('cs_subtotal').textContent = fmtRp(qty * rate);
}

document.addEventListener('DOMContentLoaded', function () {
    const qtyEl  = document.getElementById('cs_qty');
    const rateEl = document.getElementById('cs_rate');
    if (typeof Inputmask !== 'undefined') {
        Inputmask({ alias:'numeric', groupSeparator:'.', radixPoint:',', digits:3, allowMinus:false, removeMaskOnSubmit:false }).mask(qtyEl);
        Inputmask({ alias:'numeric', groupSeparator:'.', radixPoint:',', digits:0, allowMinus:false, removeMaskOnSubmit:false }).mask(rateEl);
    }
    qtyEl.addEventListener('input', recalc);
    rateEl.addEventListener('input', recalc);

    // Auto-fill rate berdasarkan kategori
    const catEl = document.getElementById('cs_category');
    window.jQuery(catEl).on('change', function () {
        if (rateEl.value && unmask(rateEl.value) > 0) return; // jangan timpa kalau sudah diisi manual
        const catId = this.value;
        const rate = RATES.find(r => r.category_id == catId) || RATES.find(r => r.category_id === null);
        if (rate) {
            rateEl.value = Math.round(rate.rate_per_kg).toLocaleString('id-ID');
            recalc();
        }
    });

    recalc();
});
</script>
@endpush

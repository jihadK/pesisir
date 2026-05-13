@php $isEdit = $isEdit ?? false; @endphp

<div class="card mb-5">
    <div class="card-header"><h3 class="card-title">{{ $isEdit ? 'Edit Tarif Jasa' : 'Tarif Jasa Baru' }}</h3></div>
    <div class="card-body">
        <div class="row mb-4">
            <label class="col-form-label col-md-3 fw-semibold required">Nama Jasa</label>
            <div class="col-md-9">
                <input type="text" name="name" value="{{ old('name', $rate->name) }}"
                       class="form-control form-control-solid @error('name') is-invalid @enderror"
                       maxlength="100" required placeholder="Mis. Bersihkan Tuna" />
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row mb-4">
            <label class="col-form-label col-md-3 fw-semibold">Sub-Kategori</label>
            <div class="col-md-9">
                <select name="category_id" class="form-select form-select-solid" data-control="select2" data-placeholder="(Berlaku semua kategori)">
                    <option value="">— Berlaku untuk semua —</option>
                    @foreach($categories as $c)
                        <option value="{{ $c->id }}" @selected(old('category_id', $rate->category_id)==$c->id)>{{ $c->name }}</option>
                    @endforeach
                </select>
                <div class="form-text fs-8">Kosongkan kalau tarif berlaku untuk semua sub-kategori. Pilih spesifik kalau tarif beda per jenis ikan.</div>
            </div>
        </div>

        <div class="row mb-4">
            <label class="col-form-label col-md-3 fw-semibold required">Tarif per Kg</label>
            <div class="col-md-4">
                <div class="input-group">
                    <span class="input-group-text">Rp</span>
                    <input type="text" name="rate_per_kg" id="rate_input"
                           value="{{ old('rate_per_kg', $rate->rate_per_kg ? number_format((float)$rate->rate_per_kg, 0, ',', '.') : '') }}"
                           class="form-control form-control-solid text-end @error('rate_per_kg') is-invalid @enderror" required />
                    <span class="input-group-text">/ kg</span>
                </div>
                @error('rate_per_kg')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row mb-4">
            <label class="col-form-label col-md-3 fw-semibold">Catatan</label>
            <div class="col-md-9">
                <textarea name="notes" rows="2" class="form-control form-control-solid" maxlength="255">{{ old('notes', $rate->notes) }}</textarea>
            </div>
        </div>

        <div class="row">
            <label class="col-form-label col-md-3 fw-semibold">Status</label>
            <div class="col-md-9">
                <div class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="sr_active" @checked(old('is_active', $rate->is_active ?? true)) />
                    <label class="form-check-label fw-semibold ms-3" for="sr_active">Aktif</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('service_rates.index') }}" class="btn btn-light">Batal</a>
    <button type="submit" class="btn btn-primary"><i class="ki-outline ki-check fs-2"></i> {{ $isEdit ? 'Update' : 'Simpan' }}</button>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const el = document.getElementById('rate_input');
    if (el && typeof Inputmask !== 'undefined') {
        Inputmask({ alias:'numeric', groupSeparator:'.', radixPoint:',', digits:0, allowMinus:false, removeMaskOnSubmit:false }).mask(el);
    }
});
</script>
@endpush

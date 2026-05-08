@php $isEdit = $isEdit ?? false; @endphp

<div class="row">
    <div class="col-md-8">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">{{ $isEdit ? 'Edit Tier' : 'Tier Baru' }}</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Nama</label>
                    <div class="col-md-9">
                        <input type="text" name="name" value="{{ old('name', $tier->name) }}"
                               class="form-control form-control-solid @error('name') is-invalid @enderror"
                               placeholder="Retail / Grosir / Reseller / Restoran" maxlength="50" required />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Deskripsi</label>
                    <div class="col-md-9">
                        <textarea name="description" rows="3"
                                  class="form-control form-control-solid @error('description') is-invalid @enderror"
                                  maxlength="255">{{ old('description', $tier->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Status</h3></div>
            <div class="card-body">
                <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active_switch"
                           @checked(old('is_active', $tier->is_active ?? true)) />
                    <label class="form-check-label fw-semibold ms-3" for="is_active_switch">Aktif</label>
                </div>
                <div class="text-muted fs-7">Tier non-aktif tidak akan muncul saat assign customer atau set harga produk.</div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('price_tiers.index') }}" class="btn btn-light">Batal</a>
    <button type="submit" class="btn btn-primary"><i class="ki-outline ki-check fs-2"></i> {{ $isEdit ? 'Update' : 'Simpan' }}</button>
</div>

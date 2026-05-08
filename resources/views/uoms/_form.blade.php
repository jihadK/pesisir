@php $isEdit = $isEdit ?? false; @endphp

<div class="row">
    <div class="col-md-8">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">{{ $isEdit ? 'Edit Satuan' : 'Satuan Baru' }}</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Kode</label>
                    <div class="col-md-9">
                        <input type="text" name="code" value="{{ old('code', $uom->code) }}"
                               class="form-control form-control-solid text-uppercase @error('code') is-invalid @enderror"
                               placeholder="KG, BOX, EKR" maxlength="10"
                               {{ $isEdit ? 'readonly' : 'required' }} />
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ $isEdit ? 'Kode tidak bisa diubah.' : 'Huruf kapital atau angka. Contoh: KG, EKR, BOX' }}</div>
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Nama</label>
                    <div class="col-md-9">
                        <input type="text" name="name" value="{{ old('name', $uom->name) }}"
                               class="form-control form-control-solid @error('name') is-invalid @enderror"
                               placeholder="Kilogram" maxlength="50" required />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Simbol</label>
                    <div class="col-md-9">
                        <input type="text" name="symbol" value="{{ old('symbol', $uom->symbol) }}"
                               class="form-control form-control-solid @error('symbol') is-invalid @enderror"
                               placeholder="kg" maxlength="10" />
                        @error('symbol')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Simbol pendek untuk display (mis. "kg", "ekr").</div>
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Deskripsi</label>
                    <div class="col-md-9">
                        <textarea name="description" rows="3"
                                  class="form-control form-control-solid @error('description') is-invalid @enderror"
                                  maxlength="255" placeholder="Penjelasan singkat tentang satuan ini">{{ old('description', $uom->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('uoms.index') }}" class="btn btn-light">Batal</a>
    <button type="submit" class="btn btn-primary"><i class="ki-outline ki-check fs-2"></i> {{ $isEdit ? 'Update' : 'Simpan' }}</button>
</div>

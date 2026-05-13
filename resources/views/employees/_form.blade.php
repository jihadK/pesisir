@php $isEdit = $isEdit ?? false; @endphp

<div class="card mb-5">
    <div class="card-header"><h3 class="card-title">{{ $isEdit ? 'Edit Pegawai' : 'Pegawai Baru' }}</h3></div>
    <div class="card-body">
        <div class="row mb-4">
            <label class="col-form-label col-md-3 fw-semibold required">Kode</label>
            <div class="col-md-4">
                <input type="text" name="code" value="{{ old('code', $employee->code) }}"
                       class="form-control form-control-solid text-uppercase @error('code') is-invalid @enderror"
                       maxlength="20" required placeholder="EMP-001" style="text-transform:uppercase" />
                @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row mb-4">
            <label class="col-form-label col-md-3 fw-semibold required">Nama</label>
            <div class="col-md-9">
                <input type="text" name="name" value="{{ old('name', $employee->name) }}"
                       class="form-control form-control-solid @error('name') is-invalid @enderror" maxlength="100" required />
                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        <div class="row mb-4">
            <label class="col-form-label col-md-3 fw-semibold">Posisi/Jabatan</label>
            <div class="col-md-9">
                <input type="text" name="position" value="{{ old('position', $employee->position) }}"
                       class="form-control form-control-solid" maxlength="50" placeholder="Mis. Pembersih Ikan, Packing, dll" />
            </div>
        </div>

        <div class="row mb-4">
            <label class="col-form-label col-md-3 fw-semibold">No. HP</label>
            <div class="col-md-9">
                <input type="text" name="phone" value="{{ old('phone', $employee->phone) }}"
                       class="form-control form-control-solid" maxlength="20" />
            </div>
        </div>

        <div class="row mb-4">
            <label class="col-form-label col-md-3 fw-semibold">Catatan</label>
            <div class="col-md-9">
                <textarea name="notes" rows="2" class="form-control form-control-solid" maxlength="255">{{ old('notes', $employee->notes) }}</textarea>
            </div>
        </div>

        <div class="row">
            <label class="col-form-label col-md-3 fw-semibold">Status</label>
            <div class="col-md-9">
                <div class="form-check form-switch form-check-custom form-check-solid">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="emp_active" @checked(old('is_active', $employee->is_active ?? true)) />
                    <label class="form-check-label fw-semibold ms-3" for="emp_active">Aktif</label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('employees.index') }}" class="btn btn-light">Batal</a>
    <button type="submit" class="btn btn-primary">
        <i class="ki-outline ki-check fs-2"></i> {{ $isEdit ? 'Update' : 'Simpan' }}
    </button>
</div>

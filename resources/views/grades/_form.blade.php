@php $isEdit = $isEdit ?? false; @endphp

<div class="row">
    <div class="col-md-8">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">{{ $isEdit ? 'Edit Grade' : 'Grade Baru' }}</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Kode</label>
                    <div class="col-md-9">
                        <input type="text" name="code" value="{{ old('code', $grade->code) }}"
                               class="form-control form-control-solid text-uppercase @error('code') is-invalid @enderror"
                               placeholder="A, B, C" maxlength="10"
                               {{ $isEdit ? 'readonly' : 'required' }} />
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ $isEdit ? 'Kode tidak bisa diubah.' : 'Huruf kapital atau angka. Contoh: A, B, C' }}</div>
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Nama</label>
                    <div class="col-md-9">
                        <input type="text" name="name" value="{{ old('name', $grade->name) }}"
                               class="form-control form-control-solid @error('name') is-invalid @enderror"
                               placeholder="Premium / Sashimi Grade" maxlength="50" required />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Warna Badge</label>
                    <div class="col-md-9">
                        <div class="d-flex align-items-center gap-3">
                            <input type="color" name="color" id="grade_color"
                                   value="{{ old('color', $grade->color ?? '#6c757d') }}"
                                   class="form-control form-control-color form-control-solid"
                                   style="width:60px;height:42px" />
                            <input type="text" id="grade_color_hex"
                                   value="{{ old('color', $grade->color ?? '#6c757d') }}"
                                   class="form-control form-control-solid @error('color') is-invalid @enderror"
                                   style="max-width:130px;font-family:monospace" maxlength="7"
                                   pattern="^#[0-9A-Fa-f]{6}$" />
                            <div class="badge fw-bold px-3 py-2" id="grade_preview"
                                 style="background:{{ old('color', $grade->color ?? '#6c757d') }}">
                                Preview
                            </div>
                        </div>
                        @error('color')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="form-text">Pilih warna untuk badge grade. Format hex 6 digit (mis. #FFD700).</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('grades.index') }}" class="btn btn-light">Batal</a>
    <button type="submit" class="btn btn-primary"><i class="ki-outline ki-check fs-2"></i> {{ $isEdit ? 'Update' : 'Simpan' }}</button>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    var picker = document.getElementById('grade_color');
    var hex    = document.getElementById('grade_color_hex');
    var preview= document.getElementById('grade_preview');

    function getContrastText(hexColor) {
        var h = hexColor.replace('#','');
        if (h.length !== 6) return '#fff';
        var r = parseInt(h.substr(0,2),16), g = parseInt(h.substr(2,2),16), b = parseInt(h.substr(4,2),16);
        var luma = (0.299*r + 0.587*g + 0.114*b) / 255;
        return luma > 0.6 ? '#1f2937' : '#ffffff';
    }
    function applyColor(c) {
        if (! /^#[0-9A-Fa-f]{6}$/.test(c)) return;
        preview.style.background = c;
        preview.style.color = getContrastText(c);
    }
    // sync picker → hex input
    picker.addEventListener('input', function () { hex.value = this.value.toUpperCase(); applyColor(this.value); });
    // sync hex input → picker
    hex.addEventListener('input', function () {
        if (/^#[0-9A-Fa-f]{6}$/.test(this.value)) { picker.value = this.value; applyColor(this.value); }
    });
    applyColor(hex.value);
});
</script>
@endpush

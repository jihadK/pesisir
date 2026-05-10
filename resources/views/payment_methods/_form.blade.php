@php $isEdit = $isEdit ?? false; @endphp

<div class="row">
    <div class="col-md-8">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">{{ $isEdit ? 'Edit Metode' : 'Metode Baru' }}</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Kode</label>
                    <div class="col-md-9">
                        <input type="text" name="code" value="{{ old('code', $method->code) }}"
                               class="form-control form-control-solid text-uppercase @error('code') is-invalid @enderror"
                               placeholder="TF-BCA / QRIS / COD" maxlength="20" required style="text-transform:uppercase" />
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Nama</label>
                    <div class="col-md-9">
                        <input type="text" name="name" value="{{ old('name', $method->name) }}"
                               class="form-control form-control-solid @error('name') is-invalid @enderror" maxlength="50" required />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Tipe</label>
                    <div class="col-md-9">
                        <select name="type" id="pm_type" class="form-select form-select-solid @error('type') is-invalid @enderror" required>
                            <option value="">Pilih tipe...</option>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', $method->type)==$key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5 type-bank">
                    <label class="col-form-label col-md-3 fw-semibold">Nama Bank</label>
                    <div class="col-md-9">
                        <input type="text" name="bank_name" value="{{ old('bank_name', $method->bank_name) }}"
                               class="form-control form-control-solid" placeholder="BCA / Mandiri / BRI" maxlength="50" />
                    </div>
                </div>

                <div class="row mb-5 type-bank">
                    <label class="col-form-label col-md-3 fw-semibold">Nomor Rekening</label>
                    <div class="col-md-9">
                        <input type="text" name="account_no" value="{{ old('account_no', $method->account_no) }}"
                               class="form-control form-control-solid" placeholder="0000000001" maxlength="50" />
                    </div>
                </div>

                <div class="row mb-5 type-bank">
                    <label class="col-form-label col-md-3 fw-semibold">Atas Nama</label>
                    <div class="col-md-9">
                        <input type="text" name="account_holder" value="{{ old('account_holder', $method->account_holder) }}"
                               class="form-control form-control-solid" maxlength="100" />
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Deskripsi</label>
                    <div class="col-md-9">
                        <input type="text" name="description" value="{{ old('description', $method->description) }}"
                               class="form-control form-control-solid" maxlength="255"
                               placeholder="Mis. 'Bayar tunai saat barang dikirim' atau 'Scan untuk e-wallet'" />
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Urutan Tampil</label>
                    <div class="col-md-3">
                        <input type="number" name="display_order" value="{{ old('display_order', $method->display_order) }}"
                               class="form-control form-control-solid" min="0" max="9999" />
                        <div class="form-text">Angka kecil tampil duluan.</div>
                    </div>
                </div>

                <div class="row">
                    <label class="col-form-label col-md-3 fw-semibold">Status</label>
                    <div class="col-md-9">
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="pm_active"
                                   @checked(old('is_active', $method->is_active ?? true)) />
                            <label class="form-check-label fw-semibold ms-3" for="pm_active">Aktif</label>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4 type-qris">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">QRIS Image</h3></div>
            <div class="card-body text-center">
                <div class="mb-3">
                    @if($method->qris_image_display_url)
                        <img src="{{ $method->qris_image_display_url }}" id="qris_preview" alt="QRIS"
                             style="max-width:100%;height:auto;border:1px solid #eee;border-radius:6px" />
                    @else
                        <div id="qris_preview_empty" class="text-muted py-5 border border-dashed rounded">
                            <i class="ki-outline ki-scan-barcode fs-3x"></i>
                            <div class="mt-2">Belum ada QRIS</div>
                        </div>
                    @endif
                </div>
                <input type="file" name="qris_image" accept="image/png,image/jpeg,image/webp"
                       class="form-control form-control-sm form-control-solid" />
                <div class="form-text mt-1">PNG/JPG/WebP. Maks 1 MB.</div>

                @if($isEdit && $method->qris_image_url)
                    <div class="form-check form-check-custom form-check-solid mt-3 justify-content-center">
                        <input type="checkbox" name="remove_qris" value="1" id="rm_qris" class="form-check-input" />
                        <label for="rm_qris" class="form-check-label fs-7 ms-2 text-danger">Hapus QRIS saat ini</label>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('payment_methods.index') }}" class="btn btn-light">Batal</a>
    <button type="submit" class="btn btn-primary">
        <i class="ki-outline ki-check fs-2"></i> {{ $isEdit ? 'Update' : 'Simpan' }}
    </button>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const typeEl = document.getElementById('pm_type');
    function applyTypeVisibility() {
        const t = typeEl.value;
        const showBank = (t === 'transfer' || t === 'giro' || t === 'cheque');
        const showQris = (t === 'ewallet');
        document.querySelectorAll('.type-bank').forEach(el => el.style.display = showBank ? '' : 'none');
        document.querySelectorAll('.type-qris').forEach(el => el.style.display = showQris ? '' : 'none');
    }
    typeEl.addEventListener('change', applyTypeVisibility);
    applyTypeVisibility();
});
</script>
@endpush

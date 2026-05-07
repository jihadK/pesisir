@php $isEdit = $isEdit ?? false; @endphp

<div class="row">
    <div class="col-md-8">
        {{-- Section 1: Identitas --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Identitas Customer</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Kode</label>
                    <div class="col-md-9">
                        <input type="text" name="code" value="{{ old('code', $customer->code) }}"
                               class="form-control form-control-solid text-uppercase @error('code') is-invalid @enderror"
                               placeholder="CUST-001" maxlength="20"
                               {{ $isEdit ? 'readonly' : 'required' }} />
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ $isEdit ? 'Kode tidak bisa diubah setelah dibuat.' : 'Huruf kapital, angka, tanda hubung. Contoh: CUST-001' }}</div>
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Nama</label>
                    <div class="col-md-9">
                        <input type="text" name="name" value="{{ old('name', $customer->name) }}"
                               class="form-control form-control-solid @error('name') is-invalid @enderror"
                               placeholder="PT. Restoran Sejahtera" maxlength="150" required />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Tipe Customer</label>
                    <div class="col-md-9">
                        <select name="customer_type" id="customer_type"
                                class="form-select form-select-solid @error('customer_type') is-invalid @enderror"
                                data-control="select2" data-hide-search="true" required>
                            @foreach($types as $k => $label)
                                <option value="{{ $k }}" @selected(old('customer_type', $customer->customer_type) === $k)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('customer_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Tier harga akan auto-suggest sesuai tipe (boleh diubah).</div>
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Contact Person</label>
                    <div class="col-md-9">
                        <input type="text" name="contact_person" value="{{ old('contact_person', $customer->contact_person) }}"
                               class="form-control form-control-solid @error('contact_person') is-invalid @enderror"
                               maxlength="100" />
                        @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Phone</label>
                    <div class="col-md-9">
                        <input type="text" name="phone" value="{{ old('phone', $customer->phone) }}"
                               class="form-control form-control-solid @error('phone') is-invalid @enderror"
                               maxlength="20" />
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Email</label>
                    <div class="col-md-9">
                        <input type="email" name="email" value="{{ old('email', $customer->email) }}"
                               class="form-control form-control-solid @error('email') is-invalid @enderror"
                               maxlength="100" />
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 2: Alamat & Pajak --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Alamat &amp; Pajak</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Alamat</label>
                    <div class="col-md-9">
                        <textarea name="address" rows="3" class="form-control form-control-solid @error('address') is-invalid @enderror">{{ old('address', $customer->address) }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Kota</label>
                    <div class="col-md-9">
                        <input type="text" name="city" value="{{ old('city', $customer->city) }}"
                               class="form-control form-control-solid @error('city') is-invalid @enderror"
                               maxlength="100" />
                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">NPWP</label>
                    <div class="col-md-9">
                        <input type="text" name="npwp" id="customer_npwp"
                               value="{{ old('npwp', $customer->npwp) }}"
                               class="form-control form-control-solid @error('npwp') is-invalid @enderror"
                               placeholder="00.000.000.0-000.000 atau 16 digit"
                               maxlength="30" />
                        @error('npwp')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Format 15 digit (XX.XXX.XXX.X-XXX.XXX) atau 16 digit (NPWP NIK).</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Section 3: Pricing & Limit --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Pricing &amp; Limit Kredit</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Tier Harga</label>
                    <div class="col-md-9">
                        <select name="price_tier_id" id="price_tier_id"
                                class="form-select form-select-solid @error('price_tier_id') is-invalid @enderror"
                                data-control="select2" data-placeholder="Pilih tier...">
                            <option value=""></option>
                            @foreach($tiers as $t)
                                <option value="{{ $t->id }}"
                                        data-name="{{ strtolower($t->name) }}"
                                        @selected(old('price_tier_id', $customer->price_tier_id) == $t->id)>
                                    {{ $t->name }}
                                </option>
                            @endforeach
                        </select>
                        @error('price_tier_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Credit Limit (Rp)</label>
                    <div class="col-md-9">
                        <input type="text" name="credit_limit" id="customer_credit_limit"
                               value="{{ old('credit_limit', $customer->credit_limit ? number_format((float)$customer->credit_limit, 0, ',', '.') : '0') }}"
                               class="form-control form-control-solid @error('credit_limit') is-invalid @enderror" required />
                        @error('credit_limit')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Isi 0 untuk Cash on Delivery (COD). Format otomatis dengan separator titik.</div>
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">TOP (Hari)</label>
                    <div class="col-md-3">
                        <input type="number" name="payment_terms_days"
                               value="{{ old('payment_terms_days', $customer->payment_terms_days ?? 0) }}"
                               class="form-control form-control-solid @error('payment_terms_days') is-invalid @enderror"
                               min="0" max="365" required />
                        @error('payment_terms_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Sidebar Status --}}
    <div class="col-md-4">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Status</h3></div>
            <div class="card-body">
                <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                           id="is_active_switch"
                           @checked(old('is_active', $customer->is_active ?? true)) />
                    <label class="form-check-label fw-semibold ms-3" for="is_active_switch">Aktif</label>
                </div>
                <div class="text-muted fs-7">
                    Customer non-aktif tidak akan muncul saat membuat SO baru.
                </div>
            </div>
        </div>

        @if($isEdit)
            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Info</h3></div>
                <div class="card-body">
                    <div class="d-flex flex-stack mb-3">
                        <span class="text-muted">Dibuat:</span>
                        <span class="fw-bold">{{ $customer->created_date?->format('d M Y H:i') ?? '—' }}</span>
                    </div>
                    <div class="d-flex flex-stack">
                        <span class="text-muted">Diubah:</span>
                        <span class="fw-bold">{{ $customer->updated_date?->format('d M Y H:i') ?? '—' }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('customers.index') }}" class="btn btn-light">Batal</a>
    <button type="submit" class="btn btn-primary">
        <i class="ki-outline ki-check fs-2"></i>
        {{ $isEdit ? 'Update Customer' : 'Simpan Customer' }}
    </button>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ========== Inputmask: NPWP — accept 15 atau 16 digit ==========
    var npwpEl = document.getElementById('customer_npwp');
    if (npwpEl && typeof Inputmask !== 'undefined') {
        Inputmask({
            mask: ['99.999.999.9-999.999', '9999999999999999'],
            keepStatic: true,
            jitMasking: true
        }).mask(npwpEl);
    }

    // ========== Inputmask: Credit Limit Rupiah dengan separator titik ==========
    var clEl = document.getElementById('customer_credit_limit');
    if (clEl && typeof Inputmask !== 'undefined') {
        Inputmask({
            alias: 'numeric',
            groupSeparator: '.',
            radixPoint: ',',
            digits: 0,
            allowMinus: false,
            rightAlign: false,
            unmaskAsNumber: false,
            removeMaskOnSubmit: false
        }).mask(clEl);
    }

    // ========== Auto-suggest price_tier dari customer_type ==========
    var typeMap = @json($typeToTier ?? []);
    var typeEl = document.getElementById('customer_type');
    var tierEl = document.getElementById('price_tier_id');
    if (typeEl && tierEl) {
        $(typeEl).on('change', function () {
            var suggestedName = (typeMap[this.value] || '').toLowerCase();
            if (! suggestedName) return;
            var $opt = $(tierEl).find('option').filter(function () {
                return $(this).data('name') === suggestedName;
            }).first();
            if ($opt.length) {
                $(tierEl).val($opt.val()).trigger('change');
            }
        });
    }
});
</script>
@endpush

@php $isEdit = $isEdit ?? false; @endphp

<div class="row">
    <div class="col-md-8">
        {{-- Section 1: Identitas --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Identitas Supplier</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Kode</label>
                    <div class="col-md-9">
                        <input type="text" name="code" value="{{ old('code', $supplier->code) }}"
                               class="form-control form-control-solid text-uppercase @error('code') is-invalid @enderror"
                               placeholder="SUP-001" maxlength="20" readonly />
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Kode di-generate otomatis.</div>
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Nama Perusahaan / Pemasok</label>
                    <div class="col-md-9">
                        <input type="text" name="name" value="{{ old('name', $supplier->name) }}"
                               class="form-control form-control-solid @error('name') is-invalid @enderror"
                               placeholder="PT. Sumber Ikan Lestari" maxlength="150" required />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Contact Person</label>
                    <div class="col-md-9">
                        <input type="text" name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}"
                               class="form-control form-control-solid @error('contact_person') is-invalid @enderror"
                               placeholder="Pak Hartono" maxlength="100" />
                        @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Phone</label>
                    <div class="col-md-9">
                        <input type="text" name="phone" value="{{ old('phone', $supplier->phone) }}"
                               class="form-control form-control-solid @error('phone') is-invalid @enderror"
                               placeholder="081234567890" maxlength="20" />
                        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <input type="hidden" name="email" value="{{ old('email', $supplier->email) }}" />
            </div>
        </div>

        {{-- Section 2: Alamat & Pajak --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Alamat &amp; Pajak</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Alamat</label>
                    <div class="col-md-9">
                        <textarea name="address" rows="3"
                                  class="form-control form-control-solid @error('address') is-invalid @enderror"
                                  placeholder="Jl. ...">{{ old('address', $supplier->address) }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Kota</label>
                    <div class="col-md-9">
                        <input type="text" name="city" value="{{ old('city', $supplier->city) }}"
                               class="form-control form-control-solid @error('city') is-invalid @enderror"
                               placeholder="Jakarta" maxlength="100" />
                        @error('city')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <input type="hidden" name="npwp" value="{{ old('npwp', $supplier->npwp) }}" />
            </div>
        </div>

        {{-- Section 3: Pembayaran --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Pembayaran</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Bank</label>
                    <div class="col-md-9">
                        <input type="text" name="bank_name" value="{{ old('bank_name', $supplier->bank_name) }}"
                               class="form-control form-control-solid @error('bank_name') is-invalid @enderror"
                               placeholder="BCA / Mandiri / BRI" maxlength="50" />
                        @error('bank_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">No. Rekening</label>
                    <div class="col-md-9">
                        <input type="text" name="bank_account" value="{{ old('bank_account', $supplier->bank_account) }}"
                               class="form-control form-control-solid @error('bank_account') is-invalid @enderror"
                               placeholder="1234567890" maxlength="50" />
                        @error('bank_account')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
                <input type="hidden" name="payment_terms_days" value="{{ old('payment_terms_days', $supplier->payment_terms_days ?? 0) }}" />
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
                           @checked(old('is_active', $supplier->is_active ?? true)) />
                    <label class="form-check-label fw-semibold ms-3" for="is_active_switch">Aktif</label>
                </div>
                <div class="text-muted fs-7">
                    Supplier non-aktif tidak akan muncul saat membuat PO baru.
                </div>
            </div>
        </div>

        @if($isEdit)
            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Info</h3></div>
                <div class="card-body">
                    <div class="d-flex flex-stack mb-3">
                        <span class="text-muted">Dibuat:</span>
                        <span class="fw-bold">{{ $supplier->created_date?->format('d M Y H:i') ?? '—' }}</span>
                    </div>
                    <div class="d-flex flex-stack">
                        <span class="text-muted">Diubah:</span>
                        <span class="fw-bold">{{ $supplier->updated_date?->format('d M Y H:i') ?? '—' }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('suppliers.index') }}" class="btn btn-light">Batal</a>
    <button type="submit" class="btn btn-primary">
        <i class="ki-outline ki-check fs-2"></i>
        {{ $isEdit ? 'Update Supplier' : 'Simpan Supplier' }}
    </button>
</div>

{{--
    Partial form yang dipakai create + edit.
    Variable: $warehouse, $types, $picUsers, $isEdit (bool)
--}}
@php $isEdit = $isEdit ?? false; @endphp

<div class="row">
    {{-- ===== Card Identitas ===== --}}
    <div class="col-md-7">
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">Identitas Gudang</h3>
            </div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Kode</label>
                    <div class="col-md-9">
                        <input type="text" name="code"
                               value="{{ old('code', $warehouse->code) }}"
                               class="form-control form-control-solid text-uppercase @error('code') is-invalid @enderror"
                               placeholder="WH-PUSAT"
                               maxlength="20"
                               {{ $isEdit ? 'readonly' : 'required' }} />
                        @error('code')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">{{ $isEdit ? 'Kode tidak bisa diubah setelah dibuat.' : 'Huruf kapital, angka, tanda hubung. Contoh: WH-PUSAT' }}</div>
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Nama Gudang</label>
                    <div class="col-md-9">
                        <input type="text" name="name"
                               value="{{ old('name', $warehouse->name) }}"
                               class="form-control form-control-solid @error('name') is-invalid @enderror"
                               placeholder="Gudang Pusat Jakarta"
                               maxlength="100" required />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Tipe</label>
                    <div class="col-md-9">
                        <select name="type" class="form-select form-select-solid @error('type') is-invalid @enderror"
                                data-control="select2" data-hide-search="true" required>
                            @foreach($types as $key => $label)
                                <option value="{{ $key }}" @selected(old('type', $warehouse->type) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Alamat</label>
                    <div class="col-md-9">
                        <textarea name="address" rows="3"
                                  class="form-control form-control-solid @error('address') is-invalid @enderror"
                                  placeholder="Jl. Industri Raya No. 1, Jakarta">{{ old('address', $warehouse->address) }}</textarea>
                        @error('address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">Suhu &amp; PIC</h3>
            </div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Suhu Min (°C)</label>
                    <div class="col-md-3">
                        <input type="number" step="0.1" name="temperature_min"
                               value="{{ old('temperature_min', $warehouse->temperature_min) }}"
                               class="form-control form-control-solid @error('temperature_min') is-invalid @enderror"
                               placeholder="-25.0" />
                        @error('temperature_min')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <label class="col-form-label col-md-3 fw-semibold">Suhu Max (°C)</label>
                    <div class="col-md-3">
                        <input type="number" step="0.1" name="temperature_max"
                               value="{{ old('temperature_max', $warehouse->temperature_max) }}"
                               class="form-control form-control-solid @error('temperature_max') is-invalid @enderror"
                               placeholder="-18.0" />
                        @error('temperature_max')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">PIC (Penanggung Jawab)</label>
                    <div class="col-md-9">
                        <select name="pic_user_id" class="form-select form-select-solid @error('pic_user_id') is-invalid @enderror"
                                data-control="select2" data-placeholder="Pilih PIC...">
                            <option value=""></option>
                            @foreach($picUsers as $user)
                                <option value="{{ $user->id }}"
                                        @selected(old('pic_user_id', $warehouse->pic_user_id) == $user->id)>
                                    {{ $user->full_name }} ({{ $user->username }})
                                </option>
                            @endforeach
                        </select>
                        @error('pic_user_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Sidebar status ===== --}}
    <div class="col-md-5">
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">Status</h3>
            </div>
            <div class="card-body">
                <div class="form-check form-switch form-check-custom form-check-solid mb-5">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1"
                           id="is_active_switch"
                           @checked(old('is_active', $warehouse->is_active ?? true)) />
                    <label class="form-check-label fw-semibold ms-3" for="is_active_switch">
                        Aktif
                    </label>
                </div>
                <div class="text-muted fs-7">
                    Gudang non-aktif tidak akan muncul di pilihan saat membuat PO/SO/Transfer baru,
                    namun data history tetap utuh.
                </div>
            </div>
        </div>

        @if($isEdit)
            <div class="card mb-5">
                <div class="card-header">
                    <h3 class="card-title">Info</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-stack mb-3">
                        <span class="text-muted">Dibuat:</span>
                        <span class="fw-bold">{{ $warehouse->created_date?->format('d M Y H:i') ?? '—' }}</span>
                    </div>
                    <div class="d-flex flex-stack">
                        <span class="text-muted">Diubah:</span>
                        <span class="fw-bold">{{ $warehouse->updated_date?->format('d M Y H:i') ?? '—' }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('warehouses.index') }}" class="btn btn-light">Batal</a>
    <button type="submit" class="btn btn-primary">
        <i class="ki-outline ki-check fs-2"></i>
        {{ $isEdit ? 'Update Gudang' : 'Simpan Gudang' }}
    </button>
</div>

@php $isEdit = $isEdit ?? false; @endphp

<div class="row">
    <div class="col-md-8">
        {{-- ===== Section 1: Identitas ===== --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Identitas Produk</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">SKU</label>
                    <div class="col-md-9">
                        <div class="d-flex gap-2">
                            <input type="text" name="sku" id="prod_sku"
                                   value="{{ old('sku', $product->sku) }}"
                                   class="form-control form-control-solid text-uppercase @error('sku') is-invalid @enderror"
                                   placeholder="FISH-TUNA-A-001" maxlength="50"
                                   {{ $isEdit ? 'readonly' : 'required' }} />
                            @if(! $isEdit)
                                <button type="button" id="btn_suggest_sku" class="btn btn-light-info" title="Generate SKU dari Kategori + Grade">
                                    <i class="ki-outline ki-magic-stick fs-3"></i> Generate
                                </button>
                            @endif
                        </div>
                        @error('sku')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="form-text">{{ $isEdit ? 'SKU tidak bisa diubah.' : 'Huruf kapital, angka, tanda hubung. Klik Generate untuk auto-suggestion.' }}</div>
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Barcode</label>
                    <div class="col-md-9">
                        <div class="d-flex gap-2">
                            <input type="text" name="barcode" id="prod_barcode"
                                   value="{{ old('barcode', $product->barcode) }}"
                                   class="form-control form-control-solid @error('barcode') is-invalid @enderror"
                                   placeholder="EAN-13 atau custom" maxlength="50" />
                            <button type="button" id="btn_gen_barcode" class="btn btn-light-info" title="Generate barcode random EAN-13">
                                <i class="ki-outline ki-barcode fs-3"></i> Generate
                            </button>
                        </div>
                        @error('barcode')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Nama Produk</label>
                    <div class="col-md-9">
                        <input type="text" name="name" value="{{ old('name', $product->name) }}"
                               class="form-control form-control-solid @error('name') is-invalid @enderror"
                               placeholder="Tuna Sirip Kuning Premium" maxlength="150" required />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Nama Ilmiah</label>
                    <div class="col-md-9">
                        <input type="text" name="scientific_name" value="{{ old('scientific_name', $product->scientific_name) }}"
                               class="form-control form-control-solid @error('scientific_name') is-invalid @enderror"
                               placeholder="Thunnus albacares" maxlength="150" />
                        @error('scientific_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Asal Tangkap</label>
                    <div class="col-md-9">
                        <input type="text" name="origin" value="{{ old('origin', $product->origin) }}"
                               class="form-control form-control-solid @error('origin') is-invalid @enderror"
                               placeholder="Selat Makassar / Laut Banda" maxlength="100" />
                        @error('origin')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Deskripsi</label>
                    <div class="col-md-9">
                        <textarea name="description" rows="3"
                                  class="form-control form-control-solid @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Section 2: Klasifikasi ===== --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Klasifikasi</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Kategori</label>
                    <div class="col-md-9">
                        <select name="category_id" id="prod_category" class="form-select form-select-solid @error('category_id') is-invalid @enderror"
                                data-control="select2" data-placeholder="Pilih kategori..." required>
                            <option value=""></option>
                            @foreach($categories as $c)
                                <option value="{{ $c['id'] }}" @selected(old('category_id', $product->category_id)==$c['id'])>
                                    {!! str_repeat('&nbsp;&nbsp;', $c['depth']) !!}{{ $c['depth'] > 0 ? '└ ' : '' }}{{ $c['name'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Grade</label>
                    <div class="col-md-9">
                        <select name="grade_id" id="prod_grade" class="form-select form-select-solid @error('grade_id') is-invalid @enderror"
                                data-control="select2" data-placeholder="(Opsional)">
                            <option value=""></option>
                            @foreach($grades as $g)
                                <option value="{{ $g->id }}" @selected(old('grade_id', $product->grade_id)==$g->id)>{{ $g->code }} — {{ $g->name }}</option>
                            @endforeach
                        </select>
                        @error('grade_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Satuan Dasar (UoM)</label>
                    <div class="col-md-9">
                        <select name="base_uom_id" class="form-select form-select-solid @error('base_uom_id') is-invalid @enderror"
                                data-control="select2" data-placeholder="Pilih satuan..." required>
                            <option value=""></option>
                            @foreach($uoms as $u)
                                <option value="{{ $u->id }}" @selected(old('base_uom_id', $product->base_uom_id)==$u->id)>{{ $u->code }} — {{ $u->name }}</option>
                            @endforeach
                        </select>
                        @error('base_uom_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Satuan dasar untuk tracking stock. Konversi ke satuan lain bisa di-set nanti.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Section 3: Penyimpanan & Kualitas ===== --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Penyimpanan &amp; Kualitas</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Suhu Penyimpanan</label>
                    <div class="col-md-9">
                        <div class="row">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <input type="number" step="0.1" name="storage_temp_min" value="{{ old('storage_temp_min', $product->storage_temp_min) }}"
                                           class="form-control form-control-solid @error('storage_temp_min') is-invalid @enderror" placeholder="-25.0" />
                                    <span class="input-group-text">°C min</span>
                                </div>
                            </div>
                            <div class="col-md-2 d-flex align-items-center justify-content-center">~</div>
                            <div class="col-md-5">
                                <div class="input-group">
                                    <input type="number" step="0.1" name="storage_temp_max" value="{{ old('storage_temp_max', $product->storage_temp_max) }}"
                                           class="form-control form-control-solid @error('storage_temp_max') is-invalid @enderror" placeholder="-18.0" />
                                    <span class="input-group-text">°C max</span>
                                </div>
                            </div>
                        </div>
                        @error('storage_temp_min')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                        @error('storage_temp_max')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Umur Simpan (hari)</label>
                    <div class="col-md-3">
                        <input type="number" name="shelf_life_days" value="{{ old('shelf_life_days', $product->shelf_life_days) }}"
                               class="form-control form-control-solid @error('shelf_life_days') is-invalid @enderror"
                               min="0" max="3650" placeholder="30" />
                        @error('shelf_life_days')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Mudah Rusak (Perishable)</label>
                    <div class="col-md-9">
                        <div class="form-check form-switch form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="is_perishable" value="1" id="is_perishable_switch"
                                   @checked(old('is_perishable', $product->is_perishable ?? true)) />
                            <label class="form-check-label fw-semibold ms-3" for="is_perishable_switch">Ya, produk perishable</label>
                        </div>
                        <div class="form-text">Produk perishable wajib pakai batch tracking & FEFO picking.</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Section 4: Stock Level ===== --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Stock Level (Reorder Point)</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Stock Minimum</label>
                    <div class="col-md-3">
                        <input type="number" step="0.001" name="min_stock_level" value="{{ old('min_stock_level', $product->min_stock_level) }}"
                               class="form-control form-control-solid @error('min_stock_level') is-invalid @enderror" min="0" placeholder="0.000" />
                        @error('min_stock_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Alert kalau stock total &lt; nilai ini.</div>
                    </div>
                    <label class="col-form-label col-md-3 fw-semibold">Stock Maximum</label>
                    <div class="col-md-3">
                        <input type="number" step="0.001" name="max_stock_level" value="{{ old('max_stock_level', $product->max_stock_level) }}"
                               class="form-control form-control-solid @error('max_stock_level') is-invalid @enderror" min="0" placeholder="(opsional)" />
                        @error('max_stock_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Section 5: Harga Default ===== --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Harga Default</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Harga Pokok (Cost)</label>
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="default_cost_price" id="prod_cost"
                                   value="{{ old('default_cost_price', $product->default_cost_price ? number_format((float)$product->default_cost_price, 0, ',', '.') : '') }}"
                                   class="form-control form-control-solid @error('default_cost_price') is-invalid @enderror" placeholder="0" />
                        </div>
                        @error('default_cost_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                </div>
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Harga Jual</label>
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="text" name="default_sell_price" id="prod_sell"
                                   value="{{ old('default_sell_price', $product->default_sell_price ? number_format((float)$product->default_sell_price, 0, ',', '.') : '') }}"
                                   class="form-control form-control-solid @error('default_sell_price') is-invalid @enderror" placeholder="0" />
                        </div>
                        @error('default_sell_price')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="form-text">Harga jual default. Bisa di-override per tier nanti di modul Pricing.</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-9 offset-md-3">
                        <div class="alert alert-light-info py-2 fs-7 mb-0" id="margin_alert" style="display:none">
                            <i class="ki-outline ki-information fs-3 me-1"></i>
                            Estimasi margin: <strong id="margin_value">0%</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== Sidebar: Image + Status + Info ===== --}}
    <div class="col-md-4">
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Gambar Produk</h3></div>
            <div class="card-body text-center">
                <div class="image-input image-input-empty image-input-outline mb-3" data-kt-image-input="true">
                    <div id="image_preview"
                         class="image-input-wrapper rounded mx-auto"
                         style="width:200px;height:200px;background-image:url('{{ $product->image_display_url }}');background-size:cover;background-position:center;border:2px dashed #d1d3e0;background-color:#f9f9fa"></div>
                </div>
                <input type="file" name="image" id="prod_image" accept="image/jpeg,image/png,image/webp" class="form-control form-control-solid form-control-sm" />
                @error('image')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                <div class="form-text mt-2">JPG/PNG/WebP. Maks. 2 MB.</div>

                @if($isEdit && $product->image_url)
                    <div class="form-check form-check-custom form-check-solid mt-3 justify-content-center">
                        <input type="checkbox" name="remove_image" value="1" id="remove_image_chk" class="form-check-input" />
                        <label for="remove_image_chk" class="form-check-label fs-7 ms-2 text-danger">Hapus gambar saat ini</label>
                    </div>
                @endif
            </div>
        </div>

        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Status</h3></div>
            <div class="card-body">
                <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                    <input class="form-check-input" type="checkbox" name="is_active" value="1" id="is_active_switch"
                           @checked(old('is_active', $product->is_active ?? true)) />
                    <label class="form-check-label fw-semibold ms-3" for="is_active_switch">Aktif</label>
                </div>
                <div class="text-muted fs-7">Produk non-aktif tidak akan muncul saat membuat PO/SO baru.</div>
            </div>
        </div>

        @if($isEdit)
            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Info</h3></div>
                <div class="card-body fs-7">
                    <div class="d-flex flex-stack mb-2">
                        <span class="text-muted">Total Stock:</span>
                        <span class="fw-bold">{{ number_format($product->getTotalStock(), 3) }} {{ $product->baseUom?->code }}</span>
                    </div>
                    <div class="d-flex flex-stack mb-2">
                        <span class="text-muted">Dibuat oleh:</span>
                        <span class="fw-bold">{{ $product->createdBy?->full_name ?? '—' }}</span>
                    </div>
                    <div class="d-flex flex-stack mb-2">
                        <span class="text-muted">Dibuat:</span>
                        <span class="fw-bold">{{ $product->created_date?->format('d M Y H:i') ?? '—' }}</span>
                    </div>
                    <div class="d-flex flex-stack">
                        <span class="text-muted">Diubah:</span>
                        <span class="fw-bold">{{ $product->updated_date?->format('d M Y H:i') ?? '—' }}</span>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('products.index') }}" class="btn btn-light">Batal</a>
    <button type="submit" class="btn btn-primary">
        <i class="ki-outline ki-check fs-2"></i> {{ $isEdit ? 'Update Produk' : 'Simpan Produk' }}
    </button>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // ========== Inputmask Rupiah untuk cost & sell ==========
    if (typeof Inputmask !== 'undefined') {
        ['prod_cost','prod_sell'].forEach(function (id) {
            var el = document.getElementById(id);
            if (el) {
                Inputmask({
                    alias: 'numeric', groupSeparator: '.', radixPoint: ',', digits: 0,
                    allowMinus: false, removeMaskOnSubmit: false
                }).mask(el);
            }
        });
    }

    // ========== Auto-calc margin ==========
    function getNum(el) { return parseFloat((el.value || '0').replace(/\./g, '').replace(',', '.')) || 0; }
    function updateMargin() {
        var cost = getNum(document.getElementById('prod_cost'));
        var sell = getNum(document.getElementById('prod_sell'));
        var alert = document.getElementById('margin_alert');
        var val = document.getElementById('margin_value');
        if (cost > 0 && sell > 0) {
            var margin = ((sell - cost) / cost * 100).toFixed(1);
            val.textContent = margin + '%';
            alert.style.display = '';
        } else {
            alert.style.display = 'none';
        }
    }
    ['prod_cost','prod_sell'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) el.addEventListener('input', updateMargin);
    });
    updateMargin();

    // ========== Image preview ==========
    var imgInput = document.getElementById('prod_image');
    var preview  = document.getElementById('image_preview');
    if (imgInput && preview) {
        imgInput.addEventListener('change', function (e) {
            var file = e.target.files[0];
            if (! file) return;
            if (file.size > 2 * 1024 * 1024) {
                Swal.fire({ icon:'warning', title:'Ukuran terlalu besar', text:'Maks 2 MB', confirmButtonText:'OK', customClass:{confirmButton:'btn btn-warning'}, buttonsStyling:false });
                this.value = '';
                return;
            }
            var reader = new FileReader();
            reader.onload = function (ev) { preview.style.backgroundImage = "url('" + ev.target.result + "')"; };
            reader.readAsDataURL(file);
        });
    }

    @if(! $isEdit)
    // ========== SKU Generator ==========
    var btnSku = document.getElementById('btn_suggest_sku');
    if (btnSku) {
        btnSku.addEventListener('click', function () {
            var catId = document.getElementById('prod_category').value;
            var grdId = document.getElementById('prod_grade').value;
            if (! catId) {
                Swal.fire({ icon:'info', title:'Pilih kategori dulu', text:'SKU di-generate dari kategori + grade.',
                    confirmButtonText:'OK', customClass:{confirmButton:'btn btn-info'}, buttonsStyling:false });
                return;
            }
            this.disabled = true; this.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            fetch("{{ route('products.suggest-sku') }}?category_id=" + catId + "&grade_id=" + grdId, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
            })
            .then(r => r.json())
            .then(data => {
                if (data.resCode === '00') {
                    document.getElementById('prod_sku').value = data.data.sku;
                }
            })
            .finally(() => { this.disabled = false; this.innerHTML = '<i class="ki-outline ki-magic-stick fs-3"></i> Generate'; });
        });
    }
    @endif

    // ========== Barcode Generator (random EAN-13) ==========
    var btnBc = document.getElementById('btn_gen_barcode');
    if (btnBc) {
        btnBc.addEventListener('click', function () {
            // Generate 12 digit random + checksum digit ke-13
            var digits = '';
            for (var i = 0; i < 12; i++) digits += Math.floor(Math.random() * 10);
            // EAN-13 checksum
            var sum = 0;
            for (var i = 0; i < 12; i++) sum += parseInt(digits[i]) * (i % 2 === 0 ? 1 : 3);
            var checksum = (10 - (sum % 10)) % 10;
            document.getElementById('prod_barcode').value = digits + checksum;
        });
    }
});
</script>
@endpush

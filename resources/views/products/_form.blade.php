@php $isEdit = $isEdit ?? false; @endphp

<div class="row">
    <div class="col-md-8">
        {{-- ===== Section 1: Identitas ===== --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Identitas Produk</h3></div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Sub-Kategori</label>
                    <div class="col-md-9">
                        <select name="category_id" id="prod_category" class="form-select form-select-solid @error('category_id') is-invalid @enderror"
                                data-control="select2" data-placeholder="Pilih sub-kategori..." required>
                            <option value=""></option>
                            @foreach($categories as $c)
                                @if($c['depth'] >= 1)
                                    <option value="{{ $c['id'] }}" @selected(old('category_id', $product->category_id)==$c['id'])>
                                        {{ $c['breadcrumb'] }}
                                    </option>
                                @endif
                            @endforeach
                        </select>
                        @error('category_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Pilih sub-kategori (level-2). Group induk otomatis dipakai untuk SKU.</div>
                    </div>
                </div>

                @php
                    // Default Grade = B (Standard). Cari ID-nya, fallback ke grade pertama.
                    $defaultGradeId = $grades->firstWhere('code', 'B')?->id ?? $grades->first()?->id;
                    $selectedGradeId = old('grade_id', $product->grade_id ?? $defaultGradeId);
                @endphp
                {{-- Grade di-hide, default B-Standard. Tetap di-submit untuk SKU generator. --}}
                <input type="hidden" name="grade_id" id="prod_grade" value="{{ $selectedGradeId }}" />

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Kode Produk</label>
                    <div class="col-md-9">
                        <div class="d-flex gap-2">
                            <input type="text" name="sku" id="prod_sku"
                                   value="{{ old('sku', $product->sku) }}"
                                   class="form-control form-control-solid text-uppercase @error('sku') is-invalid @enderror"
                                   placeholder="FISH-TUNA-B-001" maxlength="50"
                                   {{ $isEdit ? 'readonly' : 'readonly required' }} />
                            @if(! $isEdit)
                                <button type="button" id="btn_suggest_sku" class="btn btn-light-info" title="Generate Kode Produk dari Sub-Kategori">
                                    <i class="ki-outline ki-magic-stick fs-3"></i> Generate
                                </button>
                            @endif
                        </div>
                        @error('sku')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                        <div class="form-text">{{ $isEdit ? 'Kode produk tidak bisa diubah.' : 'Pilih sub-kategori dulu, lalu klik Generate.' }}</div>
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
                        <input type="text" name="name" id="prod_name" value="{{ old('name', $product->name) }}"
                               class="form-control form-control-solid @error('name') is-invalid @enderror"
                               placeholder="Otomatis dari sub-kategori, bisa diedit" maxlength="150" required />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text fs-8">Default ambil dari nama sub-kategori. Bisa diedit untuk varian (mis. "Tuna Premium").</div>
                    </div>
                </div>

                {{-- Nama Ilmiah & Asal Tangkap di-hide tapi tetap di-submit (nilai existing dipertahankan) --}}
                <input type="hidden" name="scientific_name" value="{{ old('scientific_name', $product->scientific_name) }}" />
                <input type="hidden" name="origin" value="{{ old('origin', $product->origin) }}" />

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Deskripsi</label>
                    <div class="col-md-9">
                        <textarea name="description" rows="3"
                                  class="form-control form-control-solid @error('description') is-invalid @enderror">{{ old('description', $product->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Badge untuk customer portal --}}
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Badge (Portal Customer)</label>
                    <div class="col-md-9">
                        <select name="badge" class="form-select form-select-solid" data-control="select2" data-placeholder="(Tanpa badge)" data-allow-clear="true">
                            <option value=""></option>
                            @foreach(\App\Models\Product::badgeOptions() as $code => $opt)
                                <option value="{{ $code }}" @selected(old('badge', $product->badge) === $code)>{{ $opt['label'] }}</option>
                            @endforeach
                        </select>
                        <div class="form-text fs-8">Label highlight di card produk portal (mis. Best Seller).</div>
                    </div>
                </div>

                {{-- Nutrition tags --}}
                @php $nutItems = old('nutrition_info', $product->nutrition_info ?? []); @endphp
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Kandungan Vitamin / Nutrisi</label>
                    <div class="col-md-9">
                        <div id="nut_rows" class="d-flex flex-column gap-3">
                            @forelse($nutItems as $i => $n)
                                <div class="card border border-gray-300 p-3 nut-row">
                                    <div class="d-flex gap-2 align-items-start mb-2">
                                        <input type="text" name="nutrition_info[{{ $i }}][label]" value="{{ $n['label'] ?? '' }}" placeholder="Label (mis. Tinggi Protein)" maxlength="50" class="form-control form-control-solid form-control-sm" />
                                        <input type="text" name="nutrition_info[{{ $i }}][icon]" value="{{ $n['icon'] ?? '' }}" placeholder="Icon (opsional, mis. fitness_center)" maxlength="40" class="form-control form-control-solid form-control-sm" style="max-width:240px" />
                                        <button type="button" class="btn btn-sm btn-light-danger btn-del-nut"><i class="ki-outline ki-trash fs-3"></i></button>
                                    </div>
                                    <textarea name="nutrition_info[{{ $i }}][detail]" placeholder="Detail / manfaat (opsional, tampil di popup card portal)" rows="2" maxlength="500" class="form-control form-control-solid form-control-sm">{{ $n['detail'] ?? '' }}</textarea>
                                </div>
                            @empty
                            @endforelse
                        </div>
                        <button type="button" id="btn_add_nut" class="btn btn-sm btn-light-primary mt-2"><i class="ki-outline ki-plus fs-3"></i> Tambah Nutrisi</button>
                        <div class="form-text fs-8">Tampil sebagai badge kecil di card portal customer (max 2). Sisanya/detail muncul di popup. <a href="https://fonts.google.com/icons" target="_blank">Lihat daftar icon Material Symbols</a> (mis. <code>fitness_center</code>, <code>local_pharmacy</code>, <code>egg</code>, <code>spa</code>).</div>
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Section 2: Spesifikasi Pack ===== --}}
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">Spesifikasi Pack</h3>
                <div class="card-toolbar text-muted fs-7">Produk dijual per pack — definisikan satuan, isi &amp; berat per pack.</div>
            </div>
            <div class="card-body">
                {{-- Satuan Dasar (UoM) --}}
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
                        <div class="form-text">Default: <strong>Pack</strong>. Pilih satuan lain hanya untuk produk khusus (mis. jual per kg).</div>
                    </div>
                </div>

                {{-- Tipe Isi --}}
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Tipe Isi</label>
                    <div class="col-md-9">
                        @php $packType = old('pack_content_type', $product->pack_content_type ?? 'potong'); @endphp
                        <div class="d-flex gap-3">
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="pack_content_type" value="ekor" @checked($packType === 'ekor') />
                                <span class="form-check-label fw-semibold ms-2">Ekor (ikan utuh)</span>
                            </label>
                            <label class="form-check form-check-custom form-check-solid">
                                <input class="form-check-input" type="radio" name="pack_content_type" value="potong" @checked($packType === 'potong') />
                                <span class="form-check-label fw-semibold ms-2">Potong (fillet/cutting)</span>
                            </label>
                        </div>
                        @error('pack_content_type')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Jumlah Isi --}}
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Jumlah Isi / Pack</label>
                    <div class="col-md-9">
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox" id="pack_content_range_toggle"
                                   @checked(old('pack_content_min', $product->pack_content_min) != old('pack_content_max', $product->pack_content_max) && $product->pack_content_max) />
                            <label class="form-check-label fw-semibold ms-3" for="pack_content_range_toggle">Range (min – max)</label>
                            <span class="text-muted fs-7 ms-3">Aktifkan kalau jumlah isi bisa bervariasi (mis. 4–5 potong).</span>
                        </div>
                        <div class="row g-3 align-items-center">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <input type="number" name="pack_content_min" id="pack_content_min" min="1" max="9999"
                                           value="{{ old('pack_content_min', $product->pack_content_min) }}"
                                           class="form-control form-control-solid @error('pack_content_min') is-invalid @enderror" placeholder="4" />
                                    <span class="input-group-text" id="pack_content_unit_min">potong</span>
                                </div>
                            </div>
                            <div class="col-md-2 text-center range-only" id="pack_content_sep">–</div>
                            <div class="col-md-5 range-only" id="pack_content_max_wrap">
                                <div class="input-group">
                                    <input type="number" name="pack_content_max" id="pack_content_max" min="1" max="9999"
                                           value="{{ old('pack_content_max', $product->pack_content_max) }}"
                                           class="form-control form-control-solid @error('pack_content_max') is-invalid @enderror" placeholder="5" />
                                    <span class="input-group-text" id="pack_content_unit_max">potong</span>
                                </div>
                            </div>
                        </div>
                        @error('pack_content_min')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                        @error('pack_content_max')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>

                {{-- Berat per Pack --}}
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Berat / Pack</label>
                    <div class="col-md-9">
                        <div class="form-check form-switch form-check-custom form-check-solid mb-3">
                            <input class="form-check-input" type="checkbox" id="pack_weight_range_toggle"
                                   @checked(old('pack_weight_min_g', $product->pack_weight_min_g) != old('pack_weight_max_g', $product->pack_weight_max_g) && $product->pack_weight_max_g) />
                            <label class="form-check-label fw-semibold ms-3" for="pack_weight_range_toggle">Range (min – max)</label>
                            <span class="text-muted fs-7 ms-3">Aktifkan kalau berat bisa bervariasi (mis. 200–215 g).</span>
                        </div>
                        <div class="row g-3 align-items-center">
                            <div class="col-md-5">
                                <div class="input-group">
                                    <input type="number" step="0.01" name="pack_weight_min_g" id="pack_weight_min" min="0.01"
                                           value="{{ old('pack_weight_min_g', $product->pack_weight_min_g) }}"
                                           class="form-control form-control-solid @error('pack_weight_min_g') is-invalid @enderror" placeholder="200" />
                                    <span class="input-group-text">gram</span>
                                </div>
                            </div>
                            <div class="col-md-2 text-center range-only" id="pack_weight_sep">–</div>
                            <div class="col-md-5 range-only" id="pack_weight_max_wrap">
                                <div class="input-group">
                                    <input type="number" step="0.01" name="pack_weight_max_g" id="pack_weight_max" min="0.01"
                                           value="{{ old('pack_weight_max_g', $product->pack_weight_max_g) }}"
                                           class="form-control form-control-solid @error('pack_weight_max_g') is-invalid @enderror" placeholder="215" />
                                    <span class="input-group-text">gram</span>
                                </div>
                            </div>
                        </div>
                        @error('pack_weight_min_g')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                        @error('pack_weight_max_g')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Section 3: Penyimpanan & Kualitas — HIDDEN dengan default values ===== --}}
        <input type="hidden" name="storage_temp_min" value="{{ old('storage_temp_min', $product->storage_temp_min ?? -25) }}" />
        <input type="hidden" name="storage_temp_max" value="{{ old('storage_temp_max', $product->storage_temp_max ?? -18) }}" />
        <input type="hidden" name="shelf_life_days"  value="{{ old('shelf_life_days', $product->shelf_life_days ?? 60) }}" />
        <input type="hidden" name="is_perishable"    value="1" />

        {{-- ===== Section 4: Stock Level ===== --}}
        <div class="card mb-5">
            <div class="card-header"><h3 class="card-title">Stok</h3></div>
            <div class="card-body">
                @if(! $isEdit)
                    {{-- Stok Awal: hanya saat Create Produk --}}
                    <div class="row mb-5">
                        <label class="col-form-label col-md-3 fw-semibold">Stok Awal</label>
                        <div class="col-md-3">
                            <input type="number" step="1" name="initial_stock" value="{{ old('initial_stock', 0) }}"
                                   class="form-control form-control-solid @error('initial_stock') is-invalid @enderror" min="0" placeholder="0" />
                            @error('initial_stock')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="form-text fs-8">Qty awal yang sudah ada di gudang (opsional). Akan dicatat sebagai Stock Opening.</div>
                        </div>
                    </div>
                @endif

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Stok Minimum</label>
                    <div class="col-md-3">
                        <input type="number" step="1" name="min_stock_level" value="{{ old('min_stock_level', (int) $product->min_stock_level) }}"
                               class="form-control form-control-solid @error('min_stock_level') is-invalid @enderror" min="0" placeholder="0" />
                        @error('min_stock_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Alert kalau stok total &lt; nilai ini.</div>
                    </div>
                    <label class="col-form-label col-md-3 fw-semibold">Stok Maksimum</label>
                    <div class="col-md-3">
                        <input type="number" step="1" name="max_stock_level" value="{{ old('max_stock_level', $product->max_stock_level ? (int) $product->max_stock_level : '') }}"
                               class="form-control form-control-solid @error('max_stock_level') is-invalid @enderror" min="0" placeholder="(opsional)" />
                        @error('max_stock_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>

        {{-- ===== Section 5: Harga Default ===== --}}
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">Harga Default</h3>
                <div class="card-toolbar text-muted fs-7">Isi Cost &amp; Margin% → Harga Jual otomatis dihitung &amp; dibulatkan ke kelipatan 1.000.</div>
            </div>
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
                    <label class="col-form-label col-md-3 fw-semibold">Margin (%)</label>
                    <div class="col-md-9">
                        <div class="input-group" style="max-width:280px">
                            <input type="number" step="0.01" min="0" max="9999.99" name="default_margin_percent" id="prod_margin"
                                   value="{{ old('default_margin_percent', $product->default_margin_percent !== null ? rtrim(rtrim(number_format((float)$product->default_margin_percent, 2, '.', ''), '0'), '.') : '') }}"
                                   class="form-control form-control-solid @error('default_margin_percent') is-invalid @enderror" placeholder="25" />
                            <span class="input-group-text">%</span>
                            <button type="button" id="btn_calc_sell" class="btn btn-light-info" title="Hitung Harga Jual dari Cost + Margin">
                                <i class="ki-outline ki-calculator fs-3"></i> Hitung
                            </button>
                        </div>
                        @error('default_margin_percent')<div class="text-danger fs-7 mt-1">{{ $message }}</div>@enderror
                        <div class="form-text">Target margin terhadap Cost. Mengubah Cost atau Margin akan auto-hitung Harga Jual.</div>
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
                        <div class="form-text">Auto-hitung dari Cost × (1 + Margin%) → bulat ke kelipatan 1.000. Boleh diedit manual untuk override (margin akan menyesuaikan).</div>
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Untung Bersih</label>
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text bg-light-success text-success">
                                <i class="ki-outline ki-dollar fs-3"></i>
                            </span>
                            <input type="text" id="prod_profit" readonly tabindex="-1"
                                   class="form-control form-control-solid bg-light-success text-success fw-bold fs-4"
                                   value="Rp 0" />
                            <span class="input-group-text bg-light-success text-success fw-bold" id="prod_profit_margin">0%</span>
                        </div>
                        <div class="form-text">
                            Profit = Harga Jual − Harga Pokok. Otomatis terhitung dari Cost &amp; Sell.
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

    // ========== Harga: Cost / Margin / Sell — auto-calc ==========
    var costEl       = document.getElementById('prod_cost');
    var marginEl     = document.getElementById('prod_margin');
    var sellEl       = document.getElementById('prod_sell');
    var profitEl     = document.getElementById('prod_profit');
    var profitMargEl = document.getElementById('prod_profit_margin');

    var isProgrammatic = false;
    function getRupiah(el) { return parseFloat((el.value || '0').replace(/\./g, '').replace(',', '.')) || 0; }
    function getNum(el)    { return parseFloat(el.value) || 0; }
    function setRupiah(el, val) {
        if (typeof Inputmask !== 'undefined' && el.inputmask) {
            el.inputmask.setValue(Math.round(val));
        } else {
            el.value = Math.round(val).toLocaleString('id-ID');
        }
    }
    function fmtRupiah(val) {
        return 'Rp ' + Math.round(val).toLocaleString('id-ID');
    }
    // Bulat ke kelipatan 1.000 (normal rounding: 0.5 ke atas)
    function roundToThousand(val) {
        return Math.round(val / 1000) * 1000;
    }

    // Hitung sell dari cost + margin → bulat 1.000
    function recalcSellFromMargin() {
        if (isProgrammatic) return;
        var cost   = getRupiah(costEl);
        var margin = getNum(marginEl);
        if (cost <= 0 || margin <= 0) { updateMarginAlert(); return; }
        var rawSell = cost * (1 + margin / 100);
        var sell = roundToThousand(rawSell);
        isProgrammatic = true;
        setRupiah(sellEl, sell);
        isProgrammatic = false;
        updateMarginAlert();
    }

    // Hitung margin% aktual dari cost & sell (saat sell di-override manual)
    function recalcMarginFromSell() {
        if (isProgrammatic) return;
        var cost = getRupiah(costEl);
        var sell = getRupiah(sellEl);
        if (cost <= 0) { updateMarginAlert(); return; }
        var margin = ((sell - cost) / cost) * 100;
        isProgrammatic = true;
        marginEl.value = (Math.round(margin * 100) / 100).toString();
        isProgrammatic = false;
        updateMarginAlert();
    }

    function updateMarginAlert() {
        var cost = getRupiah(costEl);
        var sell = getRupiah(sellEl);
        var profit = sell - cost;
        var hasData = cost > 0 && sell > 0;

        // Update field Untung Bersih
        if (profitEl) {
            profitEl.value = hasData ? fmtRupiah(profit) : 'Rp 0';
            // Warna: hijau kalau untung, merah kalau rugi, abu kalau kosong
            profitEl.classList.remove('bg-light-success','text-success','bg-light-danger','text-danger','bg-light','text-muted');
            var wrap = profitEl.closest('.input-group');
            if (wrap) {
                wrap.querySelectorAll('.input-group-text').forEach(function(s) {
                    s.classList.remove('bg-light-success','text-success','bg-light-danger','text-danger','bg-light','text-muted');
                });
            }
            var cls;
            if (! hasData)        cls = ['bg-light','text-muted'];
            else if (profit < 0)  cls = ['bg-light-danger','text-danger'];
            else                  cls = ['bg-light-success','text-success'];
            profitEl.classList.add(cls[0], cls[1]);
            if (wrap) wrap.querySelectorAll('.input-group-text').forEach(function(s){ s.classList.add(cls[0], cls[1]); });
        }
        // Update label margin% di belakang field profit
        if (profitMargEl) {
            if (hasData) {
                var m = (profit / cost) * 100;
                profitMargEl.textContent = (Math.round(m * 10) / 10).toFixed(1) + '%';
            } else {
                profitMargEl.textContent = '0%';
            }
        }
    }

    // Wiring:
    //  - Ubah cost atau margin → auto recalc sell
    //  - Ubah sell manual → auto recalc margin
    //  - Tombol "Hitung" → force recalc sell dari cost+margin
    if (costEl)   costEl.addEventListener('input',  recalcSellFromMargin);
    if (marginEl) marginEl.addEventListener('input', recalcSellFromMargin);
    if (sellEl)   sellEl.addEventListener('input',  recalcMarginFromSell);
    var btnCalc = document.getElementById('btn_calc_sell');
    if (btnCalc) btnCalc.addEventListener('click', recalcSellFromMargin);

    // Initial alert state
    updateMarginAlert();

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

    // ========== Auto-fill Nama Produk dari Sub-Kategori ==========
    // Kalau field nama masih kosong, isi otomatis dengan nama sub-kategori yang dipilih.
    // Kalau user sudah mengetik manual, tidak akan di-overwrite.
    var prodNameEl = document.getElementById('prod_name');
    var prodCatEl  = document.getElementById('prod_category');
    var nameManuallyEdited = !! (prodNameEl?.value || '').trim();
    if (prodNameEl) {
        prodNameEl.addEventListener('input', function () {
            nameManuallyEdited = !! this.value.trim();
        });
    }
    if (prodCatEl && window.jQuery) {
        window.jQuery(prodCatEl).on('change', function () {
            if (nameManuallyEdited) return;
            var sel = this.options[this.selectedIndex];
            if (! sel || ! sel.value) return;
            // Breadcrumb format: "Ikan Laut › Tuna" — ambil bagian terakhir
            var breadcrumb = sel.text.trim();
            var parts = breadcrumb.split('›').map(function (s) { return s.trim(); });
            var subName = parts[parts.length - 1];
            if (subName) prodNameEl.value = subName;
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
                Swal.fire({ icon:'info', title:'Pilih Sub-Kategori dulu', text:'Sub-kategori wajib dipilih sebelum generate kode produk.',
                    confirmButtonText:'OK', customClass:{confirmButton:'btn btn-info'}, buttonsStyling:false });
                return;
            }
            var btn = this;
            btn.disabled = true; btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span>';
            fetch("{{ route('products.suggest-sku') }}?category_id=" + catId + "&grade_id=" + grdId, {
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content }
            })
            .then(r => r.json())
            .then(data => {
                if (data.resCode === '00') {
                    document.getElementById('prod_sku').value = data.data.sku;
                } else {
                    Swal.fire({ icon:'warning', title:'Gagal generate SKU', text: data.message || 'Tidak bisa membuat SKU.',
                        confirmButtonText:'OK', customClass:{confirmButton:'btn btn-warning'}, buttonsStyling:false });
                }
            })
            .catch(() => {
                Swal.fire({ icon:'error', title:'Error', text:'Tidak bisa terhubung ke server.',
                    confirmButtonText:'OK', customClass:{confirmButton:'btn btn-danger'}, buttonsStyling:false });
            })
            .finally(() => { btn.disabled = false; btn.innerHTML = '<i class="ki-outline ki-magic-stick fs-3"></i> Generate'; });
        });
    }
    @endif

    // ========== Pack content/weight: range toggle + ekor/potong unit label ==========
    function setupRangeToggle(toggleId, sepId, maxWrapId, minId, maxId) {
        var toggle = document.getElementById(toggleId);
        var sep    = document.getElementById(sepId);
        var wrap   = document.getElementById(maxWrapId);
        var minEl  = document.getElementById(minId);
        var maxEl  = document.getElementById(maxId);
        if (! toggle) return;

        function apply() {
            if (toggle.checked) {
                sep.style.display = '';
                wrap.style.display = '';
            } else {
                sep.style.display = 'none';
                wrap.style.display = 'none';
                // Sinkron: max = min saat tidak range, supaya server validation lolos
                maxEl.value = minEl.value;
            }
        }
        toggle.addEventListener('change', apply);
        minEl.addEventListener('input', function () {
            if (! toggle.checked) maxEl.value = minEl.value;
        });
        apply();
    }
    setupRangeToggle('pack_content_range_toggle', 'pack_content_sep', 'pack_content_max_wrap', 'pack_content_min', 'pack_content_max');
    setupRangeToggle('pack_weight_range_toggle',  'pack_weight_sep',  'pack_weight_max_wrap',  'pack_weight_min',  'pack_weight_max');

    // Update unit label "ekor/potong" pada input-group jumlah isi
    function updatePackUnitLabel() {
        var checked = document.querySelector('input[name="pack_content_type"]:checked');
        var label = checked ? checked.value : 'potong';
        var u1 = document.getElementById('pack_content_unit_min');
        var u2 = document.getElementById('pack_content_unit_max');
        if (u1) u1.textContent = label;
        if (u2) u2.textContent = label;
    }
    document.querySelectorAll('input[name="pack_content_type"]').forEach(function (r) {
        r.addEventListener('change', updatePackUnitLabel);
    });
    updatePackUnitLabel();

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

    // ===== Nutrition repeater =====
    const nutRows = document.getElementById('nut_rows');
    const btnAddNut = document.getElementById('btn_add_nut');
    function reindexNut() {
        nutRows.querySelectorAll('.nut-row').forEach((row, idx) => {
            row.querySelectorAll('input').forEach(inp => {
                inp.name = inp.name.replace(/nutrition_info\[\d*\]/, `nutrition_info[${idx}]`);
            });
        });
    }
    function addNutRow() {
        const idx = nutRows.querySelectorAll('.nut-row').length;
        const div = document.createElement('div');
        div.className = 'card border border-gray-300 p-3 nut-row';
        div.innerHTML = `
            <div class="d-flex gap-2 align-items-start mb-2">
                <input type="text" name="nutrition_info[${idx}][label]" placeholder="Label (mis. Tinggi Protein)" maxlength="50" class="form-control form-control-solid form-control-sm" />
                <input type="text" name="nutrition_info[${idx}][icon]" placeholder="Icon (opsional, mis. fitness_center)" maxlength="40" class="form-control form-control-solid form-control-sm" style="max-width:240px" />
                <button type="button" class="btn btn-sm btn-light-danger btn-del-nut"><i class="ki-outline ki-trash fs-3"></i></button>
            </div>
            <textarea name="nutrition_info[${idx}][detail]" placeholder="Detail / manfaat (opsional, tampil di popup card portal)" rows="2" maxlength="500" class="form-control form-control-solid form-control-sm"></textarea>
        `;
        nutRows.appendChild(div);
    }
    btnAddNut?.addEventListener('click', addNutRow);
    nutRows?.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-del-nut');
        if (! btn) return;
        btn.closest('.nut-row').remove();
        reindexNut();
    });
});
</script>
@endpush

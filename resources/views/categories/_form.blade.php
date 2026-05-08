@php $isEdit = $isEdit ?? false; @endphp

<div class="row">
    <div class="col-md-8">
        <div class="card mb-5">
            <div class="card-header">
                <h3 class="card-title">{{ $isEdit ? 'Edit Kategori' : 'Kategori Baru' }}</h3>
            </div>
            <div class="card-body">
                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold required">Nama</label>
                    <div class="col-md-9">
                        <input type="text" name="name" id="cat_name"
                               value="{{ old('name', $category->name) }}"
                               class="form-control form-control-solid @error('name') is-invalid @enderror"
                               placeholder="Ikan Pelagis" maxlength="100" required />
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Slug</label>
                    <div class="col-md-9">
                        <input type="text" name="slug" id="cat_slug"
                               value="{{ old('slug', $category->slug) }}"
                               class="form-control form-control-solid @error('slug') is-invalid @enderror"
                               placeholder="otomatis dari nama" maxlength="100" />
                        @error('slug')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">URL-friendly identifier. Kosongkan untuk auto-generate dari nama.</div>
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Kategori Induk</label>
                    <div class="col-md-9">
                        <select name="parent_id" class="form-select form-select-solid @error('parent_id') is-invalid @enderror"
                                data-control="select2" data-placeholder="(Root - tanpa induk)">
                            <option value="">— Root (tanpa induk) —</option>
                            @foreach($parentList as $p)
                                <option value="{{ $p['id'] }}" @selected(old('parent_id', $category->parent_id) == $p['id'])>
                                    {!! str_repeat('&nbsp;&nbsp;&nbsp;', $p['depth']) !!}{{ $p['depth'] > 0 ? '└ ' : '' }}{{ $p['name'] }}
                                </option>
                            @endforeach
                        </select>
                        @error('parent_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        <div class="form-text">Pilih kategori induk untuk membuat sub-kategori, atau kosongkan untuk kategori root.</div>
                    </div>
                </div>

                <div class="row mb-5">
                    <label class="col-form-label col-md-3 fw-semibold">Deskripsi</label>
                    <div class="col-md-9">
                        <textarea name="description" rows="4"
                                  class="form-control form-control-solid @error('description') is-invalid @enderror">{{ old('description', $category->description) }}</textarea>
                        @error('description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        @if($isEdit)
            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Info</h3></div>
                <div class="card-body">
                    <div class="d-flex flex-stack mb-3">
                        <span class="text-muted">Breadcrumb:</span>
                        <span class="fw-bold text-end fs-7">{{ $category->getBreadcrumb() }}</span>
                    </div>
                    <div class="d-flex flex-stack mb-3">
                        <span class="text-muted">Sub-kategori:</span>
                        <span class="fw-bold">{{ $category->getChildrenCount() }}</span>
                    </div>
                    <div class="d-flex flex-stack mb-3">
                        <span class="text-muted">Produk:</span>
                        <span class="fw-bold">{{ $category->getProductCount() }}</span>
                    </div>
                    <div class="d-flex flex-stack">
                        <span class="text-muted">Dibuat:</span>
                        <span class="fw-bold fs-7">{{ $category->created_date?->format('d M Y H:i') ?? '—' }}</span>
                    </div>
                </div>
            </div>
        @else
            <div class="card mb-5">
                <div class="card-header"><h3 class="card-title">Tips</h3></div>
                <div class="card-body fs-7 text-muted">
                    <ul class="mb-0 ps-3">
                        <li>Kategori root tidak punya induk</li>
                        <li>Sub-kategori bisa punya sub-kategori lagi (multi-level)</li>
                        <li>Slug otomatis dibuat dari nama kalau dikosongkan</li>
                        <li>Kategori yang punya child atau dipakai produk tidak bisa dihapus</li>
                    </ul>
                </div>
            </div>
        @endif
    </div>
</div>

<div class="d-flex justify-content-end gap-2">
    <a href="{{ route('categories.index') }}" class="btn btn-light">Batal</a>
    <button type="submit" class="btn btn-primary">
        <i class="ki-outline ki-check fs-2"></i>
        {{ $isEdit ? 'Update Kategori' : 'Simpan Kategori' }}
    </button>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    // Auto-generate slug dari nama saat field nama diketik (kalau slug masih kosong)
    var nameEl = document.getElementById('cat_name');
    var slugEl = document.getElementById('cat_slug');
    var manuallyEdited = !! slugEl.value;

    function slugify(s) {
        return s.toString().toLowerCase()
            .replace(/[^a-z0-9\s-]/g, '')
            .trim()
            .replace(/\s+/g, '-')
            .replace(/-+/g, '-');
    }

    nameEl.addEventListener('input', function () {
        if (! manuallyEdited) slugEl.value = slugify(this.value);
    });
    slugEl.addEventListener('input', function () { manuallyEdited = !! this.value; });
});
</script>
@endpush

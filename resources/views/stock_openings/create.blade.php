@extends('layouts.app')

@section('title', 'Stock Opening Baru')
@section('page_title', 'Stock Opening Baru')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Inventory</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('stock_openings.index') }}" class="text-muted">Stock Opening</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Baru</li>
@endsection

@section('content')
<form method="POST" action="{{ route('stock_openings.store') }}" id="frm_opening">
    @csrf

    <div class="card mb-5">
        <div class="card-header"><h3 class="card-title">Header Stock Opening</h3></div>
        <div class="card-body">
            <div class="row mb-5">
                <label class="col-form-label col-md-2 fw-semibold required">Warehouse</label>
                <div class="col-md-5">
                    <select name="warehouse_id" class="form-select form-select-solid @error('warehouse_id') is-invalid @enderror"
                            data-control="select2" data-placeholder="Pilih warehouse..." required>
                        <option value=""></option>
                        @foreach($warehouses as $w)
                            <option value="{{ $w->id }}" @selected(old('warehouse_id')==$w->id)>{{ $w->code }} — {{ $w->name }}</option>
                        @endforeach
                    </select>
                    @error('warehouse_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <div class="row mb-5">
                <label class="col-form-label col-md-2 fw-semibold">Catatan</label>
                <div class="col-md-10">
                    <textarea name="notes" rows="2" class="form-control form-control-solid"
                              placeholder="Mis. saldo awal go-live 10 Mei 2026">{{ old('notes') }}</textarea>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-5">
        <div class="card-header">
            <h3 class="card-title">Item Produk</h3>
            <div class="card-toolbar">
                <button type="button" class="btn btn-light-primary btn-sm" id="btn_add_row">
                    <i class="ki-outline ki-plus fs-3"></i> Tambah Baris
                </button>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-row-bordered align-middle gy-3" id="tbl_items" style="min-width:900px">
                    <thead>
                        <tr class="fw-bold text-muted bg-light">
                            <th style="min-width:220px">Produk *</th>
                            <th class="text-end" style="min-width:110px">Qty *</th>
                            <th style="min-width:70px">UoM</th>
                            <th class="text-end" style="min-width:140px">Cost (Rp) *</th>
                            <th style="min-width:140px">Production</th>
                            <th style="min-width:140px">Expiry</th>
                            <th style="min-width:60px"></th>
                        </tr>
                    </thead>
                    <tbody id="items_body">
                        {{-- Diisi via JS --}}
                    </tbody>
                </table>
            </div>
            <div class="d-md-none alert alert-light-info mt-3 fs-8 py-2 mb-0">
                <i class="ki-outline ki-information fs-3 me-1"></i>
                Tabel bisa di-<strong>geser ke samping</strong> untuk lihat semua kolom.
            </div>
            @error('items')<div class="text-danger fs-7 mt-2">{{ $message }}</div>@enderror
            <div class="form-text mt-3">
                <i class="ki-outline ki-information fs-3 me-1"></i>
                Untuk produk perishable: Production/Expiry boleh kosong → sistem akan generate batch dengan expiry = today + shelf-life dari master produk.
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2">
        <a href="{{ route('stock_openings.index') }}" class="btn btn-light">Batal</a>
        <button type="submit" class="btn btn-primary">
            <i class="ki-outline ki-check fs-2"></i> Simpan Stock Opening
        </button>
    </div>
</form>

@php
    $productData = $products->map(fn($p) => [
        'id'   => $p->id,
        'sku'  => $p->sku,
        'name' => $p->name,
        'uom'  => $p->baseUom?->code ?? '',
        'is_perishable' => (bool) $p->is_perishable,
        'cost' => (float) $p->default_cost_price,
    ])->values();
@endphp

@push('scripts')
<script>
const PRODUCTS = @json($productData);
const PRODUCT_OPTIONS = PRODUCTS.map(p => `<option value="${p.id}">${p.sku} — ${p.name}</option>`).join('');

let rowIdx = 0;

function addRow() {
    const idx = rowIdx++;
    const tr = document.createElement('tr');
    tr.dataset.idx = idx;
    tr.innerHTML = `
        <td>
            <select name="items[${idx}][product_id]" class="form-select form-select-sm form-select-solid prod-select"
                    data-control="select2" data-placeholder="Pilih produk..." style="width:100%">
                <option value=""></option>
                ${PRODUCT_OPTIONS}
            </select>
        </td>
        <td>
            <input type="number" step="0.001" min="0.001" name="items[${idx}][quantity]" class="form-control form-control-sm form-control-solid text-end" placeholder="0" />
        </td>
        <td><span class="uom-label text-muted">—</span></td>
        <td>
            <input type="text" name="items[${idx}][cost_price]" class="form-control form-control-sm form-control-solid text-end cost-input" placeholder="0" />
        </td>
        <td>
            <input type="date" name="items[${idx}][production_date]" class="form-control form-control-sm form-control-solid prod-date" disabled />
        </td>
        <td>
            <input type="date" name="items[${idx}][expiry_date]" class="form-control form-control-sm form-control-solid exp-date" disabled />
        </td>
        <td class="text-end">
            <button type="button" class="btn btn-sm btn-light-danger btn-del-row" title="Hapus baris">
                <i class="ki-outline ki-trash fs-3"></i>
            </button>
        </td>
    `;
    document.getElementById('items_body').appendChild(tr);

    // Re-init select2 on new select
    if (window.jQuery) {
        $(tr).find('.prod-select').select2({
            placeholder: 'Pilih produk...',
            width: '100%',
            dropdownParent: $(tr).closest('.card')
        }).on('change', function () {
            const p = PRODUCTS.find(x => x.id == this.value);
            const $tr = $(tr);
            if (p) {
                $tr.find('.uom-label').text(p.uom || '—');
                $tr.find('.cost-input').val(p.cost ? Math.round(p.cost).toLocaleString('id-ID') : '');
                $tr.find('.prod-date, .exp-date').prop('disabled', ! p.is_perishable);
            } else {
                $tr.find('.uom-label').text('—');
                $tr.find('.prod-date, .exp-date').prop('disabled', true);
            }
        });
    }

    // Inputmask Rupiah
    const costEl = tr.querySelector('.cost-input');
    if (typeof Inputmask !== 'undefined' && costEl) {
        Inputmask({ alias:'numeric', groupSeparator:'.', radixPoint:',', digits:0, allowMinus:false, removeMaskOnSubmit:false }).mask(costEl);
    }

    // Hapus row
    tr.querySelector('.btn-del-row').addEventListener('click', () => {
        tr.remove();
        if (! document.getElementById('items_body').children.length) addRow();
    });
}

document.addEventListener('DOMContentLoaded', () => {
    addRow();
    document.getElementById('btn_add_row').addEventListener('click', addRow);
});
</script>
@endpush
@endsection

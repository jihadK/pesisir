@extends('layouts.app')

@section('title', 'Daftar Produk')
@section('page_title', 'Produk')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Produk</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Daftar Produk</li>
@endsection

@push('styles')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <form method="GET" action="{{ route('products.index') }}" class="d-flex align-items-center position-relative my-1">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                <input type="text" name="q" value="{{ $filters['q'] }}"
                       class="form-control form-control-solid w-300px ps-13"
                       placeholder="Cari SKU / nama / barcode / origin..." />
                @if(array_filter($filters))<a href="{{ route('products.index') }}" class="btn btn-sm btn-light ms-2">Reset</a>@endif
            </form>
        </div>

        <div class="card-toolbar">
            <div class="d-flex justify-content-end align-items-center flex-wrap gap-2">
                <form method="GET" action="{{ route('products.index') }}" class="d-flex flex-wrap gap-2 me-2">
                    <input type="hidden" name="q" value="{{ $filters['q'] }}">
                    <input type="hidden" name="view" value="{{ $filters['view'] }}">

                    <select name="category_id" class="form-select form-select-solid form-select-sm w-150px"
                            data-control="select2" data-placeholder="Kategori"
                            onchange="this.form.submit()">
                        <option value=""></option>
                        @foreach($categories as $c)
                            <option value="{{ $c['id'] }}" @selected($filters['category_id']==$c['id'])>
                                {!! str_repeat('&nbsp;&nbsp;', $c['depth']) !!}{{ $c['depth'] > 0 ? '└ ' : '' }}{{ $c['name'] }}
                            </option>
                        @endforeach
                    </select>

                    <select name="grade_id" class="form-select form-select-solid form-select-sm w-130px"
                            data-control="select2" data-hide-search="true" data-placeholder="Grade"
                            onchange="this.form.submit()">
                        <option value=""></option>
                        @foreach($grades as $g)<option value="{{ $g->id }}" @selected($filters['grade_id']==$g->id)>{{ $g->code }} — {{ $g->name }}</option>@endforeach
                    </select>

                    <select name="uom_id" class="form-select form-select-solid form-select-sm w-110px"
                            data-control="select2" data-hide-search="true" data-placeholder="UoM"
                            onchange="this.form.submit()">
                        <option value=""></option>
                        @foreach($uoms as $u)<option value="{{ $u->id }}" @selected($filters['uom_id']==$u->id)>{{ $u->code }}</option>@endforeach
                    </select>

                    <select name="status" class="form-select form-select-solid form-select-sm w-130px"
                            data-control="select2" data-hide-search="true" data-placeholder="Status"
                            onchange="this.form.submit()">
                        <option value=""></option>
                        <option value="active"   @selected($filters['status']==='active')>Aktif</option>
                        <option value="inactive" @selected($filters['status']==='inactive')>Non-aktif</option>
                    </select>

                    <label class="form-check form-check-custom form-check-solid form-check-sm align-self-center ms-2">
                        <input type="checkbox" name="stock_low" value="1" class="form-check-input" @checked($filters['stock_low']) onchange="this.form.submit()" />
                        <span class="form-check-label fs-7 text-gray-700 ms-2">Stock Low</span>
                    </label>

                    <label class="form-check form-check-custom form-check-solid form-check-sm align-self-center ms-2">
                        <input type="checkbox" name="trash" value="1" class="form-check-input" @checked($filters['trash']) onchange="this.form.submit()" />
                        <span class="form-check-label fs-7 text-gray-700 ms-2">Terhapus</span>
                    </label>
                </form>

                <a href="{{ route('products.create') }}" class="btn btn-sm btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> Tambah Produk
                </a>
            </div>
        </div>
    </div>

    <div class="card-body py-4">
        <table id="kt_products_table" class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="min-w-60px"></th>
                    <th class="min-w-130px">SKU</th>
                    <th class="min-w-200px">Nama</th>
                    <th class="min-w-120px">Kategori</th>
                    <th class="min-w-100px">Grade</th>
                    <th class="text-end min-w-100px">Stock</th>
                    <th class="min-w-80px">UoM</th>
                    <th class="text-end min-w-130px">Harga Jual</th>
                    <th class="min-w-100px">Status</th>
                    <th class="text-end min-w-100px">Aksi</th>
                </tr>
                <tr class="filter-row">
                    <th></th>
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari SKU" data-col="1" /></th>
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari nama" data-col="2" /></th>
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari kategori" data-col="3" /></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="text-gray-700 fw-semibold">
                @foreach($products as $p)
                    <tr class="{{ $p->trashed() ? 'opacity-50' : '' }}">
                        <td>
                            <div class="symbol symbol-50px">
                                <img src="{{ $p->image_display_url }}" alt="{{ $p->name }}" class="object-fit-cover rounded" style="width:50px;height:50px" />
                            </div>
                        </td>
                        <td><span class="text-gray-900 fw-bold fs-7">{{ $p->sku }}</span>
                            @if($p->barcode)<div class="text-muted fs-8"><i class="ki-outline ki-barcode fs-7"></i> {{ $p->barcode }}</div>@endif
                        </td>
                        <td>
                            <a href="{{ route('products.show', $p) }}" class="text-gray-900 text-hover-primary fw-bold">{{ $p->name }}</a>
                            @if($p->scientific_name)<div class="text-muted fs-8 fst-italic">{{ $p->scientific_name }}</div>@endif
                        </td>
                        <td>{{ $p->category?->name ?? '—' }}</td>
                        <td>
                            @if($p->grade)
                                <span class="badge fw-bold" style="background:{{ $p->grade->color ?? '#6c757d' }};color:#fff">{{ $p->grade->code }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td class="text-end fw-bold {{ $p->is_stock_low ? 'text-danger' : '' }}">
                            {{ number_format($p->total_stock, 3) }}
                            @if($p->is_stock_low)<i class="ki-outline ki-warning fs-3 text-danger ms-1" title="Stock di bawah minimum"></i>@endif
                        </td>
                        <td><span class="badge badge-light">{{ $p->baseUom?->code ?? '—' }}</span></td>
                        <td class="text-end">
                            @if($p->default_sell_price > 0)
                                Rp {{ number_format($p->default_sell_price, 0, ',', '.') }}
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>
                            @if($p->trashed())
                                <span class="badge badge-light-dark">Terhapus</span>
                            @else
                                <span class="badge badge-light-{{ $p->is_active ? 'success' : 'danger' }}">{{ $p->is_active ? 'Aktif' : 'Non-aktif' }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="#" class="btn btn-sm btn-light btn-active-light-primary"
                               data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                Aksi <i class="ki-outline ki-down fs-5 ms-1"></i>
                            </a>
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-150px py-3" data-kt-menu="true">
                                @if($p->trashed())
                                    <div class="menu-item px-3">
                                        <form method="POST" action="{{ route('products.restore', $p->id) }}"
                                              data-sweet-confirm
                                              data-sweet-title="Pulihkan Produk?"
                                              data-sweet-text="Produk '{{ $p->name }}' akan dipulihkan."
                                              data-sweet-icon="question"
                                              data-sweet-confirm-text="Ya, pulihkan"
                                              data-sweet-confirm-class="btn btn-success me-2">
                                            @csrf
                                            <button type="submit" class="menu-link px-3 w-100 text-start bg-transparent border-0 text-success">Pulihkan</button>
                                        </form>
                                    </div>
                                @else
                                    <div class="menu-item px-3"><a href="{{ route('products.show', $p) }}" class="menu-link px-3">Detail</a></div>
                                    <div class="menu-item px-3"><a href="{{ route('products.edit', $p) }}" class="menu-link px-3">Edit</a></div>
                                    <div class="menu-item px-3">
                                        <form method="POST" action="{{ route('products.destroy', $p) }}"
                                              data-sweet-confirm
                                              data-sweet-title="Hapus Produk?"
                                              data-sweet-text="Produk '{{ $p->name }}' akan dihapus (dapat dipulihkan)."
                                              data-sweet-icon="warning"
                                              data-sweet-confirm-text="Ya, hapus"
                                              data-sweet-confirm-class="btn btn-danger me-2">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="menu-link px-3 w-100 text-start bg-transparent border-0 text-danger">Hapus</button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    var dt = $('#kt_products_table').DataTable({
        info: true, order: [[1, 'asc']], pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
        language: {
            search: '', lengthMenu: 'Tampil _MENU_',
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_',
            paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
            zeroRecords: 'Tidak ada produk yang cocok',
            emptyTable: @json($filters['trash'] ? 'Tidak ada produk yang terhapus.' : 'Belum ada data produk. Klik "Tambah Produk" di atas.')
        },
        columnDefs: [
            { orderable: false, targets: [0, 9] },
            { searchable: false, targets: [0, 4, 5, 6, 7, 8, 9] }
        ],
        initComplete: function () { $('.dataTables_filter').hide(); }
    });

    $('.filter-row input').on('click', e => e.stopPropagation())
        .on('keyup change clear', function () { var c = $(this).data('col'); if (dt.column(c).search() !== this.value) dt.column(c).search(this.value).draw(); });
    $('.filter-row th').off('click.DT').on('click', e => e.stopPropagation());
});
</script>
@endpush

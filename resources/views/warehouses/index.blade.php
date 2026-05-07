@extends('layouts.app')

@section('title', 'Daftar Gudang')
@section('page_title', 'Gudang')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Gudang</li>
@endsection

@push('styles')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<x-flash-messages />

<div class="card">
    {{-- ========== Toolbar / filter ========== --}}
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <form method="GET" action="{{ route('warehouses.index') }}" class="d-flex align-items-center position-relative my-1">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                <input type="text" name="q" value="{{ $filters['q'] }}"
                       class="form-control form-control-solid w-250px ps-13"
                       placeholder="Cari kode / nama / alamat..." />
                @if($filters['q'] || $filters['type'] || $filters['status'])
                    <a href="{{ route('warehouses.index') }}" class="btn btn-sm btn-light ms-2">Reset</a>
                @endif
            </form>
        </div>

        <div class="card-toolbar">
            <div class="d-flex justify-content-end align-items-center" id="kt_warehouse_toolbar">
                <form method="GET" action="{{ route('warehouses.index') }}" class="d-flex gap-2 me-3">
                    <input type="hidden" name="q" value="{{ $filters['q'] }}">

                    <select name="type" class="form-select form-select-solid form-select-sm w-150px"
                            data-control="select2" data-hide-search="true" data-placeholder="Semua Tipe"
                            onchange="this.form.submit()">
                        <option value=""></option>
                        @foreach($types as $key => $label)
                            <option value="{{ $key }}" @selected($filters['type'] === $key)>{{ $label }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="form-select form-select-solid form-select-sm w-150px"
                            data-control="select2" data-hide-search="true" data-placeholder="Semua Status"
                            onchange="this.form.submit()">
                        <option value=""></option>
                        <option value="active"   @selected($filters['status'] === 'active')>Aktif</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Non-aktif</option>
                    </select>
                </form>

                <a href="{{ route('warehouses.create') }}" class="btn btn-sm btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> Tambah Gudang
                </a>
            </div>
        </div>
    </div>

    {{-- ========== DataTable ========== --}}
    <div class="card-body py-4">
        <table id="kt_warehouses_table" class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="min-w-100px">Kode</th>
                    <th class="min-w-200px">Nama Gudang</th>
                    <th class="min-w-120px">Tipe</th>
                    <th class="min-w-100px">Suhu (°C)</th>
                    <th class="min-w-150px">PIC</th>
                    <th class="min-w-100px">Status</th>
                    <th class="text-end min-w-100px">Aksi</th>
                </tr>
                {{-- Per-column search row --}}
                <tr class="filter-row">
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari kode" data-col="0" /></th>
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari nama" data-col="1" /></th>
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari tipe" data-col="2" /></th>
                    <th></th>
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari PIC" data-col="4" /></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="text-gray-700 fw-semibold">
                @foreach($warehouses as $wh)
                    <tr>
                        <td><span class="text-gray-900 fw-bold">{{ $wh->code }}</span></td>
                        <td>
                            <a href="{{ route('warehouses.show', $wh) }}" class="text-gray-900 text-hover-primary fw-bold">
                                {{ $wh->name }}
                            </a>
                            @if($wh->address)
                                <div class="text-muted fs-7">{{ \Illuminate\Support\Str::limit($wh->address, 50) }}</div>
                            @endif
                        </td>
                        <td><span class="badge {{ $wh->type_badge_class }} fw-bold">{{ $wh->type_label }}</span></td>
                        <td>
                            @if($wh->temperature_min !== null || $wh->temperature_max !== null)
                                <span class="text-gray-700">{{ $wh->temperature_min ?? '-' }} ~ {{ $wh->temperature_max ?? '-' }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </td>
                        <td>{{ $wh->picUser?->full_name ?? '—' }}</td>
                        <td>
                            @if($wh->is_active)
                                <span class="badge badge-light-success">Aktif</span>
                            @else
                                <span class="badge badge-light-danger">Non-aktif</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="#" class="btn btn-sm btn-light btn-active-light-primary"
                               data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                Aksi <i class="ki-outline ki-down fs-5 ms-1"></i>
                            </a>
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-150px py-3"
                                 data-kt-menu="true">
                                <div class="menu-item px-3">
                                    <a href="{{ route('warehouses.show', $wh) }}" class="menu-link px-3">Detail</a>
                                </div>
                                <div class="menu-item px-3">
                                    <a href="{{ route('warehouses.edit', $wh) }}" class="menu-link px-3">Edit</a>
                                </div>
                                <div class="menu-item px-3">
                                    <form method="POST" action="{{ route('warehouses.toggle', $wh) }}"
                                          data-sweet-confirm
                                          data-sweet-title="{{ $wh->is_active ? 'Nonaktifkan Gudang?' : 'Aktifkan Gudang?' }}"
                                          data-sweet-text="Gudang '{{ $wh->name }}' akan {{ $wh->is_active ? 'dinonaktifkan' : 'diaktifkan' }}."
                                          data-sweet-icon="{{ $wh->is_active ? 'warning' : 'question' }}"
                                          data-sweet-confirm-text="Ya, {{ $wh->is_active ? 'nonaktifkan' : 'aktifkan' }}"
                                          data-sweet-confirm-class="btn {{ $wh->is_active ? 'btn-danger' : 'btn-success' }} me-2">
                                        @csrf @method('PATCH')
                                        <button type="submit" class="menu-link px-3 w-100 text-start bg-transparent border-0 {{ $wh->is_active ? 'text-danger' : 'text-success' }}">
                                            {{ $wh->is_active ? 'Nonaktifkan' : 'Aktifkan' }}
                                        </button>
                                    </form>
                                </div>
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
        // Init DataTable
        var dt = $('#kt_warehouses_table').DataTable({
            info: true,
            order: [[0, 'asc']],
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
            language: {
                search: '',
                lengthMenu: 'Tampil _MENU_',
                info: 'Menampilkan _START_ - _END_ dari _TOTAL_',
                infoEmpty: 'Tidak ada data',
                paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
                zeroRecords: 'Tidak ada data yang cocok',
                emptyTable: 'Belum ada data gudang. Klik "Tambah Gudang" di atas.'
            },
            columnDefs: [
                { orderable: false, targets: [3, 6] },  // suhu & aksi tidak sortable
                { searchable: false, targets: [3, 5, 6] }  // suhu, status, aksi excluded dari column-search
            ],
            initComplete: function () {
                // Sembunyikan default search bar (kita pakai yang di toolbar)
                $('.dataTables_filter').hide();
            }
        });

        // Per-column search — stop click bubbling supaya tidak trigger sort
        $('.filter-row input').on('click', function (e) {
            e.stopPropagation();
        }).on('keyup change clear', function () {
            var colIdx = $(this).data('col');
            if (dt.column(colIdx).search() !== this.value) {
                dt.column(colIdx).search(this.value).draw();
            }
        });

        // Cegah seluruh filter-row jadi sortable trigger
        $('.filter-row th').off('click.DT').on('click', function (e) {
            e.stopPropagation();
        });

        // Init select2 untuk filter form (Metronic auto-init)
        if (typeof KTApp !== 'undefined') KTApp.init();
    });
</script>
@endpush

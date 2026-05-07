@extends('layouts.app')

@section('title', 'Daftar Supplier')
@section('page_title', 'Supplier')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Mitra</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Supplier</li>
@endsection

@push('styles')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <form method="GET" action="{{ route('suppliers.index') }}" class="d-flex align-items-center position-relative my-1">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                <input type="text" name="q" value="{{ $filters['q'] }}"
                       class="form-control form-control-solid w-300px ps-13"
                       placeholder="Cari kode / nama / kontak / phone / email..." />
                @if(array_filter($filters))
                    <a href="{{ route('suppliers.index') }}" class="btn btn-sm btn-light ms-2">Reset</a>
                @endif
            </form>
        </div>

        <div class="card-toolbar">
            <div class="d-flex justify-content-end align-items-center">
                <form method="GET" action="{{ route('suppliers.index') }}" class="d-flex gap-2 me-3">
                    <input type="hidden" name="q" value="{{ $filters['q'] }}">

                    <select name="city" class="form-select form-select-solid form-select-sm w-150px"
                            data-control="select2" data-placeholder="Semua Kota"
                            onchange="this.form.submit()">
                        <option value=""></option>
                        @foreach($cities as $c)
                            <option value="{{ $c }}" @selected($filters['city'] === $c)>{{ $c }}</option>
                        @endforeach
                    </select>

                    <select name="status" class="form-select form-select-solid form-select-sm w-150px"
                            data-control="select2" data-hide-search="true" data-placeholder="Semua Status"
                            onchange="this.form.submit()">
                        <option value=""></option>
                        <option value="active"   @selected($filters['status'] === 'active')>Aktif</option>
                        <option value="inactive" @selected($filters['status'] === 'inactive')>Non-aktif</option>
                    </select>

                    <label class="form-check form-check-custom form-check-solid form-check-sm align-self-center ms-2">
                        <input type="checkbox" name="trash" value="1" class="form-check-input" @checked($filters['trash'])
                               onchange="this.form.submit()" />
                        <span class="form-check-label fs-7 text-gray-700 ms-2">Tampilkan Terhapus</span>
                    </label>
                </form>

                <a href="{{ route('suppliers.create') }}" class="btn btn-sm btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> Tambah Supplier
                </a>
            </div>
        </div>
    </div>

    <div class="card-body py-4">
        <table id="kt_suppliers_table" class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="min-w-100px">Kode</th>
                    <th class="min-w-200px">Nama</th>
                    <th class="min-w-150px">Kontak</th>
                    <th class="min-w-120px">Phone</th>
                    <th class="min-w-100px">Kota</th>
                    <th class="text-center min-w-80px">TOP</th>
                    <th class="min-w-100px">Status</th>
                    <th class="text-end min-w-100px">Aksi</th>
                </tr>
                <tr class="filter-row">
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari kode" data-col="0" /></th>
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari nama" data-col="1" /></th>
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari kontak" data-col="2" /></th>
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari phone" data-col="3" /></th>
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari kota" data-col="4" /></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="text-gray-700 fw-semibold">
                @foreach($suppliers as $s)
                    <tr class="{{ $s->trashed() ? 'opacity-50' : '' }}">
                        <td><span class="text-gray-900 fw-bold">{{ $s->code }}</span></td>
                        <td>
                            <a href="{{ route('suppliers.show', $s) }}" class="text-gray-900 text-hover-primary fw-bold">
                                {{ $s->name }}
                            </a>
                            @if($s->email)
                                <div class="text-muted fs-7"><i class="ki-outline ki-sms fs-7"></i> {{ $s->email }}</div>
                            @endif
                        </td>
                        <td>{{ $s->contact_person ?? '—' }}</td>
                        <td>{{ $s->phone ?? '—' }}</td>
                        <td>{{ $s->city ?? '—' }}</td>
                        <td class="text-center"><span class="badge badge-light-info">{{ $s->payment_terms_days }} hari</span></td>
                        <td>
                            @if($s->trashed())
                                <span class="badge badge-light-dark">Terhapus</span>
                            @else
                                <span class="badge {{ $s->status_badge }}">{{ $s->status_label }}</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <a href="#" class="btn btn-sm btn-light btn-active-light-primary"
                               data-kt-menu-trigger="click" data-kt-menu-placement="bottom-end">
                                Aksi <i class="ki-outline ki-down fs-5 ms-1"></i>
                            </a>
                            <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-600 menu-state-bg-light-primary fw-semibold fs-7 w-150px py-3"
                                 data-kt-menu="true">
                                @if($s->trashed())
                                    <div class="menu-item px-3">
                                        <form method="POST" action="{{ route('suppliers.restore', $s->id) }}"
                                              data-sweet-confirm
                                              data-sweet-title="Pulihkan Supplier?"
                                              data-sweet-text="Supplier '{{ $s->name }}' akan dipulihkan."
                                              data-sweet-icon="question"
                                              data-sweet-confirm-text="Ya, pulihkan"
                                              data-sweet-confirm-class="btn btn-success me-2">
                                            @csrf
                                            <button type="submit" class="menu-link px-3 w-100 text-start bg-transparent border-0 text-success">Pulihkan</button>
                                        </form>
                                    </div>
                                @else
                                    <div class="menu-item px-3">
                                        <a href="{{ route('suppliers.show', $s) }}" class="menu-link px-3">Detail</a>
                                    </div>
                                    <div class="menu-item px-3">
                                        <a href="{{ route('suppliers.edit', $s) }}" class="menu-link px-3">Edit</a>
                                    </div>
                                    <div class="menu-item px-3">
                                        <form method="POST" action="{{ route('suppliers.destroy', $s) }}"
                                              data-sweet-confirm
                                              data-sweet-title="Hapus Supplier?"
                                              data-sweet-text="Supplier '{{ $s->name }}' akan dihapus (dapat dipulihkan)."
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
    var dt = $('#kt_suppliers_table').DataTable({
        info: true,
        order: [[0, 'asc']],
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
        language: {
            search: '', lengthMenu: 'Tampil _MENU_',
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_',
            infoEmpty: 'Tidak ada data',
            paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
            zeroRecords: 'Tidak ada data yang cocok',
            emptyTable: @json($filters['trash'] ? 'Tidak ada supplier yang terhapus.' : 'Belum ada data supplier. Klik "Tambah Supplier" di atas.')
        },
        columnDefs: [
            { orderable: false, targets: [7] },
            { searchable: false, targets: [5, 6, 7] }
        ],
        initComplete: function () { $('.dataTables_filter').hide(); }
    });

    $('.filter-row input').on('click', function (e) { e.stopPropagation(); })
        .on('keyup change clear', function () {
            var c = $(this).data('col');
            if (dt.column(c).search() !== this.value) dt.column(c).search(this.value).draw();
        });
    $('.filter-row th').off('click.DT').on('click', function (e) { e.stopPropagation(); });
});
</script>
@endpush

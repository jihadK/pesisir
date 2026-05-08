@extends('layouts.app')

@section('title', 'Tier Harga')
@section('page_title', 'Tier Harga')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Produk</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Tier Harga</li>
@endsection

@push('styles')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <form method="GET" action="{{ route('price_tiers.index') }}" class="d-flex align-items-center position-relative my-1">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                <input type="text" name="q" value="{{ $filters['q'] }}"
                       class="form-control form-control-solid w-300px ps-13"
                       placeholder="Cari nama / deskripsi..." />
                @if($filters['q'] || $filters['status'])<a href="{{ route('price_tiers.index') }}" class="btn btn-sm btn-light ms-2">Reset</a>@endif
            </form>
        </div>
        <div class="card-toolbar">
            <form method="GET" action="{{ route('price_tiers.index') }}" class="d-flex me-3">
                <input type="hidden" name="q" value="{{ $filters['q'] }}">
                <select name="status" class="form-select form-select-solid form-select-sm w-150px"
                        data-control="select2" data-hide-search="true" data-placeholder="Status"
                        onchange="this.form.submit()">
                    <option value=""></option>
                    <option value="active"   @selected($filters['status']==='active')>Aktif</option>
                    <option value="inactive" @selected($filters['status']==='inactive')>Non-aktif</option>
                </select>
            </form>
            <a href="{{ route('price_tiers.create') }}" class="btn btn-sm btn-primary">
                <i class="ki-outline ki-plus fs-2"></i> Tambah Tier
            </a>
        </div>
    </div>
    <div class="card-body py-4">
        <table id="kt_tiers_table" class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="min-w-150px">Nama</th>
                    <th class="min-w-300px">Deskripsi</th>
                    <th class="text-center min-w-130px">Customer Pakai</th>
                    <th class="min-w-100px">Status</th>
                    <th class="text-end min-w-100px">Aksi</th>
                </tr>
                <tr class="filter-row">
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari nama" data-col="0" /></th>
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari deskripsi" data-col="1" /></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="text-gray-700 fw-semibold">
                @foreach($tiers as $t)
                    <tr>
                        <td><span class="fw-bold text-gray-900">{{ $t->name }}</span></td>
                        <td class="text-muted">{{ $t->description ?? '—' }}</td>
                        <td class="text-center">
                            @if($t->customer_count > 0)
                                <span class="badge badge-light-info">{{ $t->customer_count }} customer</span>
                            @else
                                <span class="text-muted fs-7">—</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge badge-light-{{ $t->is_active ? 'success' : 'danger' }}">
                                {{ $t->is_active ? 'Aktif' : 'Non-aktif' }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('price_tiers.edit', $t) }}" class="btn btn-sm btn-light-primary">Edit</a>
                            <form method="POST" action="{{ route('price_tiers.destroy', $t) }}" class="d-inline"
                                  data-sweet-confirm
                                  data-sweet-title="Hapus Tier?"
                                  data-sweet-text="Tier '{{ $t->name }}' akan dihapus permanen."
                                  data-sweet-icon="warning"
                                  data-sweet-confirm-text="Ya, hapus"
                                  data-sweet-confirm-class="btn btn-danger me-2">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-light-danger">Hapus</button>
                            </form>
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
    var dt = $('#kt_tiers_table').DataTable({
        info: true, order: [[0, 'asc']], pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']],
        language: { search: '', lengthMenu: 'Tampil _MENU_', info: 'Menampilkan _START_ - _END_ dari _TOTAL_',
            paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
            zeroRecords: 'Tidak ada data yang cocok',
            emptyTable: 'Belum ada tier. Klik "Tambah Tier" di atas.' },
        columnDefs: [{ orderable: false, targets: [4] }, { searchable: false, targets: [2, 3, 4] }],
        initComplete: function () { $('.dataTables_filter').hide(); }
    });
    $('.filter-row input').on('click', e => e.stopPropagation())
        .on('keyup change clear', function () { var c = $(this).data('col'); if (dt.column(c).search() !== this.value) dt.column(c).search(this.value).draw(); });
    $('.filter-row th').off('click.DT').on('click', e => e.stopPropagation());
});
</script>
@endpush

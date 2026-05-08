@extends('layouts.app')

@section('title', 'Product Grades')
@section('page_title', 'Grade Produk')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Produk</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Grade</li>
@endsection

@push('styles')
    <link href="{{ asset('assets/plugins/custom/datatables/datatables.bundle.css') }}" rel="stylesheet" type="text/css" />
@endpush

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <form method="GET" action="{{ route('grades.index') }}" class="d-flex align-items-center position-relative my-1">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                <input type="text" name="q" value="{{ $filters['q'] }}"
                       class="form-control form-control-solid w-300px ps-13"
                       placeholder="Cari kode / nama..." />
                @if($filters['q'])<a href="{{ route('grades.index') }}" class="btn btn-sm btn-light ms-2">Reset</a>@endif
            </form>
        </div>
        <div class="card-toolbar">
            <a href="{{ route('grades.create') }}" class="btn btn-sm btn-primary">
                <i class="ki-outline ki-plus fs-2"></i> Tambah Grade
            </a>
        </div>
    </div>
    <div class="card-body py-4">
        <table id="kt_grades_table" class="table align-middle table-row-dashed fs-6 gy-5">
            <thead>
                <tr class="text-start text-muted fw-bold fs-7 text-uppercase gs-0">
                    <th class="min-w-100px">Kode</th>
                    <th class="min-w-200px">Nama</th>
                    <th class="min-w-150px">Warna Badge</th>
                    <th class="min-w-200px">Preview</th>
                    <th class="text-end min-w-100px">Aksi</th>
                </tr>
                <tr class="filter-row">
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari kode" data-col="0" /></th>
                    <th><input type="text" class="form-control form-control-sm form-control-solid" placeholder="Cari nama" data-col="1" /></th>
                    <th></th>
                    <th></th>
                    <th></th>
                </tr>
            </thead>
            <tbody class="text-gray-700 fw-semibold">
                @foreach($grades as $g)
                    <tr>
                        <td><span class="fw-bold text-gray-900">{{ $g->code }}</span></td>
                        <td>{{ $g->name }}</td>
                        <td>
                            <span class="d-inline-block rounded-circle border border-gray-300"
                                  style="width:16px;height:16px;background:{{ $g->display_color }};vertical-align:middle"></span>
                            <code class="ms-2 fs-7">{{ $g->color ?? '—' }}</code>
                        </td>
                        <td>
                            <span class="badge fw-bold px-3 py-2"
                                  style="background:{{ $g->display_color }};color:{{ $g->contrast_text }}">
                                Grade {{ $g->code }} — {{ $g->name }}
                            </span>
                        </td>
                        <td class="text-end">
                            <a href="{{ route('grades.edit', $g) }}" class="btn btn-sm btn-light-primary">Edit</a>
                            <form method="POST" action="{{ route('grades.destroy', $g) }}" class="d-inline"
                                  data-sweet-confirm
                                  data-sweet-title="Hapus Grade?"
                                  data-sweet-text="Grade '{{ $g->code }}' akan dihapus permanen."
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
    var dt = $('#kt_grades_table').DataTable({
        info: true, order: [[0, 'asc']], pageLength: 10,
        lengthMenu: [[10, 25, 50, -1], [10, 25, 50, 'Semua']],
        language: { search: '', lengthMenu: 'Tampil _MENU_', info: 'Menampilkan _START_ - _END_ dari _TOTAL_',
            paginate: { previous: 'Sebelumnya', next: 'Selanjutnya' },
            zeroRecords: 'Tidak ada data yang cocok',
            emptyTable: 'Belum ada grade. Klik "Tambah Grade" di atas.' },
        columnDefs: [{ orderable: false, targets: [2, 3, 4] }, { searchable: false, targets: [2, 3, 4] }],
        initComplete: function () { $('.dataTables_filter').hide(); }
    });
    $('.filter-row input').on('click', e => e.stopPropagation())
        .on('keyup change clear', function () { var c = $(this).data('col'); if (dt.column(c).search() !== this.value) dt.column(c).search(this.value).draw(); });
    $('.filter-row th').off('click.DT').on('click', e => e.stopPropagation());
});
</script>
@endpush

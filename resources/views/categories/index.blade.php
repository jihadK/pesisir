@extends('layouts.app')

@section('title', 'Kategori Produk')
@section('page_title', 'Kategori Produk')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted">Produk</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Kategori</li>
@endsection

@push('styles')
    <link href="{{ asset('assets/plugins/custom/jstree/jstree.bundle.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .jstree-default .jstree-anchor { line-height: 28px; height: 28px; }
        .jstree-default .jstree-icon { line-height: 28px; }
        .jstree-default .jstree-node { margin-left: 22px; min-height: 28px; }
        .jstree-default .jstree-clicked { background: #f1f3f9; box-shadow: none; border-radius: 4px; }
        .jstree-default .jstree-hovered { background: #f8f9fa; border-radius: 4px; }
        #kt_categories_tree { padding: 1rem 0; }
    </style>
@endpush

@section('content')
<div class="row g-5">
    {{-- Stat cards --}}
    <div class="col-md-12">
        <div class="row g-3 mb-5">
            <div class="col-md-4">
                <div class="card card-flush">
                    <div class="card-body py-4">
                        <div class="d-flex flex-stack">
                            <div>
                                <div class="text-muted fs-7 text-uppercase">Total Kategori</div>
                                <div class="fs-2 fw-bolder">{{ $totalCategories }}</div>
                            </div>
                            <i class="ki-outline ki-folder fs-3hx text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-flush">
                    <div class="card-body py-4">
                        <div class="d-flex flex-stack">
                            <div>
                                <div class="text-muted fs-7 text-uppercase">Kategori Root</div>
                                <div class="fs-2 fw-bolder">{{ $totalRoot }}</div>
                            </div>
                            <i class="ki-outline ki-element-equal fs-3hx text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card card-flush">
                    <div class="card-body py-4">
                        <div class="d-flex flex-stack">
                            <div>
                                <div class="text-muted fs-7 text-uppercase">Sub-kategori</div>
                                <div class="fs-2 fw-bolder">{{ $totalCategories - $totalRoot }}</div>
                            </div>
                            <i class="ki-outline ki-tree fs-3hx text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Tree view --}}
    <div class="col-md-7">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Struktur Kategori</h3>
                <div class="card-toolbar">
                    <button class="btn btn-sm btn-light me-2" id="kt_tree_expand">
                        <i class="ki-outline ki-arrow-down fs-3"></i> Buka Semua
                    </button>
                    <button class="btn btn-sm btn-light me-2" id="kt_tree_collapse">
                        <i class="ki-outline ki-arrow-up fs-3"></i> Tutup Semua
                    </button>
                    <a href="{{ route('categories.create') }}" class="btn btn-sm btn-primary">
                        <i class="ki-outline ki-plus fs-2"></i> Tambah Kategori
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if(empty($tree))
                    <div class="text-center py-10 text-muted">
                        <i class="ki-outline ki-folder fs-3hx text-gray-300"></i>
                        <div class="mt-3">Belum ada kategori. <a href="{{ route('categories.create') }}">Tambah sekarang</a>.</div>
                    </div>
                @else
                    <div id="kt_categories_tree"></div>
                @endif
            </div>
        </div>
    </div>

    {{-- Detail panel --}}
    <div class="col-md-5">
        <div class="card sticky-top" style="top: 20px">
            <div class="card-header">
                <h3 class="card-title">Detail Kategori</h3>
            </div>
            <div class="card-body" id="kt_category_detail">
                <div class="text-center text-muted py-10">
                    <i class="ki-outline ki-information-5 fs-3hx text-gray-300"></i>
                    <div class="mt-3">Klik salah satu kategori di tree untuk melihat detail dan aksi.</div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="{{ asset('assets/plugins/custom/jstree/jstree.bundle.js') }}"></script>
<script>
window.CATEGORY_TREE = @json($tree);

document.addEventListener('DOMContentLoaded', function () {
    var $tree = $('#kt_categories_tree');
    if (! $tree.length) return;

    $tree.jstree({
        core: {
            data: window.CATEGORY_TREE,
            themes: { name: 'default', responsive: true, dots: true, icons: true },
            check_callback: true
        },
        plugins: ['wholerow', 'types'],
        types: {
            default: { icon: 'ki-outline ki-folder fs-3 text-warning' }
        }
    });

    // Saat node di-click, render detail panel
    $tree.on('select_node.jstree', function (e, data) {
        var node = data.node.original;
        if (! node || ! node.id) return;

        var html = ''
            + '<div class="mb-4">'
            +   '<div class="text-muted fs-7 text-uppercase mb-1">Nama Kategori</div>'
            +   '<div class="fs-3 fw-bold">' + escapeHtml(node.name) + '</div>'
            + '</div>'
            + '<div class="mb-4">'
            +   '<div class="text-muted fs-7 text-uppercase mb-1">Slug</div>'
            +   '<code>' + escapeHtml(node.slug) + '</code>'
            + '</div>'
            + '<div class="separator my-4"></div>'
            + '<div class="d-flex flex-wrap gap-2">'
            +   '<a href="' + window.ROUTES.edit.replace(':id', node.id) + '" class="btn btn-sm btn-light-primary">'
            +     '<i class="ki-outline ki-pencil fs-3"></i> Edit'
            +   '</a>'
            +   '<a href="' + window.ROUTES.create + '?parent=' + node.id + '" class="btn btn-sm btn-light-success">'
            +     '<i class="ki-outline ki-plus fs-3"></i> Tambah Sub-Kategori'
            +   '</a>'
            +   '<button type="button" class="btn btn-sm btn-light-danger" onclick="deleteCategory(' + node.id + ', \'' + escapeJs(node.name) + '\')">'
            +     '<i class="ki-outline ki-trash fs-3"></i> Hapus'
            +   '</button>'
            + '</div>';

        document.getElementById('kt_category_detail').innerHTML = html;
    });

    // Expand / collapse all
    document.getElementById('kt_tree_expand').addEventListener('click', () => $tree.jstree('open_all'));
    document.getElementById('kt_tree_collapse').addEventListener('click', () => $tree.jstree('close_all'));
});

window.ROUTES = {
    edit:    "{{ route('categories.edit', ['category' => ':id']) }}",
    create:  "{{ route('categories.create') }}",
    destroy: "{{ url('categories') }}/:id"
};

function escapeHtml(s) { return String(s).replace(/[&<>"']/g, m => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
function escapeJs(s)   { return String(s).replace(/'/g, "\\'"); }

function deleteCategory(id, name) {
    sweetConfirm({
        title: 'Hapus Kategori?',
        text: 'Kategori "' + name + '" akan dihapus permanen.',
        icon: 'warning',
        confirmText: 'Ya, hapus',
        confirmClass: 'btn btn-danger me-2'
    }).then(function (r) {
        if (! r.isConfirmed) return;
        var form = document.createElement('form');
        form.method = 'POST';
        form.action = window.ROUTES.destroy.replace(':id', id);
        form.innerHTML = '<input type="hidden" name="_token" value="{{ csrf_token() }}">' +
                         '<input type="hidden" name="_method" value="DELETE">';
        // Tandai supaya tidak re-trigger sweet
        form.dataset.sweetConfirmed = '1';
        document.body.appendChild(form);
        form.submit();
    });
}
</script>
@endpush

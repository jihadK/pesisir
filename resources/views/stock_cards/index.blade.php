@extends('layouts.app')

@section('title', 'Kartu Stok')
@section('page_title', 'Kartu Stok')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Inventory</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Kartu Stok</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title flex-column">
            <h2 class="mb-1">Kartu Stok</h2>
            <span class="text-muted fs-7">Pilih produk untuk lihat riwayat mutasi stoknya.</span>
        </div>
        <div class="card-toolbar">
            <form method="GET" class="d-flex align-items-center position-relative">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                <input type="text" name="q" value="{{ $q }}"
                       class="form-control form-control-solid w-300px ps-13"
                       placeholder="Cari SKU / nama produk..." />
            </form>
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-row-bordered table-row-gray-100 align-middle gy-3">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4">SKU</th>
                        <th>Nama Produk</th>
                        <th>Kategori</th>
                        <th class="text-end">Saldo Total</th>
                        <th>UoM</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($products as $p)
                    <tr>
                        <td class="ps-4 fw-bold">{{ $p->sku }}</td>
                        <td>{{ $p->name }}</td>
                        <td class="text-muted">{{ $p->category?->name ?? '—' }}</td>
                        @php $ts = (float)$p->total_stock; @endphp
                        <td class="text-end fw-bold">{{ floor($ts)==$ts ? number_format($ts,0,',','.') : number_format($ts,3,',','.') }}</td>
                        <td>{{ $p->baseUom?->code ?? '—' }}</td>
                        <td class="text-end pe-4">
                            <a href="{{ route('stock_cards.show', $p) }}" class="btn btn-sm btn-light-primary">
                                <i class="ki-outline ki-questionnaire-tablet fs-3"></i> Lihat Kartu
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-10">Tidak ada produk.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $products->links() }}</div>
    </div>
</div>
@endsection

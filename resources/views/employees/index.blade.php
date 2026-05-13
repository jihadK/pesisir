@extends('layouts.app')

@section('title', 'Pegawai')
@section('page_title', 'Pegawai')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Konfigurasi</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Pegawai</li>
@endsection

@section('content')
<div class="card">
    <div class="card-header border-0 pt-6">
        <div class="card-title">
            <form method="GET" class="d-flex align-items-center position-relative">
                <i class="ki-outline ki-magnifier fs-3 position-absolute ms-5"></i>
                <input type="text" name="q" value="{{ $q }}" class="form-control form-control-solid w-300px ps-13" placeholder="Cari nama / kode / posisi..." />
            </form>
        </div>
        <div class="card-toolbar">
            @if(auth()->user()?->hasPermission('employee.create'))
                <a href="{{ route('employees.create') }}" class="btn btn-primary">
                    <i class="ki-outline ki-plus fs-2"></i> Tambah Pegawai
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-row-bordered align-middle gy-3">
                <thead>
                    <tr class="fw-bold text-muted bg-light">
                        <th class="ps-4">Kode</th>
                        <th>Nama</th>
                        <th>Posisi</th>
                        <th>No. HP</th>
                        <th class="text-center">Status</th>
                        <th class="text-end pe-4">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($employees as $e)
                    <tr>
                        <td class="ps-4"><code>{{ $e->code }}</code></td>
                        <td class="fw-bold">{{ $e->name }}</td>
                        <td>{{ $e->position ?? '—' }}</td>
                        <td class="fs-7">{{ $e->phone ?? '—' }}</td>
                        <td class="text-center">
                            @if($e->is_active)<span class="badge badge-light-success">Aktif</span>
                            @else<span class="badge badge-light-secondary">Non-aktif</span>@endif
                        </td>
                        <td class="text-end pe-4">
                            @if(auth()->user()?->hasPermission('employee.update'))
                                <a href="{{ route('employees.edit', $e) }}" class="btn btn-sm btn-light-warning"><i class="ki-outline ki-pencil fs-3"></i></a>
                            @endif
                            @if(auth()->user()?->hasPermission('employee.delete'))
                                <form method="POST" action="{{ route('employees.destroy', $e) }}" class="d-inline" onsubmit="return confirm('Hapus pegawai {{ $e->name }}?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-light-danger"><i class="ki-outline ki-trash fs-3"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="text-center text-muted py-10">Belum ada pegawai.</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-4">{{ $employees->links() }}</div>
    </div>
</div>
@endsection

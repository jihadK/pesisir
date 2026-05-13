@extends('layouts.app')
@section('title', 'Tambah Pegawai')
@section('page_title', 'Tambah Pegawai')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Konfigurasi</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('employees.index') }}" class="text-muted">Pegawai</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Baru</li>
@endsection
@section('content')
<form method="POST" action="{{ route('employees.store') }}">
    @csrf
    @include('employees._form', ['isEdit' => false])
</form>
@endsection

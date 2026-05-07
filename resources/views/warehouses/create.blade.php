@extends('layouts.app')

@section('title', 'Tambah Gudang')
@section('page_title', 'Tambah Gudang Baru')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('warehouses.index') }}" class="text-muted text-hover-primary">Gudang</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Tambah</li>
@endsection

@section('content')
<x-flash-messages />

<form method="POST" action="{{ route('warehouses.store') }}">
    @csrf
    @include('warehouses._form', ['isEdit' => false])
</form>
@endsection

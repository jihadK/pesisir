@extends('layouts.app')
@section('title', 'Tambah Tarif Jasa')
@section('page_title', 'Tambah Tarif Jasa')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Konfigurasi</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('service_rates.index') }}" class="text-muted">Tarif Jasa</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Baru</li>
@endsection
@section('content')
<form method="POST" action="{{ route('service_rates.store') }}">
    @csrf
    @include('service_rates._form', ['isEdit' => false])
</form>
@endsection

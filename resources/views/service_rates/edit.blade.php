@extends('layouts.app')
@section('title', 'Edit Tarif Jasa')
@section('page_title', 'Edit Tarif Jasa')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Konfigurasi</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('service_rates.index') }}" class="text-muted">Tarif Jasa</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $rate->name }}</li>
@endsection
@section('content')
<form method="POST" action="{{ route('service_rates.update', $rate) }}">
    @csrf @method('PUT')
    @include('service_rates._form', ['isEdit' => true])
</form>
@endsection

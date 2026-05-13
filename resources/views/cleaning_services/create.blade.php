@extends('layouts.app')
@section('title', 'Catat Jasa Bersih')
@section('page_title', 'Catat Jasa Bersih')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Pembelian</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('cleaning_services.index') }}" class="text-muted">Jasa Bersih Ikan</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Baru</li>
@endsection
@section('content')
<form method="POST" action="{{ route('cleaning_services.store') }}">
    @csrf
    @include('cleaning_services._form', ['isEdit' => false])
</form>
@endsection

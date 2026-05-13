@extends('layouts.app')
@section('title', 'Catat Pembelian')
@section('page_title', 'Catat Pembelian Lain-lain')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Pembelian</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('supplies_purchases.index') }}" class="text-muted">Pembelian Lain-lain</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Baru</li>
@endsection
@section('content')
<form method="POST" action="{{ route('supplies_purchases.store') }}">
    @csrf
    @include('supplies_purchases._form', ['isEdit' => false])
</form>
@endsection

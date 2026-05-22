@extends('layouts.app')

@section('title', 'Edit Kontrak Harga')
@section('page_title', 'Edit Kontrak Harga')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Penjualan</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('customer_prices.index') }}" class="text-muted">Kontrak Harga</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Edit</li>
@endsection

@section('content')
<form method="POST" action="{{ route('customer_prices.update', $row) }}">
    @csrf @method('PUT')
    @include('customer_prices._form', ['isEdit' => true])
</form>
@endsection

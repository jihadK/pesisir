@extends('layouts.app')

@section('title', 'Kontrak Harga Baru')
@section('page_title', 'Kontrak Harga Baru')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Penjualan</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('customer_prices.index') }}" class="text-muted">Kontrak Harga</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Baru</li>
@endsection

@section('content')
<form method="POST" action="{{ route('customer_prices.store') }}">
    @csrf
    @include('customer_prices._form', ['isEdit' => false])
</form>
@endsection

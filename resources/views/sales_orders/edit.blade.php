@extends('layouts.app')

@section('title', 'Edit Sales Order')
@section('page_title', 'Edit Sales Order')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Penjualan</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('sales_orders.index') }}" class="text-muted">Sales Order</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $so->so_number }}</li>
@endsection

@section('content')
<form method="POST" action="{{ route('sales_orders.update', $so) }}">
    @csrf @method('PUT')
    @include('sales_orders._form', ['isEdit' => true])
</form>
@endsection

@extends('layouts.app')

@section('title', 'Sales Order Baru')
@section('page_title', 'Sales Order Baru')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Penjualan</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('sales_orders.index') }}" class="text-muted">Sales Order</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Baru</li>
@endsection

@section('content')
<form method="POST" action="{{ route('sales_orders.store') }}">
    @csrf
    @include('sales_orders._form', ['isEdit' => false])
</form>
@endsection

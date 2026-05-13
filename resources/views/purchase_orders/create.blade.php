@extends('layouts.app')

@section('title', 'PO Baru')
@section('page_title', 'Purchase Order Baru')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Pembelian</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('purchase_orders.index') }}" class="text-muted">Purchase Order</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Baru</li>
@endsection

@section('content')
<form method="POST" action="{{ route('purchase_orders.store') }}">
    @csrf
    @include('purchase_orders._form', ['isEdit' => false])
</form>
@endsection

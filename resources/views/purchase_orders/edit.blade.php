@extends('layouts.app')

@section('title', 'Edit PO')
@section('page_title', 'Edit Purchase Order')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Pembelian</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('purchase_orders.index') }}" class="text-muted">Purchase Order</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $po->po_number }}</li>
@endsection

@section('content')
<form method="POST" action="{{ route('purchase_orders.update', $po) }}">
    @csrf @method('PUT')
    @include('purchase_orders._form', ['isEdit' => true])
</form>
@endsection

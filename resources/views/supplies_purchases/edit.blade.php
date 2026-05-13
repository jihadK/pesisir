@extends('layouts.app')
@section('title', 'Edit Pembelian')
@section('page_title', 'Edit Pembelian Lain-lain')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Pembelian</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('supplies_purchases.index') }}" class="text-muted">Pembelian Lain-lain</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $purchase->purchase_no }}</li>
@endsection
@section('content')
<form method="POST" action="{{ route('supplies_purchases.update', $purchase) }}">
    @csrf @method('PUT')
    @include('supplies_purchases._form', ['isEdit' => true])
</form>
@endsection

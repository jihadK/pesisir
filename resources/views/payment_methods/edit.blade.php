@extends('layouts.app')

@section('title', 'Edit Metode Pembayaran')
@section('page_title', 'Edit Metode Pembayaran')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Konfigurasi</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('payment_methods.index') }}" class="text-muted">Metode Pembayaran</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">{{ $method->code }}</li>
@endsection

@section('content')
<form method="POST" action="{{ route('payment_methods.update', $method) }}" enctype="multipart/form-data">
    @csrf @method('PUT')
    @include('payment_methods._form', ['isEdit' => true])
</form>
@endsection

@extends('layouts.app')
@section('title', 'Tambah Produk')
@section('page_title', 'Tambah Produk Baru')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('products.index') }}" class="text-muted text-hover-primary">Produk</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Tambah</li>
@endsection
@section('content')
<form method="POST" action="{{ route('products.store') }}" enctype="multipart/form-data">@csrf
    @include('products._form', ['isEdit' => false])
</form>
@endsection

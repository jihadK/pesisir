@extends('layouts.app')
@section('title', 'Edit Produk')
@section('page_title', 'Edit: ' . $product->name)
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('products.index') }}" class="text-muted text-hover-primary">Produk</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Edit</li>
@endsection
@section('content')
<form method="POST" action="{{ route('products.update', $product) }}" enctype="multipart/form-data">@csrf @method('PUT')
    @include('products._form', ['isEdit' => true])
</form>
@endsection

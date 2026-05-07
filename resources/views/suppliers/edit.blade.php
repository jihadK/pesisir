@extends('layouts.app')

@section('title', 'Edit Supplier')
@section('page_title', 'Edit: ' . $supplier->name)
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('suppliers.index') }}" class="text-muted text-hover-primary">Supplier</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Edit</li>
@endsection

@section('content')
<form method="POST" action="{{ route('suppliers.update', $supplier) }}">
    @csrf @method('PUT')
    @include('suppliers._form', ['isEdit' => true])
</form>
@endsection

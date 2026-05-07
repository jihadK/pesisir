@extends('layouts.app')

@section('title', 'Edit Gudang')
@section('page_title', 'Edit: ' . $warehouse->name)
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('warehouses.index') }}" class="text-muted text-hover-primary">Gudang</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Edit</li>
@endsection

@section('content')
<x-flash-messages />

<form method="POST" action="{{ route('warehouses.update', $warehouse) }}">
    @csrf @method('PUT')
    @include('warehouses._form', ['isEdit' => true])
</form>
@endsection

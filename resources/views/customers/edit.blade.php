@extends('layouts.app')

@section('title', 'Edit Customer')
@section('page_title', 'Edit: ' . $customer->name)
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('customers.index') }}" class="text-muted text-hover-primary">Customer</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Edit</li>
@endsection

@section('content')
<form method="POST" action="{{ route('customers.update', $customer) }}">
    @csrf @method('PUT')
    @include('customers._form', ['isEdit' => true])
</form>
@endsection

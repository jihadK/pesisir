@extends('layouts.app')
@section('title', 'Edit Kategori')
@section('page_title', 'Edit: ' . $category->name)
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('categories.index') }}" class="text-muted text-hover-primary">Kategori</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Edit</li>
@endsection
@section('content')
<form method="POST" action="{{ route('categories.update', $category) }}">@csrf @method('PUT')
    @include('categories._form', ['isEdit' => true])
</form>
@endsection

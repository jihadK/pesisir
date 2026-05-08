@extends('layouts.app')
@section('title', 'Tambah Kategori')
@section('page_title', 'Tambah Kategori Baru')
@section('breadcrumb')
    <li class="breadcrumb-item text-muted">Master Data</li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-muted"><a href="{{ route('categories.index') }}" class="text-muted text-hover-primary">Kategori</a></li>
    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
    <li class="breadcrumb-item text-gray-900">Tambah</li>
@endsection
@section('content')
<form method="POST" action="{{ route('categories.store') }}">@csrf
    @include('categories._form', ['isEdit' => false])
</form>
@endsection

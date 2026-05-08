@extends('layouts.app')
@section('title', 'Tambah Satuan')
@section('page_title', 'Tambah Satuan')
@section('content')
<form method="POST" action="{{ route('uoms.store') }}">@csrf
    @include('uoms._form', ['isEdit' => false])
</form>
@endsection

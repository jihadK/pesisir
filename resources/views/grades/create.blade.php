@extends('layouts.app')
@section('title', 'Tambah Grade')
@section('page_title', 'Tambah Grade')
@section('content')
<form method="POST" action="{{ route('grades.store') }}">@csrf
    @include('grades._form', ['isEdit' => false])
</form>
@endsection

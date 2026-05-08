@extends('layouts.app')
@section('title', 'Edit Grade')
@section('page_title', 'Edit: ' . $grade->code . ' — ' . $grade->name)
@section('content')
<form method="POST" action="{{ route('grades.update', $grade) }}">@csrf @method('PUT')
    @include('grades._form', ['isEdit' => true])
</form>
@endsection

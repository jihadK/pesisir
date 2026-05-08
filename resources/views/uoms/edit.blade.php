@extends('layouts.app')
@section('title', 'Edit Satuan')
@section('page_title', 'Edit: ' . $uom->code . ' — ' . $uom->name)
@section('content')
<form method="POST" action="{{ route('uoms.update', $uom) }}">@csrf @method('PUT')
    @include('uoms._form', ['isEdit' => true])
</form>
@endsection

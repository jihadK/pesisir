@extends('layouts.app')
@section('title', 'Tambah Tier')
@section('page_title', 'Tambah Tier Harga')
@section('content')
<form method="POST" action="{{ route('price_tiers.store') }}">@csrf
    @include('price_tiers._form', ['isEdit' => false])
</form>
@endsection

@extends('layouts.app')
@section('title', 'Edit Tier')
@section('page_title', 'Edit: ' . $tier->name)
@section('content')
<form method="POST" action="{{ route('price_tiers.update', $tier) }}">@csrf @method('PUT')
    @include('price_tiers._form', ['isEdit' => true])
</form>
@endsection

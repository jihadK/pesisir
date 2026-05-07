@extends('layouts.auth')

@section('title', 'Sign In')

@section('content')
<div class="d-flex flex-column flex-lg-row flex-column-fluid">
    {{-- Form side --}}
    <div class="d-flex flex-column flex-lg-row-fluid w-lg-50 p-10 order-2 order-lg-1">
        <div class="d-flex flex-center flex-column flex-lg-row-fluid">
            <div class="w-lg-500px p-10">
                <form method="POST" action="{{ route('login.attempt') }}" class="form w-100" id="kt_sign_in_form">
                    @csrf

                    <div class="text-center mb-11">
                        <h1 class="text-gray-900 fw-bolder mb-3">Sign In</h1>
                        <div class="text-gray-500 fw-semibold fs-6">{{ config('app.name') }}</div>
                    </div>

                    @if ($errors->any())
                        <div class="alert alert-danger d-flex align-items-center mb-8">
                            <i class="ki-outline ki-shield-cross fs-2hx text-danger me-4"></i>
                            <div class="d-flex flex-column">
                                @foreach ($errors->all() as $error)
                                    <span>{{ $error }}</span>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if (session('status'))
                        <div class="alert alert-success mb-8">{{ session('status') }}</div>
                    @endif

                    <div class="fv-row mb-8">
                        <input type="text" name="login" placeholder="Username atau Email"
                               value="{{ old('login') }}" autocomplete="username" autofocus
                               class="form-control bg-transparent" />
                    </div>

                    <div class="fv-row mb-3">
                        <input type="password" name="password" placeholder="Password"
                               autocomplete="current-password"
                               class="form-control bg-transparent" />
                    </div>

                    <div class="d-flex flex-stack flex-wrap gap-3 fs-base fw-semibold mb-8">
                        <label class="form-check form-check-custom form-check-solid">
                            <input class="form-check-input" type="checkbox" name="remember" value="1" />
                            <span class="form-check-label text-gray-700">Ingat saya</span>
                        </label>
                        <a href="#" class="link-primary">Lupa password?</a>
                    </div>

                    <div class="d-grid mb-10">
                        <button type="submit" id="kt_sign_in_submit" class="btn btn-primary">
                            <span class="indicator-label">Sign In</span>
                            <span class="indicator-progress">Mohon tunggu...
                                <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- Aside (banner) --}}
    <div class="d-flex flex-lg-row-fluid w-lg-50 bgi-size-cover bgi-position-center order-1 order-lg-2"
         style="background-image: url({{ asset('assets/media/auth/bg10.jpeg') }})">
        <div class="d-flex flex-column flex-center py-15 px-5 px-md-15 w-100">
            <a href="#" class="mb-12">
                <img alt="Logo" src="{{ asset('assets/media/logos/custom-3.svg') }}" class="h-75px" />
            </a>
            <h1 class="text-white fs-2qx fw-bolder text-center mb-7">{{ config('app.name') }}</h1>
            <div class="text-white fs-base text-center">
                Sistem Manajemen Stock &amp; Penjualan Ikan<br/>
                Akses real-time ke stock, penjualan, dan invoicing — dari mana saja.
            </div>
        </div>
    </div>
</div>
@endsection

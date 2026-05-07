@extends('layouts.app')

@section('title', 'Dashboard')
@section('page_title', 'Dashboard')
@section('breadcrumb')
    <li class="breadcrumb-item text-gray-900">Dashboard</li>
@endsection

@section('content')
@php
    $user    = $currentUser ?? auth()->user();
    $profile = $user->profile;
    $initial = strtoupper(mb_substr($user->full_name, 0, 1));
@endphp

{{-- ===== Welcome card ===== --}}
<div class="card mb-5 mb-xl-10">
    <div class="card-body pt-9 pb-0">
        <div class="d-flex flex-wrap flex-sm-nowrap mb-3">
            <div class="me-7 mb-4">
                <div class="symbol symbol-100px symbol-lg-160px symbol-fixed position-relative">
                    @if ($profile?->avatar_url)
                        <img src="{{ $profile->avatar_url }}" alt="{{ $user->full_name }}" />
                    @else
                        <div class="symbol-label fs-1 bg-light-primary text-primary fw-bold">{{ $initial }}</div>
                    @endif
                    <div class="position-absolute translate-middle bottom-0 start-100 mb-6 bg-success rounded-circle border border-4 border-white h-20px w-20px"></div>
                </div>
            </div>
            <div class="flex-grow-1">
                <div class="d-flex justify-content-between align-items-start flex-wrap mb-2">
                    <div class="d-flex flex-column">
                        <div class="d-flex align-items-center mb-2">
                            <span class="text-gray-900 text-hover-primary fs-2 fw-bold me-1">{{ $user->full_name }}</span>
                            <span class="badge badge-light-success me-auto">{{ ucfirst($user->role->name ?? '—') }}</span>
                        </div>
                        <div class="d-flex flex-wrap fw-semibold fs-6 mb-4 pe-2">
                            <span class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2">
                                <i class="ki-outline ki-profile-circle fs-4 me-1"></i>{{ $user->username }}
                            </span>
                            <span class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2">
                                <i class="ki-outline ki-sms fs-4 me-1"></i>{{ $user->email }}
                            </span>
                            @if ($user->phone)
                                <span class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2">
                                    <i class="ki-outline ki-phone fs-4 me-1"></i>{{ $user->phone }}
                                </span>
                            @endif
                            @if ($profile?->position)
                                <span class="d-flex align-items-center text-gray-500 text-hover-primary me-5 mb-2">
                                    <i class="ki-outline ki-briefcase fs-4 me-1"></i>{{ $profile->position }}
                                </span>
                            @endif
                            @if ($profile?->department)
                                <span class="d-flex align-items-center text-gray-500 text-hover-primary mb-2">
                                    <i class="ki-outline ki-office-bag fs-4 me-1"></i>{{ $profile->department }}
                                </span>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="d-flex flex-wrap flex-stack">
                    <div class="d-flex flex-column flex-grow-1 pe-8">
                        <div class="d-flex flex-wrap">
                            {{-- Last login --}}
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="ki-outline ki-time fs-3 text-success me-2"></i>
                                    <div class="fs-6 fw-bold">
                                        {{ $user->last_login_at?->diffForHumans() ?? '—' }}
                                    </div>
                                </div>
                                <div class="fw-semibold fs-7 text-gray-500">Login Terakhir</div>
                            </div>

                            {{-- Warehouse count --}}
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 me-6 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="ki-outline ki-home-2 fs-3 text-primary me-2"></i>
                                    <div class="fs-6 fw-bold">{{ $user->warehouses->count() }}</div>
                                </div>
                                <div class="fw-semibold fs-7 text-gray-500">Akses Gudang</div>
                            </div>

                            {{-- 2FA --}}
                            <div class="border border-gray-300 border-dashed rounded min-w-125px py-3 px-4 mb-3">
                                <div class="d-flex align-items-center">
                                    <i class="ki-outline ki-shield-tick fs-3 {{ $user->two_factor_enabled ? 'text-success' : 'text-warning' }} me-2"></i>
                                    <div class="fs-6 fw-bold">
                                        {{ $user->two_factor_enabled ? 'Aktif' : 'Tidak Aktif' }}
                                    </div>
                                </div>
                                <div class="fw-semibold fs-7 text-gray-500">2FA</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

{{-- ===== Stat cards ===== --}}
<div class="row g-5 g-xl-10 mb-5 mb-xl-10">
    <div class="col-md-6 col-lg-3">
        <div class="card card-flush bgi-no-repeat bgi-size-contain bgi-position-x-end h-md-100"
             style="background-color: #F1416C; background-image:url({{ asset('assets/media/patterns/vector-1.png') }})">
            <div class="card-header pt-5">
                <div class="card-title d-flex flex-column">
                    <span class="fs-2hx fw-bold text-white me-2 lh-1 ls-n2">{{ number_format($stats['products_active']) }}</span>
                    <span class="text-white opacity-75 pt-1 fw-semibold fs-6">Produk Aktif</span>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card card-flush h-md-100">
            <div class="card-header pt-5">
                <div class="card-title d-flex flex-column">
                    <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ number_format($stats['stock_low']) }}</span>
                    <span class="text-gray-500 pt-1 fw-semibold fs-6">Stock di Bawah Minimum</span>
                </div>
            </div>
            <div class="card-body d-flex align-items-end pt-0">
                <i class="ki-outline ki-package fs-3hx text-gray-300"></i>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card card-flush h-md-100">
            <div class="card-header pt-5">
                <div class="card-title d-flex flex-column">
                    <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">{{ number_format($stats['so_today']) }}</span>
                    <span class="text-gray-500 pt-1 fw-semibold fs-6">Sales Order Hari Ini</span>
                </div>
            </div>
            <div class="card-body d-flex align-items-end pt-0">
                <i class="ki-outline ki-basket fs-3hx text-gray-300"></i>
            </div>
        </div>
    </div>

    <div class="col-md-6 col-lg-3">
        <div class="card card-flush h-md-100">
            <div class="card-header pt-5">
                <div class="card-title d-flex flex-column">
                    <span class="fs-2hx fw-bold text-gray-900 me-2 lh-1 ls-n2">
                        Rp {{ number_format($stats['ar_outstanding'], 0, ',', '.') }}
                    </span>
                    <span class="text-gray-500 pt-1 fw-semibold fs-6">AR Outstanding</span>
                </div>
            </div>
            <div class="card-body d-flex align-items-end pt-0">
                <i class="ki-outline ki-dollar fs-3hx text-gray-300"></i>
            </div>
        </div>
    </div>
</div>

{{-- ===== Bottom row: warehouses + recent logins ===== --}}
<div class="row g-5 g-xl-10">

    {{-- Akses gudang --}}
    <div class="col-xl-5">
        <div class="card card-flush h-xl-100">
            <div class="card-header pt-7">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-800">Akses Gudang Anda</span>
                    <span class="text-gray-500 mt-1 fw-semibold fs-6">{{ $user->warehouses->count() }} gudang ter-assign</span>
                </h3>
            </div>
            <div class="card-body pt-5">
                @forelse ($user->warehouses as $wh)
                    <div class="d-flex align-items-center mb-7">
                        <div class="symbol symbol-50px me-5">
                            <span class="symbol-label bg-light-primary">
                                <i class="ki-outline ki-home-2 fs-2x text-primary"></i>
                            </span>
                        </div>
                        <div class="d-flex flex-column flex-grow-1">
                            <span class="text-gray-800 fw-bold fs-6">{{ $wh->name }}
                                @if ($wh->pivot->is_default)
                                    <span class="badge badge-light-success ms-2">Default</span>
                                @endif
                            </span>
                            <span class="text-gray-500 fw-semibold fs-7">{{ $wh->code }} &middot; {{ $wh->type }}</span>
                        </div>
                        <span class="badge badge-light-{{ $wh->pivot->access_level === 'admin' ? 'danger' : ($wh->pivot->access_level === 'write' ? 'warning' : 'info') }} fw-bold">
                            {{ strtoupper($wh->pivot->access_level) }}
                        </span>
                    </div>
                @empty
                    <div class="text-muted">Belum ada gudang ter-assign untuk akun Anda.</div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Recent login attempts --}}
    <div class="col-xl-7">
        <div class="card card-flush h-xl-100">
            <div class="card-header pt-7">
                <h3 class="card-title align-items-start flex-column">
                    <span class="card-label fw-bold text-gray-800">Aktivitas Login Terakhir</span>
                    <span class="text-gray-500 mt-1 fw-semibold fs-6">8 percobaan login terbaru di sistem</span>
                </h3>
            </div>
            <div class="card-body pt-5">
                <table class="table table-row-bordered table-row-gray-200 align-middle gy-3 gs-0">
                    <thead>
                        <tr class="text-gray-500 fw-bold fs-7 text-uppercase">
                            <th>Email / Username</th>
                            <th>IP</th>
                            <th>Status</th>
                            <th class="text-end">Waktu</th>
                        </tr>
                    </thead>
                    <tbody class="fw-semibold fs-6 text-gray-700">
                        @forelse ($recentLogins as $log)
                            <tr>
                                <td>{{ $log->email ?? '—' }}</td>
                                <td><span class="text-muted fs-7">{{ $log->ip_address }}</span></td>
                                <td>
                                    @if ($log->success)
                                        <span class="badge badge-light-success">Sukses</span>
                                    @else
                                        <span class="badge badge-light-danger" title="{{ $log->failure_reason }}">
                                            {{ $log->failure_reason }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-end text-muted fs-7">
                                    {{ \Carbon\Carbon::parse($log->attempted_at)->diffForHumans() }}
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-muted">Belum ada aktivitas.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

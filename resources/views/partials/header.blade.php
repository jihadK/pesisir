@php
    $user = $currentUser ?? auth()->user();
    $profile = $user->profile;
    $defaultWh = $user->warehouses->firstWhere('pivot.is_default', true);
    $initial = strtoupper(mb_substr($user->full_name, 0, 1));
@endphp

<div id="kt_header" class="header align-items-stretch">
    <div class="container-fluid d-flex align-items-stretch justify-content-between">

        {{-- Aside mobile toggle --}}
        <div class="d-flex align-items-center d-lg-none ms-n4 me-1" title="Show aside menu">
            <div class="btn btn-icon btn-active-color-white" id="kt_aside_mobile_toggle">
                <i class="ki-outline ki-burger-menu fs-1"></i>
            </div>
        </div>

        {{-- Mobile logo --}}
        <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
            <a href="{{ route('dashboard') }}" class="d-lg-none">
                <strong class="fs-3 text-white">{{ config('app.name') }}</strong>
            </a>
        </div>

        <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1">
            <div class="d-flex align-items-stretch" id="kt_header_nav"></div>

            <div class="topbar d-flex align-items-stretch flex-shrink-0">

                {{-- Theme mode toggle --}}
                <div class="d-flex align-items-center">
                    <a href="#" class="topbar-item px-3 px-lg-4"
                       data-kt-menu-trigger="{default:'click', lg: 'hover'}"
                       data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                        <i class="ki-outline ki-night-day theme-light-show fs-1"></i>
                        <i class="ki-outline ki-moon theme-dark-show fs-1"></i>
                    </a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px"
                         data-kt-menu="true" data-kt-element="theme-mode-menu">
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                                <span class="menu-icon"><i class="ki-outline ki-night-day fs-2"></i></span>
                                <span class="menu-title">Light</span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                <span class="menu-icon"><i class="ki-outline ki-moon fs-2"></i></span>
                                <span class="menu-title">Dark</span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                <span class="menu-icon"><i class="ki-outline ki-screen fs-2"></i></span>
                                <span class="menu-title">System</span>
                            </a>
                        </div>
                    </div>
                </div>

                {{-- User menu --}}
                <div class="d-flex align-items-stretch" id="kt_header_user_menu_toggle">
                    <div class="topbar-item cursor-pointer symbol px-3 px-lg-5 me-n3 me-lg-n5 symbol-30px symbol-md-40px"
                         data-kt-menu-trigger="click" data-kt-menu-attach="parent"
                         data-kt-menu-placement="bottom-end" data-kt-menu-flip="bottom">
                        @if ($profile?->avatar_url)
                            <img src="{{ $profile->avatar_url }}" alt="{{ $user->full_name }}" />
                        @else
                            <div class="symbol-label fs-3 bg-light-primary text-primary fw-bold">{{ $initial }}</div>
                        @endif
                    </div>

                    {{-- Dropdown user --}}
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-300px"
                         data-kt-menu="true">
                        <div class="menu-item px-3">
                            <div class="menu-content d-flex align-items-center px-3">
                                <div class="symbol symbol-50px me-5">
                                    @if ($profile?->avatar_url)
                                        <img alt="{{ $user->full_name }}" src="{{ $profile->avatar_url }}" />
                                    @else
                                        <div class="symbol-label fs-2 bg-light-primary text-primary fw-bold">{{ $initial }}</div>
                                    @endif
                                </div>
                                <div class="d-flex flex-column">
                                    <div class="fw-bold d-flex align-items-center fs-5">
                                        {{ $user->full_name }}
                                        <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">
                                            {{ ucfirst($user->role->name ?? '—') }}
                                        </span>
                                    </div>
                                    <span class="fw-semibold text-muted fs-7">{{ $user->email }}</span>
                                    @if ($profile?->position || $profile?->department)
                                        <span class="fw-semibold text-muted fs-8 mt-1">
                                            {{ $profile->position }}@if($profile->position && $profile->department) &middot; @endif{{ $profile->department }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="separator my-2"></div>

                        @if ($defaultWh)
                            <div class="menu-item px-3">
                                <div class="menu-content px-3 py-2">
                                    <div class="text-muted fs-8 text-uppercase ls-1">Gudang Default</div>
                                    <div class="fw-bold fs-7 text-gray-800">{{ $defaultWh->name }}</div>
                                </div>
                            </div>
                            <div class="separator my-2"></div>
                        @endif

                        <div class="menu-item px-5">
                            <a href="#" class="menu-link px-5">
                                <span class="menu-text">My Profile</span>
                            </a>
                        </div>

                        <div class="menu-item px-5">
                            <a href="#" class="menu-link px-5">
                                <span class="menu-text">Ganti Password</span>
                            </a>
                        </div>

                        <div class="separator my-2"></div>

                        <div class="menu-item px-5">
                            <form method="POST" action="{{ route('logout') }}" id="kt_logout_form">
                                @csrf
                                <button type="submit" class="menu-link px-5 w-100 text-start bg-transparent border-0">
                                    <span class="menu-icon"><i class="ki-outline ki-exit-right fs-2"></i></span>
                                    <span class="menu-text">Sign Out</span>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

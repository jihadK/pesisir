<!DOCTYPE html>
<html lang="id">
<head>
    <base href="{{ url('/') }}/" />
    <title>@yield('title', 'Dashboard') &mdash; {{ config('app.name') }}</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="icon" type="image/png" href="{{ asset('assets/media/logos/logo-pesisir-web.png') }}" />
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/logo-pesisir-web.png') }}" />
    <link rel="apple-touch-icon" href="{{ asset('assets/media/logos/logo-pesisir-web.png') }}" />
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700" />
    <link href="{{ asset('assets/plugins/global/plugins.bundle.css') }}" rel="stylesheet" type="text/css" />
    <link href="{{ asset('assets/css/style.bundle.css') }}" rel="stylesheet" type="text/css" />
    @stack('styles')
</head>
<body id="kt_body"
      class="header-fixed header-tablet-and-mobile-fixed toolbar-enabled toolbar-fixed toolbar-tablet-and-mobile-fixed aside-enabled aside-fixed"
      style="--kt-toolbar-height:55px;--kt-toolbar-height-tablet-and-mobile:55px">

<script>
    var defaultThemeMode = "light";
    var themeMode = (document.documentElement.getAttribute("data-bs-theme-mode")) ||
        (localStorage.getItem("data-bs-theme") || defaultThemeMode);
    if (themeMode === "system") {
        themeMode = window.matchMedia("(prefers-color-scheme: dark)").matches ? "dark" : "light";
    }
    document.documentElement.setAttribute("data-bs-theme", themeMode);
</script>

<div class="d-flex flex-column flex-root">
    <div class="page d-flex flex-row flex-column-fluid">

        {{-- ========== ASIDE / SIDEBAR ========== --}}
        @include('partials.sidebar')

        {{-- ========== WRAPPER ========== --}}
        <div class="wrapper d-flex flex-column flex-row-fluid" id="kt_wrapper">

            {{-- ========== HEADER ========== --}}
            @include('partials.header')

            {{-- ========== CONTENT ========== --}}
            <div class="content d-flex flex-column flex-column-fluid" id="kt_content">

                {{-- Toolbar (page title + breadcrumb) --}}
                <div class="toolbar" id="kt_toolbar">
                    <div id="kt_toolbar_container" class="container-fluid d-flex flex-stack">
                        <div class="page-title d-flex align-items-center me-3 flex-wrap lh-1">
                            <h1 class="d-flex align-items-center text-gray-900 fw-bold my-1 fs-3">
                                @yield('page_title', 'Dashboard')
                            </h1>
                            <span class="h-20px border-gray-200 border-start mx-4"></span>
                            <ul class="breadcrumb breadcrumb-separatorless fw-semibold fs-7 my-1">
                                <li class="breadcrumb-item text-muted">
                                    <a href="{{ route('dashboard') }}" class="text-muted text-hover-primary">Home</a>
                                </li>
                                @hasSection('breadcrumb')
                                    <li class="breadcrumb-item"><span class="bullet bg-gray-300 w-5px h-2px"></span></li>
                                    @yield('breadcrumb')
                                @endif
                            </ul>
                        </div>
                    </div>
                </div>

                {{-- Post (main content) --}}
                <div class="post d-flex flex-column-fluid" id="kt_post">
                    <div id="kt_content_container" class="container-xxl">
                        @yield('content')
                    </div>
                </div>
            </div>

            {{-- ========== FOOTER ========== --}}
            <div class="footer py-4 d-flex flex-lg-column" id="kt_footer">
                <div class="container-fluid d-flex flex-column flex-md-row flex-stack">
                    <div class="text-gray-900 order-2 order-md-1">
                        <span class="text-muted fw-semibold me-1">{{ date('Y') }} &copy;</span>
                        <strong class="text-gray-800">{{ config('app.name') }}</strong>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>var hostUrl = "{{ asset('assets/') }}/";</script>
<script src="{{ asset('assets/plugins/global/plugins.bundle.js') }}"></script>
<script src="{{ asset('assets/js/scripts.bundle.js') }}"></script>

<x-sweet-helpers />
<x-sweet-flash />

@auth
    @if(auth()->user()?->hasPermission('sales_order.create') && ! request()->routeIs('sales_orders.create'))
        <a href="{{ route('sales_orders.create') }}"
           class="fab-new-order"
           title="Buat Order Baru"
           aria-label="Buat Order Baru">
            <i class="ki-outline ki-plus fab-icon-main"></i>
            <span class="fab-label">Order Baru</span>
        </a>
        <style>
            .fab-new-order {
                position: fixed;
                bottom: 28px;
                right: 28px;
                z-index: 1040;
                display: inline-flex;
                align-items: center;
                gap: 10px;
                padding: 14px 22px 14px 18px;
                background: linear-gradient(135deg, #1976d2 0%, #1565c0 100%);
                color: #fff !important;
                font-weight: 600;
                font-size: 14px;
                border-radius: 999px;
                text-decoration: none;
                box-shadow: 0 6px 20px rgba(25, 118, 210, 0.45), 0 2px 6px rgba(0,0,0,0.12);
                transition: transform .2s ease, box-shadow .2s ease, padding .25s ease;
            }
            .fab-new-order .fab-icon-main {
                font-size: 22px !important;
                line-height: 1;
                color: #fff;
                transition: transform .25s ease;
            }
            .fab-new-order .fab-label {
                white-space: nowrap;
                transition: max-width .25s ease, opacity .2s ease, margin .25s ease;
            }
            .fab-new-order:hover {
                transform: translateY(-3px);
                box-shadow: 0 10px 28px rgba(25, 118, 210, 0.55), 0 4px 10px rgba(0,0,0,0.15);
                color: #fff !important;
            }
            .fab-new-order:hover .fab-icon-main { transform: rotate(90deg); }
            .fab-new-order:active { transform: translateY(-1px); }
            @media (max-width: 576px) {
                .fab-new-order {
                    width: 56px;
                    height: 56px;
                    padding: 0;
                    gap: 0;
                    bottom: 20px;
                    right: 20px;
                    justify-content: center;
                }
                .fab-new-order .fab-label {
                    max-width: 0;
                    opacity: 0;
                    overflow: hidden;
                    margin: 0;
                    display: none;
                }
            }
            @media print { .fab-new-order { display: none !important; } }
        </style>
    @endif
@endauth

@stack('scripts')
</body>
</html>

<!DOCTYPE html>
<html lang="id">
<head>
    <base href="{{ url('/') }}/" />
    <title>@yield('title', 'Dashboard') &mdash; {{ config('app.name') }}</title>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <link rel="shortcut icon" href="{{ asset('assets/media/logos/favicon.ico') }}" />
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

@stack('scripts')
</body>
</html>

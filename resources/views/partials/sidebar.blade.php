<div id="kt_aside" class="aside aside-dark aside-hoverable"
     data-kt-drawer="true" data-kt-drawer-name="aside"
     data-kt-drawer-activate="{default: true, lg: false}"
     data-kt-drawer-overlay="true"
     data-kt-drawer-width="{default:'200px', '300px': '250px'}"
     data-kt-drawer-direction="start"
     data-kt-drawer-toggle="#kt_aside_mobile_toggle">

    {{-- Brand --}}
    <div class="aside-logo flex-column-auto" id="kt_aside_logo">
        <a href="{{ route('dashboard') }}" class="d-flex align-items-center">
            <span class="fs-2 fw-bolder text-white">{{ config('app.name') }}</span>
        </a>
        <div id="kt_aside_toggle"
             class="btn btn-icon w-auto px-0 btn-active-color-primary aside-toggle me-n2"
             data-kt-toggle="true" data-kt-toggle-state="active"
             data-kt-toggle-target="body" data-kt-toggle-name="aside-minimize">
            <i class="ki-outline ki-double-left fs-1 rotate-180"></i>
        </div>
    </div>

    {{-- Menu --}}
    <div class="aside-menu flex-column-fluid">
        <div class="hover-scroll-overlay-y my-5 my-lg-5" id="kt_aside_menu_wrapper"
             data-kt-scroll="true"
             data-kt-scroll-activate="{default: false, lg: true}"
             data-kt-scroll-height="auto"
             data-kt-scroll-dependencies="#kt_aside_logo, #kt_aside_footer"
             data-kt-scroll-wrappers="#kt_aside_menu"
             data-kt-scroll-offset="0">

            <div class="menu menu-column menu-title-gray-800 menu-state-title-primary menu-state-icon-primary menu-state-bullet-primary menu-arrow-gray-500"
                 id="#kt_aside_menu" data-kt-menu="true">

                {{-- ===== Dashboard (single item, tanpa accordion) ===== --}}
                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
                        <span class="menu-icon"><i class="ki-outline ki-element-11 fs-2"></i></span>
                        <span class="menu-title">Dashboard</span>
                    </a>
                </div>

                {{-- ===== MASTER DATA ===== --}}
                <div class="menu-item">
                    <div class="menu-content pt-8 pb-2">
                        <span class="menu-section text-muted text-uppercase fs-8 ls-1">Master Data</span>
                    </div>
                </div>

                <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('uoms.*','grades.*','price_tiers.*','categories.*','products.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon"><i class="ki-outline ki-package fs-2"></i></span>
                        <span class="menu-title">Produk</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion menu-active-bg">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('products.*') ? 'active' : '' }}" href="{{ route('products.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Daftar Produk</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('categories.*') ? 'active' : '' }}" href="{{ route('categories.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Kategori</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('grades.*') ? 'active' : '' }}" href="{{ route('grades.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Grade</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('uoms.*') ? 'active' : '' }}" href="{{ route('uoms.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Satuan (UoM)</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('price_tiers.*') ? 'active' : '' }}" href="{{ route('price_tiers.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Tier Harga</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('suppliers.*','customers.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon"><i class="ki-outline ki-people fs-2"></i></span>
                        <span class="menu-title">Mitra</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion menu-active-bg">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Supplier</span>
                            </a>
                        </div>
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('customers.*') ? 'active' : '' }}" href="{{ route('customers.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Customer</span>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="menu-item">
                    <a class="menu-link {{ request()->routeIs('warehouses.*') ? 'active' : '' }}" href="{{ route('warehouses.index') }}">
                        <span class="menu-icon"><i class="ki-outline ki-home-2 fs-2"></i></span>
                        <span class="menu-title">Warehouse</span>
                    </a>
                </div>

                @if(auth()->user()?->hasPermission('payment_method.view'))
                <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('payment_methods.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon"><i class="ki-outline ki-setting-3 fs-2"></i></span>
                        <span class="menu-title">Konfigurasi</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion menu-active-bg">
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('payment_methods.*') ? 'active' : '' }}" href="{{ route('payment_methods.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Metode Pembayaran</span>
                            </a>
                        </div>
                    </div>
                </div>
                @endif

                {{-- ===== INVENTORY ===== --}}
                <div class="menu-item">
                    <div class="menu-content pt-8 pb-2">
                        <span class="menu-section text-muted text-uppercase fs-8 ls-1">Inventory</span>
                    </div>
                </div>

                <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('stock_openings.*','stock_adjustments.*','stock_cards.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon"><i class="ki-outline ki-parcel fs-2"></i></span>
                        <span class="menu-title">Stock</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion menu-active-bg">
                        @if(auth()->user()?->hasPermission('stock_opening.view'))
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('stock_openings.*') ? 'active' : '' }}" href="{{ route('stock_openings.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Stock Opening</span>
                            </a>
                        </div>
                        @endif
                        @if(auth()->user()?->hasPermission('stock_adjustment.view'))
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('stock_adjustments.*') ? 'active' : '' }}" href="{{ route('stock_adjustments.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Stock Adjustment</span>
                            </a>
                        </div>
                        @endif
                        @if(auth()->user()?->hasPermission('stock_card.view'))
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('stock_cards.*') ? 'active' : '' }}" href="{{ route('stock_cards.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Kartu Stok</span>
                            </a>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- ===== PENJUALAN ===== --}}
                <div class="menu-item">
                    <div class="menu-content pt-8 pb-2">
                        <span class="menu-section text-muted text-uppercase fs-8 ls-1">Penjualan</span>
                    </div>
                </div>

                <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('sales_orders.*','delivery_orders.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon"><i class="ki-outline ki-basket fs-2"></i></span>
                        <span class="menu-title">Sales</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion menu-active-bg">
                        @if(auth()->user()?->hasPermission('sales_order.view'))
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('sales_orders.*') ? 'active' : '' }}" href="{{ route('sales_orders.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Sales Order</span>
                            </a>
                        </div>
                        @endif
                        @if(auth()->user()?->hasPermission('delivery_order.view'))
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('delivery_orders.*') ? 'active' : '' }}" href="{{ route('delivery_orders.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Delivery Order</span>
                            </a>
                        </div>
                        @endif
                    </div>
                </div>

                <div data-kt-menu-trigger="click" class="menu-item menu-accordion {{ request()->routeIs('invoices.*','payments.*') ? 'here show' : '' }}">
                    <span class="menu-link">
                        <span class="menu-icon"><i class="ki-outline ki-bill fs-2"></i></span>
                        <span class="menu-title">Invoicing</span>
                        <span class="menu-arrow"></span>
                    </span>
                    <div class="menu-sub menu-sub-accordion menu-active-bg">
                        @if(auth()->user()?->hasPermission('invoice.view'))
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('invoices.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Invoice</span>
                            </a>
                        </div>
                        @endif
                        @if(auth()->user()?->hasPermission('payment.view'))
                        <div class="menu-item">
                            <a class="menu-link {{ request()->routeIs('payments.*') ? 'active' : '' }}" href="{{ route('payments.index') }}">
                                <span class="menu-bullet"><span class="bullet bullet-dot"></span></span>
                                <span class="menu-title">Payment</span>
                            </a>
                        </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>

    {{-- Footer aside --}}
    <div class="aside-footer flex-column-auto pt-5 pb-7 px-5" id="kt_aside_footer">
        <a href="{{ route('dashboard') }}" class="btn btn-custom btn-primary w-100">
            <span class="btn-label">Quick Action</span>
            <i class="ki-outline ki-document btn-icon fs-2 m-0"></i>
        </a>
    </div>
</div>

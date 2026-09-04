<style>
    .navbar-toggler {
        border: none;
        box-shadow: none !important;
    }

    .navbar-toggler:focus {
        box-shadow: none !important;
    }

    #logo-brand {
        width: 24px;
        height: 24px;
    }

    .navbar .dropdown-menu {
        border: none;
        border-radius: 10px;
        padding: 8px;
        margin-top: 10px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.10);
    }

    .navbar .dropdown-item {
        padding: 9px 14px;
        border-radius: 7px;
        font-size: 14px;
        transition: all .2s ease;
    }

    .navbar .dropdown-item:hover {
        background-color: #f3f4f6;
        color: #000000;
    }

    .navbar .dropdown-toggle::after {
        margin-left: 6px;
        vertical-align: middle;
    }

    @media (max-width: 991px) {

        .navbar-collapse {
            margin-top: 10px;
        }

        .navbar-nav {
            margin-bottom: 10px;
        }

        .d-flex.align-items-center.gap-3 {
            flex-direction: column;
            align-items: flex-start !important;
            gap: 10px !important;
        }
    }
</style>
<nav id="mainNavbar" class="navbar navbar-expand-lg bg-white shadow-sm">
    <div class="container-fluid">

        <!-- Logo -->
        <a class="navbar-brand d-flex align-items-center" href="#">
            <img id="logo-brand" src="{!! asset('public/css/images/morich.png') !!}" alt="Logo">
            <span class="nav-title ms-2">Finishing & Packing</span>
        </a>

        <!-- Hamburger -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarPackingList"
            aria-controls="navbarPackingList" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Menu -->
        <div class="collapse navbar-collapse" id="navbarPackingList">

            <!-- Left Menu -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">

            </ul>

            <!-- Right Menu -->
            <div class="d-flex align-items-center gap-4">
                @php
                    $guserpk = Session::get('guserpk');
                @endphp
                @if (in_array($guserpk, [23, 34]))
                    <!-- TRANSFER TO FINISHING -->
                    <a href="{{ route('tf_finishing.index') }}"
                        class="nav-link p-0 @if (request()->is('tf-finishing*')) fw-bold @endif">
                        Transfer to Finishing
                    </a>
                    <!-- POLIBAG -->
                    <a href="{{ route('transfer.index') }}"
                        class="nav-link p-0 @if (request()->is('polibag*')) fw-bold @endif">
                        Polibag
                    </a>
                    {{-- STOK SISA --}}
                    {{-- <a href="{{ route('stok-sisa.index') }}"
                        class="nav-link p-0 @if (request()->is('stok-sisa*')) fw-bold @endif">
                        Stok Sisa(Grade)
                    </a> --}}

                    <div class="dropdown">
                        <a href="#"
                            class="nav-link dropdown-toggle p-0
                            @if (request()->is('stok-sisa*') || request()->is('kirim-sisa*')) fw-bold @endif"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Stok Sisa
                        </a>
                    
                        <ul class="dropdown-menu dropdown-menu-end">
                            {{-- SUBMENU 1 --}}
                            <li>
                                <a class="dropdown-item @if (request()->is('stok-sisa*')) active fw-semibold @endif"
                                    href="{{ route('stok-sisa.index') }}">
                                    Stok Sisa (Grade)
                                </a>
                            </li>
                            {{-- SUBMENU 2 --}}
                            <li>
                                <a class="dropdown-item @if (request()->is('kirim-sisa*')) active fw-semibold @endif"
                                    href="{{ route('lo.index') }}">
                                    Kirim Sisa ke Gudang
                                </a>
                            </li>
                        </ul>
                    </div>
                @endif
                
                @if (in_array($guserpk, [34, 37]))
                    <!-- PACKING -->
                    <a href="{{ route('packing.index') }}"
                        class="nav-link p-0 @if (request()->is('packing*')) fw-bold @endif">
                        Packing
                    </a>
                @endif
                @if (in_array($guserpk, [36, 34]))
                    <!-- Sisa Produksi -->
                    {{-- <a href="{{ route('sisa-produksi.index') }}"
                        class="nav-link p-0 @if (request()->is('sisa-produksi*')) fw-bold @endif">
                        Sisa Produksi
                    </a> --}}

                    <div class="dropdown">
                        <a href="#"
                            class="nav-link dropdown-toggle p-0
                            @if (request()->is('sisa-produksi*') || request()->is('terima-sisa*') || request()->is('keluarkan-sisa*')) fw-bold @endif"
                            role="button"
                            data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Sisa Produksi
                        </a>
                    
                        <ul class="dropdown-menu dropdown-menu-end">
                            {{-- SUBMENU 1 --}}
                            <li>
                                <a class="dropdown-item @if (request()->is('sisa-produksi*')) active fw-semibold @endif"
                                    href="{{ route('sisa-produksi.index') }}">
                                    All Data Sisa
                                </a>
                            </li>
                            {{-- SUBMENU 2 --}}
                            <li>
                                <a class="dropdown-item @if (request()->is('terima-sisa*')) active fw-semibold @endif"
                                    href="{{ route('lo.gudang.index') }}">
                                    Terima Sisa
                                </a>
                            </li>
                            {{-- SUBMENU 3 --}}
                            <li>
                                <a class="dropdown-item @if (request()->is('keluarkan-sisa*')) active fw-semibold @endif"
                                    href="{{ route('lo.keluargudang.index') }}">
                                    Keluarkan Sisa
                                </a>
                            </li>
                        </ul>
                    </div>
                    <a href="{{ route('sisa-sample.index') }}"
                        class="nav-link p-0 @if (request()->is('sisa-sample*')) fw-bold @endif">
                        Sisa Sample
                    </a>
                @endif
                
                @if (in_array($guserpk, [34]))
                    <!-- finGoods -->
                    <a href="{{ route('stuff.index') }}"
                    class="nav-link p-0 @if (request()->is('finGoods*')) fw-bold @endif">
                        Finished Goods
                    </a>
                    <a href="{{ route('finGoods.index') }}"
                    class="nav-link p-0 @if (request()->is('stuffing*')) fw-bold @endif">
                        Stuffing
                    </a>
                    {{-- <a href="{{ route('stok-sisa.index') }}"
                        class="nav-link p-0 @if (request()->is('stok-sisa*') || (request()->is('polibag/input*') && session('guserpk') == 35)) fw-bold @endif">
                        Stok Sisa(Grade)
                    </a> --}}
                @endif
                @if (in_array($guserpk, [34, 35, 38]))
                    <!-- FG/STUFFING -->
                    <a href="{{ route('finish-good-stuffing.index') }}"
                        class="nav-link p-0 @if (request()->is('finish-good-stuffing*')) fw-bold @endif">
                        FG/Stuffing
                    </a>
                @endif
                @if (in_array($guserpk, [17, 34]))
                    <!-- INSPECTION -->
                    <a href="{{ route('inspection.index') }}" class="nav-link p-0 @if (request()->is('inspection*')) fw-bold @endif">
                        Inspection
                    </a>
                @endif

                @if (!empty(Session::get('login')))
                    <div class="dropdown">
                        <button class="btn dropdown-toggle-custom p-0" type="button" data-bs-toggle="dropdown">

                            {{ Str::limit(ucwords(strtolower(Session::get('login'))), 15) }}

                            <img id="polygon" src="{!! asset('public/css/images/Polygon.png') !!}">
                        </button>

                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom-global">

                            <li>
                                <a class="dropdown-item" href="javascript:void(0)" onclick="changePassword()">
                                    Password
                                </a>
                            </li>

                            <li>
                                <hr class="dropdown-divider">
                            </li>

                            <li>
                                <a class="dropdown-item" href="{{ route('logout') }}">
                                    Logout
                                </a>
                            </li>

                        </ul>
                    </div>
                @endif

            </div>

        </div>
    </div>
</nav>

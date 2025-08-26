<style>
    .btn.dropdown-toggle:focus,
    .btn.dropdown-toggle:active,
    .btn.dropdown-toggle.show {
        outline: none;
        box-shadow: none;
        border: none;
    }
</style>

<nav class="navbar pb-1 pt-1">
    <div class="container-fluid" style="background-color: white;">
        <div class="navbar-brand d-inline-flex d-inline-flex-custom" href="#" style="padding-left:10px;">
            <div class="p-1 align-self-center">
                <div div class="input-group">
                    <div class="align-self-center" style="margin-top: -1%;">
                        <img id="logo-brand" src="{!! asset('public/css/images/morich.png') !!}">
                    </div>
                    <div class="align-self-center" align=center>
                        <span class="nav-title">Finance</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-inline-flex d-inline-flex-custom align-self-center gap-5" style="padding-right: 20px;">
            <div class="p-0 d-inline-flex gap-3 flex-wrap">

                @if (in_array(Session::get('deppk'), ['1', '3', '6', '8', '24']))
                <div class="dropdown">
                    
                    <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border: none; outline: none; box-shadow: none; 
                    @if (request()->segment(1) == 'barang') font-weight:700; @endif
                    @if (request()->segment(1) == 'stok-barang') font-weight:700; @endif
                    @if (request()->segment(1) == 'supplier') font-weight:700; @endif
                    @if (request()->segment(1) == 'kategori-barang') font-weight:700; @endif">
                        Tabel
                    </button>

                    <ul class="dropdown-menu dropdown-menu">
                        @if (in_array(Session::get('deppk'), ['1']))
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'barang') font-weight:700; @endif" href="{{ route('page.barang') }}">List Barang Upload</a></li>
                        <hr class="dropdown-divider p-0 m-0">
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'stok-barang') font-weight:700; @endif" href="{{ route('page.stok-barang') }}">Stok Barang</a></li>
                        <hr class="dropdown-divider p-0 m-0">   
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'supplier') font-weight:700; @endif" href="{{ route('supplier.index') }}">Supplier</a></li>
                        <hr class="dropdown-divider p-0 m-0">   
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'kategori-barang') font-weight:700; @endif" href="{{ route('kategori-barang.index') }}">Kategori Barang</a></li>
                        
                        @endif
                    
                        @if (in_array(Session::get('deppk'), ['3', '6', '24', '8']))
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'supplier') font-weight:700; @endif" href="{{ route('supplier.index') }}">Supplier</a></li>
                        <hr class="dropdown-divider p-0 m-0">   
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'kategori-barang') font-weight:700; @endif" href="{{ route('kategori-barang.index') }}">Kategori Barang</a></li>
                        @endif
                    </ul>
                </div>
                @endif

                <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border: none; outline: none; box-shadow: none;
                    @if (request()->segment(1) == 'pembelian-cash-tempo') font-weight:700; @endif
                    @if (request()->segment(1) == 'tanda-terima') font-weight:700; @endif
                    @if (request()->segment(1) == 'pengisian-pengembalian-kas') font-weight:700; @endif
                    @if (request()->segment(1) == 'purchase-order') font-weight:700; @endif
                    @if (request()->segment(1) == 'purchase-request') font-weight:700; @endif">
                        Aktifitas
                    </button>
           
                    <ul class="dropdown-menu dropdown-menu">
                        <!-- User Gudang, dll -->
                        @if (!in_array(Session::get('deppk'), ['1', '3', '6', '8', '24']))
                            <li><a class="dropdown-item" style="@if (request()->segment(1) == 'purchase-request') font-weight:700; @endif" href="{{ route('index.poreq') }}">Purchase Request</a></li>
                        @endif

                        <!-- User CR -->
                        @if (in_array(Session::get('deppk'), ['24']))
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'pembelian-cash-tempo') font-weight:700; @endif" href="{{ route('page.pembelian-ct') }}">Pembelian Cash / Tempo</a></li>
                        <hr class="dropdown-divider p-0 m-0">   
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'tanda-terima') font-weight:700; @endif" href="{{ route('page.tanda-terima') }}">Tanda Terima</a></li>
                        <hr class="dropdown-divider p-0 m-0">
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'pengisian-pengembalian-kas') font-weight:700; @endif" href="{{ route('page.kas-ppk') }}">Pengisian / Pengembalian Kas</a></li>
                        <hr class="dropdown-divider p-0 m-0">
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'purchase-request') font-weight:700; @endif" href="{{ route('index.poreq') }}">Purchase Request</a></li>
                        @endif

                        <!-- User Grace Purchasing -->
                        @if (in_array(Session::get('deppk'), ['3']))
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'pembelian-cash-tempo') font-weight:700; @endif" href="{{ route('page.pembelian-ct') }}">Pembelian Cash / Tempo</a></li>
                        <hr class="dropdown-divider p-0 m-0">   
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'tanda-terima') font-weight:700; @endif" href="{{ route('page.tanda-terima') }}">Tanda Terima</a></li>
                        <hr class="dropdown-divider p-0 m-0">
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'purchase-order') font-weight:700; @endif" href="{{ route('index.porder') }}">Purchase Order</a></li>
                        <hr class="dropdown-divider p-0 m-0">
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'purchase-request') font-weight:700; @endif" href="{{ route('index.poreq') }}">Purchase Request</a></li>
                        @endif
                        <!-- <hr class="dropdown-divider p-0 m-0"> -->

                        <!-- User IT -->
                        @if (in_array(Session::get('deppk'), ['1']))
                            <li><a class="dropdown-item" style="@if (request()->segment(1) == 'purchase-order') font-weight:700; @endif" href="{{ route('index.porder') }}">Purchase Order</a></li>
                            <hr class="dropdown-divider p-0 m-0">   
                            <li><a class="dropdown-item" style="@if (request()->segment(1) == 'purchase-request') font-weight:700; @endif" href="{{ route('index.poreq') }}">Purchase Request</a></li>
                            <hr class="dropdown-divider p-0 m-0">
                            <li><a class="dropdown-item" style="@if (request()->segment(1) == 'tanda-terima') font-weight:700; @endif" href="{{ route('page.tanda-terima') }}">Tanda Terima</a></li>
                        @endif


                        <!-- User Finance -->
                        @if (in_array(Session::get('deppk'), ['6']))
                            <li><a class="dropdown-item" style="@if (request()->segment(1) == 'pengisian-pengembalian-kas') font-weight:700; @endif" href="{{ route('page.kas-ppk') }}">Pengisian / Pengembalian Kas</a></li>
                            <hr class="dropdown-divider p-0 m-0">
                            <li><a class="dropdown-item" style="@if (request()->segment(1) == 'purchase-request') font-weight:700; @endif" href="{{ route('index.poreq') }}">Purchase Request</a></li>
                        @endif


                        <!-- User HRD -->
                        @if (in_array(Session::get('deppk'), ['8']))
                            <li><a class="dropdown-item" style="@if (request()->segment(1) == 'pembelian-cash-tempo') font-weight:700; @endif" href="{{ route('page.pembelian-ct') }}">Pembelian Cash / Tempo</a></li>
                            <hr class="dropdown-divider p-0 m-0">   
                            <li><a class="dropdown-item" style="@if (request()->segment(1) == 'pengisian-pengembalian-kas') font-weight:700; @endif" href="{{ route('page.kas-ppk') }}">Pengisian / Pengembalian Kas</a></li>
                            <hr class="dropdown-divider p-0 m-0">
                            <li><a class="dropdown-item" style="@if (request()->segment(1) == 'purchase-request') font-weight:700; @endif" href="{{ route('index.poreq') }}">Purchase Request</a></li>
                            <hr class="dropdown-divider p-0 m-0">
                            <li><a class="dropdown-item" style="@if (request()->segment(1) == 'tanda-terima') font-weight:700; @endif" href="{{ route('page.tanda-terima') }}">Tanda Terima</a></li>
                        @endif
                    </ul>
                </div>

                @if (in_array(Session::get('deppk'), ['1', '3', '6', '8', '24']))
                <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border: none; outline: none; box-shadow: none; 
                    @if (request()->segment(2) == 'pembayaran-cash-giro') font-weight:700; @endif
                    @if (request()->segment(3) == 'cash-tempo') font-weight:700; @endif
                    @if (request()->segment(2) == 'bukti-kas-keluar') font-weight:700; @endif
                    @if (request()->segment(2) == 'purchase-request') font-weight:700; @endif
                    @if (request()->segment(2) == 'purchase-order') font-weight:700; @endif
                    @if (request()->segment(3) == 'purchase-order') font-weight:700; @endif
                    @if (request()->segment(2) == 'stok-barang') font-weight:700; @endif">
                        Laporan
                    </button>

                    <ul class="dropdown-menu dropdown-menu">
                        <!-- User CR dan HRD-->
                        @if (in_array(Session::get('deppk'), ['24', '8'])) 
                            <li><a class="dropdown-item" style="@if (request()->segment(3) == 'cash-tempo') font-weight:700; @endif" href="{{ route('page.ct') }}">Pembelian Cash/Tempo</a></li>
                        @endif
                        
                        <!-- User Finance -->
                        @if (in_array(Session::get('deppk'), ['6']))
                            <li><a class="dropdown-item" style="@if (request()->segment(2) == 'purchase-request') font-weight:700; @endif" href="{{ route('page.lap-pr') }}">Purchase Request</a></li>
                            <hr class="dropdown-divider p-0 m-0">
                            <li><a class="dropdown-item" style="@if (request()->segment(2) == 'purchase-order') font-weight:700; @endif" href="{{ route('page.lap-po') }}">Purchase Order</a></li>
                            <hr class="dropdown-divider p-0 m-0">
                            <li><a class="dropdown-item" style="@if (request()->segment(2) == 'pembayaran-cash-giro') font-weight:700; @endif" href="{{ route('page.cg') }}">Pembayaran Cash/Giro</a></li>
                            <hr class="dropdown-divider p-0 m-0">
                            <li><a class="dropdown-item" style="@if (request()->segment(2) == 'bukti-kas-keluar') font-weight:700; @endif" href="{{ route('page.bkk') }}">Bukti Kas Keluar</a></li>
                        @endif

                        <!-- user Grace Pur -->
                        @if (in_array(Session::get('deppk'), ['3'])) 
                            <li><a class="dropdown-item" style="@if (request()->segment(3) == 'cash-tempo') font-weight:700; @endif" href="{{ route('page.ct') }}">Pembelian Cash/Tempo</a></li>
                            <hr class="dropdown-divider p-0 m-0">
                            <li><a class="dropdown-item" style="@if (request()->segment(2) == 'purchase-order') font-weight:700; @endif" href="{{ route('page.lap-po')}}">Purchase Order</a></li>
                            <hr class="dropdown-divider p-0 m-0">
                            <li><a class="dropdown-item" style="@if (request()->segment(2) == 'stok-barang') font-weight:700; @endif" href="{{ route('page.lap-stok')}}">Stok Barang</a></li>
                        @endif

                        <!-- user IT -->
                        @if (in_array(Session::get('deppk'), ['1'])) 
                            <li><a class="dropdown-item" style="@if (request()->segment(2) == 'purchase-order') font-weight:700; @endif" href="{{ route('page.lap-po')}}">Purchase Order</a></li>
                        @endif
                    </ul>
                </div>
                @endif

                <!-- User Gudang, dll selain staf -->
                @if (!in_array(Session::get('deppk'), ['1', '3', '6', '8', '24']))
                    <div class="dropdown">
                        <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border: none; outline: none; box-shadow: none; 
                        @if (request()->segment(2) == 'purchase-request') font-weight:700; @endif">
                            Laporan
                        </button>
                        <ul class="dropdown-menu dropdown-menu">
                                <li><a class="dropdown-item" style="@if (request()->segment(2) == 'purchase-request') font-weight:700; @endif" href="{{ route('page.lap-pr')}}">Purchase Request</a></li>
                        </ul>
                    </div>
                @endif


                <!-- <div class="dropdown">
                    <button class="btn dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="border: none; outline: none; box-shadow: none;">
                        Purchase
                    </button>

                    <ul class="dropdown-menu dropdown-menu">
                        <li><a class="dropdown-item" style="@if (request()->segment(1) == 'purchase-order') font-weight:700; @endif" href="{{ route('index.porder') }}">Purchase Order</a></li>
                        <hr class="dropdown-divider p-0 m-0"> -->

                        <!-- @if (in_array(Session::get('guserpk'), ['5', '8', '10'])) -->
                        
                        <!-- ini guser dari akses -->
                        <!-- <li><a class="dropdown-item" style="@if (request()->segment(1) == 'purchase-order') font-weight:700; @endif" href="{{ route('index.porder') }}">Purchase Order</a></li>
                        <hr class="dropdown-divider p-0 m-0"> -->

                        <!-- @endif -->

                        <!-- @if (in_array(Session::get('deppk'), ['1', '3', '8', '9']) || in_array(Session::get('guserpk'), ['2', '5', '6', '8', '10']))
                            <li><a class="dropdown-item" style="@if (request()->segment(1) == 'purchase-request') font-weight:700; @endif" href="{{ route('index.poreq') }}">Purchase Request</a></li>
                        @endif
                    </ul>
                </div> -->

                <!-- <div class="p-1 mt-1 align-self-center">
                    <a href="{{ route('index.poreq') }}"
                        style="cursor: pointer;color: black;text-decoration: none; @if (request()->segment(1) == 'purchase-request') font-weight: 700; @endif">
                        Purchase Request
                    </a>
                </div>

                @if (in_array(Session::get('deppk'), ['1', '3', '8', '9']) || in_array(Session::get('guserpk'), ['2', '5', '6', '8', '9', '10']))
                    <div class="p-1 mt-1 align-self-center">
                        <a href="{{ route('index.porder') }}"
                            style="cursor:pointer; color:black; text-decoration:none; @if (request()->segment(1) == 'purchase-order') font-weight:700; @endif">
                            Purchase Order
                        </a>
                    </div>
                @endif -->

                <!-- @if (in_array(Session::get('guserpk'), ['3', '6']))
                    <div class="p-1 mt-1 align-self-center">
                        <a href="{{ route('po-cash-tempo.index') }}" style="cursor:pointer; color:black; text-decoration:none; @if (request()->segment(1) == 'purchase-cash-tempo') font-weight:700; @endif">
                            Purchase Cash / Tempo
                        </a>
                    </div>
                @endif -->

                @if (strtolower(Session::get('gusernm')) == 'finance')
                    <div class="p-1 mt-1 align-self-center">
                        <a href="#"
                            style="cursor: pointer;color: black;text-decoration: none; @if (request()->segment(1) == 'xxx') font-weight: 700; @endif">
                            Keuangan
                        </a>
                    </div>

                    <div class="p-1 mt-1 align-self-center">
                        <a href="#"
                            style="cursor: pointer;color: black;text-decoration: none; @if (request()->segment(1) == 'xx') font-weight: 700; @endif">
                            Payment
                        </a>
                    </div>

                    <div class="p-1 mt-1 align-self-center">
                        <a href="#"
                            style="cursor: pointer;color: black;text-decoration: none; @if (request()->segment(1) == 'xxx') font-weight: 700; @endif">
                            Shipment
                        </a>
                    </div>
                @endif
            </div>

            <div class="p-1 mt-0 align-self-center">
                @if (!empty(Session::get('login')))
                    <div class="btn-group p-0">
                        <button type="button" class="btn dropdown-toggle-custom p-0" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            {{ Str::limit(ucwords(strtolower(Session::get('login'))), 8) }}
                            <img id="polygon" src="{!! asset('public/css/images/Polygon.png') !!}" style="margin-top: -2px;">
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-custom-global p-0">
                            <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-start p-2"
                                    onclick="changePassword()">Password</a></li>
                            <li>
                                <hr class="dropdown-divider p-0 m-0">
                            </li>
                            <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-end p-2"
                                    href="{{ route('logout') }}">Logout</a></li>
                        </ul>
                    </div>
                @endif
            </div>
        </div>
    </div>
</nav>

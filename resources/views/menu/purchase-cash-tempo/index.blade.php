@extends('layout.main')

@section('css_custom')
    <style>
        .button-add {
            justify-content: center;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            border-radius: 8px;
            background: #000;
            color: #FFF;
            height: 30px;
        }

        .btn-black {
            color: #fff;
            background: #000;
        }

        .btn-transparent {
            color: #000;
            background: transparent;
        }

        .rounded {
            border-radius: 8px;
        }

        .circle {
            width: 40px;
            height: 40px;
            background-color: red;
            color: white;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            line-height: 40px;
            border-radius: 50%;
            display: inline-block;
        }

        .btn-group-v1 {
            height: 34px;
            width: 140px;
        }

        .border-1 {
            border: 1px solid red;
        }

        .border-list-fltr {
            border-radius: 8px;
            border: 1px solid var(--Grey-light-3, #E0E0E0);
            background: #FFF;
        }

        .field-text {
            font-size: 16px;
            font-style: normal;
            font-weight: 400;
            line-height: 20px;
        }

        .text-qty-orders {
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 18px;
        }

        .text-filter {
            color: #000;
            font-size: 14px;
            font-style: normal;
            font-weight: 700;
            line-height: 18px;
        }

        .datagrid-cell {
            /* font-family: 'Arial'; */
            font-style: normal;
            font-weight: 400;
            font-size: 13px;
            line-height: 18px;
            white-space: normal;
        }

        .datagrid-row {
            height: auto !important;
            white-space: normal !important;
        }

        /* .datagrid-body {
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        overflow-y: hidden !important;
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                        height: auto !important;
                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                     } */

        .lines-no3 .datagrid-body td {
            border-right: 1px dotted transparent;
            border-bottom: 1px dotted transparent;
        }

        .dropdown-menu {
            max-height: 200px;
            overflow-y: auto;
            /* Add scroll if there are many checkboxes */
        }

        .imgsort {
            position: relative;
            width: 12px !important;
            height: 11.26px !important;
            left: 0px !important;
            top: 0px !important;
        }

        .dropdown-toggle-custom-sortlist {
            border-style: none !important;
        }

        .dropdown-toggle-custom-sortlist::after {
            display: none;
            border-style: none !important;
        }

        .dropdown-item:active {
            background-color: transparent;
            color: inherit;
            outline: none;
            box-shadow: none;
        }

        .w-filter {
            width: 165px !important;
            height: 34px !important;
        }

        .text-select-filter {
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 18px;
            width: 100px;
        }

        .text-select-filter2 {
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 18px;
            color: #359DD9;
            cursor: pointer;
        }

        .text-link {
            color: var(--Blue, #359DD9);
            font-size: 14px;
            font-style: normal;
            font-weight: 400;
            line-height: 18px;
        }

        .text-title-modal {
            font-size: 16px;
            font-style: normal;
            font-weight: 700;
            line-height: 22px;
        }

        .text-subtitle-modal {
            font-size: 14px;
            font-style: normal;
            font-weight: 700;
            line-height: 22px;
        }

        .multi-filter {
            width: 1000px;
        }

        .combobox-item {
            border-bottom: 1px solid #AFAFAF;
            padding: 8px;
            margin-bottom: 0px;
            /* opacity: 50% !important; */
        }

        .bi::before,
        [class^="bi-"]::before,
        [class*=" bi-"]::before {
            display: inline-block;
            font-family: bootstrap-icons !important;
            font-style: normal;
            font-weight: normal !important;
            font-variant: normal;
            text-transform: none;
            line-height: 1;
            vertical-align: -.125em;
            -webkit-font-smoothing: antialiased;
            -moz-osx-font-smoothing: grayscale;
        }

        .bi-eye-fill::before {
            content: url("{{ asset('public/css/images/caret-up-fill.svg') }}");
        }

        .bi-eye-slash-fill::before {
            content: url("{{ asset('public/css/images/caret-down-fill.svg') }}");
        }

        .bi-eye2::before {
            content: url("{{ asset('public/css/images/caret-up-fill.svg') }}");
        }

        .lines-no .datagrid-body td {
            border-right: 1px dotted transparent;
            border-bottom: 1px dotted transparent;
        }

        .lines-no2 .datagrid-header {
            border-right: 1px dotted transparent;
            border-bottom: 1px dotted transparent;
            background: #fff;
        }

        .col .datagrid-row-over,
        .col .datagrid-header td.datagrid-header-over {
            background: #fff;
            color: #000000;
            cursor: default;
        }

        .col .datagrid-row-selected {
            background: #fff;
            color: #000000;
        }
    </style>
@endsection

@section('js_custom')
    <script>
        // Configuration object for consistent styling
        const DATAGRID_STYLES = {
            smallTable: {
                header: {
                    background: 'transparent',
                    border: '1px solid #858585',
                    borderRadius: '8px'
                },
                body: {
                    overflowY: 'hidden'
                },
                panel: {
                    border: 'none',
                    classes: ['lines-no', 'lines-no3']
                },
                headerCells: {
                    borderRight: 'none'
                }
            },
            mobileTable: {
                header: {
                    visibility: 'hidden',
                    classes: ['h-0']
                },
                panel: {
                    border: 'none',
                    classes: ['lines-no', 'lines-no3']
                }
            }
        };

        // Initialize datagrid styling based on row count
        function initializeDatagrid(length) {
            if (length <= 6) {
                styleSmallTable('#dg-po');
            } else {
                styleMobileTable('#dg-po-mobile');
            }
        }

        // Style small table view
        function styleSmallTable(selector) {
            const $panel = $(selector).datagrid('getPanel');
            const styles = DATAGRID_STYLES.smallTable;

            $panel.find('div.datagrid-header')
                .css(styles.header);
            $panel.find('div.datagrid-body')
                .css(styles.body);
            $panel.find('div.datagrid-header td[field]')
                .css(styles.headerCells);
            $panel.css(styles.panel)
                .addClass(styles.panel.classes.join(' '));
        }

        // Style mobile table view
        function styleMobileTable(selector) {
            const $panel = $(selector).datagrid('getPanel');
            const styles = DATAGRID_STYLES.mobileTable;

            $panel.find('div.datagrid-header')
                .css(styles.header)
                .addClass(styles.header.classes.join(' '));
            $panel.css(styles.panel)
                .addClass(styles.panel.classes.join(' '));
        }

        // Calculate and set table height
        function settingTableHeight(length) {
            const navbar = document.querySelector('.navbar');
            let navbarHeight = 0;

            // Check if navbar exists
            if (navbar) {
                navbarHeight = parseInt(window.getComputedStyle(navbar).height) || 0;
            } else {
                console.warn('Navbar element not found, using default height');
                navbarHeight = 50; // Fallback height (adjust as needed)
            }

            const windowHeight = window.innerHeight - navbarHeight * 5;
            console.log('Navbar height:', navbarHeight, 'Window height:', windowHeight);

            const height = length <= 6 ? windowHeight : 'auto';
            $('#dg-po, #dg-po-mobile').each((_, selector) => {
                $(selector).datagrid('getPanel')
                    .find('div.datagrid-view2')
                    .css('height', height);
            });
        }

        function styler1(index, row) {
            return 'border-top:1px solid #ededed;border-bottom:3px solid #ededed;border-left:1px solid #ededed;border-top-left-radius:5px;' +
                'border-bottom-left-radius:5px;height:35px;';
        }

        function styler2(index, row) {
            return 'border-top:1px solid #ededed;border-bottom:3px solid #ededed;';
        }

        function styler3(index, row) {
            return 'border-top:1px solid #ededed;border-bottom:3px solid #ededed;border-right:1px solid #ededed;border-top-right-radius:5px;' +
                'border-bottom-right-radius:5px;';
        }

        // Format action column
        function formatAttribute(value, row) {
            const imageUrl = "{{ asset('public/css/images/More.png') }}";
            var hr = '<li><hr class="dropdown-divider mt-1 mb-1"></li>';
            // var imageUrl = "{{ asset('public/css/images/More.png') }}"; // Pastikan asset ini tersedia
            var actionButton = '';
            var AccountUserpk = {{ Session::get('userpk') }};

            if(AccountUserpk == 6){
                if (row.pkab === 2) {
                    
                    if (row.TotCountTglByr === 0) {
                        
                        actionButton = hr + '<li><a class="dropdown-item dropdown-item-custom" id="btn-posting-' + (row.index || '') + '" onclick="KlikPaid(' + (row.belipk || 'null') + ');" style="color:#359DD9;">Paid</a></li>';
                    } else if (row.TotCountTglByr > 0) {
                        actionButton = hr + '<li><a class="dropdown-item dropdown-item-custom" id="btn-unposting-' + (row.index || '') + '" onclick="KlikUnPaid(' + (row.belipk || 'null') + ');" style="color:#359DD9;">Unpaid</a></li>';
                    }
                }
            }

            return '<div>' +
                        '<button id="button-action-' + (row.index || '') + '" type="button" class="btn dropdown-toggle-custom-sortlist pt-0" data-bs-toggle="dropdown">' +
                        '<img src="' + imageUrl + '" height="15px" width="5px">' +
                        '</button>' +
                        (value || '') +
                        '<ul id="dropdown-menu-action-' + (row.index || '') + '" class="dropdown-menu pt-1 pb-1">' +
                        '<li><a class="dropdown-item dropdown-item-custom" id="btn-detail-' + (row.index || '') + '" onclick="KlikDetail(' + (row.belipk || 'null') + ');" style="color:#359DD9;">Detail</a></li>' +
                        
                        actionButton +
                        // hr +
                        // '<li><a class="dropdown-item dropdown-item-custom" id="btn-detail-' + (row.index || '') + '" onclick="KlikPrintPo(' + (row.belipk || 'null') + ');" style="color:#359DD9;">Print</a></li>' +
                        '</ul>' +
                    '</div>';
        }

        function KlikPaid(belipk){
            $('#belipkOnModal').val(belipk);
            $('#ModalKlikPaid').modal('show');
        }

        function CancelPaid(){
            $('#ModalKlikPaid').modal('hide');
            $('#TypeBill').combobox('clear');
            $('#DateSelected').datebox('clear');
            checkFormValid();
        }

        function toYYYYMMDD(date) {
            var d = new Date(date || Date.now());
            var year = d.getFullYear();
            var month = '' + (d.getMonth() + 1);
            var day = '' + d.getDate();

            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;

            return [year, month, day].join('-'); // Format YYYY-MM-DD
        }


        function ProcessPaid(){
            var belipk = $('#belipkOnModal').val();
            var codepk = $('#TypeBill').combobox('getValue');
            var DateSelectRaw = $('#DateSelected').datebox('getValue');
            var jadwal = toYYYYMMDD(myparser2(DateSelectRaw));

            if (!codepk || !jadwal) {
                alert("Type pembayaran dan tanggal wajib diisi!");
                return;
            }

            $.ajax({
                url: "{{ route('post.paid-tempo', '') }}/" + belipk,
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                    "codepk": codepk,
                    "jadwal": jadwal
                },
                success: function(response, textStatus, xhr) {
                    $('#TypeBill').combobox('setValue', '');
                    $('#DateSelected').datebox('setValue', '');
                    $('#ModalKlikPaid').modal('hide');
                    showAlert(xhr.status, response.message);
                    // setTimeout(() => location.reload(), 1300);
                      $('#dg-po').datagrid('reload');
                },
                error: function(xhr) {
                    console.error("Error detail:", xhr.responseText);
                    $('#erroronModal').text(xhr.responseJSON?.message || 'Terjadi kesalahan.');
                    alert('An error occurred. Please try again.');
                }
            });
        }

        function KlikUnPaid(belipk){
            $('#belipkUnPaid').val(belipk);
            $('#ModalKlikUnPaid').modal('show');
        }

        function ProcessUnPaid(){
            var belipk = $('#belipkUnPaid').val();
            $.ajax({
                url: "{{ route('post.unpaid-tempo', '') }}/" + belipk,
                type: "POST",
                data: {
                    "_token": "{{ csrf_token() }}",
                },
                success: function(response, textStatus, xhr) {
                    $('#ModalKlikUnPaid').modal('hide');
                    showAlert(xhr.status, response.message);
                      $('#dg-po').datagrid('reload');
                },
                error: function(xhr) {
                    console.error("Error detail:", xhr.responseText);
                    $('#erroronModal').text(xhr.responseJSON?.message || 'Terjadi kesalahan.');
                    alert('An error occurred. Please try again.');
                }
            });
        }

        function CancelUnPaid(){
            $('#ModalKlikUnPaid').modal('hide');
        }

        // Load data with filters
        function loadDataByFilter() {
            const params = {
                searchByInput: $('#searchByInput').val(),
                filterByYear: $('#filterByYear').val(),
                filterByMonth: $('#filterByMonth').val(),
                sortlistByDate: $('#sortlistByDate').val(),
                filterByTerm:  $('#filterByTerm').val(),
            };

            console.log('loadDataByFilter:', params);
            $('#dg-po-shadow, #dg-po').datagrid('load', params);
        }

        // Handle sort by date
        function sortlistByDate(value) {
            document.getElementById('sortlistByDate').value = value;
            if (value == 11) {
                $('#Invdatedesc').removeClass('d-none');
                $('#Invdateasc').addClass('d-none');
            } else if (value == 12) {
                $('#Invdatedesc').addClass('d-none');
                $('#Invdateasc').removeClass('d-none');
            } else {
                $('#Invdatedesc').removeClass('d-none');
                $('#Invdateasc').addClass('d-none');
            }
            loadDataByFilter();
        }

        // Navigate to detail page
        function KlikDetail(pk) {
            window.location.href = "{{ url('/') }}/purchase-cash-tempo/detail/" + pk;
        }

        // Print PO
        function KlikPrintPo(popk) {
            window.open("{{ url('/') }}/purchase-order/print/" + popk, "_blank");
        }

        // Format date
        function formatterDate(value) {
            if (!value) return '';
            const date = new Date(value);
            return `${date.getDate().toString().padStart(2, '0')}/${(date.getMonth() + 1).toString().padStart(2, '0')}/${date.getFullYear()}`;
        }

        // Update notran
        async function updateNotran(url) {
            try {
                const response = await $.ajax({
                    url,
                    type: "POST",
                    data: {
                        _token: $('meta[name="csrf-token"]').attr('content')
                    }
                });
                localStorage.setItem("notran-beli", response.data);
                return response.data;
            } catch (error) {
                throw new Error(`Failed to update notran: ${error.statusText}`);
            }
        }

        // Add new PO
        async function addPo() {
            try {
                let notran = localStorage.getItem("notran-beli");
                if (!notran) {
                    notran = await updateNotran("{{ route('po-cash-tempo.notran') }}");
                }
                window.location.href = "{{ route('po-cash-tempo.create') }}";
            } catch (error) {
                console.error('Error in addPo:', error);
                alert('Failed to create new PO. Please try again.');
            }
        }

        // Initialize on document ready
        $(document).ready(function() {
            console.log('jQuery version:', jQuery.fn.jquery);
            initializeDatagrid(0);
            $('#dg-po-shadow').datagrid({
                onLoadSuccess: function(data) {
                    settingTableHeight(data.total);
                }
            });
        });

        function myformatter2(date) {
            var d = new Date(date || Date.now());
            var day = '' + d.getDate(); // Hari
            var month = '' + (d.getMonth() + 1); // Bulan (ditambah 1 karena 0-based)
            var year = d.getFullYear(); // Tahun

            if (day.length < 2) day = '0' + day;
            if (month.length < 2) month = '0' + month;

            return [day, month, year].join('/'); // Format DD/MM/YYYY
        }

        function myparser2(s) {
            if (!s) return new Date();

            // Pisahkan string berdasarkan '/'
            var ss = s.split('/');
            var d = parseInt(ss[0], 10); // Hari
            var m = parseInt(ss[1], 10); // Bulan
            var y = parseInt(ss[2], 10); // Tahun

            // Pastikan formatnya sesuai (DD/MM/YYYY)
            if (!isNaN(d) && !isNaN(m) && !isNaN(y)) {
                return new Date(y, m - 1, d); // JavaScript Date: Bulan mulai dari 0
            } else {
                return new Date();
            }
        }

        function checkFormValid() {
            var finishVal = $('#DateSelected').datebox('getValue');
            var typeBillVal = $('#TypeBill').combobox('getValue');

            
            if (typeBillVal && finishVal) {
                $('#get_print').prop('disabled', false).removeClass('disabled');
            } else {
                $('#get_print').prop('disabled', true).addClass('disabled');
            }
        }

        $(document).ready(function () {
            $('#TypeBill').combobox({
                onChange: function () {
                    checkFormValid();
                }
            });

            $('#DateSelected').datebox({
                formatter: myformatter2,
                parser: myparser2,
                onChange: function () {
                    checkFormValid();
                }
            });
            checkFormValid();
        });
    </script>
@endsection

@section('content')

    <div class="modal fade" id="ModalKlikPaid" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false"
        aria-labelledby="exampleModalKlikPaid" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                <div class="modal-body">
                    <div class="row">
                        <input type="text" id="belipkOnModal" hidden>

                        <div class="col-md-6">
                            <label for="tgl" class="col-form-label">Pilih Type Pembayaran:</label>
                            <div class="p-0">
                            <input class="easyui-combobox" id="TypeBill" name="TypeBill" style="width:100px; height:30px;" method="get" 
                                data-options="panelHeight:'auto',  valueField:'codepk', textField:'text',
                                        data:[
                                            {codepk:'C', text:'CASH'}, 
                                            {codepk:'G', text:'GIRO'}
                                        ]">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label for="tglinv" class="col-form-label">Tanggal Pembayaran:</label>
                            <div class="p-0">
                                <input id="DateSelected" name="DateSelected" class="easyui-datebox" data-options="formatter:myformatter2, parser:myparser2">
                            </div>
                        </div>
                    </div>
                </div>   
                <div class="modal-footer" style="border:none;">
                    <button type="button" class="btn btn-link" onclick="CancelPaid();" style="text-decoration: none; color:#000;"><b>Cancel</b></button>
                    <button id="get_print" class="btn btn-dark my-1 py-1"  onclick="ProcessPaid();">Process Paid</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="ModalKlikUnPaid" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false" aria-labelledby="exampleModalKlikUnPaid" aria-hidden="true">
        <div class="modal-dialog modal-md">
            <div class="modal-content">
                  <div class="modal-header" style="border:none;">
                    <b>Konfirmasi Unpaid</b>
                </div>
                <input type="text" id="belipkUnPaid" hidden>

                <div class="modal-body">
                    <p>	Apakah anda yakin melakukan unpaid data yang sudah di bayar sebelumnya?</p>
                </div>   
                <div class="modal-footer" style="border:none;">
                    <button type="button" class="btn btn-link" onclick="CancelUnPaid();" style="text-decoration: none; color:#000;"><b>Cancel</b></button>
                    <button class="btn btn-dark my-1 py-1"  onclick="ProcessUnPaid();">Process UnPaid</button>
                </div>
            </div>
        </div>
    </div>

    <div class="main-wrapper">
        <div class="container-fluid p-4">
            <div class="d-flex flex-column">
                <div class="p-2">
                    <span class="txt-000000-700-24-28">List Pembelian Cash / Tempo</span>
                </div>

                <div class="d-flex flex-wrap p-0 pb-2 gap-2 align-items-center">
                    <div class="p-0">
                        <div class="input-group flex-nowrap input-group-search" style="height:34px;">
                            <span class="input-group-text search">
                                <img src="{{ asset('public/css/images/Shape.png') }}" plain="true" width="18px"
                                    height="18px">
                            </span>
                            <input type="text" class="form-control search" id="searchByInput"
                                onkeyup="loadDataByFilter()" placeholder="Search...">
                        </div>
                    </div>
                    <select class="easyui-combobox" id="filterByMonth"
                        data-options="editable:false, onChange:loadDataByFilter" style="width:150px; height:34px;">
                        <?php
                        $months = [
                            '01' => 'January',
                            '02' => 'February',
                            '03' => 'March',
                            '04' => 'April',
                            '05' => 'May',
                            '06' => 'June',
                            '07' => 'July',
                            '08' => 'August',
                            '09' => 'September',
                            '10' => 'October',
                            '11' => 'November',
                            '12' => 'December',
                        ];
                        
                        $currentMonth = date('m');
                        foreach ($months as $key => $month) {
                            $selected = $key == $currentMonth ? 'selected' : '';
                            echo "<option value='$key' $selected>$month</option>";
                        }
                        ?>
                    </select>
                    <div class="p-0">
                        <select class="easyui-combobox" id="filterByYear"
                            data-options="editable:false, panelHeight:'auto', onChange:loadDataByFilter"
                            style="width:100px;">
                            @for ($i = date('Y'); $i >= date('Y') - 6; $i -= 1)
                                <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                            @endfor
                        </select>
                    </div>
                    
                    <div class="p-0">
                    <input class="easyui-combobox" 
                        id="filterByTerm" 
                        name="filterByTerm" 
                        style="width:100px; height:30px;" 
                        method="get" 
                        data-options="panelHeight:'auto', 
                                        valueField:'abpk',
                                        textField:'text',
                                        value:'0',
                                        onChange:loadDataByFilter,
                                        data:[
                                            {abpk:'0', text:'All TERM'}, 
                                            {abpk:'1', text:'CASH'}, 
                                            {abpk:'2', text:'TEMPO'}
                                        ]">
                    </div>

                    <button onclick="addPo();" type="button" class="btn btn-dark" style="height:30px; line-height:30px; padding:0 10px;">&#10010; Create Data</button>

                    @if (Session::get('guserpk') == '1')
                        <div class="dropdown">
                            <button class="btn btn-dark dropdown-toggle"
                                style="height:30px; line-height:30px; padding:0 10px;" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Tabel
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="#">Supplier</a>
                                </li>
                            </ul>
                        </div>

                        <div class="dropdown">
                            <button class="btn btn-dark dropdown-toggle"
                                style="height:30px; line-height:30px; padding:0 10px;" type="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                Laporan
                            </button>
                            <ul class="dropdown-menu">
                                <li>
                                    <a class="dropdown-item" href="#">Purchase Order</a>
                                </li>
                            </ul>
                        </div>
                    @endif
                    <div class="ms-auto">
                        <div id="sortlist-grup" class="p-1 pb-0 responsive">
                            <input type="hidden" id="sortlistByDate" name="sortlistByDate" size="5">
                            <button class="btn btn-sortlist dropdown-toggle-custom-sortlist pt-0" data-bs-toggle="dropdown"
                                data-bs-target="#menu-sortlist" aria-expanded="true">
                                <img src="{{ asset('public/css/images/sort.jpg') }}" alt="" class="imgsort">
                                <span id="Invdatedesc">Date Inv (desc)</span>
                                <span id="Invdateasc" class="d-none">Date Inv (asc)</span>
                            </button>
                            <ul id="menu-sortlist"
                                class="dropdown-menu dropdown-menu-end dropdown-menu-custom-global dropdown-menu-sortlist pt-0 pb-0">
                                <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-start p-2"
                                        onclick="sortlistByDate(11)">Date Inv (descending)</a></li>
                                <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-end p-2"
                                        onclick="sortlistByDate(12)">Date Inv (ascending)</a></li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="d-none">
                    <table id="dg-po-shadow" class="easyui-datagrid" title="" method="get"
                        url="{{ route('api.po-cash-tempo.get') }}"></table>
                </div>

                <div class="p-0">
                    <table id="dg-po" class="easyui-datagrid" url="{{ route('api.po-cash-tempo.get') }}" title=""
                        style="width:99%;" align="center" toolbar="#tb" striped="false" pageSize="100"
                        pageList="[100,200,300,500]" idField="prpk" pagination="true" rownumbers="false" singleSelect="true"
                        collapsible="true" method="get" data-options="border:false">
                        <thead>
                            <tr>
                                <th field="index" styler="styler1">No</th>
                                <th field="nobukti" styler="styler2">No.Bukti</th>
                                <!-- <th field="tanggal" styler="styler2" formatter="formatterDate">Tanggal Invoice</th> -->
                                <th field="tanggal" styler="styler2">Tanggal Invoice</th>
                                <th field="noinv" styler="styler2">No.Invoice</th>
                                <th field="supnm" styler="styler2">Supplier</th>
                                <th field="totbeli" styler="styler2">Nilai Invoice</th>
                                <th field="tglbayar" styler="styler2">Tanggal Bayar</th>
                                <th field="cg" styler="styler2">C/G</th>
                                <th field="jmlbayar" styler="styler2">Jumlah Bayar</th>
                                <th field="term" styler="styler2">Term</th>
                                <th field="user" styler="styler2">User</th>
                                <th field="act" styler="styler2" formatter="formatAttribute">Act</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

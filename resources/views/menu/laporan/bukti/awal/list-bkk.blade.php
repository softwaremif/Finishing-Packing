@extends('layout.main')

@section('css_custom')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    .datagrid-header td,
        .datagrid-body td,
        .datagrid-footer td {
            border-color: #ffffff;
    }

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
    $(function() {
        $('#dg-style-shadow').datagrid('getPanel').find('div.datagrid-view').css('display', 'none');
        $('#dg-style-shadow').datagrid('getPanel').find('div.datagrid-pager').css('display', 'none');

        var dgspb = $('#dg-style').datagrid();
        dgspb.datagrid('getPanel').find('div.datagrid-header').css('background', 'transparent');
        dgspb.datagrid('getPanel').find('div.datagrid-header').css('border', '1px solid #858585');
        dgspb.datagrid('getPanel').find('div.datagrid-header').css('border-radius', '8px');
        dgspb.datagrid('getPanel').find('div.datagrid-header td[field]').css('border-right', 'none');
        dgspb.datagrid('getPanel').find('div.datagrid-body').css('overflow-y', 'hidden');
        dgspb.datagrid('getPanel').css('border', 'none');
        dgspb.datagrid('getPanel').addClass('lines-no');
        dgspb.datagrid('getPanel').addClass('lines-no3');
    })

    $(function() {
        $('#dg-style-shadow').datagrid({
            onLoadSuccess: function(data) {
                settingTableHeight(data.total);
            },
        })

        $('#ModalKlikCekCash').on('shown.bs.modal', function() {
            $('#dgListCekCash').datagrid('resize');
        })
    })

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

    $(document).ready(function () {
        var today = new Date();
        var formattedToday = myformatter2(today); // gunakan formatter kamu sendiri

        $('#SelectDate').datebox('setValue', formattedToday);
        loadDataByFilter(); // optional: langsung load data saat tanggal otomatis diset
    });


    function loadDataByFilter() {
        var searchByInput = $('#searchByInput').val();
        var SelectDate = $('#SelectDate').datebox('getValue');
        $('#dg-style-shadow').datagrid('load', {
            searchByInput: searchByInput,
            SelectDate: SelectDate,
        });

        $('#dg-style').datagrid('load', {
            searchByInput: searchByInput,
            SelectDate: SelectDate,
        })
    }

    function settingTableHeight(length) {
        var element = document.querySelector('.navbar');
        var computedStyle = window.getComputedStyle(element);
        var height = parseInt(computedStyle.getPropertyValue('height'));

        var screenWidth = window.innerWidth;
        var windowHeight = window.innerHeight - height * 5;

        console.log('height:', height);
        console.log('windowHeight:', windowHeight);

        if (length <= 6) {
            console.log("here <= 6");
            $('#dg-style').datagrid('getPanel').find('div.datagrid-view2').css('height', windowHeight);
        } else {
            console.log("here auto");
            $('#dg-style').datagrid('getPanel').find('div.datagrid-view2').css('height', 'auto');
        }
    }

    function myformatter2(date) {
        var y = date.getFullYear();
        var m = date.getMonth() + 1;
        var d = date.getDate();
        return (d < 10 ? '0' + d : d) + '/' +
            (m < 10 ? '0' + m : m) + '/' + y;
    }

    function myparser2(s) {
        if (!s) return new Date();

        var ss = s.split('/');
        var d = parseInt(ss[0], 10);
        var m = parseInt(ss[1], 10);
        var y = parseInt(ss[2], 10);

        if (!isNaN(d) && !isNaN(m) && !isNaN(y)) {
            return new Date(y, m - 1, d);
        } else {
            return new Date();
        }
    }

    function formatToIsoPdf(dateStr) {
        const parts = dateStr.split('/');
        if (parts.length !== 3) return '';
        const [day, month, year] = parts;
        return `${year}-${month}-${day}`;
    }

    function KlikPrint(){
        const params = {
            SelectDate: formatToIsoPdf($('#SelectDate').datebox('getValue')),
        };

        const query = Object.entries(params)
            .map(([key, val]) => `${key}=${encodeURIComponent(val)}`)
            .join('&');
        // window.location.href = `{{ route('pdf.list-bkk') }}?${query}`;
        window.open(`{{ route('pdf.list-bkk') }}?${query}`, '_blank');
    }

    $('#SelectDate').datebox({
        formatter: myformatter2,
        parser: myparser2,
        onChange: function () {
            loadDataByFilter(); // fungsi kamu
            updatePrintButtonState(); // update tombol print
        }
    });

    function updatePrintButtonState() {
        const SelectDate = $('#SelectDate').datebox('getValue');

        if (SelectDate) {
            $('#get_print')
                .removeClass('btn-secondary')
                .addClass('btn-dark')
                .prop('disabled', false);
        } else {
            $('#get_print')
                .removeClass('btn-dark')
                .addClass('btn-secondary')
                .prop('disabled', true);
        }
    }

    function KlikCekCash(){
        // $('#ModalKlikCekCash').modal('show');
        
        // Ambil tanggal dari input utama
        var selectedDate = $('#SelectDate').datebox('getValue');

        // Set tanggal ke input di dalam modal
        $('#SelectStart').datebox('setValue', selectedDate);
        $('#SelectFinish').datebox('setValue', selectedDate);

        // Tampilkan modal (asumsikan kamu pakai EasyUI dialog atau modal Bootstrap)
        $('#ModalKlikCekCash').modal('show'); // ganti sesuai id modal kamu

        // Load data dengan filter baru
        loadDataModalKlikCekCash();
    }

    //Set agar ketika klik cek cash belum bayar, load pertama adalah SEMUA
    $(document).ready(function () {
        $('#filterByStatusPembayaran').combobox({
            onLoadSuccess: function () {
                $(this).combobox('setValue', '1');
                loadDataModalKlikCekCash(); // load data pertama kali
            }
        });
    });

    function loadDataModalKlikCekCash() {
        var searchByInput = $('#searchByInput').val();
        var filterByStatusPembayaran = $('#filterByStatusPembayaran').combobox('getValue');
        // console.log("Status Pembayaran:", filterByStatusPembayaran);

        $('#dgListCekCash').datagrid('load', {
            searchByInput: searchByInput,
            SelectStart: $('#SelectStart').datebox('getValue'),
            SelectFinish: $('#SelectFinish').datebox('getValue'),
            filterByStatusPembayaran: filterByStatusPembayaran,
        });
    }

    function CloseModal(){
        $('#filterByStatusPembayaran').combobox('setValue', '1');
    }

    function KlikModalPrint() {
        const params = {
            searchByInput: $('#searchByInput').val(),
            SelectStart: $('#SelectStart').datebox('getValue'),
            SelectFinish: $('#SelectFinish').datebox('getValue'),
            filterByStatusPembayaran : $('#filterByStatusPembayaran').combobox('getValue'),
        };

        const query = Object.entries(params)
            .map(([key, val]) => `${key}=${encodeURIComponent(val)}`)
            .join('&');

        // window.location.href = `{{ route('pdf.cek-cash-by-modal') }}?${query}`;
        window.open(`{{ route('pdf.cek-cash-by-modal') }}?${query}`, '_blank');
    }

</script>
@endsection

@section('content')
<div class="modal fade" id="ModalKlikCekCash" tabindex="-1" data-bs-backdrop="static">
  <div class="modal-dialog modal-fullscreen-xxl-down modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header" style="border:none;">
        <div class="d-flex align-items-center">
            <div class="p-0 ps-3">
                <div class="input-group flex-nowrap input-group-search" style="height:34px;">
                    <span class="input-group-text search">
                        <img src="{{ asset('public/css/images/Shape.png') }}" plain="true" width="18px" height="18px">
                    </span>
                    <input type="text" class="form-control search" id="searchByInput" onkeyup="loadDataModalKlikCekCash()" placeholder="Search...">
                </div>
            </div>

            <div class="p-0 ps-3">
                Dari:
                <input id="SelectStart" name="SelectStart" class="easyui-datebox" style="width: 110px; height: 28px;" data-options="formatter:myformatter2, parser:myparser2, onChange:loadDataModalKlikCekCash,">
            </div>

            <div class="p-0 ps-2">
                s/d:
                <input id="SelectFinish" name="SelectFinish" class="easyui-datebox" style="width: 110px; height: 28px;" data-options="formatter:myformatter2, parser:myparser2, onChange:loadDataModalKlikCekCash,">
            </div>

            <div class="p-0 ps-3">
                <!-- <input id="filterByStatusPembayaran" name="filterByStatusPembayaran" class="easyui-combobox" method="get" data-options="valueField:'statuspk', textField:'status', onChange:loadDataModalKlikCekCash, panelHeight:'auto', limitToList:'true', url:'{{ route('get.status-pembayaran') }}', prompt:'Status Pembayaran'"> -->
                <input id="filterByStatusPembayaran" name="filterByStatusPembayaran" class="easyui-combobox" method="get"
                    data-options="
                        valueField:'statuspk',
                        textField:'status',
                        panelHeight:'auto',
                        limitToList:true,
                        url:'{{ route('get.status-pembayaran') }}',
                        onChange:loadDataModalKlikCekCash,
                        onLoadSuccess:function(){
                            $(this).combobox('setValue', '1'); // Set default to 'SEMUA'
                        }
                    ">
            </div>

            <div class="p-0 ps-3">
                <button type="button" id="get_ModalPrint" class="btn btn-sm btn-dark my-1 py-1"
                     onclick="KlikModalPrint()"><i class="fa fa-print"></i> Print
                </button>
            </div>

        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="CloseModal();"></button>
      </div> 

        <div class="modal-body">
            <div>
                <table id="dgListCekCash" class="easyui-datagrid" title="" style="width:100%;" url="{{ route('get.list-cek-cash.awal') }}"
                    align="center" toolbar="#tb" striped="true" pagination="true" pageSize="100" fitColumns="false"
                    pageList="[100,200,300,500]" method="get" rownumbers="false" multiple="true" collapsible="true"
                    data-options="multiSort:true, remoteSort:false, border:true, dnd:true, checkOnSelect:false">
                    <thead>
                        <tr>
                            <!-- <th field="belidtpk" width="30">Belidtpk</th> -->
                            <!-- <th field="belipk" width="30">Belipk</th> -->
                            <th field="index" width="30">No</th>
                            <th field="noinv" width="100">No. Inv/PO</th>
                            <th field="tanggal" width="100">Tgl Inv</th>
                            <th field="supnm" width="300">Supplier</th>
                            <th field="brgnm" width="400">Keterangan</th>
                            <th field="hrgbeli" width="100">Harga Satuan</th>
                            <th field="jmlbeli" width="100">Qty</th>
                            <th field="jmlhrg" width="100">Jumlah</th>
                            <th field="tglbayar" width="100">Tgl Bayar</th>
                            <th field="jmlbayar" width="100">Jml Bayar</th>
                            <th field="cg" width="100">Cash/BG</th>
                            <th field="user" width="100">User</th>
                            <th field="nobg" width="100">No BG</th>
                            <th field="tglaju" width="100">Tgl Aju</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
  </div>
</div>

<div class="main-wrapper">
    <div class="container-fluid p-4">
        <div class="d-flex flex-column">
            <div class="p-2">
                <span class="txt-000000-700-24-28">Laporan Bukti Kas Keluar</span>
            </div>


            <div class="d-flex flex-wrap p-0 pb-2 gap-2 align-items-center">

                <div class="p-0">
                    <div class="input-group flex-nowrap input-group-search" style="height:34px;">
                        <span class="input-group-text search">
                            <img src="{{ asset('public/css/images/Shape.png') }}" plain="true" width="18px"
                                height="18px">
                        </span>
                        <input type="text" class="form-control search" id="searchByInput" onkeyup="loadDataByFilter()"
                            placeholder="Search...">
                    </div>
                </div>

                <div class="p-0">
                    Tanggal: <input id="SelectDate" name="SelectDate" class="easyui-datebox"
                        style="width:110px; height:28px;"
                        data-options="formatter:myformatter2, parser:myparser2, onChange:loadDataByFilter,">
                </div>

                <div class="p-0">
                    <button type="button" id="get_print" class="btn btn-sm btn-secondary" onclick="KlikPrint()" disabled>
                        <i class="fa fa-print"></i> Print
                    </button>
                </div>

                <div class="p-0">
                    <button type="button" id="get_print" class="btn btn-sm btn-dark"
                        onclick="KlikSetNota()"><i class="fa fa-compress"></i> Set Nota </button>
                </div>

                <div class="p-0">
                    <button type="button" id="get_print" class="btn btn-sm btn-dark"
                        onclick="KlikCekCash()"><i class="fa fa-check-square"></i> Cek Cash yang belum bayar</button>
                </div>

            </div>

            <div class="d-none">
                <table id="dg-style-shadow" class="easyui-datagrid" title="" style="width:100%;" align="center"
                    toolbar="#tb" striped="false" pageSize="100" pageList="[100,200,300,500]" idField="prpk"
                    pagination="true" rownumbers="false" multiple="true" collapsible="true" method="get"
                    url="{{ route('get.bkk.awal') }}">
                </table>
            </div>

            <div class="p-0">
                <table id="dg-style" class="easyui-datagrid" url="{{ route('get.bkk.awal')}}" title="" style="width:99%;"
                    align="center" toolbar="#tb" striped="false" pageSize="100" pageList="[100,200,300,500]"
                    idField="belidtpk" pagination="true" rownumbers="false" singleSelect="true" collapsible="true"
                    method="get" data-options="border:false">
                    <thead>
                        <tr>
                            <!-- <th field="belipk" width="30" styler="styler1">Belipk</th>
                            <th field="belidtpk" width="30" styler="styler1">Belidt</th> -->
                            <th field="index" width="30" styler="styler1">No</th>
                            <th field="supnm" styler="styler2">Keterangan</th>
                            <th field="hrgbeli" styler="styler3">Jumlah</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
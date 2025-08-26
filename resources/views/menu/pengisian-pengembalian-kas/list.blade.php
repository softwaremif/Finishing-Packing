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

    #noinv {
        width: 40%;
    }

    #dynamic-year {
        width: 60%;
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
    $(function() {
        $('#dg-kms-shadow').datagrid('getPanel').find('div.datagrid-view').css('display', 'none');
        $('#dg-kms-shadow').datagrid('getPanel').find('div.datagrid-pager').css('display', 'none');

        var dgang = $('#dg-kms').datagrid();
        dgang.datagrid('getPanel').find('div.datagrid-header').css('background', 'transparent');
        dgang.datagrid('getPanel').find('div.datagrid-header').css('border', '1px solid #858585');
        dgang.datagrid('getPanel').find('div.datagrid-header').css('border-radius', '8px');
        dgang.datagrid('getPanel').find('div.datagrid-header td[field]').css('border-right', 'none');
        dgang.datagrid('getPanel').find('div.datagrid-body').css('overflow-y', 'hidden');
        dgang.datagrid('getPanel').css('border', 'none');
        dgang.datagrid('getPanel').addClass('lines-no');
        dgang.datagrid('getPanel').addClass('lines-no3');

        $('#dg-kms-mobile').datagrid('getPanel').find('div.datagrid-header').css('visibility', 'hidden');
        $('#dg-kms-mobile').datagrid('getPanel').find('div.datagrid-header').addClass('h-0');
        $('#dg-kms-mobile').datagrid('getPanel').css('border', 'none');
        $('#dg-kms-mobile').datagrid('getPanel').addClass('lines-no');
        $('#dg-kms-mobile').datagrid('getPanel').addClass('lines-no3');
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

    function loadDataByFilter() {
        console.log(`loadDataByFilter RUNNING >>>>>>>>>>>>>>`);
        var searchByInput = $('#searchByInput').val();
        var sortlistByDate = $('#sortlistByDate').val();
        var filterByYear = $('#filterByYear').val();
        var filterByMonth = $('#filterByMonth').val();

        console.log(`searchByInput : ${searchByInput}`);
        $('#dg-kms-shadow').datagrid('load', {
            searchByInput: searchByInput,
            sortlistByDate: sortlistByDate,
            filterByYear: filterByYear,
            filterByMonth: filterByMonth,
        });

        $('#dg-kms').datagrid('load', {
            searchByInput: searchByInput,
            sortlistByDate: sortlistByDate,
            filterByYear: filterByYear,
            filterByMonth: filterByMonth,
        })
        console.log(`loadDataByFilter END >>>>>>>>>>>>>>`);
    }

    $(function() {
        $('#dg-kms-shadow').datagrid({
        onLoadSuccess: function(data) {
            settingTableHeight(data.total);
        },
        })
    })

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
        $('#dg-kms').datagrid('getPanel').find('div.datagrid-view2').css('height', windowHeight);
        $('#dg-kms-mobile').datagrid('getPanel').find('div.datagrid-view2').css('height', windowHeight);
        } else {
        console.log("here auto");
        $('#dg-kms').datagrid('getPanel').find('div.datagrid-view2').css('height', 'auto');
        $('#dg-kms-mobile').datagrid('getPanel').find('div.datagrid-view2').css('height', 'auto');
        }
    }

    //FILTER SORT
    function sortlistByDate(value) {
        document.getElementById('sortlistByDate').value = value;
        if (value == 11) {
        $('#TanggalDesc').removeClass('d-none');
        $('#TanggalAsc').addClass('d-none');
        } else if (value == 12) {
        $('#TanggalDesc').addClass('d-none');
        $('#TanggalAsc').removeClass('d-none');
        } else {
        $('#TanggalDesc').removeClass('d-none');
        $('#TanggalAsc').addClass('d-none');
        }
        loadDataByFilter();
    }

    function formatAttribute(value, row, index) {
        return `<a type="button" class="txt-359DD9-400-14-18-100" onclick="onEditData('${row.kmspk}')">Edit</a>`;
    }

    function AddData() {
        console.log(`onAddData is running...`);
        $('#ModalCreateOrEditKms').modal('show');
        $('.kms-create').removeClass('d-none');
        $('.kms-edit').addClass('d-none');
    }

    function onEditData(kmspk) {
        console.log(`onEditData is running...`);

        $('#ModalCreateOrEditKms').modal('show');

        $('.kms-edit').removeClass('d-none');
        $('.kms-create').addClass('d-none');

        let url = "{{ url('get-ppk/detail/:kmspk') }}";
        url = url.replace(':kmspk', kmspk);

        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                console.log(`success onEditData here >>>>>`);
                console.log(response);
                $('#kmspk').val(response.data.kmspk);
                $('#tgl').val(response.data.tgl);
                $('#jumlah').val(response.data.jumlah);
                $('#ket').val(response.data.ket);
                $('#sawal').val(response.data.sawal);
                $('#tglsawal').val(response.data.tglsawal);
            },
            error: function(xhr, status, error) {
                console.log(`error onEditData here >>>>>`);
                console.log(xhr);
            }
        })
    }

    function onCancelData() {
        resetData();
        $('#ModalCreateOrEditKms').modal('hide');
        $('#formCreateOrEditKms').form('reset');
    }

    function resetData() {
        $('#kmpspk').val(null);
        $('#ermsg-tgl').text(null);
        $('#ermsg-jumlah').text(null);
        $('#ermsg-ket').text(null);
        $('#loading-edit-kms').addClass('d-none');
        $('#loading-create-kms').addClass('d-none');
    }

    function validateListData() {
        console.log(`validateListData is running...`);

        var tgl = $('#tgl').val();
        if (!tgl) $('#ermsg-tgl').text('Tanggal wajib diisi')

        var jumlah = $('#jumlah').val();
        if (!jumlah) $('#ermsg-jumlah').text('Jumlah wajib diisi')

        var ket = $('#ket').val();
        if (!ket) $('#ermsg-ket').text('Keterangan wajib diisi')

        setTimeout(() => {
            $('#ermsg-tgl').text(null);
            $('#ermsg-jumlah').text(null);
            $('#ermsg-ket').text(null);
        }, 5000);

        if ((!jumlah || !ket )) return false;

        console.log(`validateListData is valid`);

        return true;
    }

    function onSubmitData() {
        console.log(`onSubmitAng is running...`);

        if (!validateListData()) return;

        $('#loading-create-kms').removeClass('d-none');

        let url = "{{ url('get-ppk/store') }}";
        console.log(`url: ${url}`);

        let formdata = new FormData();
        formdata.append('tgl', $('#tgl').val());
        formdata.append('jumlah', $('#jumlah').val());
        formdata.append('ket', $('#ket').val());
        formdata.append('sawal', $('#sawal').val());
        formdata.append('tglsawal', $('#tglsawal').val());

        $.ajax({
            url: url,
            method: 'POST',
            data: formdata,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log(`success onSubmitData here >>>>>`);
                console.log(response);

                onCancelData();
                $('#dg-kms').datagrid('reload');
            },
            error: function(xhr, status, error) {
                console.log(`error onSubmitData here >>>>>`);
                console.log(xhr);
            }
        });
    }

    function onUpdateData() {
        console.log(`onUpdateData is running...`);

        var kmspk = $('#kmspk').val();
        if (!kmspk) return;

        if (!validateListData()) return;

        $('#loading-edit-kms').removeClass('d-none');

        let url = "{{ url('get-ppk/update/:kmspk') }}";
        url = url.replace(':kmspk', kmspk);
        console.log(`url: ${url}`);

        let formdata = new FormData();

        formdata.append('tgl', $('#tgl').val());
        formdata.append('jumlah', $('#jumlah').val());
        formdata.append('ket', $('#ket').val());
        formdata.append('sawal', $('#sawal').val());
        formdata.append('tglsawal', $('#tglsawal').val());
        formdata.append('kmspk', kmspk);

        $.ajax({
            url: url,
            method: 'POST',
            data: formdata,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                console.log(`success onUpdateData here >>>>>`);
                console.log(response);
                onCancelData();
                $('#dg-kms').datagrid('reload');
            },
            error: function(xhr, status, error) {
                console.log(`error onUpdateData here >>>>>`);
                console.log(xhr);
            }
        });
    }
</script>
@endsection

@section('content')
<div class="main-wrapper">
    <div class="container-fluid p-4">
        <div class="d-flex flex-column">
            <div class="p-2">
                <span class="txt-000000-700-24-28">List Pengisian Pengembalian Kas</span>
            </div>
            <div class="d-flex flex-wrap p-0 pb-2 gap-2 align-items-center">

                <div class="p-0">
                    <div class="input-group flex-nowrap input-group-search" style="height:34px;">
                        <span class="input-group-text search">
                            <img src="{{ asset('public/css/images/Shape.png') }}" plain="true" width="18px" height="18px">
                        </span>
                        <input type="text" class="form-control search" id="searchByInput" onkeyup="loadDataByFilter()" placeholder="Search...">
                    </div>
                </div>

                <select class="easyui-combobox" id="filterByMonth" data-options="editable:false, onChange:loadDataByFilter" style="width:150px; height:34px;">
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
                            $selected = ($key == $currentMonth) ? 'selected' : '';
                            echo "<option value='$key' $selected>$month</option>";
                        }
                    ?>
                </select>

                <div class="p-0">
                    <select class="easyui-combobox" id="filterByYear" data-options="editable:false, panelHeight:'auto', onChange:loadDataByFilter"
                        style="width:100px; height:33px;">
                        <?php
                        for ($i = date('Y'); $i >= date('Y') - 6; $i -= 1) { ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php } ?>
                    </select>
                </div>
                
                @if(Session::get('deppk') == 8)
                    <button onclick="AddData('create')" class="btn btn-dark btn-sm">&#10010; Create Data</button>
                @endif

                <div class="modal fade" id="ModalCreateOrEditKms" data-bs-backdrop="static" aria-labelledby="ModalCreateOrEditKmsLabel" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h1 class="txt-000000-700-18-24-100" id="ModalCreateOrEditKmsLabel">
                                    <span class="kms-create d-none">Create New</span>
                                    <span class="kms-edit d-none">Edit</span>
                                    Pengisian Pengembalian Kas
                                </h1>
                            </div>
                            <div class="modal-body">
                                <form id="formCreateOrEditKms" class="d-flex flex-column gap-2">
                                    <input type="text" id="kmspk" hidden>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="tgl" class="col-form-label">Tanggal:</label>
                                            <input type="date" class="form-control" id="tgl">
                                            <span id="ermsg-tgl" class="text-danger text-blink"></span>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="jumlah" class="col-form-label">Jumlah:</label>
                                            <input type="text" class="form-control" id="jumlah">
                                            <span id="ermsg-jumlah" class="text-danger text-blink"></span>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="tglsawal" class="col-form-label">Tgl Awal:</label>
                                            <input type="date" class="form-control" id="tglsawal">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="sawal" class="col-form-label">Saldo:</label>
                                            <input type="text" class="form-control" id="sawal">
                                        </div>
                                    </div>


                                    <div class="mb-3">
                                        <label for="ket" class="col-form-label">Keterangan:</label>
                                        <textarea class="form-control" id="ket"></textarea>
                                        <span id="ermsg-ket" class="text-danger text-blink"></span>                           
                                    </div>
                                </form>
                            </div>

                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-light" onclick="onCancelData()">Cancel</button>
                                <button type="button" class="btn btn-dark kms-create d-none" onclick="onSubmitData()">Save
                                    <span id="loading-create-kms" class="spinner-grow spinner-grow-sm d-none" aria-hidden="true"></span>
                                </button>

                                <button type="button" class="btn btn-dark kms-edit d-none" onclick="onUpdateData()">Update
                                    <span id="loading-edit-kms" class="spinner-grow spinner-grow-sm d-none" aria-hidden="true"></span>
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="ms-auto">
                    <div id="sortlist-grup" class="p-1 pb-0 responsive">
                        <input type="hidden" id="sortlistByDate" name="sortlistByDate" size="5">
                        <button class="btn btn-sortlist dropdown-toggle-custom-sortlist pt-0" data-bs-toggle="dropdown" data-bs-target="#menu-sortlist" aria-expanded="true">
                        <img src="{{ asset('public/css/images/sort.jpg') }}" alt="" class="imgsort">
                        <span id="TanggalDesc">Tanggal (desc)</span>
                        <span id="TanggalAsc" class="d-none">Tanggal (asc)</span>
                        </button>
                        <ul id="menu-sortlist" class="dropdown-menu dropdown-menu-end dropdown-menu-custom-global dropdown-menu-sortlist pt-0 pb-0">
                        <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-start p-2" onclick="sortlistByDate(11)">Tanggal (descending)</a></li>
                        <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-end p-2" onclick="sortlistByDate(12)">Tanggal (ascending)</a></li>
                        </ul>
                    </div>
                </div>

            </div>

        <div class="d-none">
            <table id="dg-kms-shadow" class="easyui-datagrid" title="" style="width:100%;" align="center" toolbar="#tb" striped="false" pageSize="100" pageList="[100,200,300,500]" idField="prpk" pagination="true" rownumbers="false" multiple="true" collapsible="true" method="get"
            url="{{ route('get-ppk') }}">
            </table>
        </div>

            <div class="p-0">
                <table id="dg-kms" class="easyui-datagrid" title="" style="width:100%; height:auto;" align="center" toolbar="#tb" striped="false" 
                    pageSize="100" pageList="[100,200,300,500]" idField="kmspk" pagination="true" rownumbers="false" singleSelect="true" 
                    collapsible="false" method="get" url="{{ route('get-ppk')}}" data-options="fitColumns:true, sortable:true,">
                    <thead>
                        <tr>
                            <th field="index" styler="styler1">No</th>
                            <th field="tanggal" styler="styler2">Tanggal</th>
                            <th field="jumlah" styler="styler2">Jumlah</th>
                            <th field="sawal" styler="styler2">Saldo</th>
                            <th field="ket" styler="styler2">Keterangan</th>
                            @if(Session::get('deppk') == 8)
                            <th field="tglsawal" styler="styler2">Tgl Awal</th>
                            <th field="act" styler="styler3" formatter="formatAttribute" align="right">Act</th>
                            @else
                            <th field="tglsawal" styler="styler3">Tgl Awal</th>
                            @endif
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
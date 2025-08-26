@extends('layout.main')

@section('css_custom')
<meta name="csrf-token" content="{{ csrf_token() }}">

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>
    .datagrid-header td,
        .datagrid-body td,
        .datagrid-footer td {
            border-color: #ffffff;
    }

    .datagrid-cell {
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
        $('#dg-style').datagrid('getPanel').find('div.datagrid-header').css('background', '#FFFFFF');
        $('#dg-style').datagrid('getPanel').find('div.datagrid-header').css('border-top', '1px solid #858585');
        $('#dg-style').datagrid('getPanel').find('div.datagrid-header').css('border-bottom', '1px solid #858585');
        $('#dg-style').datagrid('getPanel').find('div.datagrid-header').css('border-top-left-radius', '8px');
        $('#dg-style').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-left-radius', '8px');
        $('#dg-style').datagrid('getPanel').find('div.datagrid-header').css('border-top-right-radius', '8px');
        $('#dg-style').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-right-radius', '8px');
        $('#dg-style').datagrid('getPanel').find('div.datagrid-header').css('border-left', '1px solid #858585');
        $('#dg-style').datagrid('getPanel').find('div.datagrid-header').css('border-right', '1px solid #858585');
    })

    $(function() {
        $('#dg-style-shadow').datagrid({
            onLoadSuccess: function(data) {
                settingTableHeight(data.total);
            },
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

    function formatAttribute(value, row) {
        var hr = '<li><hr class="dropdown-divider mt-1 mb-1"></li>';
        var imageUrl = "{{ asset('public/css/images/More.png') }}"; // Pastikan asset ini tersedia        
        return '<div>' +
        '<button id="button-action-' + (row.index || '') + '" type="button" class="btn dropdown-toggle-custom-sortlist pt-0" data-bs-toggle="dropdown">' +
        '<img src="' + imageUrl + '" height="15px" width="5px">' +
        '</button>' +
        (value || '') +
        '<ul id="dropdown-menu-action-' + (row.index || '') + '" class="dropdown-menu pt-1 pb-1">' +
        '<li><a class="dropdown-item dropdown-item-custom" id="btn-detail-' + (row.index || '') + '" onclick="KlikDetail(' + (row.ttpk || 'null') + ');" style="color:#359DD9;">Detail</a></li>' +
        hr +
        '<li><a class="dropdown-item dropdown-item-custom" id="btn-detail-' + (row.index || '') + '" onclick="KlikPrint(' + (row.ttpk || 'null') + ');" style="color:#359DD9;">Print</a></li>' +
        '</ul>' +
        '</div>';
    }

    function KlikDetail(ttpk) {
        const baseUrl = "{{ url('/')}}";
        window.location.href = baseUrl + "/tanda-terima/detail/" + ttpk;
    }

    function KlikPrint(ttpk) {
        if (!ttpk) {
            alert('PTTPK not found');
            return;
        }

        var baseUrl = "{{ url('/') }}";
        var url = baseUrl + "/tanda-terima/print/" + ttpk;
        window.open(url, '_blank');
    }

    function loadDataByFilter() {
        // var searchByInput = $('#searchByInput').val();
        $('#dg-style-shadow').datagrid('load', {
                searchByInput: $('#searchByInput').val(),
                filterByYear: $('#filterByYear').val(),
                filterByMonth: $('#filterByMonth').val(),
                sortlistByDate: $('#sortlistByDate').val(),
        });

        $('#dg-style').datagrid('load', {
                searchByInput: $('#searchByInput').val(),
                filterByYear: $('#filterByYear').val(),
                filterByMonth: $('#filterByMonth').val(),
                sortlistByDate: $('#sortlistByDate').val(),
        });
    }

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

    function AddTT(){
        $.ajax({
            type: "POST",
            url: "{{ route('get.lasttt') }}",
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(data) {
                const baseUrl = "{{ url('/')}}";
                window.location.href = baseUrl+"/tanda-terima/add-tt/"+data.ttpk;
            },
            error: function(error) {
                console.log(error);
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
                <span class="txt-000000-700-24-28">List Tanda Terima Penerimaan Barang</span>
            </div>


            <div class="d-flex flex-wrap p-0 pb-2 gap-2 align-items-center">

                <div class="p-0">
                    <div class="input-group flex-nowrap input-group-search" style="height:34px;">
                        <span class="input-group-text search">
                            <img src="{{ asset('public/css/images/Shape.png') }}" plain="true" width="18px" height="18px">
                        </span>
                        <input type="text" class="form-control search" id="searchByInput" onkeyup="loadDataByFilter()" autocomplete="off" placeholder="Search...">
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
                        style="width:100px; height:34px;">
                        @for ($i = date('Y'); $i >= date('Y') - 7; $i -= 1)
                            <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        @endfor
                    </select>
                </div>
                <button onclick="AddTT();" type="button" class="btn btn-dark" style="height:30px; line-height:30px; padding:0 10px;">&#10010; Data</button>

                <div class="ms-auto">
                    <div id="sortlist-grup" class="p-1 pb-0 responsive">
                        <input type="hidden" id="sortlistByDate" name="sortlistByDate" size="5">
                        <button class="btn btn-sortlist dropdown-toggle-custom-sortlist pt-0" data-bs-toggle="dropdown"
                            data-bs-target="#menu-sortlist" aria-expanded="true">
                            <img src="{{ asset('public/css/images/sort.jpg') }}" alt="" class="imgsort">
                            <span id="Invdatedesc">Tanggal (desc)</span>
                            <span id="Invdateasc" class="d-none">Tanggal (asc)</span>
                        </button>
                        <ul id="menu-sortlist"
                            class="dropdown-menu dropdown-menu-end dropdown-menu-custom-global dropdown-menu-sortlist pt-0 pb-0">
                            <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-start p-2"
                                    onclick="sortlistByDate(11)">Tanggal (descending)</a></li>
                            <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-end p-2"
                                    onclick="sortlistByDate(12)">Tanggal (ascending)</a></li>
                        </ul>
                    </div>
                </div>
            </div>


            <div class="d-none">
                <table id="dg-style-shadow" class="easyui-datagrid" title="" style="width:100%;" align="center"
                    toolbar="#tb" striped="false" pageSize="100" pageList="[100,200,300,500]" idField="prpk"
                    pagination="true" rownumbers="false" multiple="true" collapsible="true" method="get"
                    url="{{ route('get.tt') }}">
                </table>
            </div>

            <div class="p-0">
                <table id="dg-style" class="easyui-datagrid" url="{{ route('get.tt')}}" title="" style="width:99%;"
                    align="center" toolbar="#tb" striped="false" pageSize="100" pageList="[100,200,300,500]"
                    idField="belidtpk" pagination="true" rownumbers="false" singleSelect="true" collapsible="true"
                    method="get" data-options="border:false">
                    <thead>
                        <tr>
                            <th field="index" width="30" styler="styler1">No</th>
                            <th field="nobukti" styler="styler2">No Bukti</th>
                            <th field="tanggal" styler="styler2">Tanggal</th>
                            <th field="penerima" styler="styler2">Peminta</th>
                            <th field="ket" styler="styler2">Keterangan</th>
                            <th field="act" styler="styler3" formatter="formatAttribute">Act</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
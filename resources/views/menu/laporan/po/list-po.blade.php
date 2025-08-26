@extends('layout.main')

@section('css_custom')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

<style>

</style>
@endsection

@section('js_custom')
<script>
    $(function() {
        $('#dg-po-shadow').datagrid('getPanel').find('div.datagrid-view').css('display', 'none');
        $('#dg-po-shadow').datagrid('getPanel').find('div.datagrid-pager').css('display', 'none');

        var dgspb = $('#dg-po').datagrid();
        dgspb.datagrid('getPanel').find('div.datagrid-header').css('background', 'transparent');
        dgspb.datagrid('getPanel').find('div.datagrid-header').css('border', '1px solid #858585');
        dgspb.datagrid('getPanel').find('div.datagrid-header').css('border-radius', '8px');
        dgspb.datagrid('getPanel').find('div.datagrid-header td[field]').css('border-right', 'none');
        dgspb.datagrid('getPanel').find('div.datagrid-body').css('overflow-y', 'hidden');
        dgspb.datagrid('getPanel').css('border', 'none');
        dgspb.datagrid('getPanel').addClass('lines-no');
        dgspb.datagrid('getPanel').addClass('lines-no3');
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
        // var SelectStart = $('#SelectStart').datebox('getValue');
        // var SelectFinish = $('#SelectFinish').datebox('getValue');
        var filterByYear = $('#filterByYear').val();
        var filterByMonth = $('#filterByMonth').val();

        console.log(`searchByInput : ${searchByInput}`);
        $('#dg-po-shadow').datagrid('load', {
            searchByInput: searchByInput,
            // SelectStart: SelectStart,
            // SelectFinish: SelectFinish,
            filterByYear: filterByYear,
            filterByMonth: filterByMonth,
        });

        $('#dg-po').datagrid('load', {
            searchByInput: searchByInput,
            // SelectStart: SelectStart,
            // SelectFinish: SelectFinish,
            filterByYear: filterByYear,
            filterByMonth: filterByMonth,
        })
        console.log(`loadDataByFilter END >>>>>>>>>>>>>>`);
    }

    $(function() {
        $('#dg-po-shadow').datagrid({
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
            $('#dg-po').datagrid('getPanel').find('div.datagrid-view2').css('height', windowHeight);
        } else {
            console.log("here auto");
            $('#dg-po').datagrid('getPanel').find('div.datagrid-view2').css('height', 'auto');
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

    function checkFinishDate() {
        var finishVal = $('#SelectFinish').datebox('getValue');
        if (finishVal === '') {
            $('#get_print').prop('disabled', true).addClass('disabled');
        } else {
            $('#get_print').prop('disabled', false).removeClass('disabled');
        }
    }

    $(document).ready(function () {
        // Inisialisasi ulang datebox dengan semua opsi (formatter, parser, onChange)
        $('#SelectFinish').datebox({
            formatter: myformatter2,
            parser: myparser2,
            onChange: function () {
                loadDataByFilter();
                checkFinishDate();
            }
        });
        checkFinishDate(); // Jalankan saat halaman pertama dimuat
    });

    // function KlikPrint() {
    //     const params = {
    //         searchByInput: $('#searchByInput').val(),
    //         SelectStart: formatToIsoPdf($('#SelectStart').datebox('getValue')),
    //         SelectFinish: formatToIsoPdf($('#SelectFinish').datebox('getValue')),
    //     };

    //     const query = Object.entries(params)
    //         .map(([key, val]) => `${key}=${encodeURIComponent(val)}`)
    //         .join('&');
    //     window.location.href = `{{ route('pdf.lap-pr') }}?${query}`;
    // }


    function KlikPrintKe2() {

        const params = {
            searchByInput: $('#searchByInput').val(),
            filterByMonth: $('#filterByMonth').val(),
            filterByYear: $('#filterByYear').val(),
        };

        const query = Object.entries(params)
            .map(([key, val]) => `${key}=${encodeURIComponent(val)}`)
            .join('&');
        // window.location.href = `{{ route('pdf.lap-po-ke2') }}?${query}`;
            window.open(`{{ route('pdf.lap-po-ke2') }}?${query}`, '_blank');
    }

</script>
@endsection

@section('content')
<div class="main-wrapper">
    <div class="container-fluid p-4">
        <div class="d-flex flex-column">
            <div class="p-2">
                <span class="txt-000000-700-24-28">Laporan Purchase Order</span>
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

                <!-- <div class="p-0">
                    Periode: <input id="SelectStart" name="SelectStart" class="easyui-datebox"
                        style="width:110px; height:28px;"
                        data-options="formatter:myformatter2, parser:myparser2, onChange:loadDataByFilter,">
                </div>
                <div class="p-0">
                    Sampai: <input id="SelectFinish" name="SelectFinish" class="easyui-datebox"
                        style="width:110px; height:28px;"
                        data-options="formatter:myformatter2, parser:myparser2, onChange:loadDataByFilter,">
                </div>

                <button type="button" id="get_print" class="btn btn-dark my-1 py-1" onclick="KlikPrint()">
                    <i class="fa fa-print"></i> Print
                </button> -->

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
                    style="width:100px; height:34px;">
                    <?php
                    for ($i = date('Y'); $i >= date('Y') - 6; $i -= 1) { ?>
                    <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                    <?php } ?>
                </select>
                </div>

                 <button type="button" id="get_print" class="btn btn-dark my-1 py-1" onclick="KlikPrintKe2()">
                    <i class="fa fa-print"></i> Print
                </button>

            </div>

            <div class="d-none">
                <table id="dg-po-shadow" class="easyui-datagrid" title="" style="width:100%;" align="center"
                    toolbar="#tb" striped="false" pageSize="100" pageList="[100,200,300,500]" idField="prpk"
                    pagination="true" rownumbers="false" multiple="true" collapsible="true" method="get"
                    url="{{ route('get.lap-po') }}">
                </table>
            </div>

            <div class="p-0">
                <table id="dg-po" class="easyui-datagrid" url="{{ route('get.lap-po')}}" title="" style="width:99%;"
                    align="center" toolbar="#tb" striped="false" pageSize="100" pageList="[100,200,300,500]"
                    idField="podtpk" pagination="true" rownumbers="false" singleSelect="true" collapsible="true"
                    method="get" data-options="border:false">
                    <thead>
                        <tr>
                            <th field="index" styler="styler1">No</th>
                            <th field="tanggal" styler="styler2">Tgl. PO</th>
                            <th field="nobukti" styler="styler2">No Bukti</th>
                            <!-- <th field="nopo" styler="styler2">No Po</th> -->
                            <th field="term" styler="styler2">Term</th>
                            <th field="curid" styler="styler2">MU</th>
                            <th field="supnm" styler="styler2">Supplier</th>
                            <th field="brgnm" styler="styler2" width="auto">Nama Barang</th>
                            <th field="unit" styler="styler2">Satuan</th>
                            <th field="jmlbeli" styler="styler2">Qty</th>
                            <th field="hrgbeli" styler="styler2">Harga Satuan</th>
                            <th field="hrgtotal" styler="styler2">Harga Total</th>
                            <th field="dt_user" styler="styler3">User</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
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
        $('#dg-pr-shadow').datagrid('getPanel').find('div.datagrid-view').css('display', 'none');
        $('#dg-pr-shadow').datagrid('getPanel').find('div.datagrid-pager').css('display', 'none');

        var dgspb = $('#dg-pr').datagrid();
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
        var SelectStart = $('#SelectStart').datebox('getValue');
        var SelectFinish = $('#SelectFinish').datebox('getValue');

        console.log(`searchByInput : ${searchByInput}`);
        $('#dg-pr-shadow').datagrid('load', {
            searchByInput: searchByInput,
            SelectStart: SelectStart,
            SelectFinish: SelectFinish,
        });

        $('#dg-pr').datagrid('load', {
            searchByInput: searchByInput,
            SelectStart: SelectStart,
            SelectFinish: SelectFinish,
        })
        console.log(`loadDataByFilter END >>>>>>>>>>>>>>`);
    }

    $(function() {
        $('#dg-pr-shadow').datagrid({
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
            $('#dg-pr').datagrid('getPanel').find('div.datagrid-view2').css('height', windowHeight);
        } else {
            console.log("here auto");
            $('#dg-pr').datagrid('getPanel').find('div.datagrid-view2').css('height', 'auto');
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

    function KlikPrint() {
        const params = {
            searchByInput: $('#searchByInput').val(),
            SelectStart: formatToIsoPdf($('#SelectStart').datebox('getValue')),
            SelectFinish: formatToIsoPdf($('#SelectFinish').datebox('getValue')),
        };

        const query = Object.entries(params)
            .map(([key, val]) => `${key}=${encodeURIComponent(val)}`)
            .join('&');
        window.location.href = `{{ route('pdf.lap-pr') }}?${query}`;
    }

</script>
@endsection

@section('content')
<div class="main-wrapper">
    <div class="container-fluid p-4">
        <div class="d-flex flex-column">
            <div class="p-2">
                <span class="txt-000000-700-24-28">Laporan Purchase Request</span>
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
                </button>

            </div>

            <div class="d-none">
                <table id="dg-pr-shadow" class="easyui-datagrid" title="" style="width:100%;" align="center"
                    toolbar="#tb" striped="false" pageSize="100" pageList="[100,200,300,500]" idField="prpk"
                    pagination="true" rownumbers="false" multiple="true" collapsible="true" method="get"
                    url="{{ route('get.lap-pr') }}">
                </table>
            </div>

            <div class="p-0">
                <table id="dg-pr" class="easyui-datagrid" url="{{ route('get.lap-pr')}}" title="" style="width:99%;"
                    align="center" toolbar="#tb" striped="false" pageSize="100" pageList="[100,200,300,500]"
                    idField="prdtpk" pagination="true" rownumbers="false" singleSelect="true" collapsible="true"
                    method="get" data-options="border:false">
                    <thead>
                        <tr>
                            <th field="index" styler="styler1">No</th>
                            <th field="tanggal" styler="styler2">Tgl. PR</th>
                            <th field="noinv" styler="styler2">No PR</th>
                            <th field="brgnm" styler="styler2">Nama Barang</th>
                            <th field="jmlbeli" styler="styler2">Qty</th>
                            <th field="unit" styler="styler2">Satuan</th>
                            <th field="pengirim" styler="styler2">Pengirim</th>
                            <th field="penerima" styler="styler3">Penerima</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
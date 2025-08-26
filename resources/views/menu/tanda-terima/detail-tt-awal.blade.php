@extends('layout.main')

@section('css_custom')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
    .datagrid-header td,
        .datagrid-body td,
        .datagrid-footer td {
            border-color: #ffffff;
    }

    .panel-header, .panel-body{
          border-color: #ffffff;
    }
</style>
@endsection

@section('js_custom')
    <script>
        $(function() {
            $('#dg').datagrid('getPanel').find('div.datagrid-header').css('background', '#FFFFFF');
            $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-top', '1px solid #858585');
            $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-bottom', '1px solid #858585');
            $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-top-left-radius', '8px');
            $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-left-radius', '8px');
            $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-top-right-radius', '8px');
            $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-right-radius', '8px');
            $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-left', '1px solid #858585');
            $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-right', '1px solid #858585');
        });

        function myformatter(date) {
            var d = new Date(date || Date.now()),
                month = '' + (d.getMonth() + 1),
                day = '' + d.getDate(),
                year = d.getFullYear();
            if (month.length < 2) month = '0' + month;
            if (day.length < 2) day = '0' + day;
            return [day, month, year].join('/');
        }

        function myparser(s) {
            if (!s) return new Date();
            var ss = (s.split('/'));
            var y = parseInt(ss[0], 10);
            var m = parseInt(ss[1], 10);
            var d = parseInt(ss[2], 10);
            if (!isNaN(y) && !isNaN(m) && !isNaN(d)) {
                return new Date(d, m - 1, y);
            } else {
                return new Date();
            }
        }

        function GoBack() {
            window.location.href = "{{ URL::to('tanda-terima') }}";
        }

        function styler1(value, row, index) {
            return 'border-top:1px solid #ededed;border-bottom:3px solid #ededed;border-left:1px solid #ededed;border-top-left-radius:5px;' +
                'border-bottom-left-radius:5px;height:35px;';
        }

        function styler2(value, row, index) {
            return 'border-top:1px solid #ededed;border-bottom:3px solid #ededed;';
        }

        function styler3(value, row, index) {
            return 'border-top:1px solid #ededed;border-bottom:3px solid #ededed;border-right:1px solid #ededed !important;border-top-right-radius:5px;' +
                'border-bottom-right-radius:5px;';
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
    </script>
@endsection

@section('content')
    <div class="main-wrapper min-vh-100">
        <div class="container-fluid">
            <div class="d-flex flex-column p-4 m-3">
                
                <div class="d-flex gap-3">

                    <div class="d-flex pointer" onclick="GoBack()">
                        <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                    </div>


                    <div class="d-flex flex-column flex-fill mb-3">
                        <div class="d-flex justify-content-between">
                            <div class="d-flex fw-bold">
                                No. Bukti : {{ $dt_tt->nobukti }}
                            </div>

                            <div class="d-flex justify-content-end ">
                                <button class="btn-black rounded" onclick="KlikPrint({{ $dt_tt->ttpk }});">
                                    <i class="fa-solid fa-print"></i> Print
                                </button>
                            </div>
                        </div>


                        <div class="d-flex my-3 d-none">
                            <div class="d-flex flex-column flex-fill">
                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">No Bukti</div>
                                    <input id="nobukti" class="easyui-validatebox height_input disabled" name="penerima" type="text" value="{{ $dt_tt->nobukti }}">
                                </div>

                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Tgl</div>
                                    <input id="tgl" name="tgl" class="easyui-datebox" style="width:210px;"
                                        placeholder="YYYY-MM-DD" data-options="formatter:myformatter, parser:myparser"
                                        value="{{ \Carbon\Carbon::parse($dt_tt->tgl)->format('d/m/Y') }}">
                                </div>
                            </div>

                            <div class="d-flex flex-column flex-fill">

                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Peminta</div>
                                    <input id="penerima" class="easyui-validatebox height_input" name="penerima" type="text" value="{{ $dt_tt->penerima }}">
                                </div>

                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Keterangan</div>
                                    <input id="ket" class="easyui-validatebox height_input" name="ket" type="text" value="{{ $dt_tt->ket }}">
                                </div>


                            </div>
                        </div>
                    </div>
                </div>


                <div class="p-0">
                    <table id="dg" class="easyui-datagrid" title="" toolbar="#tb"
                        pagination="false" method="get" url="{{ route('get.detail-tt', $dt_tt->ttpk)}}" rownumbers="false"
                        pageList="[100,200,300,500]" pageSize="100" singleSelect="true" collapsible="true"
                        fitColumns="true" idField="ttdtpk">
                        <thead>
                            <tr>
                                <th field="index" width="3%" styler="styler1">No</th>
                                <th field="brgnm" width="auto" styler="styler2">Keterangan</th>
                                <th field="jumlah" width="auto" styler="styler2">Qty</th>
                                <th field="satuan" width="auto" styler="styler3">Satuan</th>
                            </tr>
                        </thead>
                    </table>
                </div>


            </div>
        </div>
    </div>
@endsection

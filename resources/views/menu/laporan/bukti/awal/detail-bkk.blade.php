@extends('layout.main')

@section('css_custom')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
     /*tambahan css header*/
    .datagrid-body {
        overflow-x: hidden;
        overflow-y: hidden;
    }

    .datagrid-cell {
        font-family: 'Arial';
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

    .lines-no .datagrid-body td {
        border-right: 1px dotted transparent;
        border-bottom: 1px dotted transparent;
    }

    .lines-no3 .datagrid-body td {
        border-right: 1px dotted transparent;
        border-bottom: 1px dotted transparent;
    }

    .datagrid-header td, .datagrid-body td, .datagrid-footer td{
        border-color: white;
    }
        .panel-header,
    .panel-body {
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

        $('#dg').datagrid({
            showFooter: true
        });
    });


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

    function KlikPrint(){
        window.open(`{{ route('pdf.bkk.awal', ['tglInv' => $tglInv, 'suppk' => $suppk]) }}`, '_blank');
    }
</script>
@endsection

@section('content')

<div class="main-wrapper min-vh-100">
    <div class="container-fluid">

        <div class="d-flex flex-column p-4 m-3">

            <div class="p-0">
                <div class="d-flex flex-wrap p-0 pb-2 gap-2 align-items-center">
                    <button type="button" id="get_print" class="btn btn-sm btn-dark my-1 py-1"
                        onclick="KlikPrint()"><i class="fa fa-print"></i> Print</button>
                </div>

                <table id="dg" class="easyui-datagrid" style="height:auto;" url="{{ route('get.detail-bkk.awal', ['tglInv' => $tglInv, 'suppk' => $suppk]) }}" 
                        idField="belidtpk" method="get" data-options="border:false" singleSelect="true" fitColumns="false">
                                   <!-- showFooter="true" data-options="multiSort:false, remoteSort:false, border:false"> -->
                    <thead>
                        <tr>
                            <!-- <th field="belipk" width="30" styler="styler1">Belipk</th>
                            <th field="belidtpk" width="30" styler="styler1">Belidtpk</th> -->
                            <th field="index" width="20" styler="styler1">No</th>
                            <th field="nobukti" width="auto" styler="styler2">No Bukti</th>
                            <th field="tglinv" width="auto" styler="styler2">Tgl Inv</th>
                            <th field="noinv" width="auto" styler="styler2">No Inv/Po</th>
                            <th field="brgnm" width="auto" styler="styler2">Keterangan</th>
                            <th field="jmlbeli" width="auto" styler="styler2">Qty</th>
                            <th field="unit" width="auto" styler="styler2">Satuan</th>
                            <th field="hrgbeli" width="auto" styler="styler2">Harga Satuan</th>
                            <th field="jmlhrg" width="auto" styler="styler2">Total</th>
                            <th field="kelnm" width="auto" styler="styler3">Group</th>
                        </tr>
                    </thead>
                </table>

            </div>

        </div>
    </div>
</div>
@endsection
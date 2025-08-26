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


    function KlikPrint() {
        var tglInv = '{{ $tglInv }}';
        var suppk = $('#filterBySupplier').combobox('getValue');
        
        if (suppk && tglInv) {
            // Bangun URL print berdasarkan nilai yang dipilih
            var printUrl = "{{ route('pdf.detail.bkk', ['tglInv' => 'TEMP_TGLINV', 'suppk' => 'TEMP_SUPPK']) }}"
                .replace('TEMP_TGLINV', encodeURIComponent(tglInv))
                .replace('TEMP_SUPPK', encodeURIComponent(suppk));
            
            window.open(printUrl, '_blank');
        } else {
            alert('Silakan pilih supplier terlebih dahulu!');
        }
    }

    function loadDataByFilter() {
        var tglInv = '{{ $tglInv }}';
        var suppk = $('#filterBySupplier').combobox('getValue');

        console.log('loadDataByFilter called');
        console.log('tglInv:', tglInv);
        console.log('suppk:', suppk);

        if (suppk && tglInv) {
            // Bangun URL sesuai dengan route yang ada
            var baseUrl = "{{ route('get.detail-bkk', ['tglInv' => 'TEMP_TGLINV', 'suppk' => 'TEMP_SUPPK']) }}";
            var newUrl = baseUrl
                .replace('TEMP_TGLINV', encodeURIComponent(tglInv))
                .replace('TEMP_SUPPK', encodeURIComponent(suppk));
            
            console.log('URL baru:', newUrl);

            $('#dg').datagrid('options').url = newUrl;
            $('#dg').datagrid('reload');
        }
    }

    $(document).ready(function () {
        // Inisialisasi combobox
        $('#filterBySupplier').combobox({
            valueField: 'suppk',
            textField: 'supnm',
            panelHeight: 'auto',
            limitToList: true,
            method: 'get',
            url: '{{ route('get.supp-bkk', ['tglInv' => $tglInv]) }}',
            onChange: loadDataByFilter,
            onLoadSuccess: function() {
                var pathParts = window.location.pathname.split('/');
                var suppkFromURL = pathParts[pathParts.length - 1]; // Ambil bagian terakhir URL
                console.log('suppkFromURL:', suppkFromURL);

                if (suppkFromURL && suppkFromURL !== '2') { // Pastikan bukan bagian dari path tetap
                    $('#filterBySupplier').combobox('setValue', suppkFromURL);
                    loadDataByFilter();
                }
            }
        });
    });

    function GoBack() {
        window.location.href = "{{ URL::to('/laporan/bukti-kas-keluar')}}";
    }

    function KlikDetail(belipk){
        $.ajax({
        url: '/finance/get-detail-beli/' + belipk,
        method: 'GET',
        success: function(response) {
            console.log("Response received:", response);
            if (response.success && response.data.length > 0) {
            const DataDetailnya = response.data[0];
            
            console.log("Data details:", DataDetailnya);
            $('#belipk').val(DataDetailnya.belipk || '');
            $('#nobukti').val(DataDetailnya.nobukti || '');
            $('#noinv').val(DataDetailnya.noinv || '');
            $('#untuk').val(DataDetailnya.untuk || '');
            $('#tglinv').val(DataDetailnya.tglinv || '');
            $('#supnm').val(DataDetailnya.supnm || '');
            $('#abnm').val(DataDetailnya.abnm || '');
            $('#kelnm').val(DataDetailnya.kelnm || '');
            $('#total').val(DataDetailnya.total || '');
            $('#brgnm').val(DataDetailnya.brgnm || '');
            $('#satuan').val(DataDetailnya.satuan || '');
            $('#qty').val(DataDetailnya.qty || '');
            $('#hrg_satuan').val(DataDetailnya.hrg_satuan || '');
            $('#jml_hrg').val(DataDetailnya.jml_hrg || '');
            $('#tgl_byr').val(DataDetailnya.tgl_byr || '');
            $('#jml_byr').val(DataDetailnya.jml_byr || '');
            $('#cg').val(DataDetailnya.cg || '');

            $('#ModalKlikDetail').modal('show');
            } else {
            alert('Data detail tidak ditemukan');
            }
        },
        error: function(error) {
            console.error("Gagal mengambil data detail:", error);
            alert('Terjadi kesalahan saat mengambil data');
        }
        });
    }

</script>
@endsection

@section('content')

<div class="main-wrapper min-vh-100">
    <div class="container-fluid">

        <div class="d-flex flex-column p-4 m-3">

            <div class="p-0">
                <div class="d-flex flex-wrap p-0 pb-2 gap-3 align-items-center">
                    
                    <div class="d-flex pointer" onclick="GoBack()">
                        <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                    </div>

                    <button type="button" id="get_print" class="btn btn-sm btn-dark my-1 py-1"
                        onclick="KlikPrint()"><i class="fa fa-print"></i> Print
                    </button>

                    <input id="filterBySupplier" class="easyui-combobox"
                        data-options="
                            valueField:'suppk',
                            textField:'supnm',
                            panelHeight:'auto',
                            limitToList:true,
                            url:'{{ route('get.supp-bkk', ['tglInv' => $tglInv]) }}',
                            onChange:loadDataByFilter,
                            onLoadSuccess:function(){
                                var suppkFromURL = window.location.pathname.split('/')[6];
                                if(suppkFromURL){
                                    $('#filterBySupplier').combobox('setValue', suppkFromURL);
                                    loadDataByFilter();
                                }
                            }
                        ">

                </div>

                <table id="dg" class="easyui-datagrid" style="height:auto;" idField="belidtpk" method="get" url="{{ route('get.detail-bkk', ['tglInv' => $tglInv, 'suppk' => 'TEMP_SUPPK']) }}" data-options="border:false, singleSelect:true, fitColumns:false">
                    <thead>
                        <tr>
                            <th field="index" width="35" styler="styler1">No</th>
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

    <div class="modal fade" id="ModalKlikDetail" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="border:none;">
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="tgl" class="col-form-label">No Bukti:</label>
                            <input type="text" class="form-control" id="nobukti" disabled>
                            <!-- <span id="ermsg-tgl" class="text-danger text-blink"></span> -->
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="noinv" class="col-form-label">No Invoice:</label>
                            <input type="text" class="form-control" id="noinv" disabled>
                            <!-- <span id="ermsg-jumlah" class="text-danger text-blink"></span> -->
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="untuk" class="col-form-label">Untuk:</label>
                            <input type="text" class="form-control" id="untuk" disabled>
                            <!-- <span id="ermsg-jumlah" class="text-danger text-blink"></span> -->
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="tglinv" class="col-form-label">Tgl Invoice:</label>
                            <input type="text" class="form-control" id="tglinv" disabled>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="supnm" class="col-form-label">Supplier:</label>
                            <input type="text" class="form-control" id="supnm" disabled>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="abnm" class="col-form-label">Term:</label>
                            <input type="text" class="form-control" id="abnm" disabled>
                        </div>
                        
                        <div class="col-md-4 mb-3">
                            <label for="kelnm" class="col-form-label">Jenis:</label>
                            <input type="text" class="form-control" id="kelnm" disabled>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="total" class="col-form-label">Total:</label>
                            <input type="numeric" class="form-control" id="total" disabled>
                        </div>

                        <div class="col-md-4 mb-3">
                            <label for="sawal" class="col-form-label">&nbsp;</label><br>
                            <button type="button" class="btn btn-success">Bayar</button>
                        </div>

                    </div>

                    <div class="row ps-3">
                        <table class="table table-hover">

                            <thead>
                                <tr>
                                <!-- <th scope="col">No</th> -->
                                <th scope="col">Keterangan</th>
                                <th scope="col">Satuan</th>
                                <th scope="col">Qty</th>
                                <th scope="col">Harga Satuan</th>
                                <th scope="col">Jumlah Harga</th>
                                <th scope="col">Tanggal Bayar</th>
                                <th scope="col">Jumlah Bayar</th>
                                <th scope="col">C/G</th>

                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <!-- <td>1</td> -->
                                    <td><input type="text" class="form-control" id="brgnm" disabled></td>
                                    <td><input type="text" class="form-control" id="satuan" disabled></td>
                                    <td><input type="text" class="form-control" id="qty" disabled></td>
                                    <td><input type="text" class="form-control" id="hrg_satuan" disabled></td>
                                    <td><input type="text" class="form-control" id="jml_hrg" disabled></td>
                                    <td><input type="text" class="form-control" id="tgl_byr" disabled></td>
                                    <td><input type="text" class="form-control" id="jml_byr" disabled></td>
                                    <td><input type="text" class="form-control" id="cg" disabled></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>                
            </div>
        </div>
    </div>

</div>
@endsection
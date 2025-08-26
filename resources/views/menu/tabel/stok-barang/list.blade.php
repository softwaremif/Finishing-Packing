@extends('layout.main')

@section('css_custom')
<style>

    .datagrid-cell {
        font-style: normal;
        font-weight: 400;
        font-size: 13px;
        line-height: 18px;
        white-space: normal;
    }


    .datagrid-body {
        margin: 0;
        padding: 0;
        overflow: hidden;
        zoom: 1;
    }

    .datagrid-row {
        height: auto !important;
        white-space: normal !important;
    }

        .datagrid-header td,
        .datagrid-body td,
        .datagrid-footer td {
            border-color: #ffffff;
    }

    .panel-header, .panel-body{
          border-color: #ffffff;
    }

    .textbox {
        border: 1px solid #000000ff
    }

</style>
@endsection

@section('js_custom')
<script>
    $(function() {
        $('#dg-barang-shadow').datagrid('getPanel').find('div.datagrid-view').css('display', 'none');
        $('#dg-barang-shadow').datagrid('getPanel').find('div.datagrid-pager').css('display', 'none');

        var dgang = $('#dg-barang').datagrid();
        dgang.datagrid('getPanel').find('div.datagrid-header').css('background', 'transparent');
        dgang.datagrid('getPanel').find('div.datagrid-header').css('border', '1px solid #858585');
        dgang.datagrid('getPanel').find('div.datagrid-header').css('border-radius', '8px');
        dgang.datagrid('getPanel').find('div.datagrid-header td[field]').css('border-right', 'none');
        // dgang.datagrid('getPanel').find('div.datagrid-body').css('overflow-y', 'hidden');
        // dgang.datagrid('getPanel').find('div.datagrid-body').css('overflow-x', 'hidden');
        dgang.datagrid('getPanel').css('border', 'none');
        dgang.datagrid('getPanel').addClass('lines-no');
        dgang.datagrid('getPanel').addClass('lines-no3');

        $('#dg-barang-mobile').datagrid('getPanel').find('div.datagrid-header').css('visibility', 'hidden');
        $('#dg-barang-mobile').datagrid('getPanel').find('div.datagrid-header').addClass('h-0');
        $('#dg-barang-mobile').datagrid('getPanel').css('border', 'none');
        $('#dg-barang-mobile').datagrid('getPanel').addClass('lines-no');
        $('#dg-barang-mobile').datagrid('getPanel').addClass('lines-no3');
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

        console.log(`searchByInput : ${searchByInput}`);
        $('#dg-barang-shadow').datagrid('load', {
            searchByInput: searchByInput,
            sortlistByDate: sortlistByDate,
        });

        $('#dg-barang').datagrid('load', {
            searchByInput: searchByInput,
            sortlistByDate: sortlistByDate,
        })
        console.log(`loadDataByFilter END >>>>>>>>>>>>>>`);
    }

    $(function() {
        $('#dg-barang-shadow').datagrid({
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
        $('#dg-barang').datagrid('getPanel').find('div.datagrid-view2').css('height', windowHeight);
        $('#dg-barang-mobile').datagrid('getPanel').find('div.datagrid-view2').css('height', windowHeight);
        } else {
        console.log("here auto");
        $('#dg-barang').datagrid('getPanel').find('div.datagrid-view2').css('height', 'auto');
        $('#dg-barang-mobile').datagrid('getPanel').find('div.datagrid-view2').css('height', 'auto');
        }
    }

    //FILTER SORT
    function sortlistByDate(value) {
        document.getElementById('sortlistByDate').value = value;
        if (value == 11) {
        $('#KodeDesc').removeClass('d-none');
        $('#KodeAsc').addClass('d-none');
        } else if (value == 12) {
        $('#KodeDesc').addClass('d-none');
        $('#KodeAsc').removeClass('d-none');
        } else {
        $('#KodeDesc').removeClass('d-none');
        $('#KodeAsc').addClass('d-none');
        }
        loadDataByFilter();
    }

    function formatAttribute(value, row, index) {
        return `<a type="button" class="txt-359DD9-400-14-18-100" onclick="onEditData('${row.stokpk}')">Edit</a>`;
    }

    function AddData() {
        console.log(`onAddData is running...`);
        $('#ModalCreateOrEditBarang').modal('show');
        $('.barang-create').removeClass('d-none');
        $('.barang-edit').addClass('d-none');
    }

    function onEditData(stokpk) {
        console.log(`onEditData is running...`);

        $('#ModalCreateOrEditBarang').modal('show');

        $('.barang-edit').removeClass('d-none');
        $('.barang-create').addClass('d-none');

        let url = "{{ url('get-stok-barang/detail/:stokpk') }}";
        url = url.replace(':stokpk', stokpk);

        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                console.log(`success onEditData here >>>>>`);
                console.log(response);
                $('#stokpk').val(response.data.stokpk);
                $('#stokid').val(response.data.stokid);
                $('#stoknm').val(response.data.stoknm);
                // $('#merk').val(response.data.merk);
                // $('#codebrg').val(response.data.codebrg);
                $('#satuan').val(response.data.satuan);
                $('#sawal').val(response.data.sawal);
                $('#masuk').val(response.data.masuk);
                $('#keluar').val(response.data.keluar);
                $('#sakhir').val(response.data.sakhir);
                $('#jnsbrg').combobox('setValue', response.data.jnsbrgpk);
            },
            error: function(xhr, status, error) {
                console.log(`error onEditData here >>>>>`);
                console.log(xhr);
            }
        })
    }

    function onCancelData() {
        resetData();
        $('#ModalCreateOrEditBarang').modal('hide');
        $('#formCreateOrEditBarang').form('reset');
    }

    function resetData() {
        $('#stokpk').val(null);
        $('#ermsg-stoknm').text(null);
        $('#loading-edit-barang').addClass('d-none');
        $('#loading-create-barang').addClass('d-none');
    }

    function validateListData() {
        console.log(`validateListData is running...`);

        var stoknm = $('#stoknm').val();
        if (!stoknm) $('#ermsg-stoknm').text('Nama Barang wajib diisi')

        setTimeout(() => {
            $('#ermsg-stoknm').text(null);
        }, 5000);

        if ((!stoknm)) return false;

        console.log(`validateListData is valid`);

        return true;
    }

    function onSubmitData() {
        console.log(`onSubmitData is running...`);

        if (!validateListData()) return;

        $('#loading-create-barang').removeClass('d-none');

        let url = "{{ url('get-stok-barang/store') }}";
        console.log(`url: ${url}`);

        let formdata = new FormData();
        // formdata.append('stokid', $('#stokid').val());
        formdata.append('stoknm', $('#stoknm').val());
        // formdata.append('merk', $('#merk').val());
        // formdata.append('codebrg', $('#codebrg').val());
        formdata.append('satuan', $('#satuan').val());
        formdata.append('sawal', $('#sawal').val());
        formdata.append('masuk', $('#masuk').val());
        formdata.append('keluar', $('#keluar').val());
        formdata.append('sakhir', $('#sakhir').val());
        let jnsbrgpk = $('#jnsbrg').combobox('getValue');
        formdata.append('jnsbrgpk', jnsbrgpk);

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
                $('#dg-barang').datagrid('reload');
                showAlert(200, response.message);
            },
            error: function(xhr, status, error) {
                console.log(`error onSubmitData here >>>>>`);
                console.log(xhr);
            }
        });
    }

    function onUpdateData() {
        console.log(`onUpdateData is running...`);

        var stokpk = $('#stokpk').val();
        if (!stokpk) return;

        if (!validateListData()) return;

        $('#loading-edit-barang').removeClass('d-none');

        let url = "{{ url('get-stok-barang/update/:stokpk') }}";
        url = url.replace(':stokpk', stokpk);
        console.log(`url: ${url}`);

        let formdata = new FormData();

        formdata.append('stokpk', stokpk);
        // formdata.append('stokid', $('#stokid').val());
        formdata.append('stoknm', $('#stoknm').val());
        // formdata.append('merk', $('#merk').val());
        // formdata.append('codebrg', $('#codebrg').val());
        formdata.append('satuan', $('#satuan').val());
        formdata.append('sawal', $('#sawal').val());
        formdata.append('masuk', $('#masuk').val());
        formdata.append('keluar', $('#keluar').val());
        formdata.append('sakhir', $('#sakhir').val());
        formdata.append('jnsbrgpk', $('#jnsbrg').combobox('getValue'));

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
                $('#dg-barang').datagrid('reload');
                showAlert(200, response.message);
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
                <span class="txt-000000-700-24-28">List Stok Barang</span>
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

                <button onclick="AddData('create')" class="btn btn-dark btn-sm">&#10010; Create Data</button>

                <div class="modal fade" id="ModalCreateOrEditBarang" data-bs-backdrop="static" aria-labelledby="ModalCreateOrEditBarangLabel" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h1 class="txt-000000-700-18-24-100" id="ModalCreateOrEditBarangLabel">
                                    <span class="barang-create d-none">Create New</span>
                                    <span class="barang-edit d-none">Edit</span>
                                    Stok Barang
                                </h1>
                            </div>
                            <div class="modal-body">
                                <form id="formCreateOrEditBarang" class="d-flex flex-column gap-2">
                                    <input type="text" id="stokpk" hidden>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="stokid" class="col-form-label">Kode Stok:</label>
                                            <input type="text" class="form-control" id="stokid" disabled>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="stoknm" class="col-form-label">Nama Barang:</label>
                                            <input type="text" class="form-control" id="stoknm">
                                            <span id="ermsg-stoknm" class="text-danger text-blink"></span>
                                        </div>

                                        <!-- <div class="col-md-6 mb-3">
                                            <label for="merk" class="col-form-label">Merk:</label>
                                            <input type="text" class="form-control" id="merk">
                                        </div> -->

                                        <div class="col-md-6 mb-3">
                                            <label for="jnsbrg" class="col-form-label">Jenis Barang:</label>
                                            <input id="jnsbrg" style="height:36px; width:222px;" class="easyui-combobox" method="get"
                                                data-options=" valueField:'jnsbrgpk', textField:'jnsbrgnm', panelHeight:'auto', limitToList:true, url:'{{ route('api.get-jnsbrg') }}' ">
                                        </div>

                                        <div class="col-md-6 mb-3 d-none">
                                            <label for="codebrg" class="col-form-label">Kode Barang:</label>
                                            <input type="text" class="form-control" id="codebrg">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="satuan" class="col-form-label">Satuan:</label>
                                            <input type="text" class="form-control" id="satuan">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="sawal" class="col-form-label">Saldo Awal:</label>
                                            <input type="number" class="form-control" id="sawal">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="masuk" class="col-form-label">Total Masuk:</label>
                                            <input type="number" class="form-control" id="masuk">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="keluar" class="col-form-label">Total Keluar:</label>
                                            <input type="number" class="form-control" id="keluar">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="sakhir" class="col-form-label">Saldo Akhir:</label>
                                            <input type="number" class="form-control" id="sakhir">
                                        </div>

                                    </div>

                                </form>
                            </div>

                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-light" onclick="onCancelData()">Cancel</button>
                                <button type="button" class="btn btn-dark barang-create d-none" onclick="onSubmitData()">Save
                                    <span id="loading-create-barang" class="spinner-grow spinner-grow-sm d-none" aria-hidden="true"></span>
                                </button>

                                <button type="button" class="btn btn-dark barang-edit d-none" onclick="onUpdateData()">Update
                                    <span id="loading-edit-barang" class="spinner-grow spinner-grow-sm d-none" aria-hidden="true"></span>
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
                        <span id="KodeDesc">Kode (desc)</span>
                        <span id="KodeAsc" class="d-none">Kode (asc)</span>
                        </button>
                        <ul id="menu-sortlist" class="dropdown-menu dropdown-menu-end dropdown-menu-custom-global dropdown-menu-sortlist pt-0 pb-0">
                        <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-start p-2" onclick="sortlistByDate(11)">Kode (descending)</a></li>
                        <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-end p-2" onclick="sortlistByDate(12)">Kode (ascending)</a></li>
                        </ul>
                    </div>
                </div>

            </div>

        <div class="d-none">
            <table id="dg-barang-shadow" class="easyui-datagrid" title="" style="width:100%;" align="center" toolbar="#tb" striped="false" pageSize="100" pageList="[100,200,300,500]" idField="prpk" pagination="true" rownumbers="false" multiple="true" collapsible="true" method="get"
            url="{{ route('get.list-stok') }}">
            </table>
        </div>

            <div class="p-0">
                <table id="dg-barang" class="easyui-datagrid" title="" style="width:100%; height:auto;" align="center" toolbar="#tb" striped="false" 
                    pageSize="100" pageList="[100,200,300,500]" idField="stokpk" pagination="true" rownumbers="false" singleSelect="true" 
                    collapsible="false" method="get" url="{{ route('get.list-stok')}}" data-options="fitColumns:true, sortable:true,">
                    <thead>
                        <tr>
                            <th field="index" styler="styler1">No</th>
                            <th field="stokid" styler="styler2">Kode Stok</th>
                            <th field="stoknm" styler="styler2">Nama Barang</th>
                            <th field="jnsbrgnm" styler="styler2">Jenis Barang</th>
                            <!-- <th field="merk" styler="styler2">Merk</th> -->
                            <th field="satuan" styler="styler2">Satuan</th>
                            <th field="sawal" styler="styler2">Saldo Awal</th>
                            <th field="masuk" styler="styler2">Tot Masuk</th>
                            <th field="keluar" styler="styler2">Tot Keluar</th>
                            <th field="sakhir" styler="styler2">Saldo Akhir</th>
                            <th field="act" styler="styler3" formatter="formatAttribute" align="right">Act</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
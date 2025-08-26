@extends('layout.main')

@section('css_custom')
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
        $('#dg-supplier-shadow').datagrid('getPanel').find('div.datagrid-view').css('display', 'none');
        $('#dg-supplier-shadow').datagrid('getPanel').find('div.datagrid-pager').css('display', 'none');

        $('#dg-supplier').datagrid('getPanel').find('div.datagrid-header').css('background', '#FFFFFF');
        $('#dg-supplier').datagrid('getPanel').find('div.datagrid-header').css('border-top', '1px solid #858585');
        $('#dg-supplier').datagrid('getPanel').find('div.datagrid-header').css('border-bottom', '1px solid #858585');
        $('#dg-supplier').datagrid('getPanel').find('div.datagrid-header').css('border-top-left-radius', '8px');
        $('#dg-supplier').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-left-radius', '8px');
        $('#dg-supplier').datagrid('getPanel').find('div.datagrid-header').css('border-top-right-radius', '8px');
        $('#dg-supplier').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-right-radius', '8px');
        $('#dg-supplier').datagrid('getPanel').find('div.datagrid-header').css('border-left', '1px solid #858585');
        $('#dg-supplier').datagrid('getPanel').find('div.datagrid-header').css('border-right', '1px solid #858585');
        $('#dg-supplier').datagrid('getPanel').css('border', 'none');


        $('#dg-supplier-mobile').datagrid('getPanel').find('div.datagrid-header').css('visibility', 'hidden');
        $('#dg-supplier-mobile').datagrid('getPanel').find('div.datagrid-header').addClass('h-0');
        $('#dg-supplier-mobile').datagrid('getPanel').css('border', 'none');
        $('#dg-supplier-mobile').datagrid('getPanel').addClass('lines-no');
        $('#dg-supplier-mobile').datagrid('getPanel').addClass('lines-no3');
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
        $('#dg-supplier-shadow').datagrid('load', {
            searchByInput: searchByInput,
            sortlistByDate: sortlistByDate,
        });

        $('#dg-supplier').datagrid('load', {
            searchByInput: searchByInput,
            sortlistByDate: sortlistByDate,
        })
        console.log(`loadDataByFilter END >>>>>>>>>>>>>>`);
    }

    $(function() {
        $('#dg-supplier-shadow').datagrid({
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
        $('#dg-supplier').datagrid('getPanel').find('div.datagrid-view2').css('height', windowHeight);
        $('#dg-supplier-mobile').datagrid('getPanel').find('div.datagrid-view2').css('height', windowHeight);
        } else {
        console.log("here auto");
        $('#dg-supplier').datagrid('getPanel').find('div.datagrid-view2').css('height', 'auto');
        $('#dg-supplier-mobile').datagrid('getPanel').find('div.datagrid-view2').css('height', 'auto');
        }
    }

    //FILTER SORT
    function sortlistByDate(value) {
        document.getElementById('sortlistByDate').value = value;
        if (value == 11) {
        $('#KodeAsc').removeClass('d-none');
        $('#KodeDesc').addClass('d-none');
        } else if (value == 12) {
        $('#KodeAsc').addClass('d-none');
        $('#KodeDesc').removeClass('d-none');
        } else {
        $('#KodeAsc').removeClass('d-none');
        $('#KodeDesc').addClass('d-none');
        }
        loadDataByFilter();
    }

    function formatAttribute(value, row, index) {
        return `<a type="button" class="txt-359DD9-400-14-18-100" onclick="onEditData('${row.suppk}')">Edit</a>`;
    }

    function AddData() {
        console.log(`onAddData is running...`);
        $('#ModalCreateOrEditSupplier').modal('show');
        $('.supplier-create').removeClass('d-none');
        $('.supplier-edit').addClass('d-none');
    }

    function onEditData(suppk) {
        console.log(`onEditData is running...`);

        $('#ModalCreateOrEditSupplier').modal('show');

        $('.supplier-edit').removeClass('d-none');
        $('.supplier-create').addClass('d-none');

        let url = "{{ url('supplier/:suppk') }}";
        url = url.replace(':suppk', suppk);

        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                console.log(`success onEditData here >>>>>`);
                console.log(response);
                $('#suppk').val(response.data.suppk);
                $('#supid').val(response.data.supid);
                $('#supnm').val(response.data.supnm);
                $('#alamat').val(response.data.alamat);
                $('#kontak').val(response.data.kontak);
                $('#kota').val(response.data.kota);
                $('#telepon').val(response.data.telepon);
                $('#fax').val(response.data.fax);
                $('#email').val(response.data.email);
                // $('#nour').val(response.data.nour);
                $('input[name="nour"][value="' + response.data.nour + '"]').prop('checked', true);
            },
            error: function(xhr, status, error) {
                console.log(`error onEditData here >>>>>`);
                console.log(xhr);
            }
        })
    }

    function onCancelData() {
        resetData();
        $('#ModalCreateOrEditSupplier').modal('hide');
        $('#formCreateOrEditSupplier').form('reset');
    }

    function resetData() {
        $('#suppk').val(null);
        $('#ermsg-supnm').text(null);
        $('#loading-edit-supplier').addClass('d-none');
        $('#loading-create-supplier').addClass('d-none');
    }

    function validateListData() {
        console.log(`validateListData is running...`);

        var supnm = $('#supnm').val();
        if (!supnm) $('#ermsg-supnm').text('Nama Supplier wajib diisi')

        setTimeout(() => {
            $('#ermsg-supnm').text(null);
        }, 5000);

        if ((!supnm)) return false;

        console.log(`validateListData is valid`);

        return true;
    }

    function onSubmitData() {
        console.log(`onSubmitData is running...`);

        if (!validateListData()) return;

        $('#loading-create-supplier').removeClass('d-none');

        let url = "{{ url('supplier') }}";
        console.log(`url: ${url}`);

        let formdata = new FormData();
        formdata.append('supid', $('#supid').val());
        formdata.append('supnm', $('#supnm').val());
        formdata.append('kontak', $('#kontak').val());
        formdata.append('alamat', $('#alamat').val());
        formdata.append('kota', $('#kota').val());
        formdata.append('telepon', $('#telepon').val());
        formdata.append('fax', $('#fax').val());
        formdata.append('email', $('#email').val());
        // formdata.append('nour', $('#nour').val());
        formdata.append('nour', $('input[name="nour"]:checked').val());

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
                $('#dg-supplier').datagrid('reload');
                showAlert(200, response.message);
            },
            error: function(xhr, status, error) {
                if (xhr.status === 422) {
                    showAlert(422, xhr.responseJSON.message);
                } else {
                    showAlert(xhr.status, 'Terjadi kesalahan saat menyimpan data.');
                }
            }
        });
    }

    function onUpdateData() {
        console.log(`onUpdateData is running...`);

        var suppk = $('#suppk').val();
        if (!suppk) return;

        if (!validateListData()) return;

        $('#loading-edit-supplier').removeClass('d-none');

        let url = "{{ url('supplier') }}/" + suppk;
        console.log(`url: ${url}`);

        let formdata = new FormData();

        formdata.append('_method', 'PUT');
        formdata.append('suppk', suppk);
        formdata.append('supid', $('#supid').val());
        formdata.append('supnm', $('#supnm').val());
        formdata.append('kontak', $('#kontak').val());
        formdata.append('alamat', $('#alamat').val());
        formdata.append('kota', $('#kota').val());
        formdata.append('telepon', $('#telepon').val());
        formdata.append('fax', $('#fax').val());
        formdata.append('email', $('#email').val());
        // formdata.append('nour', $('#nour').val());
        formdata.append('nour', $('input[name="nour"]:checked').val());

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
                $('#dg-supplier').datagrid('reload');
                showAlert(200, response.message);
            },
            error: function(xhr, status, error) {
                console.log(`error onUpdateData here >>>>>`);
                console.log(xhr);

                if (xhr.status === 422) {
                    showAlert(422, xhr.responseJSON.message); // Menampilkan pesan dari backend
                } else {
                    showAlert(xhr.status, 'Terjadi kesalahan saat memperbarui data.');
                }
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
                <span class="txt-000000-700-24-28">List Supplier</span>
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

                <div class="modal fade" id="ModalCreateOrEditSupplier" data-bs-backdrop="static" aria-labelledby="ModalCreateOrEditSupplierLabel" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-scrollable">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h1 class="txt-000000-700-18-24-100" id="ModalCreateOrEditSupplierLabel">
                                    <span class="supplier-create d-none">Create New</span>
                                    <span class="supplier-edit d-none">Edit</span>
                                    Supplier
                                </h1>
                            </div>
                            <div class="modal-body">
                                <form id="formCreateOrEditSupplier" class="d-flex flex-column gap-2">
                                    <input type="text" id="suppk" hidden>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="supid" class="col-form-label">Kode Supplier:</label>
                                            <input type="text" class="form-control" id="supid">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="supnm" class="col-form-label">Nama Supplier:</label>
                                            <input type="text" class="form-control" id="supnm">
                                            <span id="ermsg-supnm" class="text-danger text-blink"></span>
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="kontak" class="col-form-label">Kontak:</label>
                                            <input type="text" class="form-control" id="kontak">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="alamat" class="col-form-label">Alamat:</label>
                                            <input type="text" class="form-control" id="alamat">
                                         </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="kota" class="col-form-label">Kota:</label>
                                            <input type="text" class="form-control" id="kota">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="telepon" class="col-form-label">Telepon:</label>
                                            <input type="text" class="form-control" id="telepon">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="fax" class="col-form-label">Fax:</label>
                                            <input type="text" class="form-control" id="fax">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="email" class="col-form-label">Email:</label>
                                            <input type="email" class="form-control" id="email">
                                        </div>

                                        <div class="col-md-12 mb-12">
                                            <label for="nout" class="col-form-label">Urutan Nota/Invoice:</label>
                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="nour" id="nour1" value="1">
                                                <label class="form-check-label" for="nour1">
                                                    Kecil 1 - untuk Karcis Parkir
                                                </label>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="nour" id="nour2" value="2">
                                                <label class="form-check-label" for="nour2">
                                                    Kecil 2 - untuk SPBU
                                                </label>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="nour" id="nour3" value="3">
                                                <label class="form-check-label" for="nour3">
                                                    Kecil 3 - untuk E-TOLL
                                                </label>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="nour" id="nour4" value="4">
                                                <label class="form-check-label" for="nour4">
                                                    Kecil 4 - untuk Struk Swalayan
                                                </label>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="nour" id="nour5" value="5">
                                                <label class="form-check-label" for="nour5">
                                                    Kecil 5 - untuk Nota Toko Manual
                                                </label>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="nour" id="nour6" value="6">
                                                <label class="form-check-label" for="nour6">
                                                    1/2 Halaman
                                                </label>
                                            </div>

                                            <div class="form-check">
                                                <input class="form-check-input" type="radio" name="nour" id="nour7" value="7">
                                                <label class="form-check-label" for="nour7">
                                                   1 Halaman
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                </form>
                            </div>

                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-light" onclick="onCancelData()">Cancel</button>
                                <button type="button" class="btn btn-dark supplier-create d-none" onclick="onSubmitData()">Save
                                    <span id="loading-create-supplier" class="spinner-grow spinner-grow-sm d-none" aria-hidden="true"></span>
                                </button>

                                <button type="button" class="btn btn-dark supplier-edit d-none" onclick="onUpdateData()">Update
                                    <span id="loading-edit-supplier" class="spinner-grow spinner-grow-sm d-none" aria-hidden="true"></span>
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
                        <span id="KodeAsc">Nama Supplier (asc)</span>
                        <span id="KodeDesc" class="d-none">Nama Supplier (desc)</span>
                        </button>
                        <ul id="menu-sortlist" class="dropdown-menu dropdown-menu-end dropdown-menu-custom-global dropdown-menu-sortlist pt-0 pb-0">
                        <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-start p-2" onclick="sortlistByDate(11)">Nama Supplier (ascending)</a></li>
                        <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-end p-2" onclick="sortlistByDate(12)">Nama Supplier (descending)</a></li>
                        </ul>
                    </div>
                </div>

            </div>

            <div class="d-none">
                <table id="dg-supplier-shadow" class="easyui-datagrid" title="" style="width:100%;" align="center" toolbar="#tb" striped="false" pageSize="100" pageList="[100,200,300,500]" idField="prpk" pagination="true" rownumbers="false" multiple="true" collapsible="true" method="get"
                url="{{ route('get.list-supplier') }}">
                </table>
            </div>

            <div class="p-0">
                <table id="dg-supplier" class="easyui-datagrid" title="" style="width:100%; height:auto;" align="center" toolbar="#tb" striped="false" 
                    pageSize="100" pageList="[100,200,300,500]" idField="suppk" pagination="true" rownumbers="false" singleSelect="true" 
                    collapsible="false" method="get" url="{{ route('get.list-supplier')}}" data-options="fitColumns:true, sortable:true,">
                    <thead>
                        <tr>
                            <th field="index" styler="styler1">No</th>
                            <th field="supid" styler="styler2">Kode Supplier</th>
                            <th field="supnm" styler="styler2">Nama Supplier</th>
                            <th field="act" styler="styler3" formatter="formatAttribute" align="right">Act</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@extends('layout.main')

@section('css_custom')
<style>

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
        $('#dg-ktb-shadow').datagrid('getPanel').find('div.datagrid-view').css('display', 'none');
        $('#dg-ktb-shadow').datagrid('getPanel').find('div.datagrid-pager').css('display', 'none');

        var dgang = $('#dg-ktb').datagrid();
        dgang.datagrid('getPanel').find('div.datagrid-header').css('background', 'transparent');
        dgang.datagrid('getPanel').find('div.datagrid-header').css('border', '1px solid #858585');
        dgang.datagrid('getPanel').find('div.datagrid-header').css('border-radius', '8px');
        dgang.datagrid('getPanel').find('div.datagrid-header td[field]').css('border-right', 'none');
        dgang.datagrid('getPanel').find('div.datagrid-body').css('overflow-y', 'hidden');
        dgang.datagrid('getPanel').css('border', 'none');
        dgang.datagrid('getPanel').addClass('lines-no');
        dgang.datagrid('getPanel').addClass('lines-no3');

        $('#dg-ktb-mobile').datagrid('getPanel').find('div.datagrid-header').css('visibility', 'hidden');
        $('#dg-ktb-mobile').datagrid('getPanel').find('div.datagrid-header').addClass('h-0');
        $('#dg-ktb-mobile').datagrid('getPanel').css('border', 'none');
        $('#dg-ktb-mobile').datagrid('getPanel').addClass('lines-no');
        $('#dg-ktb-mobile').datagrid('getPanel').addClass('lines-no3');
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
        $('#dg-ktb-shadow').datagrid('load', {
            searchByInput: searchByInput,
            sortlistByDate: sortlistByDate,
        });

        $('#dg-ktb').datagrid('load', {
            searchByInput: searchByInput,
            sortlistByDate: sortlistByDate,
        })
        console.log(`loadDataByFilter END >>>>>>>>>>>>>>`);
    }

    $(function() {
        $('#dg-ktb-shadow').datagrid({
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
        $('#dg-ktb').datagrid('getPanel').find('div.datagrid-view2').css('height', windowHeight);
        $('#dg-ktb-mobile').datagrid('getPanel').find('div.datagrid-view2').css('height', windowHeight);
        } else {
        console.log("here auto");
        $('#dg-ktb').datagrid('getPanel').find('div.datagrid-view2').css('height', 'auto');
        $('#dg-ktb-mobile').datagrid('getPanel').find('div.datagrid-view2').css('height', 'auto');
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
        return `<a type="button" class="txt-359DD9-400-14-18-100" onclick="onEditData('${row.kelpk}')">Edit</a>`;
    }

    function AddData() {
        console.log(`onAddData is running...`);
        $('#ModalCreateOrEditKtb').modal('show');
        $('.ktb-create').removeClass('d-none');
        $('.ktb-edit').addClass('d-none');
    }

    function onEditData(kelpk) {
        console.log(`onEditData is running...`);

        $('#ModalCreateOrEditKtb').modal('show');

        $('.ktb-edit').removeClass('d-none');
        $('.ktb-create').addClass('d-none');

        let url = "{{ url('kategori-barang/:kelpk') }}";
        url = url.replace(':kelpk', kelpk);

        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                console.log(`success onEditData here >>>>>`);
                console.log(response);
                $('#kelpk').val(response.data.kelpk);
                $('#kelid').val(response.data.kelid);
                $('#kelnm').val(response.data.kelnm);
            },
            error: function(xhr, status, error) {
                console.log(`error onEditData here >>>>>`);
                console.log(xhr);
            }
        })
    }

    function onCancelData() {
        resetData();
        $('#ModalCreateOrEditKtb').modal('hide');
        $('#formCreateOrEditKtb').form('reset');
    }

    function resetData() {
        $('#kelpk').val(null);
        $('#ermsg-kelnm').text(null);
        $('#loading-edit-ktb').addClass('d-none');
        $('#loading-create-ktb').addClass('d-none');
    }

    function validateListData() {
        console.log(`validateListData is running...`);

        var kelnm = $('#kelnm').val();
        if (!kelnm) $('#ermsg-kelnm').text('Nama Kategori Barang wajib diisi')

        setTimeout(() => {
            $('#ermsg-kelnm').text(null);
        }, 5000);

        if ((!kelnm)) return false;

        console.log(`validateListData is valid`);

        return true;
    }

    function onSubmitData() {
        console.log(`onSubmitData is running...`);

        if (!validateListData()) return;

        $('#loading-create-ktb').removeClass('d-none');

        let url = "{{ url('kategori-barang') }}";
        console.log(`url: ${url}`);

        let formdata = new FormData();
        formdata.append('kelid', $('#kelid').val());
        formdata.append('kelnm', $('#kelnm').val());

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
                $('#dg-ktb').datagrid('reload');
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

        var kelpk = $('#kelpk').val();
        if (!kelpk) return;

        if (!validateListData()) return;

        $('#loading-edit-ktb').removeClass('d-none');

        let url = "{{ url('kategori-barang') }}/" + kelpk;
        console.log(`url: ${url}`);

        let formdata = new FormData();

        formdata.append('_method', 'PUT');
        formdata.append('kelpk', kelpk);
        formdata.append('kelid', $('#kelid').val());
        formdata.append('kelnm', $('#kelnm').val());

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
                $('#dg-ktb').datagrid('reload');
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

                <div class="modal fade" id="ModalCreateOrEditKtb" data-bs-backdrop="static" aria-labelledby="ModalCreateOrEditKtbLabel" data-bs-keyboard="false" tabindex="-1" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header border-0">
                                <h1 class="txt-000000-700-18-24-100" id="ModalCreateOrEditKtbLabel">
                                    <span class="ktb-create d-none">Create New</span>
                                    <span class="ktb-edit d-none">Edit</span>
                                    Kategori Barang
                                </h1>
                            </div>
                            <div class="modal-body">
                                <form id="formCreateOrEditKtb" class="d-flex flex-column gap-2">
                                    <input type="text" id="kelpk" hidden>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label for="kelid" class="col-form-label">Kode:</label>
                                            <input type="text" class="form-control" id="kelid">
                                        </div>

                                        <div class="col-md-6 mb-3">
                                            <label for="kelnm" class="col-form-label">Nama Kategori Barang:</label>
                                            <input type="text" class="form-control" id="kelnm">
                                            <span id="ermsg-kelnm" class="text-danger text-blink"></span>
                                        </div>

                                    </div>

                                </form>
                            </div>

                            <div class="modal-footer border-0">
                                <button type="button" class="btn btn-light" onclick="onCancelData()">Cancel</button>
                                <button type="button" class="btn btn-dark ktb-create d-none" onclick="onSubmitData()">Save
                                    <span id="loading-create-ktb" class="spinner-grow spinner-grow-sm d-none" aria-hidden="true"></span>
                                </button>

                                <button type="button" class="btn btn-dark ktb-edit d-none" onclick="onUpdateData()">Update
                                    <span id="loading-edit-ktb" class="spinner-grow spinner-grow-sm d-none" aria-hidden="true"></span>
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
                        <span id="KodeAsc">Nama Kategori (asc)</span>
                        <span id="KodeDesc" class="d-none">Nama Kategori (desc)</span>
                        </button>
                        <ul id="menu-sortlist" class="dropdown-menu dropdown-menu-end dropdown-menu-custom-global dropdown-menu-sortlist pt-0 pb-0">
                        <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-start p-2" onclick="sortlistByDate(11)">Nama Kategori (ascending)</a></li>
                        <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-end p-2" onclick="sortlistByDate(12)">Nama Kategori (descending)</a></li>
                        </ul>
                    </div>
                </div>

            </div>

            <div class="d-none">
                <table id="dg-ktb-shadow" class="easyui-datagrid" title="" style="width:100%;" align="center" toolbar="#tb" striped="false" pageSize="100" pageList="[100,200,300,500]" idField="prpk" pagination="true" rownumbers="false" multiple="true" collapsible="true" method="get"
                url="{{ route('get.list-ktb') }}">
                </table>
            </div>

            <div class="p-0">
                <table id="dg-ktb" class="easyui-datagrid" title="" style="width:100%; height:auto;" align="center" toolbar="#tb" striped="false" 
                    pageSize="100" pageList="[100,200,300,500]" idField="kelpk" pagination="true" rownumbers="false" singleSelect="true" 
                    collapsible="false" method="get" url="{{ route('get.list-ktb')}}" data-options="fitColumns:true, sortable:true,">
                    <thead>
                        <tr>
                            <th field="index" styler="styler1">No</th>
                            <th field="kelid" styler="styler2">Kode</th>
                            <th field="kelnm" styler="styler2">Nama Kategori Barang</th>
                            <th field="act" styler="styler3" formatter="formatAttribute" align="right">Act</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
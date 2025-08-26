@extends('layout.main')

@section('css_custom')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
        $('#dg-barang-shadow').datagrid('getPanel').find('div.datagrid-view').css('display', 'none');
        $('#dg-barang-shadow').datagrid('getPanel').find('div.datagrid-pager').css('display', 'none');

        var dgang = $('#dg-barang').datagrid();
        dgang.datagrid('getPanel').find('div.datagrid-header').css('background', 'transparent');
        dgang.datagrid('getPanel').find('div.datagrid-header').css('border', '1px solid #858585');
        dgang.datagrid('getPanel').find('div.datagrid-header').css('border-radius', '8px');
        dgang.datagrid('getPanel').find('div.datagrid-header td[field]').css('border-right', 'none');
        dgang.datagrid('getPanel').find('div.datagrid-body').css('overflow-y', 'hidden');
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
        var searchByInputBarang = $('#searchByInputBarang').val();
        var sortlistByDate = $('#sortlistByDate').val();

        console.log(`searchByInputBarang : ${searchByInputBarang}`);
        $('#dg-barang-shadow').datagrid('load', {
            searchByInputBarang: searchByInputBarang,
            sortlistByDate: sortlistByDate,
        });

        $('#dg-barang').datagrid('load', {
            searchByInputBarang: searchByInputBarang,
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
        $('#BrgpkDesc').removeClass('d-none');
        $('#BrgpkAsc').addClass('d-none');
        } else if (value == 12) {
        $('#BrgpkDesc').addClass('d-none');
        $('#BrgpkAsc').removeClass('d-none');
        } else {
        $('#BrgpkDesc').removeClass('d-none');
        $('#BrgpkAsc').addClass('d-none');
        }
        loadDataByFilter();
    }

    function KlikUpload(){
        $('#ModalUploadExcel').modal('show');
    }

    function reloadDGListBarang() {
        $('#dg-barang').datagrid('reload');
        $('#ddg-barang-mobile').datagrid('reload');
    }

    // function OnSubmitUpload(){
    //     var validateUploadBarang = validateFileUploadBarang();

    //     console.log("validateUploadBarang : " + validateUploadBarang);

    //     if (!validateUploadBarang) return;

    //     var formData = new FormData();
    //     var fileUploadBarang = $("#uploadbarang")[0].files[0];

    //     formData.append('uploadbarang', fileUploadBarang);

    //     $.ajax({
    //     url: "{{ route('upload_excel.barang') }}",
    //     method: 'POST',
    //     data: formData,
    //     processData: false,
    //     contentType: false,
    //     headers: {
    //         'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
    //     },
    //     success: function (response) {
    //         console.log("success upload here >>>>> " + JSON.stringify(response));
    //         reloadDGListBarang();
    //         $('#ModalUploadExcel').modal('hide');

    //         showAlert(200, response.message);
    //     },
    //     error: function (xhr, status, error) {
    //         console.error("error upload here >>>>> " + JSON.stringify(xhr));
    //         console.error(error);

    //         var errorMessage = xhr.responseJSON.message ?? JSON.stringify(error);

    //         document.getElementById('error-uploadbarang').textContent = errorMessage;
    //     }
    //     });
    // }

        function OnSubmitUpload() {
            var validateUploadBarang = validateFileUploadBarang();
            if (!validateUploadBarang) return;

            var formData = new FormData();
            var fileUploadBarang = $("#uploadbarang")[0].files[0];
            formData.append('uploadbarang', fileUploadBarang);

            // Tampilkan progress bar
            $("#uploadProgressWrapper").show();
            $("#uploadProgressBar").css("width", "0%").text("0%");

            $.ajax({
                xhr: function () {
                    var xhr = new window.XMLHttpRequest();
                    // xhr.upload.addEventListener("progress", function (evt) {
                    //     if (evt.lengthComputable) {
                    //         var percent = Math.round((evt.loaded / evt.total) * 100);
                    //         $("#uploadProgressBar").css("width", percent + "%").text(percent + "%");
                    //     }
                    // }, false);
                    xhr.upload.addEventListener("progress", function (evt) {
                        if (evt.lengthComputable) {
                            var percent = Math.round((evt.loaded / evt.total) * 100);
                            // Tampilkan progress upload
                            $("#uploadProgressBar").css("width", percent + "%").text(percent + "%");

                            // Jika sudah 100%, beri info sedang diproses server
                            if (percent === 100) {
                                $("#uploadProgressBar").removeClass("bg-success").addClass("bg-warning").text("Processing...");
                            }
                        }
                    }, false);
                    return xhr;
                },
                url: "{{ route('upload_excel.barang') }}",
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                // success: function (response) {
                //     console.log("success upload here >>>>> " + JSON.stringify(response));
                //     reloadDGListBarang();
                //     $('#ModalUploadExcel').modal('hide');
                //     $("#uploadProgressWrapper").hide();
                //     showAlert(200, response.message);
                // },
                success: function (response) {
                    $("#uploadProgressBar")
                        .removeClass("bg-warning")
                        .addClass("bg-success")
                        .css("width", "100%")
                        .text("Completed");

                    setTimeout(() => {
                        $("#uploadProgressWrapper").hide();
                        $('#ModalUploadExcel').modal('hide');
                        reloadDGListBarang();
                        showAlert(200, response.message);
                    }, 800); // beri sedikit delay agar user sempat melihat progres selesai
                },
                error: function (xhr, status, error) {
                    console.error("error upload here >>>>> " + JSON.stringify(xhr));
                    var errorMessage = xhr.responseJSON?.message ?? error;
                    document.getElementById('error-uploadbarang').textContent = errorMessage;
                    $("#uploadProgressWrapper").hide();
                }
            });
        }


    function validateFileUploadBarang() {
        var filename = document.getElementById('uploadbarang').files[0] ? document.getElementById('uploadbarang').files[0].name : null;

        if (!filename) {
        document.getElementById('error-uploadbarang').textContent = 'Please upload file first';
        return false;
        }

        return true;
    }
</script>
@endsection

@section('content')
<div class="main-wrapper">
    <div class="container-fluid p-4">
        <div class="d-flex flex-column">
            <div class="p-2">
                <span class="txt-000000-700-24-28">List Barang</span>
            </div>
            <div class="d-flex flex-wrap p-0 pb-2 gap-2 align-items-center">

                <div class="p-0">
                    <div class="input-group flex-nowrap input-group-search" style="height:34px;">
                        <span class="input-group-text search">
                            <img src="{{ asset('public/css/images/Shape.png') }}" plain="true" width="18px" height="18px">
                        </span>
                        <input type="text" class="form-control search" id="searchByInputBarang" onkeyup="loadDataByFilter()" placeholder="Search...">
                    </div>
                </div>

                <button onclick="KlikUpload();" class="btn btn-dark btn-sm"><i class="fa fa-upload"></i> Upload by Excel</button>

                <div class="ms-auto">
                    <div id="sortlist-grup" class="p-1 pb-0 responsive">
                        <input type="hidden" id="sortlistByDate" name="sortlistByDate" size="5">
                        <button class="btn btn-sortlist dropdown-toggle-custom-sortlist pt-0" data-bs-toggle="dropdown" data-bs-target="#menu-sortlist" aria-expanded="true">
                        <img src="{{ asset('public/css/images/sort.jpg') }}" alt="" class="imgsort">
                        <span id="BrgpkDesc">No (desc)</span>
                        <span id="BrgpkAsc" class="d-none">No (asc)</span>
                        </button>
                        <ul id="menu-sortlist" class="dropdown-menu dropdown-menu-end dropdown-menu-custom-global dropdown-menu-sortlist pt-0 pb-0">
                        <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-start p-2" onclick="sortlistByDate(11)">No (descending)</a></li>
                        <li><a class="dropdown-item dropdown-item-custom-global dropdown-item-custom-global-end p-2" onclick="sortlistByDate(12)">No (ascending)</a></li>
                        </ul>
                    </div>
                </div>

            </div>

        <div class="d-none">
            <table id="dg-barang-shadow" class="easyui-datagrid" title="" style="width:100%;" align="center" toolbar="#tb" striped="false" pageSize="100" pageList="[100,200,300,500]" idField="prpk" pagination="true" rownumbers="false" multiple="true" collapsible="true" method="get"
            url="{{ route('get-barang') }}">
            </table>
        </div>

            <div class="p-0">
                <table id="dg-barang" class="easyui-datagrid" title="" style="width:100%; height:auto;" align="center" toolbar="#tb" striped="false" 
                    pageSize="100" pageList="[100,200,300,500]" idField="brgpk" pagination="true" rownumbers="false" singleSelect="true" 
                    collapsible="false" method="get" url="{{ route('get-barang')}}" data-options="fitColumns:true, sortable:true,">
                    <thead>
                        <tr>
                            <th field="index" styler="styler1">No</th>
                            <th field="brgnm" styler="styler2">Nama Barang</th>
                            <th field="noseri" styler="styler2">No Seri</th>
                            <th field="merknm" styler="styler2">Merk</th>
                            <th field="qtyawal" styler="styler2">Qty</th>
                            <th field="lastupdate" styler="styler3">Last Update</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ModalUploadExcel" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header" style="border:none;">
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
          <div class="mb-3">
            <input type="file" id="uploadbarang" class="form-control">
          </div>

          <div class="progress mb-3" style="height: 25px; display: none;" id="uploadProgressWrapper">
            <div class="progress-bar progress-bar-striped progress-bar-animated bg-success" 
                role="progressbar" id="uploadProgressBar" 
                style="width: 0%">0%</div>
            </div>

        <div class="p-2">
            <span id="error-uploadbarang" class="text-danger"></span>
          </div>
      </div>
      <div class="modal-footer" style="border:none;">
        <button type="button" class="btn btn-dark" onclick="OnSubmitUpload();"><i class="fa fa-upload"></i> Upload</button>
      </div>
    </div>
  </div>
</div>
@endsection
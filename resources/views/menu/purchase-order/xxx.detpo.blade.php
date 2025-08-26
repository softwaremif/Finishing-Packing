@extends('layout.main')

@section('css_custom')
<style>
     .font-warning {
        font-size: 12px;
        font-weight: bold;
        color: red;
        text-align: left;
    }

    .header {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        align-items: flex-start;
        padding: 16px;
        gap: 24px;
        position: absolute;
        width: 100%;
        height: 48px;
        left: 0px;
        top: 58px;
        /* Blue */
        background: #359DD9;
        /* W - Drop Shadow */
        box-shadow: 0px 0px 16px -4px rgba(0, 0, 0, 0.12);
    }

    #count2 {
        width: 200px;
        height: 20px;
        font-style: normal;
        font-weight: 700;
        font-size: 14px;
        line-height: 20px;
        color: #FFFFFF;
        flex: none;
        order: 0;
        flex-grow: 0;
    }

    .border-1 {
        border: 1px solid red;
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

    .input-group-search {
        width: 160px;
    }

    th {
        text-align: center !important;
    }

    thead {
        margin-bottom: 20px;
    }

    table {
        margin-top: 0 !important;
        background-color: #fff !important;
    }

    table.table-custom {
        border-collapse: separate;
        border-spacing: 0 8px;
    }

    .btn-outline-dark:hover {
        background-color: transparent;
        color: #000;
    }

    table.table-custom tbody tr,
    table.table-custom tbody tr td {
        border-radius: 8px;
    }

    table.table-custom tbody tr {
        box-shadow: 0px 0px 16px -4px #0000001F;
    }

    table.table-custom thead tr,
    table.table-custom thead tr th {
        border-radius: 8px;
        margin-bottom: 3px;
    }

    table.table-custom thead tr {
        box-shadow: 0px 0px 16px -4px #0000001F;
    }

    .blue {
        color: #359DD9;
    }

    .red {
        color: red;
    }

    .pointer {
        cursor: pointer;
    }

    .grey {
        color: rgba(0, 0, 0, 0.38);
    }

    .font-small {
        font-size: 12px;
    }

    .height_input {
        height: 32px;
    }

    .bg-light-grey {
        background: var(--Text-Light-grey, rgba(0, 0, 0, 0.38));
    }

    .bg-dark-grey {
        background: var(--Text-Grey, rgba(0, 0, 0, 0.60));
    }

    .sticky {
        position: fixed;
        top: 0;
        width: 100%;
    }

    .sticky+.contenta {
        padding-top: 102px;
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

    .datagrid-body {
        overflow-x: hidden;
        overflow-y: hidden;
    }

    .text-biru {
        color: #359DD9;
        font-weight: 700;
        cursor: pointer;
    }

    .datagrid-editable-input33 input {
        width: 200px !important;
    }
    
    .font-warning {
        font-size: 12px;
        font-weight: bold;
        color: red;
        text-align: left;
    }

    .header {
        display: flex;
        flex-direction: row;
        justify-content: flex-end;
        align-items: flex-start;
        padding: 16px;
        gap: 24px;
        position: absolute;
        width: 100%;
        height: 48px;
        left: 0px;
        top: 58px;
        /* Blue */
        background: #359DD9;
        /* W - Drop Shadow */
        box-shadow: 0px 0px 16px -4px rgba(0, 0, 0, 0.12);
    }

    #count2 {
        width: 200px;
        height: 20px;
        font-style: normal;
        font-weight: 700;
        font-size: 14px;
        line-height: 20px;
        color: #FFFFFF;
        flex: none;
        order: 0;
        flex-grow: 0;
    }

    .border-1 {
        border: 1px solid red;
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

    .input-group-search {
        width: 160px;
    }

    th {
        text-align: center !important;
    }

    thead {
        margin-bottom: 20px;
    }

    table {
        margin-top: 0 !important;
        background-color: #fff !important;
    }

    table.table-custom {
        border-collapse: separate;
        border-spacing: 0 8px;
    }

    .btn-outline-dark:hover {
        background-color: transparent;
        color: #000;
    }

    table.table-custom tbody tr,
    table.table-custom tbody tr td {
        border-radius: 8px;
    }

    table.table-custom tbody tr {
        box-shadow: 0px 0px 16px -4px #0000001F;
    }

    table.table-custom thead tr,
    table.table-custom thead tr th {
        border-radius: 8px;
        margin-bottom: 3px;
    }

    table.table-custom thead tr {
        box-shadow: 0px 0px 16px -4px #0000001F;
    }

    .blue {
        color: #359DD9;
    }

    .red {
        color: red;
    }

    .pointer {
        cursor: pointer;
    }

    .grey {
        color: rgba(0, 0, 0, 0.38);
    }

    .font-small {
        font-size: 12px;
    }

    .height_input {
        height: 32px;
    }

    .bg-light-grey {
        background: var(--Text-Light-grey, rgba(0, 0, 0, 0.38));
    }

    .bg-dark-grey {
        background: var(--Text-Grey, rgba(0, 0, 0, 0.60));
    }

    .sticky {
        position: fixed;
        top: 0;
        width: 100%;
    }

    .sticky+.contenta {
        padding-top: 102px;
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

    .datagrid-body {
        overflow-x: hidden;
        overflow-y: hidden;
    }

    .text-biru {
        color: #359DD9;
        font-weight: 700;
        cursor: pointer;
    }

    .datagrid-editable-input33 input {
        width: 200px !important;
    }
</style>
@endsection

@section('js_custom')
<script>
    $(function() {
        $('#dg').datagrid('getPanel').find('div.datagrid-header').css('background', '#FFFFFF');
        $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-top', '1px solid #858585');
        $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-top-left-radius', '8px');
        $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-bottom', '1px solid #858585');
        $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-left-radius', '8px');
        $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-top-right-radius', '8px');
        $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-right-radius', '8px');
        $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-left', '1px solid #858585');
        $('#dg').datagrid('getPanel').find('div.datagrid-header').css('border-right', '1px solid #858585');
        $('#dg').datagrid('getPanel').find('div.datagrid-header td[field]').css('border-right', 'none');
        $('#dg').datagrid('getPanel').css('border', 'none');
        $('#dg').datagrid('getPanel').find('div.datagrid-header').addClass('lines-no');
        $('#dg').datagrid('getPanel').find('div.datagrid-header').addClass('lines-no3');
        $('#dg').datagrid('getPanel').find('div.datagrid-header').addClass('col');
        $('#dg').datagrid('getPanel').addClass('lines-no');
        $('#dg').datagrid('getPanel').addClass('lines-no3');
    });

    $(function() {
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').css('background', '#FFFFFF');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').css('border-top', '1px solid #858585');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').css('border-top-left-radius', '8px');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').css('border-bottom', '1px solid #858585');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-left-radius', '8px');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').css('border-top-right-radius', '8px');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-right-radius', '8px');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').css('border-left', '1px solid #858585');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').css('border-right', '1px solid #858585');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header td[field]').css('border-right', 'none');
        $('#dgLookUpPo').datagrid('getPanel').css('border', 'none');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').addClass('lines-no');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').addClass('lines-no3');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').addClass('col');
        $('#dgLookUpPo').datagrid('getPanel').addClass('lines-no');
        $('#dgLookUpPo').datagrid('getPanel').addClass('lines-no3');
    });

    $(function() {
        var pathArray = window.location.pathname.split("/");
        var pathname3 = pathArray[2];

        var nilai = (pathname3 === 'detail');
        var popk = $('#popk').val();

        $('#dg').edatagrid({
            saveUrl: '{{ route("api.insert-podt", "") }}/' + popk,
            updateUrl: '{{ route("api.update-podt") }}',
            editing: nilai,
            onSuccess: function(index, row) {
                $(this).edatagrid('saveRow');
                $(this).datagrid('reload');
                console.log("ROW : " + row);
                console.log("nilai : " + nilai);
            },
            onDblClickRow: function(index, row) {
                var isEdit = $('#isEdit-' + row.podtpk).val();
                var podtpk = row.podtpk;

                if (isEdit == 'true') {
                    document.getElementById('isEdit-' + row.podtpk).value = false;
                    hiddenSave(index, podtpk);
                    $(this).datagrid('endEdit', index);
                } else {
                    console.log('onedit : ' + row.podtpk);
                    $('#isEdit-' + row.podtpk).val('true');

                    if ($(this).edatagrid('options').editing) {
                        $(this).datagrid('beginEdit', index);
                        console.log("Double click edit mode");
                    }
                }
            },
            onBeforeEdit: function(index, row) {
                var token = $('meta[name="csrf-token"]').attr('content');
                row._token = token;
            },
        });
    });

    function tambahrow() {
        $('#dg').edatagrid('addRow');
        var index = $('#dg').edatagrid('getRows').length - 1;
        var ed = $('#dg').edatagrid('getEditors', index)[0];
        $(ed.target).focus();
    }

    $(function() {
        var pathArray = window.location.pathname.split("/");
        var pathname3 = pathArray[3];
        if (pathname3 == 'add') {
            $('#dg').datagrid('showColumn', 'ck');
        } else {
            $('#dg').datagrid('hideColumn', 'ck');
        }
        
        $('#ModalLookUpPo').on('shown.bs.modal', function() {
            $('#dgLookUpPo').datagrid('resize');
        })
    });



    $(function() {
        editDisplay();
    });

    function editDisplay() {
        var pathArray = window.location.pathname.split("/");
        var pathname3 = pathArray[3];

        if (pathname3 == 'detail') {
            $('#dg').edatagrid('options').editing = true;
            $('#edit_data').removeClass('d-none');
            $('#tglpo').datebox('disable');
            $('#ab').combobox('disable');
            $('#sup').combobox('disable');
            $('#curid').combobox('disable');
            $('#ket').attr('disabled', true);
            $('#ket2').attr('disabled', true);
            $('#nopo').attr('disabled', true);
            $('#totbeli').attr('disabled', true);
            $('#addnew').addClass('d-none');
            $('#lookupgis').removeClass('d-none');
            $('#lookupgis').addClass('d-none');
        } else if (pathname3 == 'add-spb') {
            $('#lookupgis').removeClass('d-none');
            $('#cancel_submit').removeClass('d-none');
            $('#save_submit').removeClass('d-none');
            $('#add').removeClass('d-none');
        } else {
            $('#cancel_submit').addClass('d-none');
            $('#edit_data').addClass('d-none');
        }
    }

    function edit_data() {
        $('#edit_data').addClass('d-none');
        $('#cancel_save_edit').removeClass('d-none');
        $('#add').removeClass('d-none');
        $('#lookupgis').removeClass('d-none');
        $('#dg').edatagrid('options').editing = true;
        $('#dg').datagrid('showColumn', 'ck');
        $('#tglpo').datebox('enable');
        $('#ab').combobox('enable');
        $('#sup').combobox('enable');
        $('#curid').combobox('enable');
        $('#ket').attr('disabled', false);
        $('#ket2').attr('disabled', false);
        $('#nopo').attr('disabled', true);
        $('#totbeli').attr('disabled', true);
        $('#addnew').removeClass('d-none');
    }

    function cancel_edit() {
        $('#edit_data').removeClass('d-none');
        $('#dg').edatagrid('options').editing = false;
        $('#dg').datagrid('hideColumn', 'ck');
        $('#cancel_save_edit').addClass('d-none');
        $('#add').addClass('d-none');
        $('#lookupgis').addClass('d-none');

        var rows = $('#dg').datagrid('getRows');
        for (var i = 0; i < rows.length; i++) {
            $('#dg').datagrid('endEdit', i);
        }
        $('#tglpo').datebox('disable');
        $('#ab').combobox('disable');
        $('#sup').combobox('disable');
        $('#curid').combobox('disable');
        $('#ket').attr('disabled', true);
        $('#ket2').attr('disabled', true);
        $('#nopo').attr('disabled', true);
        $('#totbeli').attr('disabled', true);
        $('#addnew').addClass('d-none');
    }

    $(function() {
        $('#dg').edatagrid('options').editing = false;
        var popk = $('#popk').val();
        var url = "{{ route('api.get-podt', ['popk' => ':popk']) }}";
        url = url.replace(':popk', popk);

        $('#dg').datagrid({
            url: url,
            method: 'get',
            onLoadSuccess: function(data) {
                console.log('Data loaded successfully:', data);
            },
            onLoadError: function() {
                console.error('Failed to load data.');
            }
        });
    });

    function submit_spb(nilai) {
        var formElement = document.getElementById("form_header");
        var requiredFields = ["popk"]; // Add the required field IDs here
        var isValid = true;
        var errorMessage = "Please fill in all required fields:\n";
        requiredFields.forEach(function(fieldId) {
            var field = document.getElementById(fieldId);
            if (!field || !field.value.trim()) {
                isValid = false;
                errorMessage += `- ${fieldId}\n`;
            }
        });
        if (!isValid) {
            alert(errorMessage); // Show error message if validation fails
            return;
        }

        var formData = new FormData(formElement);
        $.ajax({
            method: "POST",
            url: "{{ route('api.edit-save-header-podt') }}",
            data: formData,
            processData: false,
            contentType: false,
            cache: false,
            success: function(response) {
                console.log("Response:", response);
                if (response.status === "success" && response.data) {
                    // var nobukti = response.data.nobukti;
                    // $("#exampleModalInfoNoBukti").text("No SPB: " + nobukti);
                    if (nilai === 'submit') {
                        // $('#ModalInfoNoBukti').modal('show');
                        // setTimeout(function() {
                        // $('#ModalInfoNoBukti').modal('hide'); // Tutup modal
                        window.location.href = "{{ route('index.poreq') }}"; // Arahkan ke halaman lain
                        // }, 3000);
                    } else {
                        cancel_edit();
                    }
                } else {
                    alert("Failed to retrieve nobukti from server.");
                }
            },
            error: function(xhr, status, error) {
                console.error("Error:", xhr);
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    showAlert(400, xhr.responseJSON.message);
                } else {
                    showAlert(400, error);
                }
            }
        });
    }


    function warning() {
        $('#warningtable2').removeClass('d-none');
    }

    var editingIndex = undefined;

    function onClickRowHandler(rowIndex) {
        if (editingIndex !== undefined) {
            $('#datagrid').datagrid('endEdit', editingIndex);
            $('#datagrid').datagrid('acceptChanges');
            $('#datagrid').datagrid('reload'); // Reload datagrid
        }
        editingIndex = rowIndex;
        $('#datagrid').datagrid('beginEdit', rowIndex);
    }

    function menu() {
        var row = $('#dg').datagrid('getChecked');
        var tchek = $('#dg').datagrid('getChecked').length;

        if (row) {
            if (tchek > 1) {
                $('#myHeader').show();
                $('#count').show();
                $('#count2').hide();
                $('#mdelete').show();
                $('#count').css('margin-right', '70%');
                count_row();
            } else {
                $('#myHeader').show();
                $('#count').show();
                $('#count2').hide();
                $('#mdelete').show();
                $('#count').css('margin-right', '70%');
                count_row();
            }
        } else {
            $('#myHeader').hide();
            $('#dg').datagrid('clearSelections');
        }

    }

    function count_row() {
        var count = $('#dg').datagrid('getChecked').length;
        if (count) {
            $('#myHeader').show();
            document.getElementById("count").innerHTML = count + " items selected";
        } else {
            $('#myHeader').hide();
            $('#dg').datagrid('clearSelections');
            document.getElementById("count").innerHTML = 0 + " items selected";
        }
    }

    function closemenu() {
        $('#myHeader').hide();
    }

    window.onscroll = function() {
        myFunctiona();
    };

    var header = document.getElementById("myHeader");
    var sticky = header.offsetTop;

    function myFunctiona() {
        if (window.pageYOffset > sticky) {
            header.classList.add("sticky");
        } else {
            header.classList.remove("sticky");
        }
    }

    function delete_list_podt() {
        var selectedRow = $('#dg').datagrid('getSelected');
        if (!selectedRow) {
            return;
        }

        var checkedRows = $('#dg').datagrid('getChecked');
        if (!checkedRows || checkedRows.length === 0) {
            return;
        }

        var ids = checkedRows.map(function(row) {
            return row.podtpk;
        });

        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        $.messager.confirm('Confirm', 'Are you sure you want to delete the selected items?', function(r) {
            if (r) {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('api.delete-podt') }}",
                    data: {
                        ids: ids
                    },
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        $('#dg').datagrid('reload');
                        $.messager.show({
                            title: '',
                            msg: response.msg,
                            timeout: 3000,
                            showType: 'slide',
                            icon: 'info',
                            width: 430,
                            height: 55,
                            style: {
                                left: 0,
                                right: '',
                                top: '',
                                bottom: -document.body.scrollTop - document.documentElement.scrollTop
                            }
                        });
                        $('#dg').datagrid('reload');
                        closemenu();
                    },
                    error: function(xhr, status, error) {
                        $.messager.show({
                            title: 'Error',
                            msg: 'An error occurred: ' + (xhr.responseText || error),
                            timeout: 3000,
                            showType: 'slide',
                            icon: 'error',
                            width: 430,
                            height: 55,
                        });
                        $('#dg').datagrid('reload');
                    }
                });
            }
        });
    }

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
        window.location.href = "{{ URL::to('purchase-order')}}";
    }

    function cancel_spb() {
        $('#ConfirmCancel').modal('show');
        $('#dg').datagrid('reload');
    }


    function KlikLoopUp(nilai) {

        $('#nilai_btn').val(nilai);
        var totalRows = $('#dg').datagrid('getRows').length;
        if (nilai == "submit") {
            if (!totalRows) {
                $('#warningtable').removeClass('d-none');
                return;
            }
        }

        $('#ModalLookUpPo').modal('show');
    }

    function SearchPurchaseRequest() {
        $('#dgLookUpPo').datagrid('load', {
            searchByInputOrder: $('#searchByInputOrder').val(),
        });
    }

    function SubmitModalLookup(dgname) {
        var popk = $('#popk2').val();
        if (dgname == 'ORDERLIST') {
            var selectedRow = $('#dgLookUpPo').datagrid('getSelected');
            if (!selectedRow) {
                $.messager.alert('Warning', 'No data selected', 'warning');
                return;
            }

            var checkedRows = $('#dgLookUpPo').datagrid('getChecked');
            if (!checkedRows || checkedRows.length === 0) {
                $.messager.alert('Warning', 'No data selected', 'warning');
                return;
            }
            var rows = $('#dgLookUpPo').datagrid('getChecked');
        }

        var formData = {
            popk: popk,
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        if (rows.length > 0) {
            console.log("Rows selected: " + rows.length);
            console.log("Selected rows: " + JSON.stringify(rows));
        } else {
            console.log("No rows are selected.");
        }

        formData.rows = rows;

        if (dgname == 'ORDERLIST') {
            url = "{{ route('add-lookup-po') }}";
        }

        $.ajax({
            type: "POST",
            url: url,
            data: formData,
            success: function(data) {
                console.log("Success:", data);
                window.location.reload();
            },
            error: function(xhr, status, error) {
                console.log("Error:", error);
                console.log(xhr);
            }
        });
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

    function KlikPrint(popk) {
        if (!popk) {
            alert('POPK not found');
            return;
        }

        var baseUrl = "{{ url('/') }}";
        var url = baseUrl + "/purchase-order/print/" + popk;
        window.open(url, '_blank');
    }
</script>
@endsection

@section('content')

<div class="modal fade" id="ModalLookUpPo" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content p-4 gap-1">
            <div class="modal-header d-flex flex-column m-0 p-0 py-2 border-0 align-items-start">
                <div class="container-fluid">
                    <div class="d-flex gap-2 mb-3" data-bs-dismiss="modal" aria-label="Close" data-bs-toggle="modal" data-bs-target="#ChooseModal">
                        <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="25" height="25">
                        <span>BACK</span>
                    </div>
                </div>
            </div>
            <div class="p-0">
                <div class="p-0 fw-bold h5">Lookup From Purchase Request</div>
            </div>
            <div class="p-0">
                <div class="input-group flex-nowrap input-group-search" style="height:34px;">
                    <span class="input-group-text search">
                        <img src="{{ asset('public/css/images/Shape.png') }}" plain="true" width="18px" height="18px">
                    </span>
                    <input type="text" class="form-control search" id="searchByInputOrder" onkeyup="SearchPurchaseRequest()" placeholder="Search...">
                </div>
            </div>

            <input type="text" name="popk2" id="popk2" value="{{ $dt_po->popk }}">

            <div class="modal-body d-flex flex-column gap-3 m-0 p-0">
                <div>
                    <table id="dgLookUpPo" class="easyui-datagrid" title="" style="width:100%; height:auto" url="{{ route('get-lookup-po') }}"
                        align="center" toolbar="#tb" striped="true" pagination="true" fitColumns="true"
                        pageList="[100,200,300,500]"  pageSize="100" method="get" rownumbers="false" multiple="true" collapsible="true"
                        data-options="multiSort:true, remoteSort:false, border:true, dnd:true, checkOnSelect:false">
                        <thead>
                            <tr>
                                <th field="ck" width="15" checkbox="true"></th>
                                <th field="tanggal" width="10%">Tanggal</th>
                                <th field="nopr" width="10%">No PR</th>
                                <th field="user" width="10%">Pengirim</th>
                                <th field="depnm" width="10%">Penerima</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
            <div class="modal-footer m-0 p-0">
                <input type="hidden" id="nilai_unit_filter" name="nilai_unit_filter">
                <button type="button" id="get_button" class="btn btn-secondary my-2 py-1" onclick="SubmitModalLookup('ORDERLIST')">GET</button>
            </div>
        </div>
    </div>
</div>

<div class="main-wrapper min-vh-100">
    <div class="container-fluid">

        <div class="d-flex flex-column p-4 m-3">
            <!-- FORM ATAS -->
            <form id="form_header">
                @csrf
                <input type="hidden" id="popk" name="popk" value="{{ $dt_po->popk }}">
                <input type="hidden" name="nilai_btn" id="nilai_btn" value="">

                <div class="d-flex gap-3">
                    @if(request()->segment(2) == 'detail')
                    <div class="d-flex pointer" onclick="GoBack()">
                        <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                    </div>
                    @elseif(request()->segment(1) == 'add-spb')
                    <div class="d-flex pointer" onclick="cancel_spb()">
                        <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                    </div>
                    @endif

                    <div class="d-flex flex-column flex-fill">
                        <div class="d-flex justify-content-between">
                            @if( $dt_po->nobukti != null)
                            <div class="d-flex fw-bold">No. PO : {{ $dt_po->nobukti}}</div>
                            @else
                            <div class="d-flex fw-bold ">Input Data PO Request</div>
                            @endif

                            <div class="d-flex gap-4 d-none" id="cancel_submit">
                                <button class="btn-transparent border-0 fw-bold" onclick="event.preventDefault(); cancel_spb()">Cancel</button>
                                <button class="btn-black rounded px-3 d-none" id="save_submit" onclick="event.preventDefault(); submit_spb('submit');">Submit</button>
                            </div>

                            <div class="d-flex gap-4 d-none" id="edit_data">
                                <button class="btn-black rounded px-3" onclick="event.preventDefault(); edit_data();">Edit</button>
                                <button class="btn-black rounded px-3" onclick="event.preventDefault(); KlikPrint({{ $dt_po->popk }});"><i class="fa-solid fa-print"></i> Pdf</button>
                            </div>

                            <div class="d-flex gap-4 d-none" id="cancel_save_edit">
                                <button class="btn-transparent border-0 fw-bold" onclick="event.preventDefault(); cancel_edit()">Cancel</button>
                                <button class="btn-black rounded px-3" id="save_edit" onclick="event.preventDefault(); submit_spb('save');">Save</button>
                            </div>
                        </div>


                        <div class="d-flex my-3">
                            <div class="d-flex flex-column flex-fill">

                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Tgl PO</div>
                                    <input id="tglpo" name="tglpo" class="easyui-datebox" style="width:210px;" placeholder="YYYY-MM-DD" data-options="formatter:myformatter, parser:myparser" value="{{ \Carbon\Carbon::parse($dt_po->tglpo)->format('d/m/Y') }}">
                                </div>

                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Term</div>
                                    <input id="ab" class="easyui-combobox col-2" name="abpk" method="get" value="{{ $dt_po->abpk }}" data-options="valueField:'abpk', textField:'abnm', prompt:'{{ $dt_po->abnm }}', url:'{{ route('api.get-ab') }}', editable:false, limitToList:'true'">
                                </div>

                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Supplier</div>
                                    <input id="sup" class="easyui-combobox col-2" style="height: 30px;" name="suppk" method="get" value="{{ $dt_po->suppk }}" data-options="valueField:'suppk', textField:'supnm', prompt:'{{ $dt_po->supnm }}', url:'{{ route('api.get-sup') }}', editable:false, limitToList:'true'">
                                </div>

                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Mata Uang</div>
                                    <input id="curid" class="easyui-combobox col-2" name="curpk" method="get" value="{{ $dt_po->curpk }}" data-options="valueField:'curpk', textField:'curid', prompt:'{{ $dt_po->curid }}', url:'{{ route('api.get-cur') }}', editable:false, limitToList:'true'">
                                </div>


                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Note Out</div>
                                    <input id="ket" class="easyui-validatebox height_input" name="ket" type="text" value="{{ $dt_po->ket }}">
                                </div>

                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Note In</div>
                                    <input id="ket2" class="easyui-validatebox height_input" name="ket2" type="text" value="{{ $dt_po->ket2 }}">
                                </div>

                            </div>

                            <div class="d-flex flex-column flex-fill">

                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">No PO</div>
                                    <input id="nopo" class="easyui-validatebox height_input" name="nopo" type="text" value="{{ $dt_po->nopo }}">
                                </div>

                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Total</div>
                                    <input id="totbeli" class="easyui-validatebox height_input" name="totbeli" type="text" value="{{ number_format($dt_po->totbeli, 2, '.', ',') }}">
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="d-flex align-items-center gap-2">

                <div class="d-flex justify-content-end align-items-center gap-3 mb-3">
                    <div id="add" class="p-0 d-none d-flex gap-3">
                        <div class="font-5 color-view-blue pointer fw-bold" onclick="tambahrow();" style="color:#359DD9; text-decoration:none;">Add Data</div>
                        <!-- <div class="font-5 blue pointer fw-bold" id="savedt" onclick="saverow();">Save</div> -->
                    </div>
                </div>

                <div id="lookupgis" class="flex-grow-0 d-none">
                    <button onclick="KlikLoopUp()" class="btn btn-sm btn-dark">Lookup Purchase Request</button>
                </div>


                <div id="warningtable" class="font-warning pb-3 d-none">
                    *) Belum ada list detail spb pada table list spb. Silahkan lengkapi data dan masukan list spb terlebih dahulu!
                </div>

            </div>

            <div class="p-0">
                <table id="dg" class="easyui-datagrid" title="" align="center" toolbar="#tb" striped="false" pagination="false" method="get"
                    rownumbers="false" singleSelect="false" collapsible="true" fitColumns="true" idField="podtpk"
                    data-options="onCheck:function(){menu();},onCheckAll:function(){menu();},onUncheck:function(){menu();},onUncheckAll:function(){},
                    multiSort:true, remoteSort:false, border:true, dnd:true, checkOnSelect:false,  onClickRow: onClickRowHandler,
                    rowStyler:function(index,row){
                        if (row.copy==3){
                            return 'color:#000000;background-color:#D6D6D6;';
                        }
                    }">
                    <thead>
                        <tr>
                            <th field="ck" width="auto" styler="styler1" checkbox="true" hidden="true"></th>
                            <th field="index" width="auto" styler="styler2" editor="disabled">No</th>
                            <th field="brgnm" width="auto" styler="styler2" editor="text">Nama Barang/Spesifikasi</th>
                            <th field="jmlbeli" width="auto" styler="styler2" editor="text">Qty</th>
                            <th field="unit" width="auto" styler="styler2" editor="text">Satuan</th>
                            <th field="hrgbeli" width="auto" styler="styler2" editor="text">Harga Satuan</th>
                            <th field="total" width="auto" styler="styler3" editor="disabled">Jumlah Harga</th>
                        </tr>
                    </thead>
                </table>
            </div>

            <div class="header" id="myHeader" onclick="" style="display:none;">
                <div id="count" style="display:none;"></div>

                <div class="delete" id="mdelete" style="width:auto; display:none;">
                    <a href="javascript:void(0)" plain="true" onclick="delete_list_podt();" style="color: #FFFFFF; text-decoration: none;"> 
                        Delete 
                    </a>
                </div>

                <div class="delete" id="mclose" style="width:auto;">
                    <a href="javascript:void(0)" plain="true" onclick="$('#myHeader').hide(); $('#dg').datagrid('clearSelections'); $('#dg').datagrid('clearChecked'); closemenu();" style="color: #FFFFFF; text-decoration: none;">
                        Close menu 
                    </a>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
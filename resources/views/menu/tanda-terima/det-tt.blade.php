@extends('layout.main')

@section('css_custom')
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<style>
    .datagrid-header td,
    .datagrid-body td,
    .datagrid-footer td {
        border-color: #ffffff;
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

    .bg-white {
        color: #000;
        background: #fff;
        border: 1px solid black;
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

    .bg-black {
        color: #fff;
        background: #000;
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

    .color-view-blue {
        color: rgb(53, 157, 217);
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

    @keyframes blink {
        0% {
            opacity: 1;
        }

        50% {
            opacity: 0;
        }

        100% {
            opacity: 1;
        }
    }

    .warning-blink {
        font-size: 12px;
        font-weight: bold;
        color: red;
        text-align: center;
        animation: blink 2s infinite;
    }
    .warning-blink-1 {
        font-size: 14px;
        font-weight: bold;
        color: red;
        text-align: center;
    }

    .font-warning {
        font-size: 12px;
        font-weight: bold;
        color: red;
        text-align: left;
    }
</style>
@endsection

@section('js_custom')
<script>
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

    $(function() {
        var pathArray = window.location.pathname.split("/");
        var pathname3 = pathArray[4];

        var nilai = (pathname3 === 'input');
        var ttpk = $('#ttpk').val();

        $('#dg').edatagrid({
            saveUrl: '{{ route("api.insert-ttdt", "") }}/' + ttpk,
            updateUrl: '{{ route("api.update-ttdt") }}',
            editing: nilai,
            onSuccess: function(index, row) {
                $(this).edatagrid('saveRow');
                $(this).datagrid('reload');
                closemenu();
                console.log("ROW : " + row.ttdtpk);
                console.log("nilai : " + nilai);
            },
            onDblClickRow: function(index, row) {
                var isEdit = $('#isEdit-' + row.ttdtpk).val();
                var ttdtpk = row.ttdtpk;

                if (isEdit == 'true') {
                    document.getElementById('isEdit-' + row.ttdtpk).value = false;
                    hiddenSave(index, ttdtpk);
                    $(this).datagrid('endEdit', index);
                } else {
                    console.log('onedit : ' + row.ttdtpk);
                    $('#isEdit-' + row.ttdtpk).val('true');

                    if ($(this).edatagrid('options').editing) {
                        $(this).datagrid('beginEdit', index);
                        console.log("Double click edit mode");
                    }
                }
            },
            onBeforeEdit: function(index, row) {
                $('#savedt').removeClass('d-none');
                var token = $('meta[name="csrf-token"]').attr('content');
                row._token = token;
            },
            onBeforeSave: function(index) {
                var editors = $('#dg').datagrid('getEditors', index);
                $('#savedt').addClass('d-none');
            }
        });
    });

    $(function() {
        var dg = $('#dg').datagrid();
        dg.datagrid('getPanel').find('div.datagrid-header').css('background', 'transparent');
        dg.datagrid('getPanel').find('div.datagrid-header').css('border', '1px solid #858585');
        dg.datagrid('getPanel').find('div.datagrid-header').css('border-radius', '8px');
        dg.datagrid('getPanel').find('div.datagrid-header td[field]').css('border-right', 'none');
        dg.datagrid('getPanel').find('div.datagrid-body').css('overflow-y', 'hidden');
        dg.datagrid('getPanel').css('border', 'none');
        dg.datagrid('getPanel').addClass('lines-no');
        dg.datagrid('getPanel').addClass('lines-no3');

        $('#dgLookUpBeli').datagrid('getPanel').find('div.datagrid-header').css('background', '#FFFFFF');
        $('#dgLookUpBeli').datagrid('getPanel').find('div.datagrid-header').css('border-top','1px solid #858585');
        $('#dgLookUpBeli').datagrid('getPanel').find('div.datagrid-header').css('border-top-left-radius', '8px');
        $('#dgLookUpBeli').datagrid('getPanel').find('div.datagrid-header').css('border-bottom','1px solid #858585');
        $('#dgLookUpBeli').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-left-radius','8px');
        $('#dgLookUpBeli').datagrid('getPanel').find('div.datagrid-header').css('border-top-right-radius', '8px');
        $('#dgLookUpBeli').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-right-radius','8px');
        $('#dgLookUpBeli').datagrid('getPanel').find('div.datagrid-header').css('border-left','1px solid #858585');
        $('#dgLookUpBeli').datagrid('getPanel').find('div.datagrid-header').css('border-right','1px solid #858585');
        $('#dgLookUpBeli').datagrid('getPanel').find('div.datagrid-header td[field]').css('border-right', 'none');
        $('#dgLookUpBeli').datagrid('getPanel').css('border', 'none');
        $('#dgLookUpBeli').datagrid('getPanel').find('div.datagrid-header').addClass('lines-no');
        $('#dgLookUpBeli').datagrid('getPanel').find('div.datagrid-header').addClass('lines-no3');
        $('#dgLookUpBeli').datagrid('getPanel').find('div.datagrid-header').addClass('col');
        $('#dgLookUpBeli').datagrid('getPanel').addClass('lines-no');
        $('#dgLookUpBeli').datagrid('getPanel').addClass('lines-no3');


        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').css('background', '#FFFFFF');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').css('border-top','1px solid #858585');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').css('border-top-left-radius', '8px');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').css('border-bottom','1px solid #858585');
        $('#dgLookUpPo').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-left-radius','8px');
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
        $('#dg').datagrid('clearSelections');
        $('#dg').datagrid('clearChecked');
    }

    $(function() {
        var pathArray = window.location.pathname.split("/");
        var pathname3 = pathArray[3];
        if (pathname3 == 'add-tt') {
            $('#dg').datagrid('showColumn', 'ck');
        } else {
            $('#dg').datagrid('hideColumn', 'ck');
        }
    });

    $(function() {
        editDisplay();
    });

    function editDisplay() {
        var pathArray = window.location.pathname.split("/");
        var pathname3 = pathArray[3];
        if (pathname3 == 'detail') {
            $('#edit_data').removeClass('d-none');
            $('#lookuppembelian').addClass('d-none');
            $('#lookuppo').addClass('d-none');
            $('#add').addClass('d-none');
            $('#addnew').addClass('d-none');
            $('#back_edit').removeClass('d-none');
            $('#back_input').addClass('d-none');
            $('#tgl').datebox('disable');
            $('#nobukti').attr('disabled', true);
            $('#penerima').attr('disabled', true);
            $('#ket').attr('disabled', true);
        } else if (pathname3 == 'add-tt') {
            $('#cancel_submit').removeClass('d-none');
            $('#save_submit').removeClass('d-none');
            $('#nobukti').attr('disabled', true);
            $('#add').removeClass('d-none');
            $('#lookuppembelian').removeClass('d-none');
            $('#lookuppo').removeClass('d-none');
            $('#back_edit').addClass('d-none');
            $('#back_input').removeClass('d-none');
        } else {
            $('#cancel_submit').addClass('d-none');
            $('#edit_data').addClass('d-none');
        }
    }

    function edit_data() {
        $('#edit_data').addClass('d-none');
        $('#cancel_save_edit').removeClass('d-none');
        $('#save_edit').removeClass('d-none');
        $('#lookuppembelian').removeClass('d-none');
        $('#lookuppo').removeClass('d-none');
        $('#addnew').removeClass('d-none');
        $('#add').removeClass('d-none');
        $('#tgl').datebox('enable');
        $('#nobukti').attr('disabled', true);
        $('#penerima').attr('disabled', false);
        $('#ket').attr('disabled', false);
        $('#dg').edatagrid('options').editing = true;
        $('#dg').datagrid('showColumn', 'ck');
        $('#savedt').addClass('d-none');
    }

    function cancel_edit() {
        $('#edit_data').removeClass('d-none');
        $('#dg').edatagrid('options').editing = false;
        $('#dg').datagrid('hideColumn', 'ck');
        $('#cancel_save_edit').addClass('d-none');
        $('#add').addClass('d-none');
        $('#lookuppembelian').addClass('d-none');
        $('#lookuppo').addClass('d-none');
        var rows = $('#dg').datagrid('getRows');
        for (var i = 0; i < rows.length; i++) {
            $('#dg').datagrid('endEdit', i);
        }
        $('#tgl').datebox('disable');
        $('#penerima').attr('disabled', true);
        $('#ket').attr('disabled', true);
        $('#nobukti').combobox('disable');
        $('#addnew').addClass('d-none');
    }

    $(function() {
        var ttpk = $('#ttpk').val(); // Make sure this input has a value
        var url = "{{ route('api.get-ttdt', ['ttpk' => ':ttpk']) }}";
        url = url.replace(':ttpk', ttpk);

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

    function tambahrow() {
        $('#dg').edatagrid('saveRow');
        $('#dg').edatagrid('addRow');
        var index = $('#dg').edatagrid('getRows').length - 1; // the editing row index
        var ed = $('#dg').edatagrid('getEditors', index)[0]; // get the first editor
        $(ed.target).focus();
    }

    function showAlert(code, message) {
        if (code == 200 || code == 201 || code == 202 || code == 204 || code == 'done') {
            document.getElementById("success-message").textContent = message;
            $('#alert-success').toast('show');
        } else if (code == 'pause') {
            document.getElementById("dark-message").textContent = message;
            $('#alert-dark').toast('show');
        } else if (code == 'codeApproved') {
            document.getElementById("success-message").textContent = message;
            $('#alert-success').toast('show');
        } else {
            document.getElementById("danger-message").textContent = message;
            $('#alert-danger').toast('show');
        }
    }

    function saverow() {
        showAlert('codeApproved', 'Data has been saved successfully!');
        $('#dg').edatagrid('saveRow');
        closemenu();
    }

    $(function() {
        cek_input_header();
    })

    function cek_input_header() {
        var pathArray = window.location.pathname.split("/");
        var pathname3 = pathArray[3];
        var baris = $('#dg').datagrid('getData').total;

        if (pathname3 == 'add-tt') {
            $('#save_submit').removeClass('d-none');
        } else {
            $('#save_edit').removeClass('d-none');
        }
    }

    function submut_tt(nilai) {
        $('#nilai_btn').val(nilai);
        var totalRows = $('#dg').datagrid('getRows').length;
        if (nilai == "submit") {
            if (!totalRows) {
                $('#warningtable').removeClass('d-none');
                return;
            }
        }

        var penerimaField = document.getElementById("penerima");
        if (!penerimaField || !penerimaField.value.trim()) {
            $('#warningtable2').removeClass('d-none'); // tampilkan warning
            return;
        } else {
            $('#warningtable2').addClass('d-none'); // sembunyikan kalau valid
        }

        var formElement = document.getElementById("form_ttpk");
        var requiredFields = ["ttpk"];
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
            return;
        }

        var formData = new FormData(formElement);

        $.ajax({
            method: "POST",
            url: "{{ route('api.edit-save-header-ttdt') }}",
            data: formData,
            processData: false,
            contentType: false,
            cache: false,
            success: function(response) {
                console.log("COBA RESPONSE" + JSON.stringify(response));
                if (nilai == 'submit') {
                    window.location.href = "{{ route('page.tanda-terima') }}";
                } else {
                    showAlert(200, response.message);
                    cancel_edit();
                }
            },
            error: function(xhr, status, error) {
                console.log("error add cuti HERE >>>>>>>>>>>>>>>> " + JSON.stringify(xhr));
                if (xhr.responseJSON && xhr.responseJSON.message) showAlert(400, xhr.responseJSON.message);
                else showAlert(400, JSON.stringify(error))
            }
        });
    }

    function back_input_data() {
        $('#ConfirmCancel').modal('show');
        $('#dg').datagrid('reload');
    }

    function ConfirmDelete() {
        localStorage.setItem('returnBack', true);
        var url = window.location.pathname;
        var segments = url.split('/');
        var ttpk = $('#ttpk').val();
        var segment3 = segments[3];
        var formData = {
            segment3: segment3,
            ttpk: ttpk,
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        console.log('ttpk : ' + ttpk + ' segment : ' + segment3)
        $.ajax({
            type: "POST",
            url: "{{ route('back.tt') }}",
            data: formData,
            success: function(data) {
                console.log("Success:", data);
                window.location.href = "{{ route('page.tanda-terima') }}";
            },
            error: function(xhr, status, error) {
                console.log("Error:", error);
            }
        });

    }

    function delete_list_ttdt() {
        var selectedRow = $('#dg').datagrid('getSelected');
        if (!selectedRow) {
            return;
        }

        var checkedRows = $('#dg').datagrid('getChecked');
        if (!checkedRows || checkedRows.length === 0) {
            return;
        }

        var ids = checkedRows.map(function(row) {
            return row.ttdtpk;
        });

        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        $.messager.confirm('Confirm', 'Are you sure you want to delete the selected items?', function(r) {
            if (r) {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('api.delete-ttdt') }}",
                    data: {
                        ids: ids
                    },
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        // $('#dg').datagrid('reload');
                        // $.messager.show({
                        //     title: '',
                        //     msg: response.msg,
                        //     timeout: 3000,
                        //     showType: 'slide',
                        //     icon: 'info',
                        //     width: 430,
                        //     height: 55,
                        //     style: {
                        //         left: 0,
                        //         right: '',
                        //         top: '',
                        //         bottom: -document.body.scrollTop - document.documentElement.scrollTop
                        //     }
                        // });
                        showAlert(200, response.message);
                        $('#dg').datagrid('reload');
                        $('#myHeader').hide();
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

    //STICKY
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

    function warning() {
        $('#warningtable2').removeClass('d-none');
    }

    function formatter1(value, row) {
        return `<div class='blue pointer' onclick='list_invsdt(${row.ttpk})'>Select</div>`;
    }

    function styler1(index, row) {
        return 'border-top:1px solid #ededed;border-bottom:3px solid #ededed !important;border-left:1px solid #ededed;border-top-left-radius:5px;' +
            'border-bottom-left-radius:5px;height:35px;';
    }

    function styler2(index, row) {
        return 'border-top:1px solid #ededed;border-bottom:3px solid #ededed !important;';
    }

    function styler3(index, row) {
        return 'border-top:1px solid #ededed;border-bottom:3px solid #ededed !important;border-right:1px solid #ededed !important;border-top-right-radius:5px;' +
            'border-bottom-right-radius:5px;';
    }

    function KlikPrint(ttpk) {
        if (!ttpk) {
            alert('PRPK not found');
            return;
        }

        var baseUrl = "{{ url('/') }}";
        var url = baseUrl + "/tanda-terima/print/" + ttpk;
        window.open(url, '_blank');
    }

    function Attrttdtpk(value, row) {
        var hiddenSave = '<input type="text" id="isEdit-' + row.ttdtpk + '" value="false">';
        return hiddenSave;
    }

    function KlikLoopUpPembelian(){
       $('#ModalLookUpPembelian').modal('show');
    }

    $(function() {
        $('#ModalLookUpPembelian').on('shown.bs.modal', function() {
            $('#dgLookUpBeli').datagrid('resize');
        })

        $('#ModalLookUpPo').on('shown.bs.modal', function() {
            $('#dgLookUpPo').datagrid('resize');
        })
    });

    
    function loadDataByFilterModalPembelian() {
        var filterByYear = $('#filterByYear').val();
        var filterByMonth = $('#filterByMonth').val();
        var searchByInputOrderPembelian = $('#searchByInputOrderPembelian').val();

        console.log(`searchByInputOrderPembelian : ${searchByInputOrderPembelian}`);

        $('#dgLookUpBeli').datagrid('load', {
            // searchByInputOrderPembelian: $('#searchByInputOrderPembelian').val(),
            filterByYear: filterByYear,
            filterByMonth: filterByMonth,
            searchByInputOrderPembelian: searchByInputOrderPembelian,
        });

        
    }

    function SubmitModalLookupPembelian() {
        const selected = $('#dgLookUpBeli').datagrid('getChecked');

        if (selected.length === 0) {
            alert("Pilih minimal satu item detail Beli terlebih dahulu.");
            return;
        }

        const ttpk = $('#ttpk3').val();
        const belidtpkList = selected.map(item => item.belidtpk);

        $.ajax({
            url: '{{ route('add-beli-to-tt') }}',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                ttpk: ttpk,
                belidtpk: belidtpkList
            },
                success: function(response, textStatus, xhr) {
                    const statusCode = xhr.status; // Ambil status HTTP
                    console.log("HTTP Status Code:", statusCode); // Debugging status code
                    $('#ModalLookUpPembelian').modal('hide');
                    $('#dgLookUpBeli').datagrid('clearSelections').datagrid('clearChecked');
                    $('#dg').datagrid('reload');

                    //===========untuk milih bulan lama jika berhasil klik get, maka akan ter reset ke bulan berjalan//
                    var currentDate = new Date();
                    var currentMonth = String(currentDate.getMonth() + 1).padStart(2, '0');
                    var currentYear = currentDate.getFullYear();

                    // Matikan sementara event onChange
                    $('#filterByMonth').combobox('options').onChange = function () {};
                    $('#filterByYear').combobox('options').onChange = function () {};

                    $('#filterByMonth').combobox('setValue', currentMonth);
                    $('#filterByYear').combobox('setValue', currentYear);

                    // Aktifkan kembali fungsi jika perlu (opsional)
                    $('#filterByMonth').combobox('options').onChange = loadDataByFilterModalPembelian;
                    $('#filterByYear').combobox('options').onChange = loadDataByFilterModalPembelian;


                    // Kosongkan input pencarian
                    $('#searchByInputOrderPembelian').val('');

                    // Muat ulang data tanpa filter (data default)
                    $('#dgLookUpBeli').datagrid('load', {
                        searchByInputOrderPembelian: ''
                    });

                    $('#dgLookUpBeli').datagrid('reload');
                    //===============//

                    showAlert(statusCode, response.message);
                },

                error: function(xhr, status, error) {
                    console.error("Error detail:", xhr.responseText);
                    alert('An error occurred. Please try again.');
                }
        });
    }

    //mengaktifkan button get pada list Beli
    $(document).ready(function() {
        $('#get_buttonBeli').prop('disabled', true).removeClass('btn-dark').addClass('btn-secondary');

        // Event listener untuk perubahan checkbox di EasyUI datagrid
        $('#dgLookUpBeli').datagrid({
            onCheck: toggleGetButton,
            onUncheck: toggleGetButton,
            onCheckAll: toggleGetButton,
            onUncheckAll: toggleGetButton
        });

        function toggleGetButton() {
            const checked = $('#dgLookUpBeli').datagrid('getChecked');
            if (checked.length > 0) {
                $('#get_buttonBeli').prop('disabled', false).removeClass('btn-secondary').addClass('btn-dark');
            } else {
                $('#get_buttonBeli').prop('disabled', true).removeClass('btn-dark').addClass('btn-secondary');
            }
        }
    });

    function KlikCloseModalPembelian() {
        var currentDate = new Date();
        var currentMonth = String(currentDate.getMonth() + 1).padStart(2, '0');
        var currentYear = currentDate.getFullYear();

        // Matikan sementara event onChange
        $('#filterByMonth').combobox('options').onChange = function () {};
        $('#filterByYear').combobox('options').onChange = function () {};

        $('#filterByMonth').combobox('setValue', currentMonth);
        $('#filterByYear').combobox('setValue', currentYear);

        // Aktifkan kembali fungsi jika perlu (opsional)
        $('#filterByMonth').combobox('options').onChange = loadDataByFilterModalPembelian;
        $('#filterByYear').combobox('options').onChange = loadDataByFilterModalPembelian;


        // Kosongkan input pencarian
        $('#searchByInputOrderPembelian').val('');

        // Muat ulang data tanpa filter (data default)
        $('#dgLookUpBeli').datagrid('load', {
            searchByInputOrderPembelian: ''
        });

        $('#dgLookUpBeli').datagrid('reload');
        // Bersihkan seleksi dan centang
        $('#dgLookUpBeli').datagrid('clearSelections').datagrid('clearChecked');
    }

    function KlikLoopUpPo(){
       $('#ModalLookUpPo').modal('show');
    }

    function loadDataByFilterModalPo() {
        // var searchByInputOrderPo = $('#searchByInputOrderPo').val();
        // console.log(`searchByInputOrderPo : ${searchByInputOrderPo}`);

        // $('#dgLookUpPo').datagrid('load', {
        //     searchByInputOrderPo: searchByInputOrderPo,
        // });

        const keyword = $('#searchByInputOrderPo').val().trim();
        console.log(`searchByInputOrderPo: ${keyword}`);
        var filterByYearPo = $('#filterByYearPo').val();
        var filterByMonthPo = $('#filterByMonthPo').val();

        $('#dgLookUpPo').datagrid('load', {
            searchByInputOrderPo: keyword,
            filterByYearPo: filterByYearPo,
            filterByMonthPo: filterByMonthPo,
        });
    }

    function SubmitModalLookupPo() {
        const selected = $('#dgLookUpPo').datagrid('getChecked');

        if (selected.length === 0) {
            alert("Pilih minimal satu item detail PO terlebih dahulu.");
            return;
        }

        const ttpk = $('#ttpk4').val();
        const podtpkList = selected.map(item => item.podtpk);

        $.ajax({
            url: '{{ route('add-po-to-tt') }}',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                ttpk: ttpk,
                podtpk: podtpkList
            },
                success: function(response, textStatus, xhr) {
                    const statusCode = xhr.status; // Ambil status HTTP
                    console.log("HTTP Status Code:", statusCode); // Debugging status code
                    $('#ModalLookUpPo').modal('hide');
                    $('#dgLookUpPo').datagrid('clearSelections').datagrid('clearChecked');
                    $('#dg').datagrid('reload');

                    //===========untuk search dan jika berhasil klik get, maka akan ter reset list awal/

                    // Kosongkan input pencarian
                    $('#searchByInputOrderPo').val('');

                    // Muat ulang data tanpa filter (data default)
                    $('#dgLookUpPo').datagrid('load', {
                        searchByInputOrderPo: ''
                    });

                    $('#dgLookUpPo').datagrid('reload');
                    //===============//

                    showAlert(statusCode, response.message);
                },

                error: function(xhr, status, error) {
                    console.error("Error detail:", xhr.responseText);
                    alert('An error occurred. Please try again.');
                }
        });
    }

    //mengaktifkan button get pada list Beli
    $(document).ready(function() {
        $('#get_buttonPo').prop('disabled', true).removeClass('btn-dark').addClass('btn-secondary');

        // Event listener untuk perubahan checkbox di EasyUI datagrid
        $('#dgLookUpPo').datagrid({
            onCheck: toggleGetButton,
            onUncheck: toggleGetButton,
            onCheckAll: toggleGetButton,
            onUncheckAll: toggleGetButton
        });

        function toggleGetButton() {
            const checked = $('#dgLookUpPo').datagrid('getChecked');
            if (checked.length > 0) {
                $('#get_buttonPo').prop('disabled', false).removeClass('btn-secondary').addClass('btn-dark');
            } else {
                $('#get_buttonPo').prop('disabled', true).removeClass('btn-dark').addClass('btn-secondary');
            }
        }
    });

    function KlikCloseModalPo() {
        // Kosongkan input pencarian
        $('#searchByInputOrderPo').val('');

        // Muat ulang data tanpa filter (data default)
        $('#dgLookUpPo').datagrid('load', {
            searchByInputOrderPo: ''
        });

        // Bersihkan seleksi dan centang
        $('#dgLookUpPo').datagrid('clearSelections').datagrid('clearChecked');
    }

</script>
@endsection

@section('content')

<div class="main-wrapper min-vh-100">
    <div class="container-fluid">
        <div class="d-flex flex-column p-4 m-3">

            <!-- FORM ATAS -->
            <form id="form_ttpk">
                @csrf
                <input type="hidden" id="ttpk" name="ttpk" value="{{ $dt_tt->ttpk }}">
                <input type="hidden" name="nilai_btn" id="nilai_btn" value="">

                <div class="d-flex gap-3">
                    <div id="back_edit" class="d-flex pointer" onclick="ConfirmDelete()">
                        <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                    </div>
                    <div id="back_input" class=" d-none d-flex pointer" onclick="back_input_data()">
                        <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                    </div>
                    <div class="d-flex flex-column flex-fill">
                        <div class="d-flex justify-content-between">
                             @if( $dt_tt->nobukti != null)
                            <div class="d-flex fw-bold">No. Bukti : {{ str_pad($dt_tt->nobukti, 6, '0', STR_PAD_LEFT) }}</div>
                            @else
                            <div class="d-flex fw-bold ">Input Data Purchase Request</div>
                            @endif

                            <div class="d-flex gap-4 d-none" id="cancel_submit">
                                <button class="btn-transparent border-0 fw-bold" onclick="event.preventDefault(); back_input_data()">Cancel</button>
                                <button class="btn-black rounded px-3 d-none" id="save_submit" onclick="event.preventDefault(); submut_tt('submit');">Submit</button>
                            </div>

                            @if(request()->segment(2) == 'detail')
                                <div class="d-flex gap-2 d-none" id="edit_data">
                                    @if(isset($dt_tt) && $getuserpk == $getuserpk)
                                        <button class="btn-black rounded px-3" onclick="event.preventDefault(); edit_data();">Edit</button>
                                        <button class="btn-black rounded px-3" onclick="event.preventDefault(); KlikPrint({{ $dt_tt->ttpk }});"><i class="fa-solid fa-print"></i> Print</button>
                                    @else
                                        <button class="btn-black rounded px-3" onclick="event.preventDefault(); KlikPrint({{$dt_tt->ttpk }});"><i class="fa-solid fa-print"></i> Print</button>
                                    @endif
                                </div>
                            @endif

                            <div class="d-flex gap-4 d-none" id="cancel_save_edit">
                                <button class="btn-transparent border-0 fw-bold"  onclick="event.preventDefault(); cancel_edit()">Cancel</button>
                                <button class="btn-black rounded px-3 d-none" id="save_edit" onclick="event.preventDefault(); submut_tt('save');">Save</button>
                            </div>
                        </div>
                        <div class="d-flex my-3">
                            <div class="d-flex flex-column flex-fill">
                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">No. Bukti</div>
                                    <input id="nobukti" class="easyui-validatebox height_input" name="nobukti" type="text" value="{{ str_pad($dt_tt->nobukti, 6, '0', STR_PAD_LEFT) }}">
                                </div>

                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Tgl</div>
                                    <input id="tgl" name="tgl" class="easyui-datebox" style="width:210px;" placeholder="YYYY-MM-DD" data-options="formatter:myformatter, parser:myparser" value="{{ \Carbon\Carbon::parse($dt_tt->tgl)->format('d/m/Y') }}">
                                </div>
                            </div>

                            <div class="d-flex flex-column flex-fill">
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
            </form>

            <!-- TAmbah baris -->
            <div class="d-flex align-items-center gap-3 mb-3">
                <div id="add" class="p-0 d-none d-flex gap-3">
                    <div class="p-0 font-5 blue pointer fw-bold" id="adddt" onclick="tambahrow();">Add Data</div>
                    <div class="ps-0 p-0 font-5 blue pointer fw-bold d-none" id="savedt" onclick="saverow();">Save</div>
                </div>
                @if (in_array(Session::get('deppk'), ['3', '8']))
                <div id="lookuppembelian" class="flex-grow-0 d-none">
                    <button onclick="KlikLoopUpPembelian()" class="btn btn-sm btn-dark">
                        <i class="fa fa-folder-open"></i> Lookup Detail Pembelian
                    </button>
                </div>
                @endif

                @if (in_array(Session::get('deppk'), ['1', '3']))
                <div id="lookuppo" class="flex-grow-0 d-none">
                    <button onclick="KlikLoopUpPo()" class="btn btn-sm btn-dark">
                        <i class="fa fa-folder-open"></i> Lookup Detail PO
                    </button>
                </div>
                @endif
            </div>

            <div id="warningtable" class="font-warning pb-3 d-none">
                *) Belum ada data list detail. Silahkan inputkan detail barang/spesifikasi terlebih dahulu!
            </div>

            <div id="warningtable2" class="font-warning pb-3 d-none">
                *) Peminta harus diisi!
            </div>

            <div class="p-0">
                <table id="dg" class="easyui-datagrid" title="" align="center" toolbar="#tb"
                    pagination="true" method="get" striped="false" pageSize="100" pageList="[100,200,300,500]"
                    rownumbers="false" singleSelect="false" collapsible="true" fitColumns="true" idField="ttdtpk"
                    data-options="onCheck:function(){menu();},onCheckAll:function(){menu();},onUncheck:function(){menu();},onUncheckAll:function(){menu2();},
                    multiSort:true, remoteSort:false, border:true, dnd:true, checkOnSelect:false,">

                    <thead>
                        <tr>
                            <th field="ttdtpk" hidden="true" formatter="Attrttdtpk"></th>
                            <th field="ck" width="auto" styler="styler1" checkbox="true" hidden="true"></th>
                            <th field="index" width="3%" styler="styler2">No</th>
                            <th field="noinv" width="auto" styler="styler2">No Inv</th>
                            <th field="nobukti" width="auto" styler="styler2">No Bukti</th>
                            <th field="tanggal" width="auto" styler="styler2">Tgl Inv</th>
                            <th field="brgnm" editor="text" width="auto" styler="styler2">Keterangan</th>
                            <th field="satuan" editor="text" width="auto" styler="styler2">Satuan</th>
                            <th field="jumlah" editor="text" width="auto" styler="styler3">Jumlah</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="header" id="myHeader" onclick="" style="display:none;">
    <div id="count" style="display:none;"></div>
    <div class="delete" id="mdelete" style="width:auto; display:none;">
        <a href="javascript:void(0)" plain="true" onclick="delete_list_ttdt();" style="color: #FFFFFF; text-decoration: none;"> Delete </a>
    </div>
    <div class="delete" id="mclose" style="width:auto;"><a href="javascript:void(0)" plain="true" onclick="$('#myHeader').hide(); $('#dg').datagrid('clearSelections'); $('#dg').datagrid('clearChecked'); closemenu();" style="color: #FFFFFF; text-decoration: none;"> Close menu </a></div>
</div>

<!-- Konfirmasi cancel -->
<div class="modal fade" id="ConfirmCancel" tabindex="-1" aria-labelledby="ConfirmCancelLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog  modal-dialog-scrollable">
        <div class="modal-content p-4">
            <div class="modal-header d-flex flex-column m-0 p-0 py-2 border-0 align-items-start">
                <p class="p-0 m-0 fw-bold h5">Confirmation</p>
            </div>
            <div class="modal-body d-flex flex-column gap-3 m-0 p-0">
                <div class="d-flex text-left">
                    Data yang sudah diisi akan hilang jika Anda keluar dari halaman ini.
                    Mohon konfirmasinya.
                </div>
            </div>
            <div class="modal-footer m-0 p-0 border-0">
                <div class="container-fluid">
                    <div class="d-flex justify-content-end">
                        <button type="button" class="px-3 py-1 border-0 btn-transparent"
                            data-bs-dismiss="modal">Close</button>
                        <button type="button" class="px-3 py-1 btn-black rounded" data-bs-dismiss="modal"
                            onclick="ConfirmDelete();">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ModalLookUpPembelian" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="border:none;">
                <h5>Lookup From Pembelian</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="KlikCloseModalPembelian();"></button>
            </div>

            <div class="d-flex flex-wrap ps-3 pb-2 gap-2 align-items-center">
                <div class="input-group flex-nowrap input-group-search" style="height:34px;">
                    <span class="input-group-text search">
                        <img src="{{ asset('public/css/images/Shape.png') }}" plain="true" width="18px" height="18px">
                    </span>
                    <input type="text" class="form-control search" id="searchByInputOrderPembelian" onkeyup="loadDataByFilterModalPembelian()" placeholder="Search...">
                </div>

                <select class="easyui-combobox" id="filterByMonth" data-options="editable:false, onChange:loadDataByFilterModalPembelian" style="width:150px; height:34px;">
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
                    <select class="easyui-combobox" id="filterByYear" data-options="editable:false, panelHeight:'auto', onChange:loadDataByFilterModalPembelian"
                        style="width:100px; height:34px;">
                        <?php
                        for ($i = date('Y'); $i >= date('Y') - 6; $i -= 1) { ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php } ?>
                    </select>
                </div>

            </div>

            <input hidden name="ttpk3" id="ttpk3" value="{{ $dt_tt->ttpk }}">
            <div class="modal-body">
                <div>
                    <table id="dgLookUpBeli" class="easyui-datagrid" title="" style="width:100%; height:auto" url="{{ route('get.lookup-detbeli-on-tt') }}"
                        align="center" toolbar="#tb" striped="true" pagination="true" fitColumns="true" idField="belidtpk"
                        pageList="[100,200,300,500]"  pageSize="100" method="get" rownumbers="false" multiSelect="true" collapsible="true"
                        data-options="multiSort:true, remoteSort:false, border:true, dnd:true, checkOnSelect:false, selectOnCheck:false">
                        <thead>
                            <tr>
                                <th field="ck" width="15" checkbox="true"></th>
                                <th field="tanggal" width="10%">Tanggal</th>
                                <th field="noinv" width="9%">No Inv</th>
                                <th field="nobukti" width="9%">No Bukti</th>
                                <th field="brgnm" width="14%">Nama Barang/Spesifikasi</th>
                                <th field="unit" width="8%">Unit</th>
                                <th field="hrgbeli" width="8%">Harga</th>
                                <th field="jmlbeli" width="8%">Qty</th>
                                <th field="totbeli" width="8%">Total</th>
                                <th field="userbuat" width="8%">User</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="modal-footer" style="border:none;">
                <button type="button" id="get_buttonBeli" class="btn btn-secondary my-2 py-1" onclick="SubmitModalLookupPembelian('ORDERLIST')">
                    <i class="fa fa-square-check"></i> GET
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="ModalLookUpPo" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">

            <div class="modal-header" style="border:none;">
                <h5>Lookup From Purchase Order</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="KlikCloseModalPo();"></button>
            </div>

            <div class="d-flex flex-wrap ps-3 pb-2 gap-2 align-items-center">
                <div class="input-group flex-nowrap input-group-search" style="height:34px;">
                    <span class="input-group-text search">
                        <img src="{{ asset('public/css/images/Shape.png') }}" plain="true" width="18px" height="18px">
                    </span>
                    <input type="text" class="form-control search" id="searchByInputOrderPo" onkeyup="loadDataByFilterModalPo()" placeholder="Search...">
                </div>
                
                <select class="easyui-combobox" id="filterByMonthPo" data-options="editable:false, onChange:loadDataByFilterModalPo" style="width:150px; height:34px;">
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
                    <select class="easyui-combobox" id="filterByYearPo" data-options="editable:false, panelHeight:'auto', onChange:loadDataByFilterModalPo"
                        style="width:100px; height:34px;">
                        <?php
                        for ($i = date('Y'); $i >= date('Y') - 6; $i -= 1) { ?>
                        <option value="<?php echo $i; ?>"><?php echo $i; ?></option>
                        <?php } ?>
                    </select>
                </div>

            </div>

            <input type="hidden" name="ttpk4" id="ttpk4" value="{{ $dt_tt->ttpk }}">
            <div class="modal-body">
                <div>
                    <table id="dgLookUpPo" class="easyui-datagrid" title="" style="width:100%; height:auto" url="{{ route('get.lookup-detpo') }}"
                        align="center" toolbar="#tb" striped="true" pagination="true" fitColumns="true" idField="podtpk"
                        pageList="[100,200,300,500]"  pageSize="100" method="get" rownumbers="false" multiSelect="true" collapsible="true"
                        data-options="multiSort:true, remoteSort:false, border:true, dnd:true, checkOnSelect:false">
                        <thead>
                            <tr>
                                <th field="ck" width="15" checkbox="true"></th>
                                <th field="tanggal" width="10%">Tanggal</th>
                                <th field="nopo" width="9%">No Po</th>
                                <th field="nobukti" width="9%">No Bukti</th>
                                <th field="brgnm" width="14%">Nama Barang/Spesifikasi</th>
                                <th field="unit" width="8%">Unit</th>
                                <th field="hrgbeli" width="8%">Harga</th>
                                <th field="jmlbeli" width="8%">Qty</th>
                                <th field="totbeli" width="8%">Total</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" id="get_buttonPo" class="btn btn-secondary my-2 py-1" onclick="SubmitModalLookupPo();">
                    <i class="fa fa-square-check"></i> GET
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
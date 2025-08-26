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
        var pathname3 = pathArray[3];

        var nilai = (pathname3 === 'detail' || pathname3 === 'add-pr');
        var prpk = $('#prpk').val();

        $('#dg').edatagrid({
            saveUrl: '{{ route("api.insert-prdt", "") }}/' + prpk,
            updateUrl: '{{ route("api.update-prdt") }}',
            editing: nilai,
            onSuccess: function(index, row) {
                $(this).edatagrid('saveRow');
                $(this).datagrid('reload');
                closemenu();
                console.log("ROW : " + row.prdtpk);
                console.log("nilai : " + nilai);
            },

            onDblClickRow: function(index, row) {
                var isEdit = $('#isEdit-' + row.prdtpk).val();
                var prdtpk = row.prdtpk;

                console.log('isEdit:', isEdit);
                console.log('row:', row);

                if (isEdit == 'true') {
                    console.log(`here masuk sini 3`);
                    document.getElementById('isEdit-' + row.prdtpk).value = 'false';
                    $(this).edatagrid('endEdit', index);
                } else {
                    console.log(`here masuk sini 1`);
                    console.log('onedit : ' + row.prdtpk);
                    $('#isEdit-' + row.prdtpk).val('true');

                    if (!$(this).edatagrid('options').editing) return;

                    $(this).edatagrid('beginEdit', index);
                    console.log("Double click edit mode");
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

        $('#dgLookUpBarang').datagrid('getPanel').find('div.datagrid-header').css('background', '#FFFFFF');
        $('#dgLookUpBarang').datagrid('getPanel').find('div.datagrid-header').css('border-top','1px solid #858585');
        $('#dgLookUpBarang').datagrid('getPanel').find('div.datagrid-header').css('border-top-left-radius', '8px');
        $('#dgLookUpBarang').datagrid('getPanel').find('div.datagrid-header').css('border-bottom','1px solid #858585');
        $('#dgLookUpBarang').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-left-radius','8px');
        $('#dgLookUpBarang').datagrid('getPanel').find('div.datagrid-header').css('border-top-right-radius', '8px');
        $('#dgLookUpBarang').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-right-radius', '8px');
        $('#dgLookUpBarang').datagrid('getPanel').find('div.datagrid-header').css('border-left', '1px solid #858585');
        $('#dgLookUpBarang').datagrid('getPanel').find('div.datagrid-header').css('border-right', '1px solid #858585');
        $('#dgLookUpBarang').datagrid('getPanel').find('div.datagrid-header td[field]').css('border-right', 'none');
        $('#dgLookUpBarang').datagrid('getPanel').css('border', 'none');
        $('#dgLookUpBarang').datagrid('getPanel').find('div.datagrid-header').addClass('lines-no');
        $('#dgLookUpBarang').datagrid('getPanel').find('div.datagrid-header').addClass('lines-no3');
        $('#dgLookUpBarang').datagrid('getPanel').find('div.datagrid-header').addClass('col');
        $('#dgLookUpBarang').datagrid('getPanel').addClass('lines-no');
        $('#dgLookUpBarang').datagrid('getPanel').addClass('lines-no3');

        $('#ModalLookUpBarang').on('shown.bs.modal', function() {
            $('#dgLookUpBarang').datagrid('resize');
        })
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
        if (pathname3 == 'add-pr') {
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
            $('#add').addClass('d-none');
            $('#lookupbarang').addClass('d-none');
            $('#addnew').addClass('d-none');
            $('#back_edit').removeClass('d-none');
            $('#back_input').addClass('d-none');
            $('#tglpr').datebox('disable');
            $('#nopr').attr('disabled', true);
            $('#user').combobox('disable');
            $('#dep').combobox('disable');
        } else if (pathname3 == 'add-pr') {
            $('#cancel_submit').removeClass('d-none');
            $('#save_submit').removeClass('d-none');
            $('#nopr').attr('disabled', true);
            $('#user').attr('disabled', true);
            $('#add').removeClass('d-none');
            $('#lookupbarang').removeClass('d-none');
            $('#back_edit').addClass('d-none');
            $('#back_input').removeClass('d-none');

            // ✅ Tambahan logika untuk cek apakah PURCHASING
            const depValue = $('#dep').combobox('getValue');
            const data = $('#dep').combobox('getData');
            const selected = data.find(item => item.deppk == depValue);
            if (selected && selected.depnm === 'PURCHASING') {
                $('#add').removeClass('d-none');
                $('#lookupbarang').removeClass('d-none');
            } else {
                $('#add').removeClass('d-none');            // tetap tampilkan Add Data
                $('#lookupbarang').addClass('d-none');      // sembunyikan Lookup jika bukan Purchasing
            }

        } else {
            $('#cancel_submit').addClass('d-none');
            $('#edit_data').addClass('d-none');
        }
    }

    function edit_data() {
        $('#edit_data').addClass('d-none');
        $('#cancel_save_edit').removeClass('d-none');
        // $('#save_edit').removeClass('d-none');
        $('#savedt').addClass('d-none');
        $('#addnew').removeClass('d-none');
        $('#add').removeClass('d-none');
        $('#tglpr').datebox('enable');
        $('#nopr').attr('disabled', true);
        $('#user').attr('disabled', true);
        $('#dep').combobox('enable');
        $('#dg').edatagrid('options').editing = true;
        $('#dg').datagrid('showColumn', 'ck');

        $('#dg').edatagrid('options').editing = true;
        $('#dg').datagrid('showColumn', 'ck');

        const depValue = $('#dep').combobox('getValue');
        $('#dep').combobox({
            onLoadSuccess: function () {
                const data = $('#dep').combobox('getData');
                const selected = data.find(item => item.deppk == depValue);
                $('#dep').combobox('enable');
                if (selected && selected.depnm === 'PURCHASING') {
                    $('#lookupbarang').removeClass('d-none');
                    $('#add').removeClass('d-none');
                } else {
                    $('#lookupbarang').addClass('d-none');
                }
            }
        });
        $('#dep').combobox('reload');
    }

    function cancel_edit() {
        $('#edit_data').removeClass('d-none');
        $('#dg').edatagrid('options').editing = false;
        $('#dg').datagrid('hideColumn', 'ck');
        $('#cancel_save_edit').addClass('d-none');
        $('#add').addClass('d-none');
        $('#lookupbarang').addClass('d-none');

        var rows = $('#dg').datagrid('getRows');
        for (var i = 0; i < rows.length; i++) {
            $('#dg').datagrid('endEdit', i);
        }
        $('#tglpr').datebox('disable');
        $('#user').attr('disabled', true);
        $('#dep').combobox('disable');
        $('#nopr').combobox('disable');
        $('#addnew').addClass('d-none');
    }

    $(function() {
        var prpk = $('#prpk').val(); // Make sure this input has a value
        var url = "{{ route('api.get-prdt', ['prpk' => ':prpk']) }}";
        url = url.replace(':prpk', prpk);

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

        if (pathname3 == 'add-pr') {
            $('#save_submit').removeClass('d-none');
        } else {
            $('#save_edit').removeClass('d-none');
        }
    }

    function submit_pr(nilai) {
        $('#nilai_btn').val(nilai);
        var totalRows = $('#dg').datagrid('getRows').length;
        if (nilai == "submit") {
            if (!totalRows) {
                $('#warningtable').removeClass('d-none');
                return;
            }
        }

        var penerimaField = document.getElementById("dep");
        if (!penerimaField || !penerimaField.value.trim()) {
            $('#warningtable2').removeClass('d-none'); // tampilkan warning
            return;
        } else {
            $('#warningtable2').addClass('d-none'); // sembunyikan kalau valid
        }

        var formElement = document.getElementById("form_prpk");
        var requiredFields = ["prpk"];
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
            url: "{{ route('api.edit-save-header-prdt') }}",
            data: formData,
            processData: false,
            contentType: false,
            cache: false,
            // success: function(response) {
            //     // console.log("COBA RESPONSE" + JSON.stringify(response));
            //     if (nilai == 'submit') {
            //         window.location.href = "{{ route('index.poreq') }}";
            //     } else {
            //         $('#dg').datagrid('reload');
            //         showAlert(200, response.message);
            //         cancel_edit();
            //     }
            // },
            // error: function(xhr, status, error) {
            //     console.log("error add cuti HERE >>>>>>>>>>>>>>>> " + JSON.stringify(xhr));
            //     if (xhr.responseJSON && xhr.responseJSON.message) showAlert(400, xhr.responseJSON.message);
            //     else showAlert(400, JSON.stringify(error))
            // }

            success: function(response) {
                    console.log("Response:", response);
                    if (response.status === "success" && response.data) {
                        if (nilai === 'submit') {
                            window.location.href = "{{ route('index.poreq') }}"; // Arahkan ke halaman lain
                        } else {
                            showAlert(200, response.message);
                            cancel_edit();
                            console.log(`success onSubmitData here >>>>>`);
                            console.log(response);
                            $('#dg').datagrid('reload');
                        }
                    } else {
                        alert("Failed to retrieve nopr from server.");
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

    function back_input_barang() {
        $('#ConfirmCancel').modal('show');
        $('#dg').datagrid('reload');
    }

    function ConfirmDelete() {
        localStorage.setItem('returnBack', true);
        var url = window.location.pathname;
        var segments = url.split('/');
        var prpk = $('#prpk').val();
        var segment3 = segments[3];
        var formData = {
            segment3: segment3,
            prpk: prpk,
            _token: $('meta[name="csrf-token"]').attr('content')
        };
        console.log('prpk : ' + prpk + ' segment : ' + segment3)
        $.ajax({
            type: "POST",
            url: "{{ route('back.pr') }}",
            data: formData,
            success: function(data) {
                console.log("Success:", data);
                window.location.href = "{{ route('index.poreq') }}";
            },
            error: function(xhr, status, error) {
                console.log("Error:", error);
            }
        });

    }

    function delete_list_prdt() {
        var selectedRow = $('#dg').datagrid('getSelected');
        if (!selectedRow) {
            return;
        }

        var checkedRows = $('#dg').datagrid('getChecked');
        if (!checkedRows || checkedRows.length === 0) {
            return;
        }

        var ids = checkedRows.map(function(row) {
            return row.prdtpk;
        });

        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
        $.messager.confirm('Confirm', 'Are you sure you want to delete the selected items?', function(r) {
            if (r) {
                $.ajax({
                    type: 'POST',
                    url: "{{ route('api.delete-prdt') }}",
                    data: {
                        ids: ids
                    },
                    headers: {
                        'X-CSRF-TOKEN': csrfToken
                    },
                    success: function(response) {
                        $('#dg').datagrid('reload');
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
        return `<div class='blue pointer' onclick='list_invsdt(${row.prpk})'>Select</div>`;
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

    function KlikPrint(prpk) {
        if (!prpk) {
            alert('PRPK not found');
            return;
        }

        var baseUrl = "{{ url('/') }}";
        var url = baseUrl + "/purchase-request/print/" + prpk;
        window.open(url, '_blank');
    }

    function KlikPosting() {
        $('#ConfirmPosting').modal('show');
    }

    function onConfirmPosting() {
        var prpk = "{{ $dt_pr->prpk }}";
        if (!prpk) return;

        $.ajax({
        url: "{{route('confirm.posting', '')}}/" + prpk,
        method: 'POST',
        data: {
            "_token": "{{ csrf_token() }}",
        },
        success: function(response, textStatus, xhr) {
            const statusCode = xhr.status; // Ambil status HTTP
            console.log("HTTP Status Code:", statusCode); // Debugging status code
            $('#ConfirmPosting').modal('hide');
            showAlert(statusCode, response.message);
            setTimeout(function() {
            location.reload();
            }, 1300);
        },

        error: function(xhr, status, error) {
            console.error("Error detail:", xhr.responseText);
            alert('An error occurred. Please try again.');
        }
        });
    }

    function Attrprdtpk(value, row) {
        var hiddenSave = '<input type="text" id="isEdit-' + row.prdtpk + '" value="false">';
        return hiddenSave;
    }

    $(function() {
        $('#dep').combobox({
            onLoadSuccess: function() {
                const val = $('#dep').combobox('getValue');
                const opts = $('#dep').combobox('getData');
                const selected = opts.find(opt => opt.deppk == val);
                if (selected && selected.depnm === 'PURCHASING') {
                    $('#lookupbarang').removeClass('d-none');
                }
            }
        });
    });

    $(document).ready(function () {
        const depValue = $('#dep').combobox('getValue');
        const data = $('#dep').combobox('getData');

        const checkSelected = function () {
            const selected = data.find(item => item.deppk == depValue);
            if (selected && selected.depnm === 'PURCHASING') {
                $('#lookupbarang').removeClass('d-none');
                $('#add').removeClass('d-none');
            } else {
                $('#lookupbarang').addClass('d-none');
                $('#add').addClass('d-none');
            }
        };

        // Tunggu data combo load baru cek
        $('#dep').combobox({
            onLoadSuccess: function () {
                checkSelected();
            }
        });
    });

    $(document).ready(function () {
        $('#dep').combobox({
            onLoadSuccess: function () {
                editDisplay(); // jalankan kembali logic setelah data penerima selesai di-load
            },
            onSelect: function (rec) {
                if (rec.depnm === 'PURCHASING') {
                    $('#lookupbarang').removeClass('d-none');
                    $('#add').removeClass('d-none');
                } else {
                    $('#lookupbarang').addClass('d-none');
                    $('#add').removeClass('d-none'); // tetap tampilkan Add Data walau bukan Purchasing
                }
            }
        });
    });

    function KlikLookUpBarang(){
        $('#ModalLookUpBarang').modal('show');
    }

    function SubmitModalLookUpBarang() {
        const selected = $('#dgLookUpBarang').datagrid('getChecked');

        if (selected.length === 0) {
            alert("Pilih minimal satu Barang terlebih dahulu.");
            return;
        }

        const prpk = $('#prpk1').val();
        const brgpkList = selected.map(item => item.brgpk);

        $.ajax({
            url: '{{ route('add-barang-to-prdt') }}',
            type: 'POST',
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                prpk: prpk,
                brgpk: brgpkList
            },
                success: function(response, textStatus, xhr) {
                    const statusCode = xhr.status; // Ambil status HTTP
                    console.log("HTTP Status Code:", statusCode); // Debugging status code
                    $('#ModalLookUpBarang').modal('hide');
                    $('#dgLookUpBarang').datagrid('clearSelections').datagrid('clearChecked');
                    $('#dg').datagrid('reload');

                    //===========untuk search dan jika berhasil klik get, maka akan ter reset list awal/
                    // Kosongkan input pencarian
                    $('#searchByInputBarang').val('');
                    // Muat ulang data tanpa filter (data default)
                    $('#dgLookUpBarang').datagrid('load', {
                        searchByInputBarang: ''
                    });
                    $('#dgLookUpBarang').datagrid('reload');
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
        $('#get_buttonBarang').prop('disabled', true).removeClass('btn-dark').addClass('btn-secondary');

        // Event listener untuk perubahan checkbox di EasyUI datagrid
        $('#dgLookUpBarang').datagrid({
            onCheck: toggleGetButton,
            onUncheck: toggleGetButton,
            onCheckAll: toggleGetButton,
            onUncheckAll: toggleGetButton
        });

        function toggleGetButton() {
            const checked = $('#dgLookUpBarang').datagrid('getChecked');
            if (checked.length > 0) {
                $('#get_buttonBarang').prop('disabled', false).removeClass('btn-secondary').addClass('btn-dark');
            } else {
                $('#get_buttonBarang').prop('disabled', true).removeClass('btn-dark').addClass('btn-secondary');
            }
        }
    });

</script>
@endsection

@section('content')

<div class="main-wrapper min-vh-100">
    <div class="container-fluid">
        <div class="d-flex flex-column p-4 m-3">

            <!-- FORM ATAS -->
            <form id="form_prpk">
                @csrf
                <input type="hidden" id="prpk" name="prpk" value="{{ $dt_pr->prpk }}">
                <input type="hidden" name="nilai_btn" id="nilai_btn" value="">

                <div class="d-flex gap-3">
                    <div id="back_edit" class="d-flex pointer" onclick="ConfirmDelete()">
                        <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                    </div>
                    <div id="back_input" class=" d-none d-flex pointer" onclick="back_input_barang()">
                        <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                    </div>
                    <div class="d-flex flex-column flex-fill">
                        <div class="d-flex justify-content-between">
                             @if( $dt_pr->nopr != null)
                            <div class="d-flex fw-bold">No. PR : {{ str_pad($dt_pr->nopr, 6, '0', STR_PAD_LEFT) }}</div>
                            @else
                            <div class="d-flex fw-bold ">Input Data Purchase Request</div>
                            @endif

                            <div class="d-flex gap-4 d-none" id="cancel_submit">
                                <button class="btn-transparent border-0 fw-bold" onclick="event.preventDefault(); back_input_barang()">Cancel</button>
                                <button class="btn-black rounded px-3 d-none" id="save_submit" onclick="event.preventDefault(); submit_pr('submit');">Submit</button>
                            </div>

                            @if(request()->segment(2) == 'detail')
                                <div class="d-flex gap-2 d-none" id="edit_data">
                                    @if(isset($dt_pr) && $getuserpk == $getuserpk && $dt_pr->posting == null)
                                        <button class="btn-black rounded px-3" onclick="event.preventDefault(); edit_data();">Edit</button>
                                        <button class="btn-black rounded px-3" onclick="event.preventDefault(); KlikPrint({{ $dt_pr->prpk }});"><i class="fa-solid fa-print"></i> Print</button>
                                        <button class="btn-black rounded px-3" onclick="event.preventDefault(); KlikPosting();"><i class="fa-solid fa-paper-plane"></i> Posting</button>
                                    @else
                                        <button class="btn-black rounded px-3" onclick="event.preventDefault(); KlikPrint({{$dt_pr->prpk }});"><i class="fa-solid fa-print"></i> Print</button>
                                    @endif
                                </div>
                            @endif

                            <div class="d-flex gap-4 d-none" id="cancel_save_edit">
                                <button class="btn-transparent border-0 fw-bold"  onclick="event.preventDefault(); cancel_edit()">Cancel</button>
                                <button class="btn-black rounded px-3 d-none" id="save_edit" onclick="event.preventDefault(); submit_pr('save');">Save</button>
                            </div>
                        </div>
                        <div class="d-flex my-3">
                            <div class="d-flex flex-column flex-fill">
                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Tgl PR</div>
                                    <input id="tglpr" name="tglpr" class="easyui-datebox" style="width:210px;" placeholder="YYYY-MM-DD" data-options="formatter:myformatter, parser:myparser" value="{{ \Carbon\Carbon::parse($dt_pr->tglpr)->format('d/m/Y') }}">
                                </div>
                     
                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Penerima</div>
                                    <input id="dep" class="easyui-combobox col-2" style="height: 30px;" name="deppk" method="get" value="{{ $dt_pr->deppk }}" data-options="valueField:'deppk', textField:'depnm', prompt:'{{ $dt_pr->depnm }}', url:'{{ route('api.get-penerima') }}', editable:false, limitToList:'true', required:'true', onSelect: function(rec) {
                                        if (rec.depnm === 'PURCHASING') {
                                            $('#lookupbarang').removeClass('d-none');
                                            $('#add').removeClass('d-none');
                                        } else {
                                            $('#lookupbarang').addClass('d-none');
                                        }
                                    }">
                                </div>
                            </div>
                            <div class="d-flex flex-column flex-fill">
                            </div>

                            <div class="d-flex flex-column flex-fill">
                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">No. PR</div>
                                    <input id="nopr" class="easyui-validatebox height_input" name="nopr" type="text" value="{{ str_pad($dt_pr->nopr, 6, '0', STR_PAD_LEFT) }}">
                                </div>

                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Pengirim</div>
                                    <input id="user" class="easyui-combobox col-2" style="width:210px; height: 30px;" name="userpk" method="get" value="{{ $dt_pr->userpk }}" data-options="valueField:'userpk', textField:'login', prompt:'{{ $dt_pr->login }}', url:'{{ route('api.get-user') }}', editable:false, limitToList:'true', disabled:true">
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </form>

            <!-- TAmbah baris -->
            <div class="d-flex align-items-center gap-3 mb-3">
                <div id="add" class="p-0 d-none d-flex gap-3">
                    <div class="font-5 blue pointer fw-bold" id="adddt" onclick="tambahrow();">Add Data</div>
                    <div class="font-5 blue pointer fw-bold d-none" id="savedt" onclick="saverow();">Save</div>
                </div>

                <div id="lookupbarang" class="flex-grow-0 d-none">
                    <button onclick="KlikLookUpBarang()" class="btn btn-sm btn-dark">
                        <i class="fa fa-folder-open"></i> Lookup Barang
                    </button>
                </div>
            </div>

            <div id="warningtable" class="font-warning pb-3 d-none">
                *) Belum ada list detail barang pada table list purchase request. Silahkan inputkan detail barang/spesifikasi terlebih dahulu!
            </div>

            <div id="warningtable2" class="font-warning pb-3 d-none">
                *) Penerima harus diisi!
            </div>

            <div class="p-0">
                <table id="dg" class="easyui-datagrid" title="" align="center" toolbar="#tb"
                    pagination="true" method="get" striped="false" pageSize="100" pageList="[100,200,300,500]"
                    rownumbers="false" singleSelect="false" collapsible="true" fitColumns="true" idField="prdtpk"
                    data-options="onCheck:function(){menu();},onCheckAll:function(){menu();},onUncheck:function(){menu();},onUncheckAll:function(){menu2();},
                    multiSort:true, remoteSort:false, border:true, dnd:true, checkOnSelect:false,
                    rowStyler:function(index,row){
                        if (row.copy==3){
                            return 'color:#000000;background-color:#D6D6D6;';
                        }
                    }">

                    <thead>
                        <tr>
                            <th field="prdtpk" hidden="true" formatter="Attrprdtpk"></th>
                            <th field="ck" width="auto" styler="styler1" checkbox="true" hidden="true"></th>
                            <!-- <th field="ck" checkbox="true" hidden="true" styler="styler1"></th> -->
                            <th field="index" width="3%" styler="styler2">No</th>
                            <!-- <th field="ck" checkbox="true" hidden="true" styler="styler1"></th>
                            <th field="index" width="3%" styler="styler1">No</th> -->
                            <th field="brgnm" editor="text" width="auto" styler="styler2">Nama Barang/Spesifikasi</th>
                            <th field="jmlbeli" editor="text" width="auto" styler="styler2">Qty</th>
                            <th field="unit" editor="text" width="auto" styler="styler3">Satuan</th>
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
        <a href="javascript:void(0)" plain="true" onclick="delete_list_prdt();" style="color: #FFFFFF; text-decoration: none;"> Delete </a>
    </div>
    <div class="delete" id="mclose" style="width:auto;"><a href="javascript:void(0)" plain="true" onclick="$('#myHeader').hide(); $('#dg').datagrid('clearSelections'); $('#dg').datagrid('clearChecked'); closemenu();" style="color: #FFFFFF; text-decoration: none;"> Close menu </a></div>
</div>

<!-- Konfirmasi Posting -->
<div class="modal fade" id="ConfirmPosting" tabindex="-1" aria-labelledby="ConfirmPostingLabel" aria-hidden="true"
    data-bs-backdrop="static">
    <div class="modal-dialog  modal-dialog-scrollable">
        <div class="modal-content p-4">
            <div class="modal-header d-flex flex-column m-0 p-0 py-2 border-0 align-items-start">
                <p class="p-0 m-0 fw-bold h5">Confirmation</p>
            </div>
            <div class="modal-body d-flex flex-column gap-3 m-0 p-0">
                <div class="d-flex text-left">
                    Pastikan data yang di isi sudah benar, posting akan di kirim dan tidak bisa edit lagi
                </div>
            </div>
            <div class="modal-footer m-0 p-0 border-0">
                <div class="container-fluid">
                    <div class="d-flex justify-content-end">
                        <button type="button" class="px-3 py-1 border-0 btn-transparent" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="px-3 py-1 btn-black rounded" data-bs-dismiss="modal" onclick="onConfirmPosting();">Confirm</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
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

<div class="modal fade" id="ModalLookUpBarang" data-bs-backdrop="static" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header" style="border:none;">
                <h5>Lookup From Barang</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close" onclick="KlikCloseModalBarang();"></button>
            </div>

            <div class="d-flex flex-wrap ps-3 pb-2 gap-2 align-items-center">
                <div class="input-group flex-nowrap input-group-search" style="height:34px;">
                    <span class="input-group-text search">
                        <img src="{{ asset('public/css/images/Shape.png') }}" plain="true" width="18px" height="18px">
                    </span>
                    <input type="text" class="form-control search" id="searchByInputOrderPembelian" onkeyup="loadDataByFilterModalBarang()" placeholder="Search...">
                </div>
            </div>

            <input hidden name="prpk1" id="prpk1" value="{{ $dt_pr->prpk }}">
            <div class="modal-body">
                <div>
                    <table id="dgLookUpBarang" class="easyui-datagrid" title="" style="width:100%; height:auto" url="{{ route('get-barang') }}"
                        align="center" toolbar="#tb" striped="true" pagination="true" fitColumns="true" idField="brgpk"
                        pageList="[100,200,300,500]"  pageSize="100" method="get" rownumbers="false" multiSelect="true" collapsible="true"
                        data-options="multiSort:true, remoteSort:false, border:true, dnd:true, checkOnSelect:false, selectOnCheck:false">
                        <thead>
                            <tr>
                                <th field="ck" width="15" checkbox="true"></th>
                                <!-- <th field="index" width="4%">No</th> -->
                                <th field="brgnm" width="20%">Nama Barang</th>
                                <th field="noseri" width="20%">No Seri</th>
                                <th field="merknm" width="20%">Merk</th>
                                <th field="qtyawal" width="7%">Qty Awal</th>
                                <th field="qtymasuk" width="7%">Qty Masuk</th>
                                <th field="qtykeluar" width="7%">Qty Keluar</th>
                                <th field="qtyakhir" width="7%">Qty Akhir</th>
                                <th field="lastupdate" width="10%">Last Update</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>

            <div class="modal-footer" style="border:none;">
                <button type="button" id="get_buttonBarang" class="btn btn-secondary my-2 py-1" onclick="SubmitModalLookUpBarang()">
                    <i class="fa fa-square-check"></i> GET
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
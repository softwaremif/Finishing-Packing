@extends('layout.main')

@section('css_custom')
<style>
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
        var pathname3 = pathArray[2];

        var nilai = (pathname3 === 'detail');
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
                // $('#savedt').addClass('d-none');
            },
            onDblClickRow: function(index, row) {
                var isEdit = $('#isEdit-' + row.prdtpk).val();
                var prdtpk = row.prdtpk;

                if (isEdit == 'true') {
                    document.getElementById('isEdit-' + row.prdtpk).value = false;
                    hiddenSave(index, prdtpk);
                    $(this).datagrid('endEdit', index);
                } else {
                    console.log('onedit : ' + row.prdtpk);
                    $('#isEdit-' + row.prdtpk).val('true');

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
                // $('#savedt').removeClass('d-none');
                var editors = $('#dg').datagrid('getEditors', index);
                var ponoEditor = editors.find(editor => editor.field === 'pono');
                if (ponoEditor) {
                    var ponoValue = $(ponoEditor.target).val();
                    if (!ponoValue) {
                        // alert('PO Number is required!');
                        warning();
                        return false; // Prevent saving
                    }
                    $('#savedt').addClass('d-none');
                    $('#warningtable').addClass('d-none');
                    $('#warningtable2').addClass('d-none');
                }
            }
        });
    });

    $(function() {
        $('#dg').edatagrid('options').editing = false;
        var prpk = $('#prpk').val();
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

    function AttrDtgdtpk(value, row) {
        var hiddenSave = '<input type="text" id="isEdit-' + row.invsdtpk + '" value="false">';
        return hiddenSave;
    }

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
    });

    //tambahan untuk menu copy replace
    /*menu*/
    function menu() {
        var cp = document.getElementById('copy').value;
        var row = $('#dg').datagrid('getChecked');
        var rows = $('#dg').datagrid('getSelected');
        var tchek = $('#dg').datagrid('getChecked').length;
        if (cp == 1) {
            $('#myHeader').show();
            $('#count').hide();
            $('#count2').show();
            $('#mreplace2').show();
            $('#mcancopy').show();
            $('#mdelete').hide();
            $('#mclose').hide();
            $('#count2').css('margin-right', '55%');
            edit_data();
        } else {
            var cc = [];
            var rows2 = $('#dg').datagrid('getSelections');
            for (var i = 0; i < rows2.length; i++) {
                var nos = rows2[i]['nos'];
                if ($.inArray(nos, cc) == -1) { // not found
                    cc.push(nos);
                }
            }

            if (rows) {
                if (tchek > 1) {
                    if (cc.length > 1) {
                        $('#myHeader').show();
                        $('#count').show();
                        $('#count2').hide();
                        $('#mtd').hide();
                        $('#mud').hide();
                        $('#mcancopy').hide();
                        $('#mdelete').show();
                        $('#count').css('margin-right', '65%');
                        count_row();
                    } else {
                        $('#myHeader').show();
                        $('#count').show();
                        $('#count2').hide();
                        $('#mcancopy').hide();
                        $('#mdelete').show();
                        $('#count').css('margin-right', '60%');
                        count_row();
                    }
                } else {
                    $('#myHeader').show();
                    $('#count').show();
                    $('#count2').hide();
                    $('#mcancopy').hide();
                    $('#mdelete').show();
                    $('#count').css('margin-right', '55%');
                    count_row();
                }
            } else {
                $('#myHeader').hide();
                $('#dg').datagrid('clearSelections');
            }
        }
    }

    function count_row() {
        var count = $('#dg').datagrid('getChecked').length;
        if (count) {
            $('#myHeader').show();
            document.getElementById("count").innerHTML = count + " items selected";
            document.getElementById("totreq").value = count;
            document.getElementById("totreq2").value = count;
        } else {
            $('#myHeader').hide();
            $('#dg').datagrid('clearSelections');
            document.getElementById("count").innerHTML = 0 + " items selected";
        }
    }

    function menu2() {
        var cp = document.getElementById('copy').value;
        if (cp == 1) {
            $('#myHeader').show();
            menu();
        } else {
            $('#myHeader').hide();
        }
    }

    function closemenu() {
        // alert('test');
        $('#myHeader').hide();
        $('#dg').datagrid('clearSelections');
        $('#dg').datagrid('clearChecked');
    }

    $(function() {
        var pathArray = window.location.pathname.split("/");
        var pathname3 = pathArray[2];
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
            $('#lookup').removeClass('d-none');
            $('#add').addClass('d-none');
            $('#alamat').textbox('disable');
            $('#telp').attr('disabled', true);
            $('#acc').attr('disabled', true);
            $('#back_edit').removeClass('d-none');
            $('#back_input').addClass('d-none');
        } else if (pathname3 == 'add-pr') {
            $('#cancel_submit').removeClass('d-none');
            $('#save_submit').removeClass('d-none');
            $('#add').removeClass('d-none');
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
        // $('#tglinv').datebox('enable');
        $('#alamat').textbox('disable');
        $('#telp').attr('disabled', false);
        $('#acc').attr('disabled', false);
        $('#from').attr('disabled', false);
        $('#to').attr('disabled', false);
    }

    function cancel_edit() {
        $('#cancel_save_edit').addClass('d-none');
        $('#add').addClass('d-none');
        var pathArray = window.location.pathname.split("/");
        var pathname3 = pathArray[3];
        if (pathname3 == 'detail') {
            $('#edit_data').removeClass('d-none');
        } else {
            $('#edit_data').addClass('d-none');
        }
        $('#statuspage').val('edit');

        $('#dg').edatagrid('options').editing = false;
        $('#dg').datagrid('hideColumn', 'ck');

        var rows = $('#dg').datagrid('getRows');
        for (var i = 0; i < rows.length; i++) {
            $('#dg').datagrid('endEdit', i);
        }

        // $('#tglinv').datebox('disable');
        $('#alamat').textbox('disable');
        $('#telp').attr('disabled', true);
        $('#acc').attr('disabled', true);
        $('#shipno').attr('disabled', true);
    }

    // function invsdt(dgname) {
    //     if (dgname == 'SOM') {
    //         var selectedRow = $('#dgSOM').datagrid('getSelected');
    //         if (!selectedRow) {
    //             $.messager.alert('Warning', 'No data selected', 'warning');
    //             return;
    //         }

    //         var checkedRows = $('#dgSOM').datagrid('getChecked');
    //         if (!checkedRows || checkedRows.length === 0) {
    //             $.messager.alert('Warning', 'No data selected', 'warning');
    //             return;
    //         }
    //         var rows = $('#dgSOM').datagrid('getChecked');
    //     } else if (dgname == 'GIS') {
    //         var selectedRow = $('#dgGIS').datagrid('getSelected');
    //         if (!selectedRow) {
    //             $.messager.alert('Warning', 'No data selected', 'warning');
    //             return;
    //         }

    //         var checkedRows = $('#dgGIS').datagrid('getChecked');
    //         if (!checkedRows || checkedRows.length === 0) {
    //             $.messager.alert('Warning', 'No data selected', 'warning');
    //             return;
    //         }
    //         var rows = $('#dgGIS').datagrid('getChecked');
    //     } else {
    //         var selectedRow = $('#dgDTINVS').datagrid('getSelected');
    //         if (!selectedRow) {
    //             $.messager.alert('Warning', 'No data selected', 'warning');
    //             return;
    //         }

    //         var checkedRows = $('#dgDTINVS').datagrid('getChecked');
    //         if (!checkedRows || checkedRows.length === 0) {
    //             $.messager.alert('Warning', 'No data selected', 'warning');
    //             return;
    //         }
    //         var rows = $('#dgDTINVS').datagrid('getChecked');
    //     }


    //     var formData = {
    //         invspk: $('#invspk').val(),
    //         acc: $('#acc').val(),
    //         shipno: $('#shipno').val(),
    //         tglsend: $('#tglsend').val(),
    //         gw: $('#gw').val(),
    //         sizebox: $('#sizebox').val(),
    //         from: $('#from').val(),
    //         _token: $('meta[name="csrf-token"]').attr('content')
    //     };

    //     if (rows.length > 0) {
    //         console.log("Rows selected: " + rows.length);
    //         console.log("Selected rows: " + JSON.stringify(rows));
    //     } else {
    //         console.log("No rows are selected.");
    //     }

    //     formData.rows = rows;

    //     if (dgname == 'SOM') {
    //         url = "#";
    //     } else if (dgname == 'GIS') {
    //         url = "#";
    //     } else {
    //         url = "#";
    //     }

    //     $.ajax({
    //         type: "POST",
    //         url: url,
    //         data: formData,
    //         success: function(data) {
    //             console.log("Success:", data);
    //             window.location.reload();
    //         },
    //         error: function(xhr, status, error) {
    //             console.log("Error:", error);
    //             console.log(xhr);
    //         }
    //     });
    // }

    $(function() {
        var invspk = $('#invspk').val(); // Make sure this input has a value
        var url = "route";
        url = url.replace(':invspk', invspk);

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

    function saverow() {
        $('#dg').edatagrid('saveRow');
        closemenu();
    }

    $(function() {
        cek_input_header();
    })


    function cek_input_header() {
        var pathArray = window.location.pathname.split("/");
        var pathname3 = pathArray[4];
        // var tglinv = $('#tglinv').val().trim();
        var alamat = $('#alamat').val().trim();
        var shipno = $('#shipno').val().trim();

        if (pathname3 == 'add-pr') {
            $('#save_submit').removeClass('d-none');
        } else {
            $('#save_edit').removeClass('d-none');
        }
    }

    function submit_invs(nilai) {
        $('#nilai_btn').val(nilai);
        var totalRows = $('#dg').datagrid('getRows').length;
        if (nilai == "submit") {
            if (!totalRows) {
                $('#warningtable').removeClass('d-none');
                return;
            }
        }
        var formElement = document.getElementById("form_invs");
        var requiredFields = ["invspk"]; // Add the required field IDs here
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
            success: function(response) {
                console.log("COBA RESPONSE" + JSON.stringify(response));

                if (nilai == 'submit') {
                    window.location.href = "index.poreq";
                } else {
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

    function back_input_barang() {
        $('#ConfirmCancel').modal('show');
        $('#dg').datagrid('reload');
    }

    function ConfirmDelete() {
        localStorage.setItem('returnBack', true);
        // localStorage.setItem('returnBack', true);
        var url = window.location.pathname;
        var segments = url.split('/');

        var invspk = $('#invspk').val();

        var segment3 = segments[4];

        var formData = {
            segment3: segment3,
            invspk: invspk,
            _token: $('meta[name="csrf-token"]').attr('content')
        };

        console.log('invspk : ' + invspk + ' segment : ' + segment3)

        $.ajax({
            type: "POST",
            url: "route.back_listinvs",
            data: formData,
            success: function(data) {
                console.log("Success:", data);
                window.location.href = "roue.list.invoice";
            },
            error: function(xhr, status, error) {
                console.log("Error:", error);
            }
        });

    }

    function delete_list_invsdt() {
        var selectedRow = $('#dg').datagrid('getSelected');
        if (!selectedRow) {
            // $.messager.alert('Warning', 'No data selected', 'warning');
            return;
        }

        var checkedRows = $('#dg').datagrid('getChecked');
        if (!checkedRows || checkedRows.length === 0) {
            // $.messager.alert('Warning', 'No data selected', 'warning');
            return;
        }

        var ids = checkedRows.map(function(row) {
            return row.invsdtpk; // Ambil dtgdtpk dari setiap baris yang dicentang
        });

        var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        // Tambahkan dialog konfirmasi
        $.messager.confirm('Confirm', 'Are you sure you want to delete the selected items?', function(r) {
            if (r) {
                // Jika pengguna mengkonfirmasi penghapusan
                $.ajax({
                    type: 'POST',
                    url: "route.delete_invsdt",
                    data: {
                        ids: ids
                    }, // Kirim sebagai array
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
        // $('#warning').modal('show');
        $('#warningtable2').removeClass('d-none');
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

</script>
@endsection

@section('content')

<div class="main-wrapper min-vh-100">
    <div class="container-fluid">
        <div class="d-flex flex-column p-4 m-3">

            <!-- FORM ATAS -->
            <form id="form_invs">
                @csrf
                <input type="hidden" id="prpk" name="prpk" value="{{ $dt_pr->prpk }}">
                <input type="hidden" name="nilai_btn" id="nilai_btn" value="">

                <div class="d-flex gap-3">
                    <div id="back_edit" class="d-flex pointer" onclick="ConfirmDelete()"><img
                            src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                    </div>
                    <div id="back_input" class=" d-none d-flex pointer" onclick="back_input_barang()"><img
                            src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                    </div>
                    <div class="d-flex flex-column flex-fill">
                        <div class="d-flex justify-content-between">
                            <div class="d-flex fw-bold ">Input Data Invoice</div>
                            <div class="d-flex gap-4 d-none" id="cancel_submit">
                                <button class="btn-transparent border-0 fw-bold"
                                    onclick="event.preventDefault(); back_input_barang()">Cancel1</button>
                                <button class="btn-black rounded px-3 d-none" id="save_submit"
                                    onclick="event.preventDefault(); submit_invs('submit');">Submit1</button>
                            </div>
                            <div class="d-flex gap-4 d-none" id="edit_data">
                                <button class="btn-black rounded px-5" id="edit_btn"
                                    onclick="event.preventDefault(); edit_data();">Edit</button>
                                <button class="btn-black rounded px-5"
                                    onclick="event.preventDefault(); cetak('1');">Pdf</button>
                            </div>
                            <div class="d-flex gap-4 d-none" id="print2">
                                <button class="btn-black rounded px-5"
                                    onclick="event.preventDefault(); cetak('1');">Pdf</button>
                            </div>
                            <div class="d-flex gap-4 d-none" id="cancel_save_edit">
                                <button class="btn-transparent border-0 fw-bold"
                                    onclick="event.preventDefault(); cancel_edit()">Cancel2</button>
                                <button class="btn-black rounded px-5 d-none" id="save_edit"
                                    onclick="event.preventDefault(); submit_invs('save');">Save</button>
                            </div>
                        </div>
                        <div class="d-flex my-3">
                            <div class="d-flex flex-column flex-fill">
                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Tgl. Invoice</div>
                                    <div>: {{ \Carbon\Carbon::parse($dt_pr->tglpr)->format('d/m/Y') }} </div>
                                </div>
   
                       
                                <div class="d-flex p-1" id="dtalamat">
                                    <div class="p-0 w-25">Alamat</div>
                                    <input class="easyui-textbox" multiline="true" style="height:85px;width:210px;" id="alamat" name="alamat" type="text" style="width:210px;" value="" disabled>
                                </div>

                                <div class="d-flex p-1" id="dttelp">
                                    <div class="p-0 w-25">Telepon</div>
                                    <input id="telp" class="easyui-validatebox height_input" name="telp" type="text" value="">
                                </div>
                            </div>

                            <div class="d-flex flex-column flex-fill">
                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">No. Acc</div>
                                    <input id="acc" class="easyui-validatebox height_input" name="acc"
                                        type="text" value="">
                                </div>
                                <div class="d-flex p-1">
                                    <div class="p-0 w-25">Shipped By</div>
                                    <input id="kurir" class="easyui-validatebox height_input" name="kurir"
                                        type="text" value="">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>

            <div class="d-flex align-items-center gap-3 mb-3">
                <div id="add" class="p-0 d-none d-flex gap-3">
                    <div class="p-0 font-5 blue pointer fw-bold" id="adddt" onclick="tambahrow();">Add Data</div>
                    <div class="ps-4 p-0 font-5 blue pointer fw-bold d-none" id="savedt" onclick="saverow();">Save</div>
                </div>
            </div>

            <div id="warningtable" class="font-warning pb-3 d-none">
                *) Belum ada list detail invoice pada table list invoice. Silahkan lengkapi data dan masukan list invoice terlebih dahulu!
            </div>
            <div id="warningtable2" class="font-warning pb-3 d-none">
                *) Nomor PO harus diisi!
            </div>

            <div class="p-0">
                <table id="dg" class="easyui-datagrid" title="" align="center" toolbar="#tb"
                    pagination="false" method="get" striped="false" pageSize="100" pageList="[100,200,300,500]"
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
                            <th field="ck" checkbox="true" hidden="true" styler="styler1"></th>
                            <th field="prdtpk" width="50" hidden="true" formatter="AttrDtgdtpk">prdtpk</th>
                            
                            <th field="index" width="3%" styler="styler1" editor="disabled">No</th>
                            <th field="brgnm" width="auto" styler="styler2" editor="text">Nama Barang/Spesifikasi</th>
                            <th field="jmlbeli" width="auto" styler="styler2" editor="text">Qty</th>
                            <th field="unit" width="auto" styler="styler3" editor="text">Satuan</th>

                            <!-- <th field="nos" width="120" styler="styler1">Status</th>
                            <th field="style" editor="text" width="120" styler="styler2">Style</th>
                            <th field="itemnm" editor="text" width="110" styler="styler2">Items</th>
                            <th field="samplenm" editor="text" width="110" styler="styler2">Sample Type</th>
                            <th field="hscode" editor="text" width="100" styler="styler2">HS Code</th>
                            <th field="colnm" editor="text" width="110" styler="styler2">Color</th>
                            <th field="fabnm" editor="text" width="120" styler="styler2">Fabric Content</th>
                            <th field="sizenm" editor="text" width="100" styler="styler2">Size</th>
                            <th field="qty" editor="text" width="100" styler="styler2">Qty</th>
                            <th field="price" editor="text" width="100" styler="styler2">Unit Price</th>
                            <th field="amount" width="100" styler="styler3">Amount</th> -->
                        </tr>
                    </thead>
                </table>
            </div>

            <!-- menu header -->
            <div class="header" id="myHeader" onclick="" style="display:none;">
                <div id="count" style="display:none;"></div>
                <div class="delete" id="mdelete" style="width:auto;display:none;"><a href="javascript:void(0)" plain="true"
                        onclick="delete_list_invsdt();" style="color: #FFFFFF; text-decoration: none;"> Delete </a></div>
                <div class="delete" id="mclose" style="width:auto;"><a href="javascript:void(0)" plain="true"
                        onclick="$('#myHeader').hide(); closemenu();"
                        style="color: #FFFFFF; text-decoration: none;"> Close menu </a></div>
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
@endsection
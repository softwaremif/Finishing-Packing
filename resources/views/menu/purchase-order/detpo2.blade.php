@extends('layout.main')

@section('css_custom')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
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
            $('#dgLookUpPr').datagrid('getPanel').find('div.datagrid-header').css('background', '#FFFFFF');
            $('#dgLookUpPr').datagrid('getPanel').find('div.datagrid-header').css('border-top','1px solid #858585');
            $('#dgLookUpPr').datagrid('getPanel').find('div.datagrid-header').css('border-top-left-radius', '8px');
            $('#dgLookUpPr').datagrid('getPanel').find('div.datagrid-header').css('border-bottom', '1px solid #858585');
            $('#dgLookUpPr').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-left-radius','8px');
            $('#dgLookUpPr').datagrid('getPanel').find('div.datagrid-header').css('border-top-right-radius', '8px');
            $('#dgLookUpPr').datagrid('getPanel').find('div.datagrid-header').css('border-bottom-right-radius','8px');
            $('#dgLookUpPr').datagrid('getPanel').find('div.datagrid-header').css('border-left','1px solid #858585');
            $('#dgLookUpPr').datagrid('getPanel').find('div.datagrid-header').css('border-right','1px solid #858585');
            $('#dgLookUpPr').datagrid('getPanel').find('div.datagrid-header td[field]').css('border-right', 'none');
            $('#dgLookUpPr').datagrid('getPanel').css('border', 'none');
            $('#dgLookUpPr').datagrid('getPanel').find('div.datagrid-header').addClass('lines-no');
            $('#dgLookUpPr').datagrid('getPanel').find('div.datagrid-header').addClass('lines-no3');
            $('#dgLookUpPr').datagrid('getPanel').find('div.datagrid-header').addClass('col');
            $('#dgLookUpPr').datagrid('getPanel').addClass('lines-no');
            $('#dgLookUpPr').datagrid('getPanel').addClass('lines-no3');

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
        });

        $(function() {
            var pathArray = window.location.pathname.split("/");
            var pathname3 = pathArray[2];
            var nilai = (pathname3 === 'detail' || pathname3 === 'add-po');
            console.log(`nilai : ${nilai}`);
            var popk = $('#popk').val();

            $('#dg').edatagrid({
                saveUrl: '{{ route('api.insert-podt', '') }}/' + popk,
                updateUrl: '{{ route('api.update-podt') }}',
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

                    console.log('isEdit:', isEdit);
                    console.log('row:', row);

                    if (isEdit == 'true') {
                        console.log(`here masuk sini 3`);
                        document.getElementById('isEdit-' + row.podtpk).value = 'false';
                        $(this).edatagrid('endEdit', index);
                    } else {
                        console.log(`here masuk sini 1`);
                        console.log('onedit : ' + row.podtpk);
                        $('#isEdit-' + row.podtpk).val('true');

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
                    $('#savedt').addClass('d-none');
                }
            });
        });

        function saveEditedRow(index) {
            var row = $('#dg').datagrid('getRows')[index];

            $.ajax({
                url: '{{ route('api.update-podt') }}',
                type: 'POST',
                data: row,
                success: function(response) {
                    if (response.success) {
                        console.log('Data berhasil diperbarui:', response);
                        $('#dg').datagrid('reload');
                    } else {
                        console.log('Gagal menyimpan data:', response);
                    }
                },
                error: function(xhr) {
                    console.log('Terjadi kesalahan dalam menyimpan data:', xhr.responseText);
                }
            });
        }

        function tambahrow() {
            $('#dg').edatagrid('addRow');
            var index = $('#dg').edatagrid('getRows').length - 1; // Ambil index terakhir (baris baru)
        }

        $(function() {
            var pathArray = window.location.pathname.split("/");
            var pathname3 = pathArray[2];
            if (pathname3 == 'add-po') {
                $('#dg').datagrid('showColumn', 'ck');
            } else {
                $('#dg').datagrid('hideColumn', 'ck');
            }

            $('#ModalLookUpPr').on('shown.bs.modal', function() {
                $('#dgLookUpPr').datagrid('resize');
            })

            $('#ModalLookUpPo').on('shown.bs.modal', function() {
                $('#dgLookUpPo').datagrid('resize');
            })

            $('#ModalLookUpBarang').on('shown.bs.modal', function() {
                $('#dgLookUpBarang').datagrid('resize');
            })
        });

        $(function() {
            editDisplay();
        });

        function editDisplay() {
            var pathArray = window.location.pathname.split("/");
            var pathname3 = pathArray[3];

            if (pathname3 == 'detail') {
                console.log(`masuk sini editDisplay detail...`);
                // $('#lookuppr').removeClass('d-none');
                // $('#lookuppo').removeClass('d-none');
                $('#dg').edatagrid('options').editing = false;
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
                $('#lookuppr').addClass('d-none');
                $('#lookuppo').addClass('d-none');
                $('#lookupbarang').addClass('d-none');

            } else if (pathname3 == 'add-po') {
                console.log(`masuk sini editDisplay add...`);
                // edit_data();
                $('#dg').edatagrid('options').editing = true;
                $('#dg').datagrid('showColumn', 'ck');
                $('#nopo').attr('disabled', true);
                $('#cancel_submit').removeClass('d-none');
                $('#save_submit').removeClass('d-none');
                $('#add').removeClass('d-none');
                $('#lookuppr').removeClass('d-none');
                $('#lookuppo').removeClass('d-none');
                $('#lookupbarang').removeClass('d-none');
                $('#totbeli').attr('disabled', true);
            } else {
                $('#cancel_submit').addClass('d-none');
                $('#edit_data').addClass('d-none');
            }
        }

        function edit_data() {
            $('#edit_data').addClass('d-none');
            $('#cancel_save_edit').removeClass('d-none');
            $('#savedt').addClass('d-none');
            $('#add').removeClass('d-none');
            $('#lookuppr').removeClass('d-none');
            $('#lookuppo').removeClass('d-none');
            $('#lookupbarang').removeClass('d-none');
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
            $('#lookuppr').addClass('d-none');
            $('#lookuppo').addClass('d-none');
            $('#lookupbarang').addClass('d-none');

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
            console.log(`saverow is running...`);
            $('#dg').edatagrid('saveRow');
            showAlert('codeApproved', 'Data has been saved successfully!');
        }

        function AttrPodtpk(value, row) {
            var hiddenSave = '<input type="text" id="isEdit-' + row.podtpk + '" value="false">';
            return hiddenSave;
        }

        function submit_po(nilai) {
            $('#nilai_btn').val(nilai);
            var totalRows = $('#dg').datagrid('getRows').length;
            if (nilai == "submit") {
                if (!totalRows) {
                    $('#warningtable').removeClass('d-none');
                    return;
                }
            }
            
            var formElement = document.getElementById("form_header");

            var abpk = $('#ab').combobox('getValue');
            var suppk = $('#sup').combobox('getValue');
            var curpk = $('#curid').combobox('getValue');

            $('#ab').val(abpk);
            $('#sup').val(suppk);
            $('#curid').val(curpk);

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
                        if (nilai === 'submit') {
                            //  showAlert(200, response.message);
                            // window.location.href = "{{ route('index.porder') }}"; 

                            showAlert(200, response.message);
                            setTimeout(function() {
                                window.location.href = "{{ route('index.porder') }}";
                            }, 1500);
                        } else {
                            cancel_edit();
                            console.log(`success onSubmitData here >>>>>`);
                            console.log(response);
                            $('#dg').datagrid('reload');
                            showAlert(200, response.message);
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
            $('#warningtable').removeClass('d-none');
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
            $('#dg').datagrid('clearSelections');
            $('#dg').datagrid('clearChecked');
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
                            $('#dg').datagrid('uncheckAll');
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
                            //         bottom: -document.body.scrollTop - document.documentElement
                            //             .scrollTop
                            //     }
                            // });
                            showAlert(200, response.message);
                            $('#dg').datagrid('reload');
                            $('#dgLookUpPr').datagrid('reload');
                            $('#dg').datagrid('uncheckAll');
                            $('#myHeader').hide();

                            setTimeout(function() {
                                hitungTotalJumlahHarga();
                                simpanTotal();
                            }, 500);
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
                            $('#dg').datagrid('uncheckAll');
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
            window.location.href = "{{ URL::to('purchase-order/list') }}";
        }

        function cancel_po() {
            $('#ConfirmCancel').modal('show');
            $('#dg').datagrid('reload');
        }

        function cancel_poX() {
            window.location.href = "{{ route('index.porder') }}";
        }

        function ConfirmDelete() {
            localStorage.setItem('returnBack', true);
            var url = window.location.pathname;
            var segments = url.split('/');
            var popk = $('#popk').val();
            var segment3 = segments[3];

            var formData = {
                segment3: segment3,
                popk: popk,
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            console.log('popk : ' + popk + ' segment : ' + segment3)

            $.ajax({
                type: "POST",
                url: "{{ route('back.po') }}",
                data: formData,
                success: function(data) {
                    console.log("Success:", data);
                    window.location.href = "{{ route('index.porder') }}";
                },
                error: function(xhr, status, error) {
                    console.log("Error:", error);
                }
            });

        }

        function KlikLookUpPr() {
            $('#ModalLookUpPr').modal('show');
        }

        function loadDataByFilterModalPr() {
            const keyword = $('#searchByInputOrder').val().trim();
            console.log(`searchByInputOrder: ${keyword}`);
            var filterByYearPr = $('#filterByYearPr').val();
            var filterByMonthPr = $('#filterByMonthPr').val();

            $('#dgLookUpPr').datagrid('load', {
                searchByInputOrder: keyword,
                filterByYearPr: filterByYearPr,
                filterByMonthPr: filterByMonthPr,
            });
        }

        function KlikLookUpPo() {
            $('#ModalLookUpPo').modal('show');
        }

        function SearchPurchaseOrder() {
            $('#dgLookUpPo').datagrid('load', {
                searchByInputOrderPo: $('#searchByInputOrderPo').val(),
            });
        }

        function SubmitModalLookup() {
            // const selected = $('#dgLookUpPr').datagrid('getSelections');
            const selected = $('#dgLookUpPr').datagrid('getChecked');
            if (selected.length === 0) {
                alert("Pilih minimal satu item detail PR terlebih dahulu.");
                return;
            }

            const popk = $('#popk2').val();
            const prdtpkList = selected.map(item => item.prdtpk);

            $.ajax({
                url: '{{ route('add-pr-to-po') }}',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    popk: popk,
                    prdtpk: prdtpkList
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $('#ModalLookUpPr').modal('hide');
                        $('#dgLookUpPr').datagrid('clearSelections').datagrid('clearChecked');
                        showAlert(200, response.message);
                        $('#dg').datagrid('reload');
                        $('#dgLookUpPr').datagrid('reload');
                    } else {
                        alert(response.message);
                    }
                }
            });
        }

        $('#dgLookUpPr').datagrid({
            rowStyler: function(index, row) {
                if (row.stspo == 1) {
                    return 'background-color:#f5f5f5;color:#aaa;cursor:not-allowed;';
                }
            },
            onLoadSuccess: function(data) {
                var dg = $(this);
                for (var i = 0; i < data.rows.length; i++) {
                    if (data.rows[i].stspo == 1) {
                        var checkbox = dg.datagrid('getPanel').find('tr[datagrid-row-index="' + i +
                            '"] input[type="checkbox"]');
                        checkbox.prop('disabled', true);
                        dg.datagrid('getPanel').find('tr[datagrid-row-index="' + i + '"]').unbind('click');
                    }
                }
            }
        });

        function SubmitModalLookupPo() {
            // const selected = $('#dgLookUpPo').datagrid('getSelections');
            const selected = $('#dgLookUpPo').datagrid('getChecked');
            if (selected.length === 0) {
                alert("Pilih minimal satu item detail PO terlebih dahulu.");
                return;
            }

            const popk = $('#popk3').val();
            const podtpkList = selected.map(item => item.podtpk);

            $.ajax({
                url: '{{ route('add-po-to-po') }}',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    popk: popk,
                    podtpk: podtpkList
                },
                success: function(response) {
                    if (response.status === 'success') {
                        $('#ModalLookUpPo').modal('hide');
                        $('#dgLookUpPo').datagrid('clearSelections').datagrid('clearChecked');
                        $('#dg').datagrid('reload');
                        $('#dgLookUpPo').datagrid('reload');
                    } else {
                        alert(response.message);
                    }
                }
            });
        }

        function KlikCloseModalPo() {
            $('#dgLookUpPo').datagrid('clearSelections').datagrid('clearChecked');
        }

        function KlikCloseModalPr() {
        
            var currentDate = new Date();
            var currentMonth = String(currentDate.getMonth() + 1).padStart(2, '0');
            var currentYear = currentDate.getFullYear();

            // Matikan sementara event onChange
            $('#filterByMonthPr').combobox('options').onChange = function () {};
            $('#filterByYearPr').combobox('options').onChange = function () {};

            $('#filterByMonthPr').combobox('setValue', currentMonth);
            $('#filterByYearPr').combobox('setValue', currentYear);

            // Aktifkan kembali fungsi jika perlu (opsional)
            $('#filterByMonthPr').combobox('options').onChange = loadDataByFilterModalPr;
            $('#filterByYearPr').combobox('options').onChange = loadDataByFilterModalPr;


            // Kosongkan input pencarian
            $('#searchByInputOrder').val('');

            // Muat ulang data tanpa filter (data default)
            $('#dgLookUpPr').datagrid('load', {
                searchByInputOrder: ''
            });

            $('#dgLookUpPr').datagrid('reload');
            // Bersihkan seleksi dan centang
            $('#dgLookUpPr').datagrid('clearSelections').datagrid('clearChecked');
        
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

        function loadTotalHarga(popk) {
            var baseUrl = '{{ url('api/get-total') }}';
            var url = baseUrl + '/' + encodeURIComponent(popk);

            // console.log(url);

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        $('#totalBox').val(response.total_hrg_beli);
                    } else {
                        $('#totalBox').text("Gagal menghitung total.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", error);
                    $('#totalBox').text("Error mengambil data.");
                }
            });
        }

        var popk = $('#popk').val();
        loadTotalHarga(popk);

        function hitungTotalJumlahHarga() {
            var rows = $('#dg').datagrid('getRows');
            var total = 0;

            for (var i = 0; i < rows.length; i++) {
                var qty = parseFloat(rows[i].jmlbeli) || 0;
                var harga = parseFloat(rows[i].hrgbeli.toString().replace(/,/g, '')) || 0;

                var subtotal = qty * harga;
                rows[i].total = subtotal;

                total += subtotal;

                // update kolom "total" secara langsung di grid (opsional)
                $('#dg').datagrid('updateRow', {
                    index: i,
                    row: {
                        total: subtotal
                    }
                });
            }

            // tampilkan hasil di kotak input
            $('#totalBox').val(total.toLocaleString('id-ID', {
                minimumFractionDigits: 2
            }));
        }

        function simpanTotal() {
            // var popk = $('#popkInput').val();
            var popk = $('#popk').val();
            var total = $('#totalBox').val()
                .replace(/\./g, '')
                .replace(/,/g, '.'); // ← ini kunci utamanya!

            $.ajax({
                url: '{{ route('api.update-totbeli') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    popk: popk,
                    totbeli: total
                },
                success: function(response) {
                    if (!response.success) {
                        console.warn('Gagal update total:', response.message);
                    }
                },
                error: function(xhr) {
                    console.error('AJAX Error:', xhr.responseText);
                }
            });
        }

        //mengaktifkan button get pada list PR
        $(document).ready(function() {
            $('#get_button').prop('disabled', true).removeClass('btn-dark').addClass('btn-secondary');

            // Event listener untuk perubahan checkbox di EasyUI datagrid
            $('#dgLookUpPr').datagrid({
                onCheck: toggleGetButton,
                onUncheck: toggleGetButton,
                onCheckAll: toggleGetButton,
                onUncheckAll: toggleGetButton
            });

            function toggleGetButton() {
                const checked = $('#dgLookUpPr').datagrid('getChecked');
                if (checked.length > 0) {
                    $('#get_button').prop('disabled', false)
                        .removeClass('btn-secondary')
                        .addClass('btn-dark');
                } else {
                    $('#get_button').prop('disabled', true)
                        .removeClass('btn-dark')
                        .addClass('btn-secondary');
                }
            }
        });


        //mengaktifkan button get pada list PO
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
                    $('#get_buttonPo').prop('disabled', false)
                        .removeClass('btn-secondary')
                        .addClass('btn-dark');
                } else {
                    $('#get_buttonPo').prop('disabled', true)
                        .removeClass('btn-dark')
                        .addClass('btn-secondary');
                }
            }
        });

        function KlikPosting() {
            $('#ConfirmPosting').modal('show');
        }

        function onConfirmPosting() {
            var popk = "{{ $dt_po->popk }}";
            if (!popk) return;

            $.ajax({
                url: "{{ route('confirm.posting.po', '') }}/" + popk,
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

        function KlikLookUpBarang(){
            $('#ModalLookUpBarang').modal('show');
        }

        function loadDataByFilterModalBarang() {
            const keyword = $('#searchByInputBarang').val().trim();
            console.log(`searchByInputBarang: ${keyword}`);

            $('#dgLookUpBarang').datagrid('load', {
                searchByInputBarang: keyword
            });
        }

        function KlikCloseModalBarang() {
            // Kosongkan input pencarian
            $('#searchByInputBarang').val('');

            // Muat ulang data tanpa filter (data default)
            $('#dgLookUpBarang').datagrid('load', {
                searchByInputBarang: ''
            });

            // Bersihkan seleksi dan centang
            $('#dgLookUpBarang').datagrid('clearSelections').datagrid('clearChecked');
        }

        function SubmitModalLookUpBarang() {
            const selected = $('#dgLookUpBarang').datagrid('getChecked');

            if (selected.length === 0) {
                alert("Pilih minimal satu Barang terlebih dahulu.");
                return;
            }

            const popk = $('#popk1').val();
            const brgpkList = selected.map(item => item.brgpk);

            $.ajax({
                url: '{{ route('add-barang-to-podt') }}',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    popk: popk,
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
    @include('menu.purchase-order.modal.modal-cancel')
    @include('menu.purchase-order.modal.modal-list-pr')
    @include('menu.purchase-order.modal.modal-list-po')
    @include('menu.purchase-order.modal.modal-list-barang')

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
                        Pastikan data yang diisi sudah benar, posting ini tidak bisa edit lagi.
                    </div>
                </div>
                <div class="modal-footer m-0 p-0 border-0">
                    <div class="container-fluid">
                        <div class="d-flex justify-content-end">
                            <button type="button" class="px-3 py-1 border-0 btn-transparent"
                                data-bs-dismiss="modal">Close</button>
                            <button type="button" class="px-3 py-1 btn-black rounded" data-bs-dismiss="modal"
                                onclick="onConfirmPosting();">Confirm</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="main-wrapper min-vh-100">
        <div class="container-fluid">

            <div class="d-flex flex-column p-4 m-3">
                <form id="form_header">
                    @csrf
                    <input type="hidden" id="popk" name="popk" value="{{ $dt_po->popk }}">

                    <input type="hidden" name="nilai_btn" id="nilai_btn" value="">

                    <div class="d-flex gap-3">
                        @if (request()->segment(2) == 'detail')
                            <div class="d-flex pointer" onclick="GoBack()">
                                <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                            </div>
                        @elseif(request()->segment(2) == 'add-po')
                            <!-- <div class="d-flex pointer" onclick="cancel_poX()"> -->
                            <div class="d-flex pointer" onclick="cancel_po()">
                                <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                            </div>
                        @endif

                        <div class="d-flex flex-column flex-fill">
                            <div class="d-flex justify-content-between">
                                @if ($dt_po->nobukti != null)
                                    <div class="d-flex fw-bold">No. Purchase Order : {{ $dt_po->nobukti }}</div>
                                @else
                                    <div class="d-flex fw-bold ">Input Data Purchase Order</div>
                                @endif

                                <div class="d-flex gap-2 d-none" id="cancel_submit">
                                    <button class="btn-transparent border-0 fw-bold"
                                        onclick="event.preventDefault(); cancel_po()">Cancel</button>
                                    <button class="btn-black rounded px-3 d-none" id="save_submit"
                                        onclick="event.preventDefault(); submit_po('submit');">Save</button>
                                </div>

                                <div class="d-flex gap-1 d-none" id="edit_data">
                                    @if (isset($dt_po) && $getuserpk == $getuserpk && $dt_po->posting == null)
                                        <button class="btn-black rounded px-3"
                                            onclick="event.preventDefault(); edit_data();">Edit</button>
                                        <button class="btn-black rounded px-3"
                                            onclick="event.preventDefault(); KlikPrint({{ $dt_po->popk }});"><i
                                                class="fa-solid fa-print"></i> Pdf</button>
                                        <button class="btn-black rounded px-3"
                                            onclick="event.preventDefault(); KlikPosting();"><i
                                                class="fa-solid fa-paper-plane"></i> Posting</button>
                                    @else
                                        <button class="btn-black rounded px-3"
                                            onclick="event.preventDefault(); KlikPrint({{ $dt_po->popk }});"><i
                                                class="fa-solid fa-print"></i> Pdf</button>
                                    @endif
                                </div>

                                <div class="d-flex gap-2 d-none" id="cancel_save_edit">
                                    <button class="btn-transparent border-0 fw-bold" onclick="event.preventDefault(); cancel_edit()">Cancel</button>
                                    <button class="btn-black rounded px-3" id="save_edit" onclick="event.preventDefault(); submit_po('save');">Save</button>
                                </div>
                            </div>


                            <div class="d-flex my-3">
                                <div class="d-flex flex-column flex-fill">

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Tgl PO</div>
                                        <input id="tglpo" name="tglpo" class="easyui-datebox" style="width:210px;"
                                            placeholder="YYYY-MM-DD" data-options="formatter:myformatter, parser:myparser"
                                            value="{{ \Carbon\Carbon::parse($dt_po->tglpo)->format('d/m/Y') }}">
                                    </div>

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Term</div>
                                        <input id="ab" class="easyui-combobox col-2" name="abpk" method="get"
                                            value="{{ $dt_po->abpk }}"
                                            data-options="valueField:'abpk', textField:'abnm', prompt:'{{ $dt_po->abnm }}', url:'{{ route('api.get-ab') }}', editable:true, limitToList:'true'">
                                    </div>

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Supplier</div>
                                        <input id="sup" class="easyui-combobox col-2" style="height: 30px;"
                                            name="suppk" method="get" value="{{ $dt_po->suppk }}"
                                            data-options="valueField:'suppk', textField:'supnm', prompt:'{{ $dt_po->supnm }}', url:'{{ route('api.get-sup') }}', editable:true, limitToList:'true'">
                                    </div>


                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Mata Uang</div>
                                        <input id="curid" class="easyui-combobox col-2" name="curpk" method="get" value="{{ $dt_po->curpk }}" data-options="valueField:'curpk', textField:'curid', prompt:'{{ $dt_po->curid }}', url:'{{ route('api.get-cur') }}', editable:false, limitToList:'true'">
                                    </div>

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Note Out</div>
                                        <input id="ket" class="easyui-validatebox height_input" name="ket"
                                            type="text" value="{{ $dt_po->ket }}">
                                    </div>

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Note In</div>
                                        <input id="ket2" class="easyui-validatebox height_input" name="ket2"
                                            type="text" value="{{ $dt_po->ket2 }}">
                                    </div>
                                </div>

                                <div class="d-flex flex-column flex-fill">

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">No PO</div>
                                        <input id="nopo" class="easyui-validatebox height_input" name="nopo"
                                            type="text" value="{{ $dt_po->nopo }}">
                                    </div>



                                    <!-- <div class="d-flex p-1">
                                        <div class="p-0 w-25">Total Kubikasi</div>
                                        <input id="totbeli" class="easyui-validatebox height_input" name="totbeli" type="text" value="{{ number_format($dt_po->totbeli, 2, '.', ',') }}">
                                    </div> -->

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Total</div>
                              
                                        <input id="totalBox" class="easyui-validatebox height_input" readonly
                                            style="background-color: #f0f0f0; color:#575757; border: 1px solid #aaa; border-radius: 8px; padding: 8px 12px; text-align: left;">

                                        <!-- <input id="totbeli" class="easyui-validatebox height_input" name="totbeli" type="text" value=""> -->
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
                            <div class="font-5 blue pointer fw-bold d-none" id="savedt" onclick="saverow();">Save
                            </div>
                        </div>

                        <div id="lookuppr" class="flex-grow-0 d-none">
                            <button onclick="KlikLookUpPr()" class="btn btn-sm btn-dark">
                                <i class="fa fa-folder-open"></i> Lookup PR
                            </button>
                        </div>

                        <!-- <div id="lookuppo" class="flex-grow-0 d-none">
                            <button onclick="KlikLookUpPo()" class="btn btn-sm btn-dark">
                                <i class="fa fa-folder-open"></i> Lookup PO
                            </button>
                        </div> -->
                        
                        @if(Session::get('deppk') == 3)
                        <div id="lookupbarang" class="flex-grow-0 d-none">
                            <button onclick="KlikLookUpBarang()" class="btn btn-sm btn-dark">
                                <i class="fa fa-folder-open"></i> Lookup Barang
                            </button>
                        </div>
                        @endif
                    </div>
                </div>

                <div id="warningtable" class="font-warning pb-3 d-none">
                    *) Belum ada list detail pada table list po. Silahkan lengkapi data dan masukan list po terlebih
                    dahulu!
                </div>

                <div class="p-0">
                    <table id="dg" class="easyui-datagrid" title="" align="center" toolbar="#tb"
                        striped="false" pagination="true" method="get" rownumbers="false"
                        pageList="[100,200,300,500]" pageSize="100" singleSelect="false" collapsible="true"
                        fitColumns="true" idField="podtpk"
                        data-options="onAfterEdit: function(index, row, changes) {hitungTotalJumlahHarga(); simpanTotal();}, onCheck:function(){menu();}, onCheckAll:function(){menu();}, onUncheck:function(){menu();}, onUncheckAll:function(){},
                    multiSort:true, remoteSort:false, border:true, dnd:true, checkOnSelect:false,
                    rowStyler:function(index,row){
                        if (row.copy==3){
                            return 'color:#000000;background-color:#D6D6D6;';
                        }
                    }">
                        <thead>
                            <tr>
                                <th field="podtpk" hidden="true" formatter="AttrPodtpk"></th>
                                <th field="ck" width="auto" styler="styler1" checkbox="true" hidden="true"></th>
                                <th field="index" width="3%" styler="styler2" editor="disabled">No</th>
                                <th field="brgnm" width="auto" styler="styler2" editor="text">Nama
                                    Barang/Spesifikasi</th>
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
                        <a href="javascript:void(0)" plain="true" onclick="delete_list_podt();"
                            style="color: #FFFFFF; text-decoration:none;"> Delete </a>
                    </div>

                    <div class="delete" id="mclose" style="width:auto;">
                        <a href="javascript:void(0)" plain="true"
                            onclick="$('#myHeader').hide(); $('#dg').datagrid('clearSelections'); $('#dg').datagrid('clearChecked'); closemenu();"
                            style="color: #FFFFFF; text-decoration:none;"> Close menu </a>
                    </div>
                </div>

            </div>
        </div>
    </div>
@endsection

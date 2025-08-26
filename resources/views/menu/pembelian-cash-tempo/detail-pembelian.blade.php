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

        });

        $(function() {
            var pathArray = window.location.pathname.split("/");
            var pathname3 = pathArray[2];
            var nilai = (pathname3 === 'detail' || pathname3 === 'add-pembelian');
            console.log(`nilai : ${nilai}`);
            var belipk = $('#belipk').val();
            var lastIndex = undefined;

            $('#dg').edatagrid({
                saveUrl: '{{ route('get.insert-belidt', '') }}/' + belipk,
                updateUrl: '{{ route('get.update-belidt') }}',
                editing: nilai,
                onSuccess: function(index, row) {
                    $(this).edatagrid('saveRow');
                    $(this).datagrid('reload');
                    console.log("ROW : " + row);
                    console.log("nilai : " + nilai);
                },
                onDblClickRow: function(index, row) {
                    var isEdit = $('#isEdit-' + row.belidtpk).val();
                    var belidtpk = row.belidtpk;

                    console.log('isEdit:', isEdit);
                    console.log('row:', row);

                    if (isEdit == 'true') {
                        console.log(`here masuk sini 3`);
                        document.getElementById('isEdit-' + row.belidtpk).value = 'false';
                        $(this).edatagrid('endEdit', index);
                    } else {
                        console.log(`here masuk sini 1`);
                        console.log('onedit : ' + row.belidtpk);
                        $('#isEdit-' + row.belidtpk).val('true');

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
                url: '{{ route('get.update-belidt') }}',
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
            if (pathname3 == 'add-pembelian') {
                $('#dg').datagrid('showColumn', 'ck');
            } else {
                $('#dg').datagrid('hideColumn', 'ck');
            }

            $('#ModalLookUpPr').on('shown.bs.modal', function() {
                $('#dgLookUpPr').datagrid('resize');
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
                $('#dg').edatagrid('options').editing = false;
                $('#edit_data').removeClass('d-none');
                $('#noinv').attr('disabled', true);
                $('#tglinv').datebox('disable');
                $('#ab').combobox('disable');
                $('#sup').combobox('disable');
                $('#curid').combobox('disable');
                $('#kel').combobox('disable');
                $('#mif').combobox('disable');
                $('#nopo').attr('disabled', true);
                $('#totbeli').attr('disabled', true);
                $('#addnew').addClass('d-none');
                $('#lookuppr').addClass('d-none');

            } else if (pathname3 == 'add-pembelian') {
                console.log(`masuk sini editDisplay add...`);
                // edit_data();
                $('#dg').edatagrid('options').editing = true;
                $('#dg').datagrid('showColumn', 'ck');
                $('#cancel_submit').removeClass('d-none');
                $('#save_submit').removeClass('d-none');
                $('#add').removeClass('d-none');
                $('#lookuppr').removeClass('d-none');
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
            $('#dg').edatagrid('options').editing = true;
            $('#dg').datagrid('showColumn', 'ck');
            $('#noinv').attr('disabled', false);
            $('#tglinv').datebox('enable');
            $('#ab').combobox('enable');
            $('#sup').combobox('enable');
            $('#curid').combobox('enable');
            $('#kel').combobox('enable');
            $('#mif').combobox('enable');
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

            var rows = $('#dg').datagrid('getRows');
            for (var i = 0; i < rows.length; i++) {
                $('#dg').datagrid('endEdit', i);
            }
            $('#noinv').attr('disabled', true);
            $('#tglinv').datebox('disable');
            $('#ab').combobox('disable');
            $('#sup').combobox('disable');
            $('#curid').combobox('disable');
            $('#kel').combobox('disable');
            $('#mif').combobox('disable');
            $('#nopo').attr('disabled', true);
            $('#totbeli').attr('disabled', true);
            $('#addnew').addClass('d-none');
        }

        $(function() {
            var belipk = $('#belipk').val();
            var url = "{{ route('get.belidt', ['belipk' => ':belipk']) }}";
            url = url.replace(':belipk', belipk);

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
            showAlert('codeApproved', 'Data has been saved successfully!');
            console.log(`saverow is running...`);
            $('#dg').edatagrid('saveRow');
        }

        function submit_beli(nilai) {
            $('#nilai_btn').val(nilai);
            var totalRows = $('#dg').datagrid('getRows').length;
            if (nilai == "submit") {
                if (!totalRows) {
                    $('#warningtable').removeClass('d-none');
                    return;
                }
            }

            if (nilai == "save") {
                if (!totalRows) {
                    $('#warningtable').removeClass('d-none');
                    return;
                }
            }

            var formElement = document.getElementById("form_header");

            var abpk = $('#ab').combobox('getValue');
            var suppk = $('#sup').combobox('getValue');
            var curpk = $('#curid').combobox('getValue');
            var kelpk = $('#kel').combobox('getValue');
            var mifpk = $('#mif').combobox('getValue');

            $('#ab').val(abpk);
            $('#sup').val(suppk);
            $('#curid').val(curpk);
            $('#kel').val(kelpk);
            $('#mif').val(mifpk);


            var requiredFields = ["belipk"]; // Add the required field IDs here
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
                alert(errorMessage); 
                return;
            }

            var formData = new FormData(formElement);
            $.ajax({
                method: "POST",
                url: "{{ route('get.edit-save-header-belidt') }}",
                data: formData,
                processData: false,
                contentType: false,
                cache: false,
                success: function(response) {
                    console.log("Response:", response);
                    if (response.status === "success" && response.data) {
                        if (nilai === 'submit') {
                            window.location.href = "{{ route('page.pembelian-ct') }}";
                        } else {
                            showAlert(200, response.message);
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

        function delete_list_belidt() {
            var selectedRow = $('#dg').datagrid('getSelected');
            if (!selectedRow) {
                return;
            }

            var checkedRows = $('#dg').datagrid('getChecked');
            if (!checkedRows || checkedRows.length === 0) {
                return;
            }

            var ids = checkedRows.map(function(row) {
                return row.belidtpk;
            });

            var csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            $.messager.confirm('Confirm', 'Are you sure you want to delete the selected items?', function(r) {
                if (r) {
                    $.ajax({
                        type: 'POST',
                        url: "{{ route('get.delete-belidt') }}",
                        data: {
                            ids: ids
                        },
                        headers: {
                            'X-CSRF-TOKEN': csrfToken
                        },
                        success: function(response) {
                            showAlert(200, response.message);
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
                            //         bottom: -document.body.scrollTop - document.documentElement.scrollTop
                            //     }
                            // });
                            
                            // $('#dg').datagrid('reload');
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
            window.location.href = "{{ URL::to('pembelian-cash-tempo') }}";
        }

        function cancel_beli() {
            $('#ConfirmCancel').modal('show');
            $('#dg').datagrid('reload');
        }

        function ConfirmDeletePembelian() {
            localStorage.setItem('returnBack', true);
            var url = window.location.pathname;
            var segments = url.split('/');
            var belipk = $('#belipk').val();
            var segment3 = segments[3];

            var formData = {
                segment3: segment3,
                belipk: belipk,
                _token: $('meta[name="csrf-token"]').attr('content')
            };

            console.log('belipk : ' + belipk + ' segment : ' + segment3)

            $.ajax({
                type: "POST",
                url: "{{ route('back.pembelian') }}",
                data: formData,
                success: function(data) {
                    console.log("Success:", data);
                    window.location.href = "{{ route('page.pembelian-ct') }}";
                },
                error: function(xhr, status, error) {
                    console.log("Error:", error);
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

        function AttrBelidtpk(value, row) {
            var hiddenSave = '<input type="text" id="isEdit-' + row.belidtpk + '" value="false">';
            return hiddenSave;
        }

        function loadTotalHarga(belipk) {
            var baseUrl = '{{ url('get-total-pembelian') }}';
            var url = baseUrl + '/' + encodeURIComponent(belipk);

            $.ajax({
                url: url,
                type: 'GET',
                success: function(response) {
                    if (response.success) {
                        $('#totalBeli').val(response.total_hrg_beli);
                    } else {
                        $('#totalBeli').text("Gagal menghitung total.");
                    }
                },
                error: function(xhr, status, error) {
                    console.error("AJAX Error:", error);
                    $('#totalBeli').text("Error mengambil data.");
                }
            });
        }

        var belipk = $('#belipk').val();
        loadTotalHarga(belipk);

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
            $('#totalBeli').val(total.toLocaleString('id-ID', {
                minimumFractionDigits: 2
            }));
        }

        function simpanTotal() {
            var belipk = $('#belipk').val();
            var total = $('#totalBeli').val()
                .replace(/\./g, '')
                .replace(/,/g, '.'); // ← ini kunci utamanya!

            $.ajax({
                url: '{{ route('get.update-total-pembelian') }}',
                type: 'POST',
                data: {
                    _token: '{{ csrf_token() }}',
                    belipk: belipk,
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

        function KlikPosting() {
            $('#ConfirmPosting').modal('show');
        }

        function onConfirmPosting() {
            var belipk = "{{ $dt_beli->belipk }}";
            if (!belipk) return;

            $.ajax({
                url: "{{ route('confirm.posting.pembelian', '') }}/" + belipk,
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

        function KlikLookUpPr(){
            $('#ModalLookUpPr').modal('show');
        }

        function SearchPurchaseRequest() {
            $('#dgLookUpPr').datagrid('load', {
                searchByInputPr: $('#searchByInputPr').val(),
            });
        }

        function SubmitModalLookUp() {
            const selected = $('#dgLookUpPr').datagrid('getChecked');
            if (selected.length === 0) {
                alert("Pilih minimal satu item detail PR terlebih dahulu.");
                return;
            }

            const belipk = $('#belipk2').val();
            const prdtpkList = selected.map(item => item.prdtpk);

            $.ajax({
                url: '{{ route('add-pr-to-pembelian') }}',
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    belipk: belipk,
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
        

    </script>
@endsection

@section('content')
    @include('menu.pembelian-cash-tempo.modal.modal-cancel')
    @include('menu.pembelian-cash-tempo.modal.modal-list-pr')
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
                    <input type="hidden" id="belipk" name="belipk" value="{{ $dt_beli->belipk }}">

                    <input type="hidden" name="nilai_btn" id="nilai_btn" value="">

                    <div class="d-flex gap-3">
                        @if (request()->segment(2) == 'detail')
                            <div class="d-flex pointer" onclick="GoBack()">
                                <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                            </div>
                        @elseif(request()->segment(2) == 'add-pembelian')
                            <div class="d-flex pointer" onclick="cancel_beli()">
                                <img src="{!! asset('public/css/images/arrow-back.png') !!}" width="27" height="27">
                            </div>
                        @endif

                        <div class="d-flex flex-column flex-fill">
                            <div class="d-flex justify-content-between">
                                @if ($dt_beli->nobukti != null)
                                    <div class="d-flex fw-bold">No. Bukti : {{ $dt_beli->nobukti }}</div>
                                @else
                                    <div class="d-flex fw-bold ">Input Data Pembelian</div>
                                @endif

                                <div class="d-flex gap-2 d-none" id="cancel_submit">
                                    <button class="btn-transparent border-0 fw-bold"
                                        onclick="event.preventDefault(); cancel_beli()">Cancel</button>
                                    <button class="btn-black rounded px-3 d-none" id="save_submit"
                                        onclick="event.preventDefault(); submit_beli('submit');">Save</button>
                                </div>

                                <div class="d-flex gap-1 d-none" id="edit_data">
                                    @if (isset($dt_beli) && $getuserpk == $getuserpk && $dt_beli->posting == null)
                                        <button class="btn-black rounded px-3"
                                            onclick="event.preventDefault(); edit_data();">Edit</button>
                                    @endif
                                </div>

                                <div class="d-flex gap-2 d-none" id="cancel_save_edit">
                                    <button class="btn-transparent border-0 fw-bold"
                                        onclick="event.preventDefault(); cancel_edit()">Cancel</button>
                                    <button class="btn-black rounded px-3" id="save_edit"
                                        onclick="event.preventDefault(); submit_beli('save');">Save</button>
                                </div>
                            </div>


                            <div class="d-flex my-3">
                                <div class="d-flex flex-column flex-fill">

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">No Bukti</div>
                                        <input id="nobukti" style="background-color: #f0f0f0;" value="{{ $dt_beli->nobukti }}" class="easyui-validatebox height_input" readonly name="nobukti">
                                    </div>


                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">No Invoice</div>
                                        <input id="noinv" class="easyui-validatebox height_input" name="noinv" type="text" value="{{ $dt_beli->noinv }}"  >
                                    </div>

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Tgl Inv</div>
                                        <input id="tglinv" name="tglinv" class="easyui-datebox" style="width:210px;"
                                            placeholder="YYYY-MM-DD" data-options="formatter:myformatter, parser:myparser"
                                            value="{{ \Carbon\Carbon::parse($dt_beli->tglinv)->format('d/m/Y') }}">
                                    </div>

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Term</div>
                                        <input id="ab" class="easyui-combobox col-2" name="abpk" method="get" value="{{ $dt_beli->abpk }}"
                                            data-options="valueField:'abpk', textField:'abnm', prompt:'{{ $dt_beli->abnm }}', url:'{{ route('api.get-ab') }}', editable:true, limitToList:'true', panelHeight:'auto'">
                                    </div>

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Mata Uang</div>
                                        <input id="curid" class="easyui-combobox col-2" name="curpk" method="get" value="{{ $dt_beli->curpk }}" data-options="valueField:'curpk', textField:'curid', prompt:'{{ $dt_beli->curid }}', url:'{{ route('api.get-cur') }}', editable:false, panelHeight:'auto'">
                                    </div>

                                </div>

                                <div class="d-flex flex-column flex-fill">

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Supplier</div>
                                        <input id="sup" class="easyui-combobox col-2" style="height: 30px;" name="suppk" method="get" value="{{ $dt_beli->suppk }}"
                                            data-options="valueField:'suppk', textField:'supnm', prompt:'{{ $dt_beli->supnm }}', url:'{{ route('api.get-sup') }}', editable:true, limitToList:'true'">
                                    </div>

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Jenis</div>
                                        <input id="kel" class="easyui-combobox col-2" style="height: 30px;" name="kelpk" method="get" value="{{ isset($dt_beli) ? $dt_beli->kelpk : '' }}"
                                            data-options="valueField:'kelpk', textField:'kelnm', prompt:'{{ $dt_beli->kelnm }}', url:'{{ route('api.get-dep') }}', editable:true, limitToList:'true'">
                                    </div>

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Untuk</div>
                                        <input id="mif" class="easyui-combobox col-2" style="height: 30px;" name="mifpk" method="get" value="{{ isset($dt_beli) ? $dt_beli->mifpk : '' }}"
                                            data-options="valueField:'mifpk', textField:'mifnm', prompt:'{{ $dt_beli->mifnm }}', url:'{{ route('api.get-mif') }}', editable:true, limitToList:'true'">
                                    </div>

                                    <div class="d-flex p-1">
                                        <div class="p-0 w-25">Total</div>
                                        <input id="totalBeli" class="easyui-validatebox height_input" readonly
                                            style="background-color: #f0f0f0; color:#575757; border: 1px solid #aaa; border-radius: 8px; padding: 8px 12px; text-align: left;">
                                    </div>

                                </div>
                            </div>
                        </div>
                    </div>

                </form>

                <div class="d-flex align-items-center gap-2">

                    <div class="d-flex justify-content-end align-items-center gap-3 mb-3">
                        <div id="add" class="p-0 d-none d-flex gap-3">
                            <div class="font-5 color-view-blue pointer fw-bold" onclick="tambahrow();"
                                style="color:#359DD9; text-decoration:none;">Add Data</div>
                            <div class="font-5 blue pointer fw-bold d-none" id="savedt" onclick="saverow();">Save
                            </div>
                        </div>

                        @if (in_array(Session::get('deppk'), ['3', '8']))
                            <div id="lookuppr" class="flex-grow-0 d-none">
                                <button onclick="KlikLookUpPr()" class="btn btn-sm btn-dark">
                                    <i class="fa fa-folder-open"></i> Lookup PR
                                </button>
                            </div>
                        @endif
                    </div>
                </div>

                <div id="warningtable" class="font-warning pb-3 d-none">
                        *) Belum ada list detail pada table pembelian. Silahkan lengkapi data dan masukan list pembelian terlebih
                        dahulu!
                </div>

                <div class="p-0">
                    <table id="dg" class="easyui-datagrid" title="" align="center" toolbar="#tb"
                        striped="false" pagination="true" method="get" rownumbers="false"
                        pageList="[100,200,300,500]" pageSize="100" singleSelect="false" collapsible="true"
                        fitColumns="true" idField="index"
                        data-options="onAfterEdit: function(index, row, changes) {hitungTotalJumlahHarga(); simpanTotal();}, onCheck:function(){menu();}, onCheckAll:function(){menu();}, onUncheck:function(){menu();}, onUncheckAll:function(){},
                        multiSort:true, remoteSort:false, border:true, dnd:true, checkOnSelect:false,
                        rowStyler:function(index,row){
                            if (row.copy==3){
                                return 'color:#000000;background-color:#D6D6D6;';
                            }
                        }">
                        <thead>
                            <tr>
                                <th field="belidtpk" hidden="true" formatter="AttrBelidtpk"></th>
                                <th field="ck" width="auto" styler="styler1" checkbox="true" hidden="true"></th>
                                <th field="index" width="3%" styler="styler2" editor="disabled">No</th>
                                <th field="brgnm" width="auto" styler="styler2" editor="text">Keterangan</th>
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
                        <a href="javascript:void(0)" plain="true" onclick="delete_list_belidt();"
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

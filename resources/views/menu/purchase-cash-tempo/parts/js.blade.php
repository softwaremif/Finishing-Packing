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
        $('#dg').edatagrid()
    });

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

    function Attrprdtpk(value, row) {
        var hiddenSave = '<input type="text" id="isEdit-' + row.prdtpk + '" value="false">';
        return hiddenSave;
    }

    $(function() {
        const notran = "{{ e($beli->nobukti ?? '') }}" || localStorage.getItem('notran-beli') || '';
        $('#notran').html(`No. Bukti ${notran}`)
        $('#nobukti').val(notran)

        let pathArray = window.location.pathname.split("/");
        const pathname = pathArray[3];


        if (pathname == "add") {
            $('#add').removeClass('d-none');
            $('#lookuppr').removeClass('d-none');
            $('#dg').datagrid('showColumn', 'ck');
            $('#save_submit').removeClass('d-none');
            $('#cancel_submit').removeClass('d-none');
        } else if (pathname == "detail") {
            $('#dg').edatagrid('options').editing = false;
            $('#lookuppr').addClass('d-none');
            $('input').attr('disabled', true);
            $('#tglInvoice').datebox('disable');
            $('.easyui-combobox').combobox('disable');
            $('#edit_data').removeClass('d-none');
            const notran = "{{ e($beli->nobukti ?? '') }}" || localStorage.getItem('notran-beli') || '';
            const total = "{{ e($beli->totbeli ?? 0) }}" || 0;
            $('#total').val(money(total));
        }

        @if (isset($beli))
            const belidtpk = pathArray[4]
            let url = "{{ route('api.get-belidt', ['belidtpk' => ':belidtpk']) }}";
            url = url.replace(':belidtpk', belidtpk);

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
        @endif
    })

    function tambahrow() {
        $('#dg').edatagrid('addRow');
        var index = $('#dg').edatagrid('getRows').length - 1; // Ambil index terakhir (baris baru)
    }

    $('#dg').edatagrid({
        onBeforeEdit: function(index, row) {
            $('#savedt').removeClass('d-none');
            var token = $('meta[name="csrf-token"]').attr('content');
            row._token = token;
        },
        onBeforeSave: function(index) {
            $('#savedt').addClass('d-none');
        }
    });

    function saverow() {
        $('#dg').edatagrid('saveRow');
    }

    function hitungTotalJumlahHarga() {
        var rows = $('#dg').datagrid('getRows');
        var total = 0;

        for (var i = 0; i < rows.length; i++) {
            var qty = parseFloat(rows[i].jmlbeli) || 0;
            var harga = parseFloat(rows[i].hrgbeli.toString().replace(/,/g, '')) || 0;

            var subtotal = qty * harga;

            // Update the 'jumlah' field for the current row
            $('#dg').datagrid('updateRow', {
                index: i,
                row: {
                    index: i + 1,
                    jmlhrg: subtotal // Update the 'jumlah' field in the table
                }
            });

            total += subtotal;
        }

        // Display the total in the input box
        $('#total').val(total.toLocaleString('id-ID', {
            minimumFractionDigits: 2
        }));
    }

    function simpanTotal() {
        var total = $('#total').val()
            .replace(/\./g, '')
            .replace(/,/g, '.'); // ← ini kunci utamanya!

    }

    function money(value, row) {
        if (value) {
            return new Intl.NumberFormat('id-ID', {
                style: 'currency',
                currency: 'IDR',
                minimumFractionDigits: 0
            }).format(value);
        }
        return value
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

    function delete_list_podt() {
        $.messager.confirm('Confirm', 'Are you sure you want to delete the selected items?', function(r) {
            if (r) {
                let dg = $('#dg');
                let rows = dg.datagrid('getSelections'); // Ambil semua baris yang dipilih
                console.log('Selected rows:', rows); // Debug: Lihat baris yang dipilih

                if (rows.length > 0) {
                    // Kumpulkan indeks baris yang dipilih
                    let indices = [];
                    for (let i = 0; i < rows.length; i++) {
                        let index = dg.datagrid('getRowIndex', rows[i]);
                        console.log(`Row ${i}: index=${index}, data=`, rows[i]); // Debug: Lihat indeks dan data
                        if (index >= 0) {
                            indices.push(index);
                        }
                    }
                    console.log('Indices to delete:', indices); // Debug: Lihat indeks yang akan dihapus

                    // Urutkan indeks dari besar ke kecil
                    indices.sort((a, b) => b - a);

                    // Hapus baris
                    for (let i = 0; i < indices.length; i++) {
                        console.log(`Deleting row at index ${indices[i]}`); // Debug: Lihat baris yang dihapus
                        dg.datagrid('deleteRow', indices[i]);
                    }

                    // Kosongkan seleksi
                    dg.datagrid('clearSelections');
                    console.log('Selections after deletion:', dg.datagrid(
                        'getSelections')); // Debug: Pastikan seleksi kosong

                    // Panggil fungsi lain jika perlu (misalnya, hitung ulang total)
                    hitungTotalJumlahHarga();
                    simpanTotal();
                } else {
                    $.messager.alert('Warning', 'Please select at least one row to delete.', 'warning');
                }
            }
        });
    }

    function parseFormattedNumber(value) {
        if (typeof value !== 'string') return value;

        // Buang titik ribuan
        value = value.replace(/\./g, '');

        // Buang koma dan desimal di belakangnya
        value = value.split(',')[0];

        return parseInt(value, 10);
    }

    function formatDateToDMY(dateStr) {
        if (!dateStr) return '';
        const parts = dateStr.split('/'); // [yyyy, mm, dd]

        if (parts.length !== 3) return dateStr;
        return `${parts[2]}-${parts[1]}-${parts[0]}`;
    }

    function totbel() {
        var rows = $('#dg').datagrid('getRows');

        var total = 0;
        for (let i = 0; i < rows.length; i++) {
            let qty = parseFloat(rows[i].jmlbeli) || 0;
            let harga = parseFloat(rows[i].hrgbeli.toString().replace(/,/g, '')) || 0;

            let subtotal = qty * harga;

            total += subtotal;
        }

        return total
    }

    function submit_pr(action) {
        
        let form = $('#form_prpk');
        let dg = $('#dg');
        let rows = dg.datagrid('getRows'); // Ambil semua baris di DataGrid

        // Validasi sisi klien
        if (rows.length === 0) {
            $.messager.alert('Warning', 'Minimal satu detail barang harus diisi.', 'warning');
            return;
        }

        // Siapkan data detail
        let details = rows.map(row => ({
            index: row.index,
            brgnm: row.brgnm,
            jmlbeli: row.jmlbeli,
            unit: row.unit,
            hrgbeli: row.hrgbeli,
            jmlhrg: row.jmlhrg
        }));

        // Ambil data form
        let formData = form.serializeArray();
        let data = {};
        formData.forEach(item => {
            // if (item.name === 'totbeli') {

            //     data[item.name] = totbel();
            // }
            if (item.name === 'tglinv') {
                data[item.name] = formatDateToDMY(item.value);
                console.log(formatDateToDMY(item.value));

            } 
            else {
                data[item.name] = item.value;
            }
            data["totbeli"] = totbel();
            
        });
        console.log(formData);
        
        data.details = details;

        let pathArray = window.location.pathname.split("/");

        const storeUrl = "{{ route('po-cash-tempo.store') }}";
        const updateBaseUrl = "{{ url('purchase-cash-tempo') }}"; // tanpa ID

        const url = action === 'submit' ?
            storeUrl :
            `${updateBaseUrl}/update/${pathArray[4]}`;

        const isSubmit = action === "submit";
        const type = "POST"; // tetap POST
        if (!isSubmit) data._method = "PUT"; // tambahkan override method jika update

        // console.log(data);
        
        // Kirim data ke server
        $.ajax({
            url: url,
            type: type,
            data: JSON.stringify(data),
            contentType: 'application/json',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    $.messager.alert('Success', response.message, 'info');
                    // Reset form dan DataGrid jika perlu
                    form.form('clear');
                    dg.datagrid('loadData', []);
                    localStorage.removeItem("notran-beli");
                    window.location.href = "{{ route('po-cash-tempo.index') }}"
                } else {
                    $.messager.alert('Error', response.message || 'Gagal menyimpan data.', 'error');
                }
            },
            error: function(xhr) {
                let errors = xhr.responseJSON?.errors || {};
                let errorMsg = 'Gagal menyimpan data:<br>';
                for (let field in errors) {
                    errorMsg += `- ${errors[field].join(', ')}<br>`;
                }
                $.messager.alert('Error', errorMsg, 'error');
            }
        });
    }

    function edit_data() {
        $('#edit_data').addClass('d-none');
        $('#back_edit').addClass('d-none');
        $('#cancel_save_edit').removeClass('d-none');
        $('#back_input').removeClass('d-none');
        $('#save_edit').removeClass('d-none');
        $('#add').removeClass('d-none');
        $('#lookuppr').removeClass('d-none');
        $('#dg').edatagrid('options').editing = true;
        $('#dg').datagrid('showColumn', 'ck');
        $('#noinvoice').attr('disabled', false);
        $('#tglInvoice').datebox('enable');
        $('.easyui-combobox').combobox('enable');
    }

    function cancel_edit() {
        $('#dg').edatagrid('options').editing = false;
        $('input').attr('disabled', false);
        $('#tglInvoice').datebox('disable');
        $('.easyui-combobox').combobox('disable');
        $('#edit_data').removeClass('d-none');
        $('#noinvoice').attr('disabled', true);
        $('#dg').edatagrid('options').editing = false;
        $('#dg').datagrid('hideColumn', 'ck');
        $('#cancel_save_edit').addClass('d-none');
        $('#add').addClass('d-none');
        $('#addnew').addClass('d-none');
        $('#back_input').addClass('d-none');
        $('#back_edit').removeClass('d-none');
    }

    function back_input_barang() {
        $('#ConfirmCancel').modal('show');
        $('#dg').datagrid('reload');
    }

    function ConfirmDelete() {
        window.location.href = "{{ route('po-cash-tempo.index') }}";
    }

    function KlikLookUpPr(nilai) {
        $('#ModalLookUpPr').modal('show');
    }
</script>

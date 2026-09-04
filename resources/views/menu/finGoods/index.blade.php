@extends('layout.main')

@section('content')
    <div class="page-wrap">

        <x-table-default
            id="dgOrder"
            sort="true"
            title="Daftar Data OP"
            :buyer-url="route('api.buyer-list')"
        >
            {{-- Filter tambahan khusus halaman ini, selain search/buyer/tahun default --}}
            <x-slot name="filters">
                <div class="p-0">
                    <input data-dg-filter="status" data-dg-filter-type="select"
                           data-dg-options='[{"value":"","text":"All Status"},{"value":"finished","text":"Finished Goods"},{"value":"inspect","text":"Inspect"},{"value":"shipment","text":"Shipment"}]'
                           style="width:150px">
                </div>
            </x-slot>

            {{-- Tabel EasyUI native, sama seperti sebelumnya --}}
            <table id="dgOrder" class="easyui-datagrid" style="width:100%;height:500px"
                   url="{{ route('finGoods.list') }}" method="get" pagination="true" pageSize="50"
                   pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="true"
                   checkOnSelect="true" selectOnCheck="true" fitColumns="false" border="false">
                   <thead frozen="true">
                    <tr>
                        <th field="action" width="90" formatter="formatAction" align="center">Aksi</th>
                        {{-- <th field="no" width="50" align="center">No</th> --}}
                        <th field="POno" width="150">PO No</th>
                        <th field="OP" width="150">OP</th>
                        <th field="shipdate" width="110" formatter="formatShipdate" align="center">Ship Date</th>
                    </tr>
                   </thead>
                <thead>
                    <tr>
                        <th field="customer" width="150">Place</th>
                        <th field="season" width="150">Season</th>
                        <th field="buyer" width="150">Buyer</th>
                        <th field="style" width="150">Style</th>
                        <th field="material" width="170">Color</th>
                        <th field="secsz" width="100">Secondary<br>Size</th>
                        <th field="pcs_stuff" width="100" formatter="pcsctn">Finished Goods</th>
                        <th field="pcs_inspect" width="100" formatter="pcsctn">Inspect</th>
                        <th field="pcs_ship" width="100" formatter="pcsctn">Shipment</th>
                        <th width="100" field="balance_ctn" formatter="balance">Balance</th>
                        <th field="silhouette" width="190" align="left">Description</th>
                    </tr>
                </thead>
            </table>
        </x-table-default>
    </div>

    {{-- Popup rincian PO+OP (MODAL BOOTSTRAP): baris per popk+part+secsz
         (OP, PO, Color, Secsz, Finished Goods/Inspect/Shipment/Balance)
         dengan tombol aksi (detail / pdf / kunci) seperti sebelumnya.
         Atribut dismiss ditulis ganda (data-dismiss + data-bs-dismiss)
         agar jalan di Bootstrap 4 maupun 5. --}}
    <div class="modal fade" id="poPopup" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="poPopupTitle">Rincian PO</h5>
                    <div class="d-flex align-items-center" style="gap:8px;">
                        {{-- Laporan GLOBAL: mencakup SELURUH place/color dalam
                             PO+OP ini (bukan hanya place yang sedang dibuka). --}}
                        <button type="button" class="btn btn-sm btn-outline-secondary"
                                onclick="openPrintGlobal()"
                                title="Cetak laporan gabungan seluruh place & color dalam PO+OP ini">
                            <i class="fas fa-globe"></i> Cetak
                        </button>
                        <button type="button" class="close btn-close"
                                data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
                <div class="modal-body">
                    <div id="poPopupBody" style="min-height:160px;">
                        <div style="text-align:center;color:#94a3b8;padding:40px 0;">Memuat...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal pesan (pengganti alert()) — bisa tampil DI ATAS modal
         rincian (z-index dinaikkan lewat CSS di bawah). --}}
    <div class="modal fade" id="msgModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:420px;">
            <div class="modal-content">
                <div class="modal-body text-center" style="padding:28px 24px;">
                    <div id="msgModalIcon" style="font-size:40px; line-height:1; margin-bottom:12px;"></div>
                    <div id="msgModalText" style="font-size:14px; color:#374151;"></div>
                </div>
                <div class="modal-footer justify-content-center" style="border-top:0; padding-top:0; padding-bottom:24px;">
                    <button type="button" class="btn btn-secondary btn-sm px-4"
                            data-dismiss="modal" data-bs-dismiss="modal">OK</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Modal konfirmasi (pengganti confirm()) — pola callback:
         tombol "Ya" menjalankan aksi yang tertunda, "Batal" membatalkan. --}}
    <div class="modal fade" id="confirmModal" tabindex="-1" role="dialog" aria-hidden="true"
         data-backdrop="static" data-bs-backdrop="static">
        <div class="modal-dialog modal-dialog-centered" role="document" style="max-width:420px;">
            <div class="modal-content">
                <div class="modal-body text-center" style="padding:28px 24px;">
                    <div style="font-size:40px; line-height:1; margin-bottom:12px;">
                        <i class="fas fa-question-circle" style="color:#f59e0b;"></i>
                    </div>
                    <div id="confirmModalText" style="font-size:14px; color:#374151;"></div>
                </div>
                <div class="modal-footer justify-content-center" style="border-top:0; padding-top:0; padding-bottom:24px; gap:8px;">
                    <button type="button" class="btn btn-light btn-sm px-4" id="confirmModalNo"
                            data-dismiss="modal" data-bs-dismiss="modal">Batal</button>
                    <button type="button" class="btn btn-primary btn-sm px-4" id="confirmModalYes">
                        Ya, Lanjutkan
                    </button>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Lebar modal rincian: 90% viewport, maks 1200px */
        #poPopup .modal-dialog.modal-xl {
            max-width: min(1200px, 90vw);
        }

        /* Modal pesan & konfirmasi harus di atas modal rincian + backdrop-nya */
        #msgModal,
        #confirmModal {
            z-index: 2000;
        }

        /* Backdrop milik modal pesan/konfirmasi diangkat DI ATAS modal
           rincian (yang z-index-nya ~1050) supaya area di belakangnya
           ikut meredup — tanpa ini modal menempel di konten putih dan
           batasnya tidak terlihat. Class ini dipasang via JS saat show. */
        .modal-backdrop.backdrop-top {
            z-index: 1990;
        }

        /* Pertegas pemisahan visual modal pesan/konfirmasi */
        #msgModal .modal-content,
        #confirmModal .modal-content {
            box-shadow: 0 18px 50px rgba(15, 23, 42, 0.35);
            border: 1px solid #e2e8f0;
            border-radius: 12px;
        }

        .po-popup-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 13px;
        }
        .po-popup-table th, .po-popup-table td {
            border-bottom: 1px solid #f3f4f6;
            padding: 10px 12px;
            text-align: center;
            vertical-align: middle;
        }
        .po-popup-table th {
            background: #f9fafb;
            font-weight: 600;
            color: #374151;
            border-bottom: 1px solid #e5e7eb;
            position: sticky;
            top: 0;
        }
        .po-popup-table td.cell-left { text-align: left; }
        .po-popup-table .sub {
            font-size: 11px;
            color: #888;
        }
    </style>
@endsection

@section('js_custom')
    <script>
        var lockCsrf = "{{ csrf_token() }}";
        // Template URL lock/unlock; __PO__/__PART__ diganti saat dipanggil.
        var lockUrlTemplate   = "{{ route('finGoods.lock', ['popk' => '__PO__', 'part' => '__PART__']) }}";
        var unlockUrlTemplate = "{{ route('finGoods.unlock', ['popk' => '__PO__', 'part' => '__PART__']) }}";
        var popupUrl          = "{{ route('finGoods.popup') }}";
        var printGlobalUrl    = "{{ route('finGoods.printGlobal') }}";
        // Hanya guserpk = 34 yang boleh membuka gembok yang sudah tertutup.
        var canUnlock = {{ (int) session('guserpk') === 34 ? 'true' : 'false' }};

        function balance(value, row, index) {
            var balancePcs = parseInt(row.balance_pcs) || 0;
            var balanceCtn = parseInt(row.balance_ctn) || 0;
            var color = balancePcs < 0 ? '#d85a30' : 'inherit';

            return '<div style="color:' + color + ';">' + balancePcs.toLocaleString() + ' pcs</div>' +
                '<div style="font-size:11px;color:#888;">' + balanceCtn.toLocaleString() + ' ctn</div>';
        }

        function pcsctn(value, row, index) {
            var field = this.field;
            var ctnMap = { pcs_stuff: 'ctn_stuff', pcs_inspect: 'ctn_inspect', pcs_ship: 'ctn_ship' };
            var ctnValue = row[ctnMap[field]] || 0;
            var pcs = parseInt(value) || 0;
            var ctn = parseInt(ctnValue) || 0;

            return '<div>' + pcs.toLocaleString() + ' pcs</div>' +
                '<div style="font-size:11px;color:#888;">' + ctn.toLocaleString() + ' ctn</div>';
        }

        function stylerBalance(value, row, index) {
            return 'color:#dc2626;font-weight:600;';
        }

        function formatShipdate(value, row, index) {
            if (!value || value === '0000-00-00' || value === '0000-00-00 00:00:00') return '-';
            return value;
        }

        // Klik detail pada baris agregat POno+OP -> buka POPUP rincian
        // per popk+part dulu; masuk halaman detail dari dalam popup.
        // Index baris dipakai (bukan menyisipkan POno/OP mentah ke onclick)
        // supaya aman dari karakter kutip di nomor PO.
        // 2 tombol aksi per baris:
        //   mata (fa-eye)  -> buka modal rincian per popk+part+secsz
        //   edit (fa-edit) -> langsung ke halaman detail (popk/part
        //                     representatif baris grid ini)
        function formatAction(value, row, index) {
            return `
                <a href="javascript:void(0)"
                   onclick="showPoPopup(${index})"
                   class="action-btn"
                   title="Lihat Rincian">
                    <i class="fas fa-eye"></i>
                </a>
                `;
                // <a href="javascript:void(0)"
                //    onclick="openTransfer('${row.POno}', '${row.OP}')"
                //    class="action-btn"
                //    style="margin-left:6px;"
                //    title="Buka Detail">
                //     <i class="fas fa-edit"></i>
                // </a>
        }

        // pono/op/poref popup yang sedang terbuka — dipakai untuk me-refresh
        // isi popup setelah aksi kunci/buka kunci.
        var popupPono  = null;
        var popupOp    = null;
        var popupPoref = null;

        // Buka/tutup modal Bootstrap — kompatibel BS4 (jQuery plugin)
        // maupun BS5 (bootstrap.Modal).
        function bsModal(el, action) {
            if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
                $(el).modal(action);
            } else if (window.bootstrap && window.bootstrap.Modal) {
                var inst = window.bootstrap.Modal.getOrCreateInstance(el);
                action === 'show' ? inst.show() : inst.hide();
            }
        }

        // Untuk modal yang tampil DI ATAS modal rincian (pesan/konfirmasi):
        // backdrop yang baru dibuat Bootstrap harus diangkat di atas modal
        // rincian, kalau tidak area belakangnya tidak meredup dan modal
        // tampak menyatu dengan konten putih di bawahnya.
        function bsModalOnTop(el) {
            bsModal(el, 'show');

            setTimeout(function () {
                $('.modal-backdrop').last().addClass('backdrop-top');
            }, 0);
        }

        function openPoModal() {
            bsModal(document.getElementById('poPopup'), 'show');
        }

        // Pengganti alert(): modal pesan kecil dengan ikon sukses/gagal.
        function showMsgModal(message, ok) {
            var icon = document.getElementById('msgModalIcon');

            icon.innerHTML = ok
                ? '<i class="fas fa-check-circle" style="color:#16a34a;"></i>'
                : '<i class="fas fa-times-circle" style="color:#dc2626;"></i>';

            document.getElementById('msgModalText').textContent = message || 'Terjadi kesalahan.';

            bsModalOnTop(document.getElementById('msgModal'));
        }

        // Pengganti confirm(): modal konfirmasi dengan callback.
        // Aksi hanya dijalankan saat user menekan "Ya, Lanjutkan";
        // "Batal" / klik di luar tidak menjalankan apa pun.
        var confirmCallback = null;

        function showConfirmModal(message, onYes) {
            confirmCallback = onYes;
            document.getElementById('confirmModalText').textContent = message || 'Lanjutkan?';
            bsModalOnTop(document.getElementById('confirmModal'));
        }

        document.getElementById('confirmModalYes').addEventListener('click', function () {
            bsModal(document.getElementById('confirmModal'), 'hide');

            var cb = confirmCallback;
            confirmCallback = null;
            if (typeof cb === 'function') cb();
        });

        function isPoModalOpen() {
            return $('#poPopup').hasClass('show');
        }

        function showPoPopup(index) {
            var row = $('#dgOrder').datagrid('getRows')[index];
            if (!row) return;

            popupPono  = row.POno;
            popupOp    = row.OP;
            popupPoref = row.poref;

            $('#poPopupTitle').text(
                'Rincian PO ' + row.POno + ' — OP ' + row.OP
                + (row.poref ? ' — Ref ' + row.poref : '')
            );
            $('#poPopupBody').html('<div style="text-align:center;color:#94a3b8;padding:40px 0;">Memuat...</div>');

            openPoModal();
            loadPopupRows();
        }

        // Cetak Global: laporan mencakup SELURUH place/color dalam PO+OP
        // ini, jadi sengaja mengirim popupPono/popupOp saja (TANPA
        // popupPlace) -- beda dari popup rincian yang di-scope place.
        function openPrintGlobal() {
            if (popupPono == null) return;

            var url = printGlobalUrl
                + '?pono=' + encodeURIComponent(popupPono)
                + '&op=' + encodeURIComponent(popupOp);
            window.open(url, '_blank');
        }

        function loadPopupRows() {
            if (popupPono == null) return;

            var url = popupUrl
                + '?pono=' + encodeURIComponent(popupPono)
                + '&op=' + encodeURIComponent(popupOp)
                + '&poref=' + encodeURIComponent(popupPoref == null ? '' : popupPoref);

            fetch(url, { headers: { 'Accept': 'application/json' } })
                .then(function (res) { return res.json(); })
                .then(function (data) {
                    renderPoPopup(data.rows || []);
                })
                .catch(function () {
                    $('#poPopupBody').html('<div style="text-align:center;color:#dc2626;padding:40px 0;">Gagal memuat data.</div>');
                });
        }

        function esc(s) {
            return String(s == null ? '' : s)
                .replace(/&/g, '&amp;').replace(/</g, '&lt;')
                .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
        }

        function pcsCtnCell(pcs, ctn) {
            pcs = parseInt(pcs) || 0;
            ctn = parseInt(ctn) || 0;
            return '<div>' + pcs.toLocaleString() + ' pcs</div>'
                + '<div class="sub">' + ctn.toLocaleString() + ' ctn</div>';
        }

        function dateCell(v) {
            if (!v || v === '0000-00-00' || v === '0000-00-00 00:00:00') return '-';
            return esc(v);
        }

        // Kolom Aksi per baris popup — logika SAMA seperti grid sebelum
        // agregasi POno+OP:
        //   min_status >= 7 -> tombol PDF (laporan) + gembok TERTUTUP
        //                      (merah bisa klik = buka, hanya guserpk 34;
        //                       abu-abu mati untuk user lain)
        //   min_status = 6  -> tombol detail + gembok TERBUKA (klik = kunci)
        //   min_status < 6  -> tombol detail saja
        function popupActionCell(r) {
            var minStatus = parseInt(r.min_status) || 0;
            var gab = (r.gabung != null ? r.gabung : 0);
            var html = '';

            if (minStatus >= 7) {
                html += '<a href="javascript:void(0)" class="action-btn" title="Laporan Packing" '
                    + 'onclick="openReport(' + r.popk + ', ' + r.part + ', ' + gab + ')">'
                    + '<img src="{{ asset('/public/css/images/pdf.jpg') }}" width="18" height="18">'
                    + '</a>';

                if (canUnlock) {
                    html += '<a href="javascript:void(0)" class="action-btn" '
                        + 'style="color:#dc2626; margin-left:6px;" '
                        + 'title="Buka kunci shipment (status kembali ke 6)" '
                        + 'onclick="unlockShipment(' + r.popk + ', ' + r.part + ')">'
                        + '<i class="fas fa-lock"></i>'
                        + '</a>';
                } else {
                    html += '<span class="action-btn" '
                        + 'style="color:#94a3b8; cursor:not-allowed; margin-left:6px;" '
                        + 'title="Terkunci (hanya admin yang bisa membuka)">'
                        + '<i class="fas fa-lock"></i>'
                        + '</span>';
                }

                return html;
            }

            html += '<a href="javascript:void(0)" class="action-btn" title="Masuk Detail" '
                + 'onclick="openTransfer(\'' + esc(r.POno) + '\', \'' + esc(r.OP) + '\')">'
                + '<i class="fas fa-edit"></i>'
                + '</a>';

            if (minStatus >= 6) {
                html += '<a href="javascript:void(0)" class="action-btn" '
                    + 'style="color:#2563eb; margin-left:6px;" '
                    + 'title="Kunci shipment (semua carton -> status 7)" '
                    + 'onclick="lockShipment(' + r.popk + ', ' + r.part + ')">'
                    + '<i class="fas fa-lock-open"></i>'
                    + '</a>';
            }

            return html;
        }

        function renderPoPopup(rows) {
            if (!rows.length) {
                $('#poPopupBody').html('<div style="text-align:center;color:#94a3b8;padding:40px 0;">Tidak ada data.</div>');
                return;
            }

            var html = '<table class="po-popup-table">'
                + '<thead><tr>'
                + '<th width="90">Aksi</th>'
                + '<th>OP</th>'
                + '<th>PO No</th>'
                + '<th>Place</th>'
                + '<th>Color</th>'
                + '<th>Secondary<br>Size</th>'
                + '<th>Ship Date</th>'
                + '<th>Finished Goods</th>'
                + '<th>Inspect</th>'
                + '<th>Shipment</th>'
                + '<th>Balance</th>'
                + '</tr></thead><tbody>';

            rows.forEach(function (r) {
                var balPcs = parseInt(r.balance_pcs) || 0;
                var balColor = balPcs < 0 ? '#d85a30' : 'inherit';

                html += '<tr>'
                    + '<td>' + popupActionCell(r) + '</td>'
                    + '<td>' + esc(r.OP) + '</td>'
                    + '<td class="cell-left">' + esc(r.POno) + '</td>'
                    + '<td class="cell-left">' + esc(r.customer) + '</td>'
                    + '<td class="cell-left">' + esc(r.material) + '</td>'
                    + '<td>' + (r.secsz ? esc(r.secsz) : '-') + '</td>'
                    + '<td>' + dateCell(r.shipdate) + '</td>'
                    + '<td>' + pcsCtnCell(r.pcs_stuff, r.ctn_stuff) + '</td>'
                    + '<td>' + pcsCtnCell(r.pcs_inspect, r.ctn_inspect) + '</td>'
                    + '<td>' + pcsCtnCell(r.pcs_ship, r.ctn_ship) + '</td>'
                    + '<td style="color:' + balColor + ';">' + pcsCtnCell(r.balance_pcs, r.balance_ctn) + '</td>'
                    + '</tr>';
            });

            html += '</tbody></table>';

            $('#poPopupBody').html(html);
        }

        function openReport(popk, parts, gab) {
            const url = `{{ route('finGoods.print', ['popk' => '__PO__', 'part' => '__PART__']) }}?gab=${gab ?? 0}`
                .replace('__PO__', popk)
                .replace('__PART__', parts);
            window.open(url, '_blank');
        }

        function openTransfer(pono, op) {
            const url = `{{ route('finGoods.detail') }}?pono=${encodeURIComponent(pono)}&op=${encodeURIComponent(op)}`;
            window.location.href = url;
        }

        function postLockAction(url, confirmMsg) {
            showConfirmModal(confirmMsg, function () {
                fetch(url, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': lockCsrf
                    }
                })
                .then(function (res) {
                    return res.json().then(function (data) {
                        return { ok: res.ok, data: data };
                    });
                })
                .then(function (r) {
                    var success = !!(r.ok && r.data.success);

                    showMsgModal(r.data.message || 'Terjadi kesalahan.', success);

                    if (success) {
                        $('#dgOrder').datagrid('reload');

                        // Popup sedang terbuka -> muat ulang barisnya juga agar
                        // ikon aksi (pdf/gembok) langsung menyesuaikan status baru.
                        if (isPoModalOpen()) {
                            loadPopupRows();
                        }
                    }
                })
                .catch(function () {
                    showMsgModal('Gagal menghubungi server.', false);
                });
            });
        }

        function lockShipment(popk, part) {
            var url = lockUrlTemplate
                .replace('__PO__', popk)
                .replace('__PART__', part);

            postLockAction(url, 'Kunci shipment? Semua carton pada PO/part ini akan dikunci.');
        }

        function unlockShipment(popk, part) {
            var url = unlockUrlTemplate
                .replace('__PO__', popk)
                .replace('__PART__', part);

            postLockAction(url, 'Buka kunci shipment? Semua carton ini akan dibuka.');
        }
    </script>
@endsection
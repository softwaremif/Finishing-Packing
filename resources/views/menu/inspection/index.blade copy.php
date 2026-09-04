@extends('layout.main')

@section('content')
    <div class="page-wrap">

        {{-- <x-table-default
            id="dgInspection"
            title="Daftar Inspection"
            :buyer-url="route('api.buyer-list')"
        > --}}

        <x-table-default
            id="dgInspection"
            title="Daftar Inspection"
            search
            search-name="search"
            search-placeholder="Search..."
            buyer
            buyer-name="buyer"
            buyer-url="{{ route('api.buyer-list') }}"
            buyer-value-field="buyer"
            buyer-text-field="buyer_name"
            buyer-mode="remote"
            year
            year-name="year"
            exfactory
            exfactory-name="ex_factory"
            sort-dropdown
            sort-asc-label="Earliest Ex Factory"
            sort-desc-label="Latest Ex Factory"
        >
            {{-- Filter tambahan khusus halaman ini, selain search/buyer/tahun default --}}
            {{-- <x-slot name="filters">
                <div class="p-0">
                    <input data-dg-filter="season" data-dg-filter-type="select"
                           data-dg-options='[{"value":"","text":"All Season"},{"value":"SS","text":"Spring/Summer"},{"value":"FW","text":"Fall/Winter"}]'
                           style="width:150px">
                </div>
            </x-slot> --}}

            {{-- Sama seperti grid Finished Goods, MINUS kolom
                 Finished Goods (pcs_stuff), Shipment (pcs_ship), dan Balance --}}
            <table id="dgInspection" class="easyui-datagrid" style="width:100%;height:500px"
                   url="{{ route('inspection.list') }}" method="get" pagination="true" pageSize="50"
                   pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="true"
                   checkOnSelect="true" selectOnCheck="true" fitColumns="false" border="false">
                   <thead frozen="true">
                       {{-- <th field="no" width="50" align="center">No</th> --}}
                       <th field="action" width="90" formatter="formatAction" align="center">Aksi</th>
                       {{-- <th field="POno" width="150">PO No</th>
                       <th field="OP" width="150">OP</th>
                       <th field="shipdate" width="110" formatter="formatShipdate" align="center">Ship Date</th> --}}
                       <th field="action" width="90" formatter="formatAction" align="center">Aksi</th>
                       <th field="inspect_status" width="150" formatter="formatInspectStatus" align="center">Status</th>
                       <th field="POno" width="150">PO No</th>
                       <th field="OP" width="150">OP</th>
                       <th field="shipdate" width="110" formatter="formatShipdate" align="center">Ship Date</th>

                       <th field="OP" width="230" formatter="formatOrderInfo">Order Information</th>
                       <th field="POno" width="220" formatter="formatPOno">PO No</th>
                       <th field="GAC" width="100" align="center" formatter="formatExFactory">Ex Factory</th>
                       <th field="pcs_inspect" width="150" formatter="pcsctn" align="center">Cartons in inspection</th>
                       <th field="inspect_status" width="150" formatter="formatInspectStatus" align="center">Inspection Status</th>
                   </thead>
                <thead>
                    <tr>
                        <th field="customer" width="150">Place</th>
                        <th field="season" width="150">Season</th>
                        <th field="buyer" width="150">Buyer</th>
                        <th field="style" width="150">Style</th>
                        <th field="material" width="150">Color</th>
                        <th field="secsz" width="100">Secondary<br>Size</th>
                        <th field="pcs_inspect" width="100" formatter="pcsctn">Inspect</th>
                        <th field="pinjam" width="140" formatter="formatDate">Pinjam</th>
                        <th field="kembali" width="140" formatter="formatDate">Kembali</th>
                        <th field="silhouette" width="190" align="left">Description</th>
                    </tr>
                </thead>
            </table>
        </x-table-default>
    </div>

    {{-- Popup rincian PO+OP+poref (MODAL BOOTSTRAP): baris per
         popk+part+secsz dengan angka Inspect + tanggal Pinjam/Kembali,
         tombol edit tiap baris masuk ke halaman detail inspection
         (sekarang GLOBAL: pono/op saja, bukan popk/part). --}}
    <div class="modal fade" id="poPopup" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="poPopupTitle">Rincian PO</h5>
                    <button type="button" class="close btn-close"
                            data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div id="poPopupBody" style="min-height:160px;">
                        <div style="text-align:center;color:#94a3b8;padding:40px 0;">Memuat...</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        /* Lebar modal rincian: 90% viewport, maks 1200px */
        #poPopup .modal-dialog.modal-xl {
            max-width: min(1200px, 90vw);
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

        .cell-stack {
        text-align: left;
        line-height: 1.35;
        }
        .cell-stack .cs-main {
            font-weight: 600;
            font-size: 13px;
            color: #0f172a;
        }
        .cell-stack .cs-sub {
            font-size: 11px;
            color: #64748b;
        }
        .cell-stack .cs-meta {
            font-size: 10.5px;
            color: #94a3b8;
        }
    </style>
@endsection

@section('js_custom')
    <script>
        var popupUrl = "{{ route('finGoods.popup') }}";
        const isSuperUser = @json(session('guserpk') === 34);
        const localNoImg = "{{ asset('public/css/images/no-img.png') }}";

        function pcsctn(value, row, index) {
            var field = this.field;
            var ctnMap = { pcs_stuff: 'ctn_stuff', pcs_inspect: 'ctn_inspect', pcs_ship: 'ctn_ship' };
            var ctnValue = row[ctnMap[field]] || 0;
            var pcs = parseInt(value) || 0;
            var ctn = parseInt(ctnValue) || 0;

            return '<div>' + pcs.toLocaleString() + ' pcs</div>' +
                '<div style="font-size:11px;color:#888;">' + ctn.toLocaleString() + ' ctn</div>';
        }

        // Kolom Pinjam / Kembali: tampilkan "-" jika kosong (belum pernah dipinjam/dikembalikan)
        function formatDate(value, row, index) {
            if (!value) return '-';
            return value;
        }

        // Status inspect — fraksi HANYA dari carton yang PERNAH diinspect:
        //   pembagi   = pinjam_count  (carton yang pernah dipinjam/inspect)
        //   pembilang = returned_count = pinjam_count - borrowed_count
        //               (yang pernah dipinjam dan kini sudah kembali)
        // Contoh: total 50 ctn, diinspect 10, belum ada yang kembali -> 0 / 10.
        // 'complete' -> semua yang dipinjam sudah kembali
        // 'partial'  -> masih ada yang dipinjam
        function formatInspectStatus(value, row, index) {
            var inspectedCount = parseInt(row.pinjam_count) || 0;
            var returnedCount  = parseInt(row.returned_count) || 0;

            if (value === 'complete') {
                return '<span class="badge" style="background:#dcfce7;color:#15803d;font-weight:600;padding:4px 10px;border-radius:10px;">' +
                    'Complete</span>' +
                    '<div style="font-size:11px;color:#888;">' + returnedCount + ' / ' + inspectedCount + ' carton</div>';
            }

            if (value === 'partial') {
                return '<span class="badge" style="background:#fef3c7;color:#b45309;font-weight:600;padding:4px 10px;border-radius:10px;">' +
                    'Belum Complete</span>' +
                    '<div style="font-size:11px;color:#888;">' + returnedCount + ' / ' + inspectedCount + ' carton</div>';
            }

            return '-';
        }

        function formatShipdate(value, row, index) {
            if (!value || value === '0000-00-00' || value === '0000-00-00 00:00:00') return '-';
            return value;
        }

        // 2 tombol aksi per baris (konsisten dengan Finished Goods):
        //   mata (fa-eye)  -> buka modal rincian per popk+part+secsz
        //   edit (fa-edit) -> langsung ke halaman detail GLOBAL (pono/op)
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
                //    onclick="openInspection('${row.POno}', '${row.OP}')"
                //    class="action-btn"
                //    style="margin-left:6px;"
                //    title="Buka Detail">
                //     <i class="fas fa-edit"></i>
                // </a>
        }

        /* ============ Modal Bootstrap (kompatibel BS4 & BS5) ============ */

        var popupPono  = null;
        var popupOp    = null;
        var popupPoref = null;

        function bsModal(el, action) {
            if (window.jQuery && typeof window.jQuery.fn.modal === 'function') {
                $(el).modal(action);
            } else if (window.bootstrap && window.bootstrap.Modal) {
                var inst = window.bootstrap.Modal.getOrCreateInstance(el);
                action === 'show' ? inst.show() : inst.hide();
            }
        }

        function showPoPopup(index) {
            var row = $('#dgInspection').datagrid('getRows')[index];
            if (!row) return;

            popupPono  = row.POno;
            popupOp    = row.OP;
            popupPoref = row.poref;

            $('#poPopupTitle').text(
                'Rincian PO ' + row.POno + ' — OP ' + row.OP
                + (row.poref ? ' — ' + row.poref : '')
            );
            $('#poPopupBody').html('<div style="text-align:center;color:#94a3b8;padding:40px 0;">Memuat...</div>');

            bsModal(document.getElementById('poPopup'), 'show');
            loadPopupRows();
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

        function renderPoPopup(rows) {
            if (!rows.length) {
                $('#poPopupBody').html('<div style="text-align:center;color:#94a3b8;padding:40px 0;">Tidak ada data.</div>');
                return;
            }

            var html = '<table class="po-popup-table">'
                + '<thead><tr>'
                + '<th width="60">Aksi</th>'
                + '<th>OP</th>'
                + '<th>PO No</th>'
                + '<th>Color</th>'
                + '<th>Secondary<br>Size</th>'
                + '<th>Ship Date</th>'
                + '<th>Inspect</th>'
                + '<th>Pinjam</th>'
                + '<th>Kembali</th>'
                + '</tr></thead><tbody>';

            rows.forEach(function (r) {
                html += '<tr>'
                    + '<td>'
                    +   '<a href="javascript:void(0)" class="action-btn" title="Inspection" '
                    +      'onclick="openInspection(\'' + esc(r.POno) + '\', \'' + esc(r.OP) + '\')">'
                    +      '<i class="fas fa-edit"></i>'
                    +   '</a>'
                    + '</td>'
                    + '<td>' + esc(r.OP) + '</td>'
                    + '<td class="cell-left">' + esc(r.POno) + '</td>'
                    + '<td class="cell-left">' + esc(r.material) + '</td>'
                    + '<td>' + (r.secsz ? esc(r.secsz) : '-') + '</td>'
                    + '<td>' + dateCell(r.shipdate) + '</td>'
                    + '<td>' + pcsCtnCell(r.pcs_inspect, r.ctn_inspect) + '</td>'
                    + '<td>' + dateCell(r.pinjam) + '</td>'
                    + '<td>' + dateCell(r.kembali) + '</td>'
                    + '</tr>';
            });

            html += '</tbody></table>';

            $('#poPopupBody').html(html);
        }

        // GLOBAL: navigasi ke halaman detail lewat pono/op saja (tidak
        // ada lagi popk/part/gab di URL).
        function openInspection(pono, op) {
            const url = `{{ route('inspection.detail') }}?pono=${encodeURIComponent(pono)}&op=${encodeURIComponent(op)}`;
            window.location.href = url;
        }


                // Ex Factory -- validasi KETAT, SAMA pola dengan index Packing.
        function formatExFactory(value) {
            if (value === null || value === undefined || value === '') return '';

            const datePart = String(value).split(' ')[0].split('T')[0];
            const parts = datePart.split('-');
            if (parts.length !== 3) return '';

            const year     = parseInt(parts[0], 10);
            const monthIdx = parseInt(parts[1], 10) - 1;
            const dayNum   = parseInt(parts[2], 10);

            if (
                !Number.isFinite(year) || year <= 0 ||
                !Number.isFinite(monthIdx) || monthIdx < 0 || monthIdx > 11 ||
                !Number.isFinite(dayNum) || dayNum <= 0 || dayNum > 31
            ) {
                return '';
            }

            const bulanSingkat = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            return `${dayNum} ${bulanSingkat[monthIdx]} ${year}`;
        }

        // PO No + Place digabung 1 cell. Badge mif ikut di sini (bukan
        // di Order Information) -- SAMA seperti versi lama formatPOno()
        // halaman ini yang menampilkan mif.
        function formatPOno(value, row) {
            const mifBadge = isSuperUser
                ? `<span class="badge bg-secondary-subtle text-secondary-emphasis mif-badge">mif ${row.mif}</span>`
                : '';
            return `
                <div class="cell-stack">
                    <div class="cs-main">${value ?? '-'}${mifBadge}</div>
                    <div class="cs-sub">${row.customer ?? '-'}</div>
                </div>
            `;
        }

        // Order Information -- foto, OP, Buyer, Season, Style, Qty
        // digabung 1 cell. SAMA pola dengan index Packing.
        function formatOrderInfo(value, row) {
            const imgUrl = row.order_image || localNoImg;
            const imgHtml = `
                <img src="${imgUrl}" width="60" height="60"
                    style="object-fit:cover;border-radius:6px;flex-shrink:0;"
                    onerror="this.onerror=null;this.src='${localNoImg}';">
            `;

            return `
                <div class="d-flex align-items-start gap-2">
                    ${imgHtml}
                    <div class="cell-stack">
                        <div class="cs-main">${row.OP ?? '-'}</div>
                        <div class="cs-sub">${row.buyer ?? '-'} &middot; ${row.season ?? '-'}</div>
                        <div class="cs-sub">${row.style ?? '-'}</div>
                        <div class="cs-meta">Qty: <strong style="color:#334155;">${Number(row.qty || 0).toLocaleString()}</strong></div>
                    </div>
                </div>
            `;
        }
    </script>
@endsection
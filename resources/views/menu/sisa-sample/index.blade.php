@extends('layout.main')
@section('css_custom')
    <style>
        .action-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 30px;
            height: 30px;
            border-radius: 8px;
            background: #e0f2fe;
            color: #0369a1;
            font-size: 13px;
            transition: 0.2s;
            text-decoration: none;
        }
        .action-btn:hover {
            background: #bae6fd;
            color: #0c4a6e;
        }
    </style>
@endsection

@section('content')
    <div class="page-wrap">
        <x-table-default
            id="dgOrder"
            title="Daftar Sisa Garment (Sample / FCA / Lab)"
            search
            search-name="cari"
            search-placeholder="Cari SR#..."
            buyer
            buyer-name="buyer"
            buyer-url="{{ route('sisa-sample.buyer-list') }}"
            buyer-value-field="buyernm"
            buyer-text-field="buyer_label"
            buyer-mode="local"
        >
            <x-slot name="filters">
                <button type="button" onclick="openTambahSampleModal()"
                    class="btn btn-dark btn-sm d-inline-flex align-items-center px-3"
                    style="border-radius:6px;">
                    <i class="fas fa-plus me-1"></i> Tambah dari Sample
                </button>
            </x-slot>

            {{-- FIX UTAMA: kolom DIRINGKAS -- sendingdate+receiptdate dan
                 tglin+tglout masing-masing digabung jadi 1 kolom (sub-baris),
                 SAMA pola dengan modul lain (PO No/OP, SR#/FU, dst). --}}
            <table id="dgOrder" class="easyui-datagrid" style="width:100%;height:600px"
                url="{{ route('sisa-sample.list') }}" method="get" pagination="true" pageSize="25"
                pageList="[25,50,100]" rownumbers="false" singleSelect="true" fitColumns="false" border="false">
                <thead>
                    <tr>
                        <th field="action" width="60" formatter="formatAction" align="center">Aksi</th>
                        <th field="no" width="50" align="center">No</th>
                        <th field="srno" width="130" formatter="formatSrFu">SR#</th>
                        <th field="samplenm" width="150" formatter="formatSampleOpsi">Sample Status<br>/ Option</th>
                        <th field="date" width="90" formatter="formatDate">Date</th>
                        <th field="buyernm" width="140">Buyer</th>
                        <th field="qty" width="110" formatter="formatQtyWash">Qty / Washing</th>
                        <th field="sendingdate" width="110" formatter="formatSendReceipt">Sending /<br>Receipt Date</th>
                        <th field="qtys" width="100" align="right" formatter="formatNumber">Qty Sisa</th>
                        <th field="qty_keluar" width="100" align="right" formatter="formatNumber">Qty Keluar</th> 
                        <th field="tglin" width="150" formatter="formatInOut">Tgl In /<br>Keluar Terakhir</th>
                        <th field="keterangan" width="180">Keterangan</th>
                    </tr>
                </thead>
            </table>
        </x-table-default>
    </div>

    
@endsection

@section('js_custom')
    <script>
        function formatAction(value, row) {
            return `
                <a href="{{ url('/sisa-sample/detail') }}/${row.srpk}/${row.statuspk}"
                    class="action-btn" title="Lihat Detail">
                    <i class="fas fa-edit"></i>
                </a>
            `;
        }

        function formatSrFu(value, row) {
            let main = value ?? '-';
            let sub = row.funm ? `<br><span class="text-muted" style="font-size:11px;">(${row.funm})</span>` : '';
            return `${main}${sub}`;
        }

        function formatSampleOpsi(value, row) {
            let main = value ?? '-';
            let sub = row.opsi ? `<br><span class="text-muted" style="font-size:11px;">(${row.opsi})</span>` : '';
            return `${main}${sub}`;
        }

        function formatQtyWash(value, row) {
            let qty = Number(value || 0).toLocaleString('id-ID');
            let wash = row.washing_label ? ` (${row.washing_label})` : '';
            return `${qty}${wash}`;
        }

        function formatNumber(value) {
            return Number(value || 0).toLocaleString('id-ID');
        }

        function formatDate(value) {
            if (!value) return '<span class="text-muted">-</span>';
            let parts = String(value).split(' ')[0].split('-');
            if (parts.length !== 3) return value;
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }

        // BARU -- gabung Sending Date + Receipt Date jadi 1 sel (sub-baris).
        function formatSendReceipt(value, row) {
            const send = row.sendingdate ? formatDate(row.sendingdate) : '<span class="text-muted">-</span>';
            const receipt = row.receiptdate ? formatDate(row.receiptdate) : '<span class="text-muted">-</span>';
            return `<span style="font-size:11px;color:#64748b;">Send:</span> ${send}<br><span style="font-size:11px;color:#64748b;">Receipt:</span> ${receipt}`;
        }

        // BARU -- gabung TglInGdg + TglOutGdg jadi 1 sel (sub-baris).
        function formatInOut(value, row) {
            const tglin = row.tglin ? formatDate(row.tglin) : '<span class="text-muted">-</span>';
            const tglKeluar = row.tglout_terakhir ? formatDate(row.tglout_terakhir) : '<span class="text-muted">-</span>';
            return `<span style="font-size:11px;color:#64748b;">In:</span> ${tglin}<br><span style="font-size:11px;color:#64748b;">Out:</span> ${tglKeluar}`;
        }
    </script>
@endsection
@include('menu.sisa-sample.modal-tambah-sample')
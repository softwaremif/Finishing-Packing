@extends('layout.main')

@section('content')
    <div class="page-wrap">

        <x-table-default
            id="dgOrder"
            title="Daftar Data OP"
            :buyer-url="route('api.buyer-list')"
        >
            {{-- Filter tambahan khusus halaman ini, selain search/buyer/tahun default --}}
            <x-slot name="filters">
                <div class="p-0">
                    <input data-dg-filter="season" data-dg-filter-type="select"
                           data-dg-options='[{"value":"","text":"All Season"},{"value":"SS","text":"Spring/Summer"},{"value":"FW","text":"Fall/Winter"}]'
                           style="width:150px">
                </div>
            </x-slot>

            {{-- Tabel EasyUI native, sama seperti sebelumnya --}}
            <table id="dgOrder" class="easyui-datagrid" style="width:100%;height:600px"
                   url="{{ route('stuffing.list') }}" method="get" pagination="true" pageSize="50"
                   pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="true"
                   checkOnSelect="true" selectOnCheck="true" fitColumns="false" border="false">
                <thead>
                    <tr>
                        <th field="action" width="60" formatter="formatAction" align="center">Aksi</th>
                        <th field="no" width="50" align="center">No</th>
                        <th field="POno" width="150">PO No</th>
                        <th field="OP" width="150">OP</th>
                        <th field="customer" width="150">Place</th>
                        <th field="season" width="150">Season</th>
                        <th field="buyer" width="150">Buyer</th>
                        <th field="style" width="150">Style</th>
                        <th field="material" width="150">Color</th>
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
@endsection

@section('js_custom')
    <script>
        // Formatter kolom, tetap fungsi JS biasa seperti sebelumnya —
        // komponen tidak ikut campur dengan isi kolom.

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

        function formatAction(value, row, index) {
            return `
                <a href="javascript:void(0)"
                   onclick="openTransfer(${row.popk}, ${row.part})"
                   class="action-btn"
                   title="Input Transfer">
                    <i class="fas fa-edit"></i>
                </a>
            `;
        }

        // Highlight balance merah, sama seperti CSS asli — jadi tanggung
        // jawab halaman, bukan komponen, karena field-nya spesifik di sini.
        function stylerBalance(value, row, index) {
            return 'color:#dc2626;font-weight:600;';
        }

        function openTransfer(popk, part) {
            window.location.href = "{{ route('stuffing.detail', ['po' => 'popk', 'part' => 'part']) }}".replace('popk', popk).replace('part', part);
        }
   
    </script>
@endsection

{{--
    Contoh matikan filter default (misal halaman lain tanpa buyer/tahun):

    <x-table-default id="dgSimple" title="Contoh" :buyer="false" :year="false">
        <table id="dgSimple" ...>...</table>
    </x-table-default>
--}}
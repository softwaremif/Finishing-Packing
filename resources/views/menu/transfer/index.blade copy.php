@extends('layout.main')

@section('css_custom')
    <style>
        .datagrid-body td[field="qty"],
        .datagrid-body td[field="transfer"],
        .datagrid-body td[field="balance"] {
            text-align: right !important;
            font-weight: 500;
        }
    </style>
@endsection

@section('content')
    <div class="page-wrap">

        <x-table-default
            id="dgOrder"
            title="Daftar Data OP"
            search
            search-name="search"
            search-placeholder="Search..."
            buyer
            buyer-name="buyer"
            buyer-url="{{ route('api.buyer-list') }}"
            buyer-value-field="buyer"
            buyer-text-field="buyer_name"
            buyer-mode="local"
            year
            year-name="year"
        >
            <table id="dgOrder" class="easyui-datagrid" style="width:100%;height:600px"
                url="{{ route('transfer.list') }}" method="get" pagination="true" pageSize="50"
                pageList="[25,50,100,200,500]" rownumbers="false" singleSelect="true" checkOnSelect="true"
                selectOnCheck="true" fitColumns="false" border="false">
                <thead>
                    <tr>
                        <th field="action" width="60" formatter="formatAction" align="center" rowspan="2">Aksi</th>
                        <th field="no" width="50" align="center" rowspan="2">No</th>
                        <th field="linenm" width="80" rowspan="2">Line</th>
                        <th field="POno" width="150" rowspan="2">PO No</th>
                        <th field="poref" width="150" rowspan="2">License<br>PO Ref</th>
                        <th field="OP" width="150" rowspan="2" formatter="formatPOno">OP</th>
                        <th field="customer" width="150" rowspan="2">Place</th>
                        <th field="season" width="150" rowspan="2">Season</th>
                        <th field="buyer" width="150" rowspan="2">Buyer</th>
                        <th field="style" width="150" rowspan="2">Style</th>
                        <th field="material" width="150" rowspan="2">Color</th>
                        <th field="secsz" width="100" rowspan="2">Secondary<br>Size</th>
                        <th colspan="3">Pcs</th>
                        <th field="silhouette" width="190" align="left" rowspan="2">Description</th>
                    </tr>
                    <tr>
                        <th field="qty" width="120" align="right">Qty</th>
                        <th field="transfer" width="120" align="right">Transfer</th>
                        <th field="balance" width="120" align="right" formatter="formatBalance">Balance</th>
                    </tr>
                </thead>
            </table>
        </x-table-default>
    </div>
@endsection

@section('js_custom')
    <script>
        function formatAction(value, row, index) {
            return `
                <a href="javascript:void(0)"
                    onclick="openTransfer(event, ${row.popk}, ${row.mif})"
                    class="action-btn"
                    title="Input Transfer">
                    <i class="fas fa-edit"></i>
                </a>
            `;
        }

        function openTransfer(e, popk, mif) {
            if (e) {
                e.preventDefault();
                e.stopPropagation();
            }

            let url = "{{ url('/polibag/input') }}/" + popk + "?mif=" + mif;
            window.location.href = url;
        }

        window.addEventListener('pageshow', function (event) {
            if (event.persisted && window.EasyuiDG) {
                window.EasyuiDG.reload('dgOrder');
            }
        });

        const isSuperUser = @json(session('guserpk') == 34);
 
        function formatPOno(value, row) {
            if (!isSuperUser) {
                return value ?? '';
            }
        
            return `
                ${value ?? ''}
                <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:10px; margin-left:4px;">
                    mif ${row.mif}
                </span>
            `;
        }

        function formatBalance(value, row) {
            value = Number(value);
            if (value > 0) {
                return `<span style="color:#16a34a;font-weight:600;">${value.toLocaleString()}</span>`;
            }

            if (value < 0) {
                return `<span style="color:#dc2626;font-weight:600;">${value.toLocaleString()}</span>`;
            }
            return `<span style="font-weight:600;">0</span>`;
        }
    </script>
@endsection
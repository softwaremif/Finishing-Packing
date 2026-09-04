<div class="modal fade" id="sisaDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    Rincian PO <span id="sisaModalPO" class="fw-bold"></span>
                    - OP <span id="sisaModalOP" class="fw-bold"></span>
                    - Buyer <span id="sisaModalBuyer" class="fw-bold"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table id="dgSisaDetail" class="easyui-datagrid" style="width:100%;height:500px"
                    url="{{ route('sisa-produksi.detail-by-po-op') }}" method="get"
                    rownumbers="false" singleSelect="true" fitColumns="false" border="false"
                    data-options="
                        loadMsg: 'Memuat data...',
                        onLoadSuccess: onSisaDetailLoad,
                        rowStyler: rowStylerOrder
                    ">
                    <thead>
                        <tr>
                            <th field="action" width="90" formatter="formatDetailAction" align="center" rowspan="2">Aksi</th>
                            <th field="no" width="45" align="center" rowspan="2">No</th>
                            <th field="tglin" formatter="formatDate" width="95" rowspan="2">Tanggal In</th>
                            <th field="tglout" formatter="formatDate" width="95" rowspan="2">Tanggal Out</th>
                            <th field="poref" width="130" rowspan="2">License<br>PO Ref</th>
                            <th field="customer" width="130" rowspan="2">Place</th>
                            <th field="material" width="130" rowspan="2">Color</th>
                            <th field="secsz" width="90" rowspan="2">Secondary<br>Size</th>
                            <th colspan="6">Pcs</th>
                            <th field="silhouette" width="180" align="left" rowspan="2">Description</th>
                        </tr>
                        <tr>
                            <th field="qty" width="90" align="right" formatter="formatNumber">Qty</th>
                            <th field="finishing" width="100" align="right" formatter="formatNumber">Transfer To<br>Finishing</th>
                            <th field="transfer" width="90" align="right" formatter="formatNumber">Polibag</th>
                            <th field="packing" width="90" align="right" formatter="formatNumber">Packing</th>
                            <th field="balance" width="90" align="right" formatter="formatBalanceCell">Balance</th>
                            <th field="keluar" width="90" align="right" formatter="formatNumber">Keluar</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
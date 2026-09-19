<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header align-items-start">
                <div>
                    <h5 class="modal-title">
                        Rincian PO <span id="detailModalPO" class="fw-bold"></span>
                        - OP <span id="detailModalOP" class="fw-bold"></span>
                    </h5>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <table id="dgDetailModal" style="width:100%;height:350px"
                    rownumbers="false" singleSelect="true" fitColumns="false" border="false">
                    <thead>
                        <tr>
                            {{-- <th field="action" width="50" align="center" formatter="formatActionModal">Aksi</th> --}}
                            <th field="no" width="45" align="center" formatter="formatNoModal">No</th>
                            <th field="customer" width="160" align="left">Place</th>
                            <th field="material" width="150" align="left">Color</th>
                            <th field="secsz" width="100" align="left">Sec Size</th>
                            <th field="qty" width="100" align="right">Order Qty</th>
                            <th field="transfer" width="110" align="right">Sisa Digrade</th>
                            <th field="diterima" width="100" align="right">Diterima</th>
                            <th field="keluar" width="110" align="right">Sudah Keluar</th>
                            <th field="balance" width="120" align="right" formatter="formatBalanceModal">Sisa di Gudang</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<style>
    #detailModal .modal-dialog.modal-xl { max-width: min(1100px, 90vw); }
    #detailModal .datagrid-cell { padding: 10px 12px; font-size: 13px; }
    #detailModal .datagrid-header .datagrid-cell { text-align: center !important; font-weight: 600; color: #374151; }
    #detailModal .datagrid-header, #detailModal .datagrid-header-inner { background: #f9fafb !important; }
    #detailModal .datagrid-body .datagrid-cell { text-align: center; }
</style>
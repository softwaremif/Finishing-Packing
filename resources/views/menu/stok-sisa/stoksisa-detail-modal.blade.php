{{-- menu/shared/transfer-detail-modal.blade.php --}}
<div class="modal fade" id="detailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header align-items-start">
                <div>
                    <h5 class="modal-title" id="detailModalTitle">
                        Rincian PO <span id="detailModalPO" class="fw-bold"></span>
                        - OP <span id="detailModalOP" class="fw-bold"></span>
                        - Buyer <span id="detailModalBuyer" class="fw-bold"></span>
                    </h5>
                    <div class="text-muted" style="font-size:13px;">
                        Desc: <span id="detailModalDesc" class="fw-semibold text-dark"></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                {{-- GANTI -- HAPUS class="easyui-datagrid", url, dan method
                     (semuanya sekarang dikendalikan lewat JS
                     openDetailModal() di file induk). --}}
                <table id="dgDetailModal" style="width:100%;height:350px"
                    rownumbers="false" singleSelect="true" fitColumns="false" border="false">
                    <thead>
                        <tr>
                            <th field="action" width="50" align="center" formatter="formatActionModal">Aksi</th>
                            <th field="no" width="45" align="center" formatter="formatNoModal">No</th>
                            <th field="customer" width="160" align="left">Place</th>
                            <th field="poref" width="120" align="left" formatter="formatDashModal">License<br>PO Ref</th>
                            <th field="material" width="140" align="left">Color</th>
                            <th field="secsz" width="100" align="left" formatter="formatDashModal">Secondary<br>Size</th>
                            @if ($cfg['showQtyColumn'])
                                <th field="qty" width="90" align="right">Qty</th>
                            @endif
                            <th field="{{ $cfg['finishingField'] }}" width="110" align="right">Transfer To <br> Finishing</th>
                            <th field="transfer" width="100" align="right">{{ $cfg['polibagColumnLabel'] }}</th>
                            @if ($cfg['showPackingColumn'])
                                <th field="packing" width="90" align="right">Packing</th>
                            @endif
                            <th field="balance" width="100" align="right" formatter="formatBalanceModal">Balance</th>
                            @if ($cfg['showKeluarColumn'])
                                <th field="keluar" width="90" align="right">Keluar</th>
                            @endif
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
<style>
    #detailModal .modal-dialog.modal-xl { max-width: min(1200px, 90vw); }
    #detailModal .datagrid-view,
    #detailModal .datagrid-view * { box-sizing: border-box !important; }
    #detailModal .datagrid-cell { padding: 10px 12px; font-size: 13px; }
    #detailModal .datagrid-header .datagrid-cell { text-align: center !important; font-weight: 600; color: #374151; }
    #detailModal .datagrid-header,
    #detailModal .datagrid-header-inner { background: #f9fafb !important; }
    #detailModal .datagrid-view,
    #detailModal .datagrid-header,
    #detailModal .datagrid-header-inner,
    #detailModal .datagrid-header-row,
    #detailModal .datagrid-header-row td,
    #detailModal .datagrid-body,
    #detailModal .datagrid-row,
    #detailModal .datagrid-body td,
    #detailModal .datagrid-htable td,
    #detailModal .datagrid-btable td { border: none !important; }
    #detailModal .datagrid-body .datagrid-cell { text-align: center; }
</style>
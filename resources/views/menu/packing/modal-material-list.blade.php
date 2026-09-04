<div class="modal fade" id="packingDetailModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header align-items-start">
                <div>
                    <h5 class="modal-title" id="detailModalTitle">
                        Rincian PO <span id="packingModalPO" class="fw-bold"></span>
                        - OP <span id="packingModalOP" class="fw-bold"></span>
                        - Buyer <span id="packingModalBuyer" class="fw-bold"></span>
                    </h5>
                    <div class="text-muted" style="font-size:13px;">
                        Desc: <span id="packingModalDesc" class="fw-semibold text-dark"></span>
                    </div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body p-0">

                {{-- Sticky bar: SAMA seperti #stickTopBar versi lama, tapi
                    scoped ke dalam modal-body (position:sticky, bukan
                    fixed) -- cuma muncul kalau ada baris dipilih. --}}
                <div id="packingModalStickyBar" class="d-none"
                    style="position: sticky; top: 0; z-index: 5; background: #359DD9; color: #fff;">
                    <div class="d-flex justify-content-between align-items-center px-3" style="height: 44px;">
                        <div>
                            <strong><span id="packingModalSelectedCount">0</span> item terpilih</strong>
                        </div>
                        <div>
                            @if ($status)
                                <span class="sticky-action d-none" id="btnModalSegelPacking">Shipment CTN</span>
                                {{-- <span class="sticky-action" id="btnModalInputStatusPacking">Input Status Packing</span> --}}
                            @endif
                            <span class="sticky-action" onclick="closePackingModalSelection()">Close</span>
                        </div>
                    </div>
                </div>

                <div class="p-3">
                    <table
                        id="dgPackingDetail"
                        class="easyui-datagrid"
                        style="width:100%;height:500px"
                        url="{{ route('packing.detail-by-po-op') }}"
                        {{-- url="{{ route('getListPack.detail-by-po-op') }}" --}}
                        method="get"
                        rownumbers="false"
                        singleSelect="false"
                        checkOnSelect="true"
                        selectOnCheck="true"
                        fitColumns="false"
                        border="false"
                            data-options="
                                loadMsg: 'Memuat data...',
                                onLoadSuccess: onPackingDetailLoad,
                                onCheck: validatePackingDetailCheck,
                                onUncheck: updatePackingDetailSelection,
                                onCheckAll: validatePackingDetailCheckAll,
                                onUncheckAll: updatePackingDetailSelection
                            "
                        >
                        <thead>
                            <tr>
                                @if (session('guserpk') === 34)
                                    <th field="action" width="70" formatter="formatPackingDetailAction" align="center" rowspan="2">Aksi</th>
                                @endif
                                @if ($status)
                                    <th field="segel_status" width="120" align="center" formatter="formatSegelStatus" rowspan="2">Status<br>Shipment CTN</th>
                                @endif
                                @if($check ?? false)
                                    <th field="ck" checkbox="true" rowspan="2"></th>
                                @endif
                                <th field="no" width="45" align="center" rowspan="2">No</th>
                                <th field="linenm" width="70" rowspan="2">Line</th>
                                @if ($status)
                                    <th colspan="2" align="center">Shipdate</th>
                                @endif
                                <th field="customer" width="130" rowspan="2">Place</th>
                                <th field="material" width="130" rowspan="2">Color</th>
                                <th field="secsz" width="80" rowspan="2">Sec Size</th>
                                <th field="qty" width="90" align="right" rowspan="2" formatter="formatNumber">Qty</th>
                                <th colspan="3" align="center">Packing /Pcs</th>
                                <th colspan="3" align="center">CTN</th>
                                {{-- <th field="silhouette" width="200" rowspan="2">Description</th> --}}
                            </tr>
                            <tr>
                                @if ($status)
                                    <th field="shipdate1" width="90" align="right" formatter="formatDate">Plan</th>
                                    <th field="shipdate2" width="90" align="right" formatter="formatDate">Actual</th>
                                @endif
                                <th field="packing_qty_plan" width="90" align="right" formatter="formatNumber">Plan</th>
                                <th field="packing_qty" width="90" align="right" formatter="formatNumber">Actual</th>
                                <th field="packing_qty_balance" width="90" align="right" formatter="formatBalanceCell">Balance</th>
                                <th field="ctn" width="80" align="center">Plan</th>
                                <th field="packing_ctn" width="80" align="center">Actual</th>
                                <th field="ctn_balance" width="80" align="center" formatter="formatBalanceCell">Balance</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
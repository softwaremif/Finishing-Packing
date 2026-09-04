<div class="modal fade" id="detailOutsisaModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg" style="border-radius:12px;">
            <div class="modal-header py-3 border-0">
                <h5 class="modal-title fw-bold" style="font-size:15px;">
                    Detail Keluar <span id="detailOutNo" class="text-primary"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body pt-0">
                <div class="mb-3">
                    <div style="font-size:12.5px; color:#64748b;">Tanggal Keluar: <strong id="detailOutDate"></strong></div>
                    <div style="font-size:12.5px; color:#64748b;">Penerima: <span id="detailOutPenerima">-</span></div>
                    <div style="font-size:12.5px; color:#64748b;">Keterangan: <span id="detailOutKeterangan">-</span></div>
                </div>

                <div class="d-flex align-items-center gap-2 mb-3" id="detailOutApprovalSteps"></div>

                <div class="table-responsive mb-3">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light" style="font-size:11px; text-transform:uppercase;">
                            <tr>
                                <th>Grade</th>
                                <th>PO No / OP</th>
                                <th>Color</th>
                                <th>Size</th>
                                <th class="text-end">Qty Keluar</th>
                                <th width="40"></th>
                            </tr>
                        </thead>
                        <tbody id="detailOutItemsBody"></tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer border-0 pt-0" id="detailOutFooter"></div>
        </div>
    </div>
</div>
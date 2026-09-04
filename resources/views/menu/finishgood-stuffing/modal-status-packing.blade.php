<div class="modal fade" id="statusPackingModal" tabindex="-1" aria-labelledby="statusPackingModalTitle" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <form id="frmStatusPacking" method="POST" action="{{ route('packing.update-gabung') }}">
                @csrf
                <input type="hidden" name="gabung" id="gabungInput">
                <div id="popkContainer"></div>

                {{-- HEADER --}}
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold text-dark" id="statusPackingModalTitle" style="font-size: 16px;">
                        Input Status Packing
                    </h5>
                    <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>

                {{-- BODY --}}
                <div class="modal-body pb-2">

                    <div class="p-3 mb-3 bg-white rounded-3 border style-keterangan-box" style="border-color: #e2e8f0 !important;">
                        <div class="fw-bold text-secondary mb-2.5 small text-uppercase" style="letter-spacing: 0.5px; font-size: 10.5px;">
                            <i class="fas fa-info-circle text-muted me-1"></i> Keterangan Kode Status Packing
                        </div>
                        
                        <div class="style-status-columns">
                            @foreach($gabung as $item)
                                <div class="status-item-loop">
                                    <strong class="text-dark bg-light px-1.5 py-0.5 rounded border border-light" style="font-size: 11.5px;">{{ $item->gabungpk }}</strong> 
                                    <span class="text-muted ms-1">→ {{ $item->keterangan }}</span>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <div class="row g-3 mb-3 align-items-end">
                        <div class="col-12 col-sm-5">
                            <label class="form-label-custom mb-1">
                                <i class="fas fa-layer-group text-secondary me-1" style="width: 14px;"></i> OP
                            </label>
                            <input type="text" id="infoOP" class="form-control border-0 bg-transparent text-dark fw-bold px-0 py-1" readonly style="cursor: not-allowed; box-shadow: none;">
                        </div>

                        <div class="col-12 col-sm-7">
                            <label class="form-label-custom">
                                <i class="fas fa-tags text-secondary me-1" style="width: 14px;"></i> Status Packing
                            </label>
                            <select id="cmbGabung" class="form-select form-control-modern" required>
                                <option value="">-- Select Status --</option>
                                @foreach($gabung as $item)
                                    <option value="{{ $item->gabungpk }}">{{ $item->gabungpk }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="table-responsive border rounded bg-white mb-3 style-modal-scrollbar" style="max-height: 220px; border-color: #e2e8f0 !important;">
                        <table class="table table-sm table-hover text-center align-middle mb-0" style="font-size: 12.5px; min-width: 750px;">
                            <thead class="table-light text-secondary sticky-top" style="font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px;">
                                <tr>
                                    <th width="50" class="py-2">No</th>
                                    <th class="py-2">PO No</th>
                                    <th class="py-2">Status<br>Packing</th>
                                    <th class="py-2">License<br>PO Ref</th>
                                    <th class="py-2">Place</th>
                                    <th class="py-2">Season</th>
                                    <th class="py-2">Buyer</th>
                                    <th class="py-2">Style</th>
                                    <th class="py-2">Color</th>
                                </tr>
                            </thead>
                            <tbody id="statusPackingBody" class="text-dark">
                                </tbody>
                        </table>
                    </div>

                </div>

                {{-- FOOTER --}}
                <div class="modal-footer border-0 pt-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal" style="font-size: 13px; border-radius: 6px; height: 33px;">
                        Cancel
                    </button>
                    <button type="button" class="btn btn-sm btn-dark px-4 d-inline-flex align-items-center gap-2" onclick="saveStatusPacking()" style="font-size: 13px; border-radius: 6px; height: 33px; background-color: #1e293b; border-color: #1e293b;">
                        <i class="fas fa-save small"></i> Save
                    </button>
                </div>

            </form>

        </div>
    </div>
</div>

<style>
    .gap-1.5 { gap: 0.35rem !important; }
    .px-1.5 { padding-left: 0.35rem !important; padding-right: 0.35rem !important; }

    /* KONTROL COLUMN CSS (Urut ke bawah dulu, maksimal 3 kolom jika layar lebar) */
    .style-status-columns {
        display: block;
        columns: 3 160px; /* Maksimal 3 kolom, otomatis menyesuaikan ke 2/1 kolom di layar HP kecil */
        column-gap: 20px;
    }

    .status-item-loop {
        display: inline-block;
        width: 100%; /* Mencegah item teks terpotong patah di tengah baris */
        margin-bottom: 8px;
        font-size: 12.5px;
    }

    .style-keterangan-box {
        background-color: #ffffff;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }

    .form-label-custom {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        font-weight: 600;
        color: #475569;
        margin-bottom: 6px;
        display: block;
    }

    /* Efek Input Modern */
    .form-control-modern {
        border-color: #cbd5e1;
        border-radius: 6px;
        font-size: 13.5px;
        color: #1e293b;
        padding: 5px 12px;
        height: 36px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02) inset;
    }

    .form-control-modern:focus {
        border-color: #64748b !important;
        box-shadow: 0 0 0 3px rgba(100, 116, 139, 0.15) !important;
        outline: 0;
    }

    /* Scrollbar halus internal modal */
    .style-modal-scrollbar::-webkit-scrollbar {
        width: 4px;
        height: 5px;
    }
    .style-modal-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
</style>
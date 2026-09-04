{{-- menu.packing.partials.header_info_global --}}
{{-- Dipakai sebagai @include awal DAN sebagai response AJAX
     reloadHeaderInfoGlobal() -- variabel yang dipakai: $dt2, $poNoList,
     $colorList, $allPopks. --}}
<script>
    // BARU: refresh window.pgAllPopks setiap partial ini di-(re)load --
    // jQuery .html() ikut MENGEKSEKUSI <script> yang disisipkan, jadi
    // ini otomatis jalan lagi tiap kali reloadHeaderInfoGlobal() dipanggil.
    window.pgAllPopks = @json($allPopks ?? []);
</script>
<div class="card shadow-sm border-0 mb-3 rounded-3 bg-white" id="poDetailCard">
    <div class="card-header bg-white py-3 border-bottom-0 d-flex align-items-center justify-content-between">
        <div class="fw-bold text-dark d-flex align-items-center" style="font-size: 15px;">
            <span class="rounded me-2"
                style="width: 4px; height: 16px; display: inline-block; background: #475569;"></span>
            Informasi Detail PO
        </div>
        <button type="button"
            class="btn btn-dark btn-sm d-inline-flex align-items-center px-2.5 py-1.5 fw-semibold"
            style="font-size: 12px; border-radius: 6px; background-color: #1e293b; border-color: #1e293b;"
            onclick="openEditPackingModal()">
            <i class="fas fa-edit me-2 small"></i> Edit Info PO
        </button>
    </div>
    <div class="card-body px-4 pb-4 pt-1">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4 mb-4">
            <div class="col">
                <div class="d-flex align-items-start gap-2">
                    <div class="bg-info-subtle text-info rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0;">
                        <i class="fas fa-layer-group fs-6" style="font-size: 13px;"></i>
                    </div>
                    <div>
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">OP</div>
                        <div class="fw-bold" style="font-size: 14px;" id="view_OP">{{ $dt2->OP ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-start gap-2">
                    <div class="rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0; background-color: #f1f5f9;">
                        <i class="fas fa-certificate text-dark fs-6"></i>
                    </div>
                    <div class="overflow-hidden">
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">License PO Ref</div>
                        <div class="text-dark fw-semibold text-truncate" style="font-size: 14px;">
                            {{ $dt2->poref ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-start gap-2">
                    <div class="bg-danger-subtle text-danger rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0;">
                        <i class="fas fa-map-marker-alt fs-6"></i>
                    </div>
                    <div class="overflow-hidden">
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">Place</div>
                        <div class="text-dark fw-semibold text-truncate" style="font-size: 14px;"
                            title="{{ $dt2->customer ?? '-' }}">{{ $dt2->customer ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-start gap-2">
                    <div class="bg-warning-subtle text-warning rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0;">
                        <i class="fas fa-calendar-alt fs-6"></i>
                    </div>
                    <div>
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">Season</div>
                        <div class="text-dark fw-semibold">{{ $dt2->season ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-start gap-2">
                    <div class="bg-success-subtle text-success rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0;">
                        <i class="fas fa-user-tie fs-6"></i>
                    </div>
                    <div class="overflow-hidden">
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">Buyer</div>
                        <div class="text-dark fw-semibold text-truncate" style="font-size: 14px;"
                            title="{{ $dt2->buyer ?? '-' }}">{{ $dt2->buyer ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-start gap-2">
                    <div class="bg-secondary-subtle text-secondary rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0;">
                        <i class="fas fa-tshirt fs-6"></i>
                    </div>
                    <div class="overflow-hidden">
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">Style Code</div>
                        <div class="text-dark fw-semibold text-truncate" style="font-size: 14px;">
                            {{ $dt2->style ?? '-' }}</div>
                    </div>
                </div>
            </div>
            {{-- Color / Material -- LIST kalau lebih dari 1 --}}
            <div class="col">
                <div class="d-flex align-items-start gap-2">
                    <div class="rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0; background-color: #f1f5f9;">
                        <i class="fas fa-palette text-dark fs-6"></i>
                    </div>
                    <div class="overflow-hidden">
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">
                            Color / Material
                            @if (($colorList ?? collect())->count() > 1)
                                <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:9px;">{{ $colorList->count() }}</span>
                            @endif
                        </div>
                        <div class="text-dark fw-semibold" style="font-size: 14px; line-height:1.5;">
                            @if (($colorList ?? collect())->count() > 1)
                                @foreach ($colorList as $c)
                                    <div class="text-truncate">{{ $c }}</div>
                                @endforeach
                            @else
                                {{ ($colorList ?? collect())->first() ?? ($dt2->material ?? '-') }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-start gap-2">
                    <div class="rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0; background-color: #f1f5f9;">
                        <i class="fas fa-align-left text-dark fs-6"></i>
                    </div>
                    <div>
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">Description</div>
                        <div class="text-muted fw-normal"
                            style="font-size: 13px; line-height: 1.4; word-break: break-word;">
                            {{ $dt2->silhouette ?? '-' }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <hr class="my-4" style="border-color: #f1f5f9; border-width: 2px;">
        <div class="row row-cols-1 row-cols-sm-2 row-cols-md-4 g-4">
            {{-- PO Number -- LIST kalau lebih dari 1 --}}
            <div class="col">
                <div class="d-flex align-items-start gap-2">
                    <div class="bg-primary-subtle text-primary rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0;">
                        <i class="fas fa-hashtag fs-6"></i>
                    </div>
                    <div class="overflow-hidden">
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">
                            PO Number
                            @if (($poNoList ?? collect())->count() > 1)
                                <span class="badge bg-secondary-subtle text-secondary-emphasis" style="font-size:9px;">{{ $poNoList->count() }}</span>
                            @endif
                        </div>
                        <div class="fw-bold text-dark" style="font-size: 14px; line-height:1.5;" id="view_POno">
                            @if (($poNoList ?? collect())->count() > 1)
                                @foreach ($poNoList as $p)
                                    <div class="text-truncate">{{ $p }}</div>
                                @endforeach
                            @else
                                {{ ($poNoList ?? collect())->first() ?? ($dt2->POno ?? '-') }}
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-start gap-2">
                    <div class="bg-secondary-subtle text-secondary rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0;">
                        <i class="far fa-calendar-alt fs-6"></i>
                    </div>
                    <div>
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">Shipdate Plan</div>
                        <div class="fw-semibold text-secondary" style="font-size: 14px;" id="view_shipdate1">
                            {{ $dt2->shipdate1 ? date('d M Y', strtotime($dt2->shipdate1)) : '-' }}
                        </div>
                    </div>
                </div>
            </div>
            {{-- <div class="col">
                <div class="d-flex align-items-start gap-2">
                    <div class="bg-success-subtle text-success rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0;">
                        <i class="fas fa-calendar-check fs-6"></i>
                    </div>
                    <div>
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">Shipdate Actual</div>
                        <div class="fw-semibold" style="font-size: 14px;" id="view_shipdate2">
                            {{ $dt2->shipdate2 ? date('d M Y', strtotime($dt2->shipdate2)) : '-' }}
                        </div>
                    </div>
                </div>
            </div> --}}
            <div class="col">
                <div class="d-flex align-items-start gap-2">
                    <div class="bg-primary-subtle text-primary rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0;">
                        <i class="fas fa-id-card fs-6"></i>
                    </div>
                    <div>
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">SAP ID</div>
                        <div class="fw-bold text-dark" style="font-size: 14px;" id="view_sap1">{{ $dt2->sap1 ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-start gap-2">
                    <div class="bg-primary-subtle text-primary rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0;">
                        <i class="fas fa-file-signature fs-6"></i>
                    </div>
                    <div>
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">SAP No</div>
                        <div class="fw-bold text-dark" style="font-size: 14px;" id="view_sap2">{{ $dt2->sap2 ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="d-flex align-items-start gap-2">
                    <div class="bg-info-subtle text-info rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0;">
                        <i class="fas fa-warehouse fs-6"></i>
                    </div>
                    <div>
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">Warehouse</div>
                        <div class="fw-semibold text-dark" style="font-size: 14px;" id="view_wh">{{ $dt2->wh ?? '-' }}</div>
                    </div>
                </div>
            </div>
            <div class="col-md-6" style="grid-column: span 2;">
                <div class="d-flex align-items-start gap-2">
                    <div class="rounded-2 d-flex align-items-center justify-content-center"
                        style="width: 36px; height: 36px; flex-shrink: 0; background-color: #f1f5f9;">
                        <i class="fas fa-comment-alt text-dark fs-6"></i>
                    </div>
                    <div>
                        <div class="text-secondary text-uppercase fw-semibold mb-0.5"
                            style="letter-spacing: 0.5px; font-size: 10px;">Keterangan</div>
                        <div class="text-secondary" style="font-size: 13px; line-height: 1.4;" id="view_ket">
                            {{ $dt2->ket ?? '-' }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
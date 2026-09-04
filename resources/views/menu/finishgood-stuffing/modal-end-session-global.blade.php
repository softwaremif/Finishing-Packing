{{-- MODAL BARU: konfirmasi End Session -- SAMA gaya dengan
     modal-segel-ctn-global/modal-shipment-ctn-global (icon + info text +
     footer konfirmasi), warna disesuaikan (merah = danger, karena
     tindakan permanen/lock). --}}

<div class="modal fade" id="endSessionModalGlobal" tabindex="-1" aria-labelledby="endSessionModalGlobalTitle"
    aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark d-flex align-items-center gap-2" id="endSessionModalGlobalTitle" style="font-size:16px;">
                    <i class="fas fa-lock" style="color:#dc2626;"></i>
                    <span id="endSessionModalTitleText">End Session</span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-2">
                <div id="endSessionInfoText" class="text-muted mb-2" style="font-size:12.5px; line-height:1.5;">
                    <i class="fas fa-info-circle me-0.5"></i>
                    <span id="endSessionInfoTextContent"></span>
                </div>

                <div id="endSessionWarningBox" class="d-none" style="font-size:12px; background:#fef3c7; border:1px solid #fde68a; border-radius:8px; padding:10px 12px; margin-bottom:8px; color:#92400e;">
                    <i class="fas fa-triangle-exclamation me-1"></i>
                    <span id="endSessionWarningText"></span>
                </div>

                <div style="font-size:12.5px; color:#475569;">
                    <i class="fas fa-lock-open me-0.5" style="color:#94a3b8;"></i>
                    Semua carton di session ini akan dikunci permanen dan tidak bisa diproses ulang.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal"
                    style="font-size:13px; border-radius:6px; height:33px;">
                    Batal
                </button>
                <button type="button" id="btnConfirmEndSession" class="btn btn-sm btn-danger px-4 d-inline-flex align-items-center gap-1"
                    style="font-size:13px; border-radius:6px; height:33px;"
                    onclick="confirmEndStuffingSession()">
                    <i class="fas fa-lock small"></i>
                    <span id="btnConfirmEndSessionText">Tutup Session</span>
                </button>
            </div>
        </div>
    </div>
</div>


<script>
    let endSessionTarget = { part: null, total: 0, shipped: 0 };

    // GANTI SELURUH endStuffingSession() -- SEBELUMNYA pakai confirm()
    // browser polos. SEKARANG buka modal dulu, eksekusi sebenarnya
    // dipindah ke confirmEndStuffingSession().
    function endStuffingSession(part, total, shipped) {
        const remaining = total - shipped;

        if (remaining > 0 && !window.isSupervisorStuffing) {
            showToast('warning', `Belum bisa End Session - masih ada ${remaining} carton yang belum masuk. Hubungi supervisor kalau perlu ditutup paksa.`);
            return;
        }

        endSessionTarget = { part, total, shipped };

        $('#endSessionModalTitleText').text(`End Session - ${part}`);

        if (remaining > 0) {
            $('#endSessionInfoTextContent').html(
                `Session <strong>Part ${part}</strong> masih ada <strong class="text-dark">${remaining}</strong> dari ${total} carton yang <strong>belum masuk</strong>.`
            );
            $('#endSessionWarningBox').removeClass('d-none');
            $('#endSessionWarningText').text(
                'Sebagai supervisor, carton yang belum masuk akan otomatis di-mark masuk terlebih dahulu, lalu SEMUA carton di session ini dikunci.'
            );
            $('#btnConfirmEndSessionText').text('Tutup Paksa & Kunci');
        } else {
            $('#endSessionInfoTextContent').html(
                `Semua <strong class="text-dark">${total}</strong> carton di session <strong> ${part}</strong> sudah masuk.`
            );
            $('#endSessionWarningBox').addClass('d-none');
            $('#btnConfirmEndSessionText').text('Tutup Session');
        }

        bootstrap.Modal.getOrCreateInstance(document.getElementById('endSessionModalGlobal')).show();
    }

    // Eksekusi sebenarnya -- dipanggil dari tombol konfirmasi di modal.
    function confirmEndStuffingSession() {
        const { part, total, shipped } = endSessionTarget;
        if (!part) return;

        $('#btnConfirmEndSession').prop('disabled', true);

        $.get("{{ route('finish-good-stuffing.list.detail.global') }}", {
            po: @json($po), op: @json($op), poref: @json($poref ?? null), mif: @json($mif),
            part: part, rows: 9999
        }, function (data) {
            const rows = data.rows || [];
            const allPackpks = rows.map(r => r.packpk);
            const remainingPackpks = rows.filter(r => r.ship_shipped !== true).map(r => r.packpk);

            if (!allPackpks.length) {
                setActiveSessionPart(null);
                loadShipmentPlanCards();
                bootstrap.Modal.getInstance(document.getElementById('endSessionModalGlobal')).hide();
                $('#btnConfirmEndSession').prop('disabled', false);
                return;
            }

            const promoteRemainingFirst = remainingPackpks.length > 0
                ? $.ajax({
                    url: "{{ route('finish-good-stuffing.bulk-ship-action') }}",
                    method: 'POST',
                    data: { packpk: remainingPackpks.join(','), action: 'shipment', mif: @json($mif) }
                })
                : $.Deferred().resolve().promise();

            promoteRemainingFirst.always(function () {
                $.ajax({
                    url: "{{ route('finish-good-stuffing.bulk-ship-action') }}",
                    method: 'POST',
                    data: { packpk: allPackpks.join(','), action: 'lock', mif: @json($mif) },
                    success: function (res) {
                        showToast(res.icon, `Session Part ${part} ditutup -- ${allPackpks.length} carton dikunci.`);
                        setActiveSessionPart(null);
                        loadPackingCards();
                        loadShipmentPlanCards();
                        bootstrap.Modal.getInstance(document.getElementById('endSessionModalGlobal')).hide();
                    },
                    error: function (xhr) {
                        const res = xhr.responseJSON || { title: 'Gagal menutup session.' };
                        showToast('error', res.title);
                    },
                    complete: function () {
                        $('#btnConfirmEndSession').prop('disabled', false);
                    }
                });
            });
        });
    }
</script>
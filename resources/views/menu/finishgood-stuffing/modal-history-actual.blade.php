<div class="modal fade" id="historyActualModal" tabindex="-1" aria-labelledby="historyActualModalTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <div class="modal-header border-0 pb-0">
                <h5 class="modal-title fw-bold text-dark" id="historyActualModalTitle" style="font-size:16px;">
                    Histori Input Actual -- Carton <span id="historyActualCartonNo" class="text-primary"></span>
                </h5>
                <button type="button" class="btn-close shadow-none" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pb-2">
                <div class="table-responsive border rounded bg-white" style="max-height: 400px;">
                    <table class="table table-sm table-hover align-middle mb-0" style="font-size:12.5px;">
                        <thead class="table-light text-secondary sticky-top"
                            style="font-size:11px; text-transform:uppercase; letter-spacing:0.3px;">
                            <tr>
                                <th width="40" class="py-2 text-center">No</th>
                                <th class="py-2 text-center" style="min-width:150px;">Tanggal Input</th>
                                <th class="py-2 text-start" style="min-width:220px;">Rincian Penambahan (per Size)</th>
                                <th class="py-2 text-end" style="min-width:80px;">Total Pcs</th>
                            </tr>
                        </thead>
                        <tbody id="historyActualList" class="text-dark"></tbody>
                    </table>
                </div>
                <div id="historyActualEmpty" class="text-center text-muted py-4 d-none" style="font-size:13px;">
                    <i class="fas fa-inbox me-1"></i> Belum ada histori input Actual untuk carton ini.
                </div>
            </div>
            <div class="modal-footer border-0 pt-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal"
                    style="font-size:13px; border-radius:6px; height:33px;">
                    Tutup
                </button>
            </div>
        </div>
    </div>
</div>

<script>
    // ============================================================
    // openHistoryModal(packpk, carton) -- ambil histori actpack lewat
    // AJAX, render sebagai daftar "Size: +N" per baris, urut terbaru
    // di atas (sudah di-order backend).
    // ============================================================
    function openHistoryModal(packpk, carton) {
        $('#historyActualCartonNo').text(carton ?? '');
        $('#historyActualList').html('<tr><td colspan="4" class="text-center text-muted py-3">Memuat...</td></tr>');
        $('#historyActualEmpty').addClass('d-none');

        bootstrap.Modal.getOrCreateInstance(document.getElementById('historyActualModal')).show();

        $.get("{{ url('packing/history') }}/" + packpk, function (data) {
            const rows = data.rows || [];

            if (!rows.length) {
                $('#historyActualList').empty();
                $('#historyActualEmpty').removeClass('d-none');
                return;
            }

            let html = '';
            rows.forEach(function (row, index) {
                const parts = [];
                for (let i = 1; i <= 25; i++) {
                    const val = Number(row[`qty${i}`] || 0);
                    if (val === 0) continue;
                    const namaSize = (window.activeSizes && window.activeSizes[i]) ? window.activeSizes[i] : `Size ${i}`;
                    const isNeg = val < 0;
                    const badgeClass = isNeg ? 'bg-danger-subtle text-danger border-danger-subtle' : 'bg-light text-dark border';
                    const sign = isNeg ? '' : '+';
                    parts.push(`<span class="badge ${badgeClass} me-1 mb-1">${namaSize}: ${sign}${val}</span>`);
                }

                const tgl = row.tglinput ? new Date(row.tglinput.replace(' ', 'T')) : null;
                const tglLabel = tgl && !isNaN(tgl)
                    ? tgl.toLocaleString('id-ID', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
                    : (row.tglinput ?? '-');

                const totalPcs = Number(row.pcs ?? 0);
                const totalIsNeg = totalPcs < 0;
                const totalClass = totalIsNeg ? 'text-danger' : 'text-success';
                const totalSign = totalIsNeg ? '' : '+';

                html += `
                    <tr>
                        <td class="text-center">${index + 1}</td>
                        <td class="text-center">${tglLabel}</td>
                        <td class="text-start">${parts.length ? parts.join('') : '<span class="text-muted">-</span>'}</td>
                        <td class="text-end fw-bold ${totalClass}">${totalSign}${totalPcs}</td>
                    </tr>
                `;
            });

            $('#historyActualList').html(html);
        }).fail(function () {
            $('#historyActualList').html('<tr><td colspan="4" class="text-center text-danger py-3">Gagal memuat histori.</td></tr>');
        });
    }
</script>
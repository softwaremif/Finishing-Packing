<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Approval {{ $docNo }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    @include('layout.css_global')
    <style>
        body { background: #f8fafc; }
        .grade-badge {
            width: 28px; height: 28px; border-radius: 6px;
            display: inline-flex; align-items: center; justify-content: center;
            font-weight: 800; font-size: 12px; color: #fff;
        }
        .grade-badge.grade-a { background: #16a34a; }
        .grade-badge.grade-b { background: #2563eb; }
        .grade-badge.grade-c { background: #d97706; }
        .item-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
        .item-table thead th {
            text-align: left; padding: 8px 10px; border-bottom: 2px solid #e2e8f0;
            background: #f8fafc; font-size: 11px; text-transform: uppercase;
            letter-spacing: .3px; color: #64748b; font-weight: 700;
        }
        .item-table tbody td { padding: 8px 10px; vertical-align: top; }
        .item-table tr.item-main-row td { border-bottom: none; }
        .item-table tr.item-size-row td {
            padding: 0 10px 10px 10px; background: #fafbfc; border-bottom: 1px solid #f1f5f9;
        }
        .item-table tfoot td { padding: 10px; font-weight: 700; border-top: 2px solid #e2e8f0; }
        .size-chip-row { display: flex; flex-wrap: wrap; gap: 6px; }
        .size-chip {
            background: #fff; border: 1px solid #eef1f5; border-radius: 6px;
            padding: 4px 8px; text-align: center; min-width: 42px;
        }
        .size-chip .sc-label { font-size: 9px; color: #94a3b8; font-weight: 700; text-transform: uppercase; }
        .size-chip .sc-value { font-size: 12.5px; font-weight: 800; color: #0f172a; }
        .action-btn {
            height: 52px; border-radius: 12px; font-size: 14px; font-weight: 700;
            transition: all .2s ease;
        }
        .btn-dark.action-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(0,0,0,.15); }
        .btn-outline-danger.action-btn:hover { transform: translateY(-1px); box-shadow: 0 4px 12px rgba(220,53,69,.15); }
        @media (max-width:576px) {
            .action-btn { height: 48px; font-size: 13px; }
        }
    </style>
</head>
<body>
    <div class="container py-5" style="max-width:640px;">
        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-4">
                <div class="text-center mb-3">
                    <i class="fas fa-file-signature fa-2x text-primary"></i>
                    <h5 class="fw-bold mt-2 mb-0">Konfirmasi Approval</h5>
                    <div class="text-muted" style="font-size:13px;">No Dokumen: <strong>{{ $docNo }}</strong></div>
                </div>
                @if(!empty($srno) || !empty($sampleStatus) || !empty($style))
                    <div class="text-center mb-3" style="font-size:12.5px; color:#64748b;">
                        @if(!empty($srno))
                            <div>SR#: <strong>{{ $srno }}</strong></div>
                        @endif
                        @if(!empty($sampleStatus))
                            <div>Sample Status: <strong>{{ $sampleStatus }}</strong></div>
                        @endif
                        @if(!empty($style))
                            <div>Style: <strong>{{ $style }}</strong></div>
                        @endif
                    </div>
                @endif

                @if($keterangan)
                    <div class="alert alert-light border" style="font-size:13px;">
                        Keterangan: {{ $keterangan }}
                    </div>
                @endif

                @if(!empty($penerima))
                    <div class="text-muted mb-2" style="font-size:13px;">Penerima: <strong>{{ $penerima }}</strong></div>
                @endif

                <div class="table-responsive mb-3">
                    <table class="item-table">
                        <thead>
                            <tr>
                                <th>Grade</th>
                                <th>PO No / OP</th>
                                <th>Color</th>
                                <th class="text-end">Pcs</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($items as $item)
                                @php
                                    $gradeCls = strtoupper($item['grade']) === 'A' ? 'grade-a' : (strtoupper($item['grade']) === 'B' ? 'grade-b' : 'grade-c');
                                @endphp
                                <tr class="item-main-row">
                                    <td><span class="grade-badge {{ $gradeCls }}">{{ $item['grade'] }}</span></td>
                                    <td>{{ $item['POno'] }} / OP {{ $item['OP'] }}</td>
                                    <td>{{ $item['color'] }} {{ !empty($item['secsz']) ? '('.$item['secsz'].')' : '' }}</td>
                                    <td class="text-end fw-semibold">{{ $item['pcs'] }}</td>
                                </tr>
                                <tr class="item-size-row">
                                    <td colspan="4">
                                        <div class="size-chip-row">
                                            @forelse($item['sizes'] as $s)
                                                <div class="size-chip">
                                                    <div class="sc-label">{{ $s['label'] }}</div>
                                                    <div class="sc-value">{{ $s['qty'] }}</div>
                                                </div>
                                            @empty
                                                <span class="text-muted" style="font-size:11px;">Tidak ada breakdown size</span>
                                            @endforelse
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" class="text-end">Total Pcs</td>
                                <td class="text-end">{{ $items->sum('pcs') }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                @if($alreadyDone)
                    <div class="alert alert-secondary text-center" style="font-size:13.5px;">
                        Untuk dokumen ini sudah pernah diproses sebelumnya. Tidak ada aksi lagi yang perlu dilakukan.
                    </div>
                @else
                    <p class="text-center mb-4" style="font-size:14px;">
                        Anda akan memutuskan dokumen ini sebagai
                        <strong>{{ $levelLabel }}</strong>.
                    </p>
                    <div class="row g-2">
                        <div class="col-6">
                            <button type="button"
                                class="btn btn-outline-danger w-100 fw-semibold action-btn"
                                data-bs-toggle="modal"
                                data-bs-target="#rejectModal">
                                <i class="fas fa-times me-1"></i> Reject
                            </button>
                        </div>
                        <div class="col-6">
                            <form method="POST" action="{{ $doApproveUrl }}">
                                @csrf
                                <button type="submit" class="btn btn-dark w-100 fw-semibold action-btn">
                                    <i class="fas fa-check me-1"></i> Approve
                                </button>
                            </form>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Modal Reject --}}
    <div class="modal fade" id="rejectModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <form method="POST" action="{{ $doRejectUrl }}">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="fas fa-exclamation-triangle text-danger me-2"></i>
                            Konfirmasi Penolakan
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <p class="mb-1" style="font-size:14px;">
                            Yakin ingin menolak dokumen <strong>{{ $docNo }}</strong> sebagai
                            <strong>{{ $levelLabel }}</strong>?
                        </p>
                        <small class="text-muted">Aksi ini tidak dapat dibatalkan setelah dikirim.</small>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                            Batal
                        </button>
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-1"></i>
                            Ya, Tolak
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @include('layout.js_global')
</body>
</html>
<!DOCTYPE html>
<html>
<body style="font-family: Arial, Helvetica, sans-serif; color: #1f2937; margin:0; padding:0; background:#f3f4f6;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f3f4f6; padding:30px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="background:#ffffff; border-radius:10px; overflow:hidden; border:1px solid #e5e7eb;">

                    {{-- HEADER --}}
                    <tr>
                        <td style="background:#1e293b; padding:22px 32px;">
                            <div style="font-size:17px; font-weight:700; color:#ffffff;">
                                {{ $docTitle }} | Needing Your Approval
                            </div>
                        </td>
                    </tr>

                    {{-- BODY --}}
                    <tr>
                        <td style="padding:28px 32px 8px 32px;">
                            <p style="font-size:14.5px; margin:0 0 4px 0;">Hello, <strong>{{ $approverName }}</strong></p>
                            <p style="font-size:13.5px; color:#4b5563; margin:0 0 20px 0;">
                                Please review the following {{ $docTitle }} details that require your approval as <strong>{{ $levelLabel }}</strong>.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="font-size:13.5px; margin-bottom:22px;">
                                <tr>
                                    <td style="padding:5px 0; color:#6b7280; width:130px;">No Dokumen</td>
                                    <td style="padding:5px 8px; color:#6b7280; width:14px;">:</td>
                                    <td style="padding:5px 0; font-weight:700; color:#111827;">{{ $docNo }}</td>
                                </tr>
                            
                                {{-- BARU -- khusus Sisa Sample --}}
                                @if(!empty($srno))
                                    <tr>
                                        <td style="padding:5px 0; color:#6b7280;">SR#</td>
                                        <td style="padding:5px 8px; color:#6b7280;">:</td>
                                        <td style="padding:5px 0; font-weight:700; color:#111827;">{{ $srno }}</td>
                                    </tr>
                                @endif
                                @if(!empty($sampleStatus))
                                    <tr>
                                        <td style="padding:5px 0; color:#6b7280;">Sample Status</td>
                                        <td style="padding:5px 8px; color:#6b7280;">:</td>
                                        <td style="padding:5px 0; font-weight:700; color:#111827;">{{ $sampleStatus }}</td>
                                    </tr>
                                @endif
                                @if(!empty($style))
                                    <tr>
                                        <td style="padding:5px 0; color:#6b7280;">Style</td>
                                        <td style="padding:5px 8px; color:#6b7280;">:</td>
                                        <td style="padding:5px 0; font-weight:700; color:#111827;">{{ $style }}</td>
                                    </tr>
                                @endif
                            
                                @if(!empty($penerima))
                                    <tr>
                                        <td style="padding:5px 0; color:#6b7280;">Penerima</td>
                                        <td style="padding:5px 8px; color:#6b7280;">:</td>
                                        <td style="padding:5px 0; font-weight:700; color:#111827;">{{ $penerima }}</td>
                                    </tr>
                                @endif
                                <tr>
                                    <td style="padding:5px 0; color:#6b7280;">Total Item</td>
                                    <td style="padding:5px 8px; color:#6b7280;">:</td>
                                    <td style="padding:5px 0; font-weight:700; color:#111827;">{{ $items->count() }} baris</td>
                                </tr>
                                <tr>
                                    <td style="padding:5px 0; color:#6b7280;">Total Pcs</td>
                                    <td style="padding:5px 8px; color:#6b7280;">:</td>
                                    <td style="padding:5px 0; font-weight:700; color:#111827;">{{ $items->sum('pcs') }}</td>
                                </tr>
                                @if($keterangan)
                                    <tr>
                                        <td style="padding:5px 0; color:#6b7280; vertical-align:top;">Keterangan</td>
                                        <td style="padding:5px 8px; color:#6b7280; vertical-align:top;">:</td>
                                        <td style="padding:5px 0; color:#111827;">{{ $keterangan }}</td>
                                    </tr>
                                @endif
                            </table>
                        </td>
                    </tr>

                    {{-- TABEL DETAIL ITEM + SIZE --}}
                    <tr>
                        <td style="padding:0 32px 8px 32px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse; font-size:12.5px;">
                                <tr style="background:#f9fafb;">
                                    <th style="text-align:left; padding:8px 10px; border-bottom:2px solid #e5e7eb; font-size:11px; text-transform:uppercase; color:#6b7280;">Grade</th>
                                    <th style="text-align:left; padding:8px 10px; border-bottom:2px solid #e5e7eb; font-size:11px; text-transform:uppercase; color:#6b7280;">PO No / OP</th>
                                    <th style="text-align:left; padding:8px 10px; border-bottom:2px solid #e5e7eb; font-size:11px; text-transform:uppercase; color:#6b7280;">Color</th>
                                    <th style="text-align:right; padding:8px 10px; border-bottom:2px solid #e5e7eb; font-size:11px; text-transform:uppercase; color:#6b7280;">Pcs</th>
                                </tr>
                                @foreach($items as $item)
                                    <tr>
                                        <td style="padding:8px 10px; border-bottom:1px solid #f3f4f6;">
                                            <span style="display:inline-block; width:24px; height:24px; line-height:24px; text-align:center; border-radius:5px; color:#fff; font-weight:bold; font-size:11px;
                                                background:{{ strtoupper($item['grade']) === 'A' ? '#16a34a' : (strtoupper($item['grade']) === 'B' ? '#2563eb' : '#d97706') }};">
                                                {{ $item['grade'] }}
                                            </span>
                                        </td>
                                        <td style="padding:8px 10px; border-bottom:1px solid #f3f4f6;">{{ $item['POno'] }} / OP {{ $item['OP'] }}</td>
                                        <td style="padding:8px 10px; border-bottom:1px solid #f3f4f6;">{{ $item['color'] }} {{ !empty($item['secsz']) ? '('.$item['secsz'].')' : '' }}</td>
                                        <td style="padding:8px 10px; border-bottom:1px solid #f3f4f6; text-align:right;">{{ $item['pcs'] }}</td>
                                    </tr>
                                    <tr>
                                        <td colspan="4" style="padding:0 10px 10px 10px; background:#fafbfc; border-bottom:1px solid #f3f4f6;">
                                            @if(count($item['sizes']))
                                                @foreach($item['sizes'] as $s)
                                                    <span style="display:inline-block; background:#fff; border:1px solid #eef1f5; border-radius:6px; padding:4px 8px; margin:4px 4px 0 0; text-align:center; min-width:38px;">
                                                        <span style="display:block; font-size:9px; color:#9ca3af; font-weight:bold; text-transform:uppercase;">{{ $s['label'] }}</span>
                                                        <span style="display:block; font-size:12px; color:#111827; font-weight:bold;">{{ $s['qty'] }}</span>
                                                    </span>
                                                @endforeach
                                            @else
                                                <span style="font-size:11px; color:#9ca3af;">Tidak ada breakdown size</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </table>
                        </td>
                    </tr>

                    {{-- TOMBOL APPROVE --}}
                    <tr>
                        <td style="padding:26px 32px; text-align:center;">
                            <a href="{{ $approveUrl }}"
                                style="background:#16a34a; color:#ffffff; padding:13px 44px; border-radius:8px; text-decoration:none; font-weight:700; font-size:14.5px; display:inline-block;">
                                Approve
                            </a>
                            <p style="font-size:12px; color:#9ca3af; margin:14px 0 0 0;">
                                Kindly confirm your decision. Thank you!
                            </p>
                        </td>
                    </tr>

                    {{-- FOOTER / SIGNATURE --}}
                    <tr>
                        <td style="padding:18px 32px 26px 32px; border-top:1px solid #f3f4f6;">
                            <p style="font-size:13px; color:#4b5563; margin:0;">Kind Regards,</p>
                            <p style="font-size:13px; font-weight:700; color:#111827; margin:2px 0 0 0;">Packing & Finishing System</p>
                            <p style="font-size:10.5px; color:#9ca3af; margin:10px 0 0 0;">
                                Link approval ini berlaku selama 14 hari sejak email ini dikirim. Tidak perlu login untuk menindaklanjuti.
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
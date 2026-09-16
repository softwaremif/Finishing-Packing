<?php

namespace App\Http\Controllers\Inspection;

use App\Http\Controllers\Controller;
use App\Http\Controllers\FinishgoodStuffing\FinishgoodStuffingController;
use App\Http\Controllers\Packing\PackingController;
use App\Services\OrderImageService;
use App\Services\ShipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InspectionController extends Controller
{
    public function index(Request $request)
    {
        return view('menu.inspection.index');
    }

    // GANTI TOTAL -- FIX UTAMA: query LANGSUNG ke pack+po, TIDAK lagi
    // lewat ShipService/'ship'. TIDAK ADA filter pos/mif di sini -- SEMUA
    // user melihat data yang SAMA (gabungan mif 1 & 2), konsisten dengan
    // index Packing/FG-Stuffing.
    public function getList(Request $request)
    {
        $page   = max(1, (int) $request->page);
        $rows   = max(1, (int) $request->rows);
        $offset = ($page - 1) * $rows;

        $db = DB::connection('mysql');

        // Ambil SEMUA popk yang SAAT INI punya minimal 1 carton sedang
        // Inspect (fca=1) -- SEMUA mif, tidak dibedakan.
        $popksWithInspect = $db->table('pack')
            ->where('fca', 1)
            ->distinct()
            ->pluck('popk');

        if ($popksWithInspect->isEmpty()) {
            return response()->json(['total' => 0, 'rows' => []]);
        }

        $poRows = $db->table('po')
            ->whereIn('popk', $popksWithInspect)
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($x) use ($search) {
                    $x->where('POno', 'like', "%{$search}%")
                        ->orWhere('OP', 'like', "%{$search}%")
                        ->orWhere('buyer', 'like', "%{$search}%")
                        ->orWhere('style', 'like', "%{$search}%");
                });
            })
            ->when($request->filled('buyer'), fn($q) => $q->where('buyer', $request->buyer))
            ->get(['popk', 'POno', 'OP', 'poref', 'mif', 'GAC', 'buyer', 'season', 'style', 'qty', 'customer', 'ordpk']);

        // Kelompokkan per PO+OP+poref (mif dijadikan atribut data, BUKAN
        // pemisah tampilan).
        $aggregated = $poRows
            ->groupBy(fn ($r) => $r->POno . '|' . $r->OP . '|' . ($r->poref ?? ''))
            ->map(function ($group) use ($db) {
                $rep = clone $group->first();
                $popks = $group->pluck('popk')->values();
        
                $inspectRows = $db->table('pack')
                    ->whereIn('popk', $popks)
                    ->where('fca', 1)
                    ->get(['packpk', 'carton', 'bundlepk', 'pcs']);
        
                // pcs -- SUM apa adanya, tidak terpengaruh grouping carton/bundle.
                $rep->pcs_inspect = (float) $inspectRows->sum('pcs');
        
                // ctn -- hitung UNIT: Mix Polibag dedupe per nomor carton, Bundle
                // dihitung SEBAGAI 1 (carton besarnya), bukan per carton kecil
                // di dalamnya.
                $countingUnits = $inspectRows->groupBy(function ($r) {
                    return $r->bundlepk ? ('bundle_' . $r->bundlepk) : ('single_' . $r->carton);
                });
                $rep->ctn_inspect = $countingUnits->count();
        
                return $rep;
            })
            ->values()
            ->sortByDesc('GAC')
            ->values();

        $total = $aggregated->count();
        $data  = $aggregated->slice($offset, $rows)->values();

        app(OrderImageService::class)->attachToRows($data, 'ordpk', 'order_image');

        foreach ($data as $i => $row) {
            $row->no = $offset + $i + 1;
        }

        return response()->json(['total' => $total, 'rows' => $data]);
    }

    /**
     * FIX UTAMA: SEKARANG render lewat view gabungan
     * (menu.shared.packing-input-global) yang SAMA dipakai Packing dan
     * FG/Stuffing.
     */
    public function inputGlobal(Request $request)
    {
        $fgController = app(FinishgoodStuffingController::class);
        $view = $fgController->inputPackingGlobal($request);
        $data = $view->getData();

        $data['pageConfig'] = [
            'mode'                => 'inspection',
            'pageTitlePrefix'     => 'Carton Sedang Inspect',
            'guserpkSegel'        => [],
            'guserpkShipmentFlow' => [],
            'guserpkTerima'       => [],

            'showPlanningChips'   => false,
            'showShipmentChips'   => false,
            'showSizeFilter'      => false,
            'showPartFilter'      => false,
            'showAddPacking'      => false,
            'showCtnManagement'   => false,
            'showScanNobar'       => false,
            'showShipmentPlan'    => false,
            'showShipmentActions' => false,
            'showEditButton'      => false,
            'showSealAction'      => false,
            'showKembalikanButton' => true,
            'showHistoryTab'      => true,
            'showBaseChips'       => false,
            'backRouteName'       => 'inspection.index',

            'routes' => [
                'back'                    => route('inspection.index'),
                'listDetailGlobal'        => route('inspection.list.detail.global'),
                'historyListDetailGlobal' => route('inspection.history.list.detail.global'),
                'breakdownSummaryGlobal'  => route('packing.breakdownSummaryGlobal'),
                'cardsInfoGlobal'         => route('packing.cardsInfoGlobal'),
                'headerInfoGlobal'        => route('packing.headerInfoGlobal'),
                'combosGlobal'            => route('packing.combosGlobal'),
                'bulkShipAction'          => route('inspection.bulk-ship-action'),
                'inspectShow'   => url('/inspection/inspect-show'),
                'inspectUpdate' => url('/inspection/inspect-update'),

                'headerPartial'    => 'menu.packing.partials.header_info_global',
                'cardsInfoPartial' => 'menu.packing.partials.cards_info_global',
                'breakdownPartial' => 'menu.packing.partials.breakdown_summary_global',

                'modalKembalikanStuffing' => 'menu.inspection.modal-kembalikan-stuffing-global',
                'modalInspectDocument'    => 'menu.inspection.modal-inspection',
                'inspectAvailableCartons' => route('inspection.inspect-available-cartons'),
                'inspectStore'            => route('inspection.inspect-store'),
                'inspectDocumentsList'    => route('inspection.inspect-documents-list'),
                'inspectDefectSubList'    => route('inspection.inspect-defect-sub-list'),
                'inspectPdfBase'          => url('/inspection/inspect-pdf'),
            ],
        ];

        return view('menu.shared.packing-input-global', $data);
    }

    // GANTI -- SEKARANG delegasi ke PackingController (SUMBER KEBENARAN
    // TUNGGAL listDetailGlobal(), sejak konsolidasi Packing/FG-Stuffing).
    public function listDetailGlobal(Request $request)
    {
        $request->validate(['po' => 'nullable', 'op' => 'required', 'poref' => 'nullable', 'mif' => 'nullable']);
        $request->merge(['status' => 'inspect']);
        return app(PackingController::class)->listDetailGlobal($request);
    }

    // FIX UTAMA -- SEBELUMNYA salah memanggil FinishgoodStuffingController
    // (method itu sudah jadi kode mati sejak konsolidasi), SEKARANG
    // konsisten pakai PackingController seperti listDetailGlobal() di atas.
    public function historyListDetailGlobal(Request $request)
    {
        $request->validate(['po' => 'nullable', 'op' => 'required', 'poref' => 'nullable', 'mif' => 'nullable']);
        $request->merge(['status' => 'inspect_history']);
        return app(PackingController::class)->listDetailGlobal($request);
    }

    public function bulkShipAction(Request $request)
    {
        return app(FinishgoodStuffingController::class)->bulkShipAction($request);
    }

    // GANTI -- FIX UTAMA: fca=1 dibaca LANGSUNG dari 'pack', tidak perlu
    // lookup shippk ke 'ship' sama sekali lagi -- pack.packpk SENDIRI
    // sudah jadi identitas baris.
    public function inspectAvailableCartons(Request $request)
    {
        $POno = (string) $request->query('po');
        $OP   = (string) $request->query('op');
        $mif  = (int) $request->query('mif', session('pos'));
        $part = $request->query('part');

        $db = DB::connection('mysql');

        $popks = $db->table('po')
            ->where('OP', $OP)
            ->where('mif', $mif)
            ->when($POno !== '', fn($q) => $q->where('POno', $POno))
            ->pluck('popk');

        if ($popks->isEmpty()) {
            return response()->json(['rows' => []]);
        }

        $qtyColumns  = collect(range(1, 40))->map(fn($i) => "pack.qty{$i}")->implode(', ');
        $sizeColumns = collect(range(1, 40))->map(fn($i) => "po.size{$i}")->implode(', ');

        $packRows = $db->table('pack')
            ->join('po', 'po.popk', '=', 'pack.popk')
            ->whereIn('pack.popk', $popks)
            ->where('pack.fca', 1)
            ->when($part !== null && $part !== '', fn($q) => $q->where('pack.part', $part))
            ->selectRaw("
                pack.packpk, pack.carton, pack.nobar, pack.material, pack.secsz, pack.part,
                pack.mixno, pack.bundlepk,
                {$qtyColumns}, {$sizeColumns}
            ")
            ->orderBy('pack.carton')
            ->get();

        foreach ($packRows as $row) {
            $sizes = [];
            for ($i = 1; $i <= 40; $i++) {
                $label = $row->{"size{$i}"} ?? null;
                $qty   = $row->{"qty{$i}"} ?? null;
                if (!empty($label) && (int) $qty > 0) {
                    $sizes[] = ['label' => $label, 'qty' => (int) $qty];
                }
                unset($row->{"size{$i}"}, $row->{"qty{$i}"});
            }
            $row->sizes = $sizes;
        }

        return response()->json(['rows' => $packRows]);
    }

    // GANTI TOTAL -- FIX UTAMA: INI endpoint yang BENAR-BENAR dipakai
    // frontend (bukan PackingController::storeInspecDocument()). Sekarang
    // memakai skema snapshot (carton, bukan shippk), dan MASIH menghitung
    // 'hasil' otomatis server-side dari defectCount vs AQL (lebih aman,
    // tidak bisa dimanipulasi klien).
    public function inspectStore(Request $request)
    {
        $validated = $request->validate([
            'aql'                => 'required|numeric|min:0',
            'keterangan'         => 'nullable|string|max:2000',
            'lines'              => 'required|array|min:1',
            'lines.*.packpk'     => 'required|integer', // GANTI -- packpk, bukan shippk
            'lines.*.size'       => 'required|string',
            'lines.*.color'      => 'nullable|string',
            'lines.*.secsz'      => 'nullable|string',
            'lines.*.qty'        => 'required|numeric|min:0.01',
            'lines.*.stspass'    => 'required|in:0,1',
            'lines.*.defects'    => 'nullable|array',
            'lines.*.defects.*'  => 'integer',
        ]);

        $db = DB::connection('mysql');

        $totPcs      = collect($validated['lines'])->sum('qty');
        $defectCount = collect($validated['lines'])->where('stspass', 0)->count();
        $aql         = (float) $validated['aql'];
        $hasilFinal  = ($defectCount >= $aql) ? 0 : 1;

        // BARU -- lookup carton/nobar/part/POno/OP/poref/mif LANGSUNG dari
        // pack+po (bukan dari input klien) -- disimpan sebagai snapshot
        // permanen di inspecdt.
        $packpksInvolved = collect($validated['lines'])->pluck('packpk')->unique()->values();
        $packInfoByPackpk = $db->table('pack')
            ->whereIn('packpk', $packpksInvolved)
            ->get(['packpk', 'popk', 'carton', 'nobar', 'part', 'pinjam', 'kembali'])
            ->keyBy('packpk');

        $popksInvolved = $packInfoByPackpk->pluck('popk')->unique()->values();
        $poInfoByPopk = $db->table('po')
            ->whereIn('popk', $popksInvolved)
            ->get(['popk', 'POno', 'OP', 'poref', 'mif'])
            ->keyBy('popk');

        try {
            $newInspecpk = DB::connection('mysql')->transaction(function () use ($db, $validated, $totPcs, $hasilFinal, $packInfoByPackpk, $poInfoByPopk) {
                $newInspecpk = (int) ($db->table('inspec')->lockForUpdate()->max('inspecpk')) + 1;
                $db->table('inspec')->insert([
                    'inspecpk' => $newInspecpk,
                    'tgl'      => now(),
                    'aql'      => $validated['aql'],
                    'totpcs'   => $totPcs,
                    'hasil'    => $hasilFinal,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);

                // GANTI -- grouping per CARTON (fisik), bukan shippk lagi.
                // Mix Polibag lintas PO otomatis tergabung (berbagi nomor
                // carton yang sama).
                $newInspecdtpk = (int) ($db->table('inspecdt')->lockForUpdate()->max('inspecdtpk'));
                $newInspecszpk = (int) ($db->table('inspecsz')->lockForUpdate()->max('inspecszpk'));
                $newInsdefpk   = (int) ($db->table('insdef')->lockForUpdate()->max('insdefpk'));

                $byPackpk = collect($validated['lines'])->groupBy('packpk');
 
                foreach ($byPackpk as $packpk => $lines) {
                    $packInfo = $packInfoByPackpk->get($packpk);
                    $poInfo   = $packInfo ? $poInfoByPopk->get($packInfo->popk) : null;
                    $carton   = $packInfo->carton ?? ('UNKNOWN-' . $packpk);
                
                    $newInspecdtpk++;
                    $db->table('inspecdt')->insert([
                        'inspecdtpk' => $newInspecdtpk,
                        'inspecpk'   => $newInspecpk, // di inspectUpdate() gunakan $inspecpk
                        'packpk'     => $packpk,
                        'carton'     => $carton,
                        'nobar'      => $packInfo->nobar ?? null,
                        'part'       => $packInfo->part ?? null,
                        'pinjam'     => $packInfo->pinjam ?? null,
                        'kembali'    => $packInfo->kembali ?? null,
                        'POno'       => $poInfo->POno ?? null,
                        'OP'         => $poInfo->OP ?? null,
                        'poref'      => $poInfo->poref ?? null,
                        'mif'        => $poInfo->mif ?? null,
                    ]);
                
                    foreach ($lines as $line) {
                        $newInspecszpk++;
                        $db->table('inspecsz')->insert([
                            'inspecszpk' => $newInspecszpk,
                            'inspecdtpk' => $newInspecdtpk,
                            'size'       => $line['size'],
                            'color'      => $line['color'] ?? null,
                            'secsz'      => $line['secsz'] ?? null,
                            'qty'        => $line['qty'],
                            'stspass'    => (int) $line['stspass'],
                        ]);
                
                        if ((int) $line['stspass'] === 0 && !empty($line['defects'])) {
                            foreach ($line['defects'] as $defectpk) {
                                $newInsdefpk++;
                                $db->table('insdef')->insert([
                                    'insdefpk'   => $newInsdefpk,
                                    'inspecszpk' => $newInspecszpk,
                                    'defectpk'   => $defectpk,
                                ]);
                            }
                        }
                    }
                }

                return $newInspecpk;
            });

            $hasilLabel = $hasilFinal === 1 ? 'LULUS' : 'REJECT';
            return response()->json([
                'icon'  => $hasilFinal === 1 ? 'success' : 'warning',
                'title' => "Dokumen Inspect #{$newInspecpk} tersimpan. Defect: {$defectCount}/{$aql} -- Hasil: {$hasilLabel}.",
                'hasil' => $hasilFinal,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal menyimpan dokumen inspect.'], 500);
        }
    }

    private function buildNoInspec($inspecpk, $tgl): string
    {
        $romanMonths = ['I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];
        $date = \Carbon\Carbon::parse($tgl);
        $month = $romanMonths[$date->month - 1];
        return sprintf('%04d/INS/%s/%d', $inspecpk, $month, $date->year);
    }

    // GANTI TOTAL -- FIX UTAMA: lepas SEPENUHNYA dari 'ship'. Karena
    // inspecdt SUDAH menyimpan snapshot POno/OP sendiri, tidak perlu JOIN
    // apa pun lagi -- match langsung ke kolom snapshot.
    public function inspectDocumentsList(Request $request)
    {
        $POno = (string) $request->query('po');
        $OP   = (string) $request->query('op');

        $db = DB::connection('mysql');

        $inspecdtRows = $db->table('inspecdt')
            ->whereRaw('TRIM(POno) = ?', [trim($POno)])
            ->whereRaw('TRIM(OP) = ?', [trim($OP)])
            ->select('inspecdtpk', 'inspecpk', 'carton', 'part')
            ->get();

        if ($inspecdtRows->isEmpty()) {
            return response()->json(['rows' => []]);
        }

        $inspecpks = $inspecdtRows->pluck('inspecpk')->unique()->values();
        $inspecHeaders = $db->table('inspec')->whereIn('inspecpk', $inspecpks)->get()->keyBy('inspecpk');

        $inspecdtpks = $inspecdtRows->pluck('inspecdtpk')->unique()->values();
        $inspecszRows = $db->table('inspecsz')->whereIn('inspecdtpk', $inspecdtpks)->get()->groupBy('inspecdtpk');

        $result = [];
        foreach ($inspecpks as $inspecpk) {
            $header = $inspecHeaders->get($inspecpk);
            if (!$header) continue;

            $dtForThisDoc = $inspecdtRows->where('inspecpk', $inspecpk);
            $parts = $dtForThisDoc->pluck('part')->filter()->unique()->values();

            $cartons = $dtForThisDoc->map(function ($dt) use ($inspecszRows) {
                $sizes = $inspecszRows->get($dt->inspecdtpk, collect());
                return [
                    'carton' => $dt->carton,
                    'sizes'  => $sizes->map(fn($s) => [
                        'label' => $s->size,
                        'color' => $s->color,
                        'secsz' => $s->secsz,
                        'qty' => (float) $s->qty,
                        'stspass' => (int) $s->stspass,
                    ])->values(),
                ];
            })->values();

            $result[] = [
                'inspecpk'  => $inspecpk,
                'no_inspec' => $this->buildNoInspec($inspecpk, $header->tgl),
                'tgl'       => $header->tgl,
                'aql'       => $header->aql,
                'totpcs'    => $header->totpcs,
                'hasil'     => (int) $header->hasil,
                'parts'     => $parts,
                'cartons'   => $cartons,
            ];
        }

        usort($result, fn($a, $b) => $b['inspecpk'] <=> $a['inspecpk']);

        return response()->json(['rows' => $result]);
    }

    // GANTI -- resolveConnection() dihapus, 'mysql' saja.
    public function inspectDefectSubList(Request $request)
    {
        $db = DB::connection('mysql');

        $subs = $db->table('sub')->orderBy('subnm')->get();
        $defects = $db->table('defect')->orderBy('defectnm')->get(['defectpk', 'code', 'subcode', 'defectnm', 'subpk']);

        return response()->json(['subs' => $subs, 'defects' => $defects]);
    }

    // GANTI TOTAL -- FIX UTAMA: lepas SEPENUHNYA dari 'ship'. Buyer/Style/
    // Season/Customer dicari via JOIN ke 'po' berdasarkan snapshot POno+OP
    // (bukan popk/packpk yang mudah basi) -- best-effort, kalau PO itu
    // sendiri masih ada di tabel po, info ini tetap muncul.
    public function inspectPdfReport(Request $request, $inspecpk)
    {
        $db = DB::connection('mysql');

        $inspec = $db->table('inspec')->where('inspecpk', $inspecpk)->first();
        abort_unless($inspec, 404);

        $noInspec = $this->buildNoInspec($inspec->inspecpk, $inspec->tgl);

        $inspecdtRows = $db->table('inspecdt')
            ->where('inspecpk', $inspecpk)
            ->select('inspecdtpk', 'packpk', 'carton', 'nobar', 'part', 'POno', 'OP', 'poref', 'pinjam', 'kembali')
            ->get();

        $packpksInvolved = $inspecdtRows->pluck('packpk')->filter()->unique()->values();
        $packMixBundleInfo = $packpksInvolved->isNotEmpty()
            ? $db->table('pack')->whereIn('packpk', $packpksInvolved)->get(['packpk', 'mixno', 'bundlepk'])->keyBy('packpk')
            : collect();
        
        $bundlepksInvolved = $packMixBundleInfo->pluck('bundlepk')->filter()->unique()->values();
        $bundleNameMap = $bundlepksInvolved->isNotEmpty()
            ? $db->table('carton_bundle')->whereIn('bundlepk', $bundlepksInvolved)->pluck('bundle_carton', 'bundlepk')
            : collect();
        
        foreach ($inspecdtRows as $dt) {
            $mb = $packMixBundleInfo->get($dt->packpk);
            $dt->bundlepk = $mb->bundlepk ?? null;
            $dt->bundle_carton = $dt->bundlepk ? ($bundleNameMap[$dt->bundlepk] ?? null) : null;
        }

        $repDt = $inspecdtRows->first();

        $poInfo = null;
        if ($repDt && $repDt->POno && $repDt->OP) {
            $poInfo = $db->table('po')
                ->where('POno', $repDt->POno)
                ->where('OP', $repDt->OP)
                ->when($repDt->poref, fn ($q) => $q->where('poref', $repDt->poref))
                ->first(['POno', 'OP', 'buyer', 'style', 'season', 'customer', 'qty']); // BARU -- qty
        }
        $poInfo = $poInfo ?? (object) [
            'POno' => $repDt->POno ?? null, 'OP' => $repDt->OP ?? null,
            'buyer' => null, 'style' => null, 'season' => null, 'customer' => null, 'qty' => null, // BARU
        ];

        $inspecdtpks = $inspecdtRows->pluck('inspecdtpk')->values();

        $inspecszRows = $db->table('inspecsz')->whereIn('inspecdtpk', $inspecdtpks)->get();
        $inspecszpks = $inspecszRows->pluck('inspecszpk')->values();

        $insdefRows = $db->table('insdef')
            ->join('defect', 'defect.defectpk', '=', 'insdef.defectpk')
            ->leftJoin('sub', 'sub.subpk', '=', 'defect.subpk')
            ->whereIn('insdef.inspecszpk', $inspecszpks)
            ->select('insdef.inspecszpk', 'defect.defectpk', 'defect.defectnm', 'defect.code', 'sub.subnm')
            ->get()
            ->groupBy('inspecszpk');

        $detailRows = [];
        foreach ($inspecdtRows as $dt) {
            $szRows = $inspecszRows->where('inspecdtpk', $dt->inspecdtpk);
            foreach ($szRows as $sz) {
                $defects = $insdefRows->get($sz->inspecszpk, collect());
                $detailRows[] = [
                    'carton'        => $dt->carton,
                    'POno'          => $dt->POno,
                    'OP'            => $dt->OP,
                    'bundle_carton' => $dt->bundle_carton,
                    'pinjam'        => $dt->pinjam,   // BARU
                    'kembali'       => $dt->kembali,  // BARU
                    'size'          => $sz->size,
                    'color'         => $sz->color,
                    'secsz'         => $sz->secsz,
                    'qty'           => $sz->qty,
                    'stspass'       => (int) $sz->stspass,
                    'defects'       => $defects->pluck('defectnm')->implode(', '),
                ];
            }
        }
 
        // BARU -- daftar SEMUA PO/OP unik yang terlibat di dokumen ini (dikirim
        // ke view utk header -- kalau >1, artinya dokumen ini mencakup carton
        // Mix Polibag lintas PO).
        $allPoOpPairs = collect($detailRows)
            ->map(fn ($r) => ['POno' => $r['POno'], 'OP' => $r['OP']])
            ->unique(fn ($p) => $p['POno'] . '|' . $p['OP'])
            ->values();

        $defectSummary = $insdefRows->flatten(1)
            ->groupBy(fn($d) => $d->subnm ?? 'Lainnya')
            ->map(function ($group, $subnm) {
                return [
                    'subnm'  => $subnm,
                    'total'  => $group->count(),
                    'detail' => $group->groupBy('defectnm')->map(fn($g) => $g->count()),
                ];
            })->values();

        $totalCarton = $inspecdtRows->pluck('carton')->unique()->count();
        $totalDefect = $inspecszRows->where('stspass', 0)->count();
        $totalPass   = $inspecszRows->where('stspass', 1)->count();

        return view('menu.inspection.pdf-report', [
            'inspec'        => $inspec,
            'noInspec'      => $noInspec,
            'poInfo'        => $poInfo,
            'detailRows'    => $detailRows,
            'defectSummary' => $defectSummary,
            'totalCarton'   => $totalCarton,
            'totalDefect'   => $totalDefect,
            'totalPass'     => $totalPass,
            'allPoOpPairs' => $allPoOpPairs,
        ]);
    }

    public function inspectShow(Request $request, $inspecpk)
    {
        $db = DB::connection('mysql');
    
        $inspec = $db->table('inspec')->where('inspecpk', $inspecpk)->first();
        abort_unless($inspec, 404);
    
        $inspecdtRows = $db->table('inspecdt')->where('inspecpk', $inspecpk)->get(); // sudah bawa SEMUA kolom, termasuk POno/OP
        $inspecdtpks  = $inspecdtRows->pluck('inspecdtpk');
    
        $inspecszRows = $db->table('inspecsz')->whereIn('inspecdtpk', $inspecdtpks)->get();
        $inspecszpks  = $inspecszRows->pluck('inspecszpk');
    
        $insdefRows = $db->table('insdef')->whereIn('inspecszpk', $inspecszpks)->get()->groupBy('inspecszpk');
        $dtByPk     = $inspecdtRows->keyBy('inspecdtpk');
    
        $lines = $inspecszRows->map(function ($sz) use ($dtByPk, $insdefRows) {
            $dt = $dtByPk->get($sz->inspecdtpk);
            return [
                'inspecszpk' => $sz->inspecszpk,
                'packpk'     => $dt->packpk ?? null,
                'carton'     => $dt->carton ?? null,
                'POno'       => $dt->POno ?? null,   // BARU
                'OP'         => $dt->OP ?? null,     // BARU
                'size'       => $sz->size,
                'color'      => $sz->color,
                'secsz'      => $sz->secsz,
                'qty'        => (float) $sz->qty,
                'stspass'    => (int) $sz->stspass,
                'defects'    => $insdefRows->get($sz->inspecszpk, collect())->pluck('defectpk')->values(),
            ];
        })->values();
    
        return response()->json([
            'inspecpk' => $inspec->inspecpk,
            'aql'      => $inspec->aql,
            'hasil'    => (int) $inspec->hasil,
            'keterangan' => $inspec->keterangan,
            'lines'    => $lines,
        ]);
    }

    // BARU -- update dokumen yang SUDAH ADA. Pola: hapus SEMUA baris anak
    // lama (inspecdt/inspecsz/insdef), insert ulang dari data terbaru --
    // paling aman utk kasus isi sample berubah drastis saat edit. Header
    // 'inspec' (inspecpk-nya) TETAP SAMA -- nomor dokumen (buildNoInspec)
    // tidak berubah.
    public function inspectUpdate(Request $request, $inspecpk)
    {
        $validated = $request->validate([
            'aql'                => 'required|numeric|min:0',
            'keterangan'         => 'nullable|string|max:2000', 
            'lines'              => 'required|array|min:1',
            'lines.*.packpk'     => 'required|integer',
            'lines.*.size'       => 'required|string',
            'lines.*.color'      => 'nullable|string',
            'lines.*.secsz'      => 'nullable|string',
            'lines.*.qty'        => 'required|numeric|min:0.01',
            'lines.*.stspass'    => 'required|in:0,1',
            'lines.*.defects'    => 'nullable|array',
            'lines.*.defects.*'  => 'integer',
        ]);

        $db = DB::connection('mysql');

        $inspec = $db->table('inspec')->where('inspecpk', $inspecpk)->first();
        abort_unless($inspec, 404);

        $totPcs      = collect($validated['lines'])->sum('qty');
        $defectCount = collect($validated['lines'])->where('stspass', 0)->count();
        $aql         = (float) $validated['aql'];
        $hasilFinal  = ($defectCount >= $aql) ? 0 : 1;

        $packpksInvolved = collect($validated['lines'])->pluck('packpk')->unique()->values();
        $packInfoByPackpk = $db->table('pack')->whereIn('packpk', $packpksInvolved)
            ->get(['packpk', 'popk', 'carton', 'nobar', 'part', 'pinjam', 'kembali'])->keyBy('packpk');
        $popksInvolved = $packInfoByPackpk->pluck('popk')->unique()->values();
        $poInfoByPopk = $db->table('po')->whereIn('popk', $popksInvolved)
            ->get(['popk', 'POno', 'OP', 'poref', 'mif'])->keyBy('popk');

        try {
            DB::connection('mysql')->transaction(function () use ($db, $inspecpk, $validated, $totPcs, $hasilFinal, $packInfoByPackpk, $poInfoByPopk) {
                $db->table('inspec')->where('inspecpk', $inspecpk)->update([
                    'aql'    => $validated['aql'],
                    'totpcs' => $totPcs,
                    'hasil'  => $hasilFinal,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);

                $oldInspecdtpks = $db->table('inspecdt')->where('inspecpk', $inspecpk)->pluck('inspecdtpk');
                $oldInspecszpks = $db->table('inspecsz')->whereIn('inspecdtpk', $oldInspecdtpks)->pluck('inspecszpk');
                $db->table('insdef')->whereIn('inspecszpk', $oldInspecszpks)->delete();
                $db->table('inspecsz')->whereIn('inspecdtpk', $oldInspecdtpks)->delete();
                $db->table('inspecdt')->where('inspecpk', $inspecpk)->delete();

                $newInspecdtpk = (int) ($db->table('inspecdt')->lockForUpdate()->max('inspecdtpk'));
                $newInspecszpk = (int) ($db->table('inspecsz')->lockForUpdate()->max('inspecszpk'));
                $newInsdefpk   = (int) ($db->table('insdef')->lockForUpdate()->max('insdefpk'));

                // Group per PACKPK (bukan carton) -- carton Mix Polibag yang
                // sama akan otomatis menghasilkan beberapa baris inspecdt,
                // masing-masing dengan PO/OP yang BENAR sesuai packpk-nya.
                $byPackpk = collect($validated['lines'])->groupBy('packpk');

                foreach ($byPackpk as $packpk => $lines) {
                    $packInfo = $packInfoByPackpk->get($packpk);
                    $poInfo   = $packInfo ? $poInfoByPopk->get($packInfo->popk) : null;
                    $carton   = $packInfo->carton ?? ('UNKNOWN-' . $packpk);

                    $newInspecdtpk++;
                    $db->table('inspecdt')->insert([
                        'inspecdtpk' => $newInspecdtpk,
                        'inspecpk'   => $inspecpk, // FIX UTAMA -- SEBELUMNYA $newInspecpk (tidak terdefinisi, fatal error)
                        'packpk'     => $packpk,
                        'carton'     => $carton,
                        'nobar'      => $packInfo->nobar ?? null,
                        'part'       => $packInfo->part ?? null,
                        'pinjam'     => $packInfo->pinjam ?? null,
                        'kembali'    => $packInfo->kembali ?? null,
                        'POno'       => $poInfo->POno ?? null,
                        'OP'         => $poInfo->OP ?? null,
                        'poref'      => $poInfo->poref ?? null,
                        'mif'        => $poInfo->mif ?? null,
                    ]);

                    foreach ($lines as $line) {
                        $newInspecszpk++;
                        $db->table('inspecsz')->insert([
                            'inspecszpk' => $newInspecszpk,
                            'inspecdtpk' => $newInspecdtpk,
                            'size'       => $line['size'],
                            'color'      => $line['color'] ?? null,
                            'secsz'      => $line['secsz'] ?? null,
                            'qty'        => $line['qty'],
                            'stspass'    => (int) $line['stspass'],
                        ]);

                        if ((int) $line['stspass'] === 0 && !empty($line['defects'])) {
                            foreach ($line['defects'] as $defectpk) {
                                $newInsdefpk++;
                                $db->table('insdef')->insert([
                                    'insdefpk'   => $newInsdefpk,
                                    'inspecszpk' => $newInspecszpk,
                                    'defectpk'   => $defectpk,
                                ]);
                            }
                        }
                    }
                }
            });

            $hasilLabel = $hasilFinal === 1 ? 'LULUS' : 'REJECT';
            return response()->json([
                'icon'  => $hasilFinal === 1 ? 'success' : 'warning',
                'title' => "Dokumen Inspect #{$inspecpk} berhasil diperbarui. Hasil: {$hasilLabel}.",
                'hasil' => $hasilFinal,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal memperbarui dokumen inspect.'], 500);
        }
    }

    // BARU -- Tab "Carton Inspec": SEMUA carton yang SEDANG di-Inspect
    // (fca=1) lintas PO/OP, tanpa filter pos/mif.
    public function globalCartonInspectList(Request $request)
    {
        $db = DB::connection('mysql');

        $rawRows = $db->table('pack')
            ->join('po', 'po.popk', '=', 'pack.popk')
            ->where('pack.fca', 1)
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($x) use ($search) {
                    $x->where('pack.carton', 'like', "%{$search}%")
                        ->orWhere('pack.nobar', 'like', "%{$search}%")
                        ->orWhere('po.POno', 'like', "%{$search}%")
                        ->orWhere('po.OP', 'like', "%{$search}%")
                        ->orWhere('po.buyer', 'like', "%{$search}%");
                });
            })
            ->select(
                'pack.packpk', 'pack.carton', 'pack.nobar', 'pack.material', 'pack.secsz', 'pack.pinjam',
                'pack.mixno', 'pack.bundlepk',
                'po.POno', 'po.OP', 'po.poref', 'po.mif', 'po.buyer', 'po.customer'
            )
            ->orderByDesc('pack.pinjam')
            ->get();

        $bundlepksInvolved = $rawRows->pluck('bundlepk')->filter()->unique()->values();
        $bundleNameMap = [];
        if ($bundlepksInvolved->isNotEmpty()) {
            $bundleNameMap = $db->table('carton_bundle')
                ->whereIn('bundlepk', $bundlepksInvolved)
                ->pluck('bundle_carton', 'bundlepk');
        }

        // BARU -- FIX UTAMA: cari dokumen yang MASIH TERBUKA (enddate NULL)
        // yang mereferensi tiap carton -- inilah dasar utk munculkan tombol
        // "Kembalikan" (sesuai aturan blocking already_documented yang sudah
        // ada: 1 carton hanya boleh ada di 1 dokumen terbuka).
        $openDocByCarton = $db->table('inspecdt')
            ->join('inspec', 'inspec.inspecpk', '=', 'inspecdt.inspecpk')
            ->whereNull('inspec.enddate')
            ->select('inspecdt.carton', 'inspec.inspecpk', 'inspec.tgl', 'inspec.hasil')
            ->get()
            ->keyBy('carton');

        $cartons = $rawRows->groupBy('carton')->map(function ($group) use ($bundleNameMap, $openDocByCarton) {
            $first = $group->first();
            $poOpList = $group->map(fn ($r) => ['POno' => $r->POno, 'OP' => $r->OP])
                ->unique(fn ($p) => $p['POno'] . '|' . $p['OP'])
                ->values();

            $openDoc = $openDocByCarton->get($first->carton);

            return [
                'carton'        => $first->carton,
                'nobar'         => $first->nobar,
                'material'      => $first->material,
                'secsz'         => $first->secsz,
                'pinjam'        => $group->max('pinjam'),
                'POno'          => $first->POno,
                'OP'            => $first->OP,
                'poref'         => $first->poref,
                'mif'           => $first->mif,
                'buyer'         => $first->buyer,
                'po_op_list'    => $poOpList,
                'is_mix'        => $poOpList->count() > 1,
                'bundlepk'      => $first->bundlepk,
                'bundle_carton' => $first->bundlepk ? ($bundleNameMap[$first->bundlepk] ?? null) : null,
                'packpks'       => $group->pluck('packpk')->unique()->values(), // BARU -- semua packpk carton ini
                'has_open_doc'  => (bool) $openDoc,                              // BARU
                'no_inspec'     => $openDoc ? $this->buildNoInspec($openDoc->inspecpk, $openDoc->tgl) : null, // BARU
                'inspec_hasil'  => $openDoc ? (int) $openDoc->hasil : null,      // BARU
            ];
        })->values();

        $countingUnits = $cartons->groupBy(function ($c) {
            return $c['bundlepk'] ? ('bundle_' . $c['bundlepk']) : ('single_' . $c['carton']);
        });
        $total = $countingUnits->count();

        $page   = max(1, (int) $request->input('page', 1));
        $rows   = max(1, (int) $request->input('rows', 200));
        $offset = ($page - 1) * $rows;
        $data   = $cartons->slice($offset, $rows)->values();

        return response()->json(['total' => $total, 'rows' => $data]);
    }

    // BARU -- Tab "Dokumen Inspect": SEMUA dokumen Inspect lintas PO/OP,
    // dibaca dari snapshot inspecdt (bukan lagi lewat 'ship').
    public function globalInspectDocumentsList(Request $request)
    {
        $page   = max(1, (int) $request->input('page', 1));
        $rows   = max(1, (int) $request->input('rows', 50));
        $offset = ($page - 1) * $rows;

        $db = DB::connection('mysql');

        $query = $db->table('inspec')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->whereExists(function ($sub) use ($search) {
                    $sub->select(DB::raw(1))->from('inspecdt')
                        ->whereColumn('inspecdt.inspecpk', 'inspec.inspecpk')
                        ->where(function ($x) use ($search) {
                            $x->where('inspecdt.carton', 'like', "%{$search}%")
                                ->orWhere('inspecdt.POno', 'like', "%{$search}%")
                                ->orWhere('inspecdt.OP', 'like', "%{$search}%");
                        });
                });
            })
            // BARU -- filter Buyer: dokumen ikut kalau SALAH SATU carton di
            // dalamnya berasal dari PO/OP milik buyer tersebut.
            ->when($request->filled('buyer'), function ($q) use ($request) {
                $buyer = $request->buyer;
                $q->whereExists(function ($sub) use ($buyer) {
                    $sub->select(DB::raw(1))->from('inspecdt')
                        ->join('po', function ($join) {
                            $join->on('po.POno', '=', 'inspecdt.POno')
                                ->on('po.OP', '=', 'inspecdt.OP');
                        })
                        ->whereColumn('inspecdt.inspecpk', 'inspec.inspecpk')
                        ->where('po.buyer', $buyer);
                });
            })
            // SEKARANG jadi filter RENTANG TANGGAL
            // inspec.tgl (1 Jan -- 31 Des tahun itu), BUKAN tahun dari OP.
            ->when($request->filled('tgl_from'), function ($q) use ($request) {
                $q->where('inspec.tgl', '>=', $request->input('tgl_from') . ' 00:00:00');
            })
            ->when($request->filled('tgl_to'), function ($q) use ($request) {
                $q->where('inspec.tgl', '<=', $request->input('tgl_to') . ' 23:59:59');
            })
            ->orderByDesc('inspecpk');

        $total = (clone $query)->count();
        $headers = $query->skip($offset)->take($rows)->get();

        $inspecpks = $headers->pluck('inspecpk')->values();
        $inspecdtRows = $db->table('inspecdt')->whereIn('inspecpk', $inspecpks)->get();

        // BARU -- bangun row sebagai OBJECT (stdClass), bukan array, supaya
        // bisa dipakai bareng OrderImageService::attachToRows() yang
        // men-set properti langsung ($row->order_image = ...).
        $data = $headers->map(function ($header) use ($inspecdtRows) {
            $dt = $inspecdtRows->where('inspecpk', $header->inspecpk);
            $poOpPairs = $dt->map(fn ($d) => ['POno' => $d->POno, 'OP' => $d->OP])
                ->unique(fn ($p) => $p['POno'] . '|' . $p['OP'])
                ->values();
        
            // hanya kalau SEMUA baris inspecdt (semua
            // carton) di dokumen ini sudah punya 'kembali' terisi.
            $allReturned = $dt->isNotEmpty() && $dt->every(fn ($d) => !empty($d->kembali));
        
            return (object) [
                'inspecpk'    => $header->inspecpk,
                'no_inspec'   => $this->buildNoInspec($header->inspecpk, $header->tgl),
                'tgl'         => $header->tgl,
                'aql'         => $header->aql,
                'totpcs'      => $header->totpcs,
                'hasil'       => (int) $header->hasil,
                'enddate'     => $header->enddate,
                'all_returned'=> $allReturned, // BARU
                'POno'        => $dt->pluck('POno')->filter()->unique()->implode(', '),
                'OP'          => $dt->pluck('OP')->filter()->unique()->implode(', '),
                'poref'       => $dt->pluck('poref')->filter()->unique()->first(),
                'mif'         => $dt->pluck('mif')->filter()->unique()->first(),
                'cartons'     => $dt->pluck('carton')->filter()->unique()->values(),
                'po_op_pairs' => $poOpPairs,
            ];
        })->values();

        // BARU -- lookup info Order (buyer/season/style/qty/customer) utk
        // PO/OP REPRESENTATIF tiap dokumen (yang PERTAMA, kalau dokumen ini
        // ternyata mencakup >1 PO/OP karena Mix Polibag).
        $uniquePairs = $data->map(fn ($d) => $d->po_op_pairs->first())->filter()->unique(fn ($p) => $p['POno'] . '|' . $p['OP']);
        $poInfoByPair = collect();
        foreach ($uniquePairs as $pair) {
            $poRow = $db->table('po')
                ->where('POno', $pair['POno'])->where('OP', $pair['OP'])
                ->first(['POno', 'OP', 'buyer', 'season', 'style', 'qty', 'ordpk', 'customer']);
            if ($poRow) {
                $poInfoByPair->put($pair['POno'] . '|' . $pair['OP'], $poRow);
            }
        }

        foreach ($data as $d) {
            $repPair = $d->po_op_pairs->first();
            $poInfo  = $repPair ? $poInfoByPair->get($repPair['POno'] . '|' . $repPair['OP']) : null;

            $d->buyer    = $poInfo->buyer ?? null;
            $d->season   = $poInfo->season ?? null;
            $d->style    = $poInfo->style ?? null;
            $d->qty      = $poInfo->qty ?? null;
            $d->ordpk    = $poInfo->ordpk ?? null;
            $d->customer = $poInfo->customer ?? null;

            unset($d->po_op_pairs);
        }

        app(OrderImageService::class)->attachToRows($data, 'ordpk', 'order_image');

        return response()->json(['total' => $total, 'rows' => $data]);
    }

    // BARU -- Tab "History Carton Inspect": SEMUA carton yang PERNAH masuk
    // Inspect kapan pun (pack.pinjam terisi), lintas PO/OP.
    public function globalHistoryCartonInspectList(Request $request)
    {
        $page   = max(1, (int) $request->input('page', 1));
        $rows   = max(1, (int) $request->input('rows', 50));
        $offset = ($page - 1) * $rows;

        $db = DB::connection('mysql');

        $query = $db->table('pack')
            ->join('po', 'po.popk', '=', 'pack.popk')
            ->whereNotNull('pack.pinjam')
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($x) use ($search) {
                    $x->where('pack.carton', 'like', "%{$search}%")
                        ->orWhere('pack.nobar', 'like', "%{$search}%")
                        ->orWhere('po.POno', 'like', "%{$search}%")
                        ->orWhere('po.OP', 'like', "%{$search}%")
                        ->orWhere('po.buyer', 'like', "%{$search}%");
                });
            });

        $total = (clone $query)->count();

        $data = $query
            ->select(
                'pack.packpk',
                'pack.carton',
                'pack.nobar',
                'pack.pinjam',
                'pack.kembali',
                'pack.reject',
                'pack.fca',
                'po.POno',
                'po.OP',
                'po.poref',
                'po.mif',
                'po.buyer'
            )
            ->orderByDesc('pack.pinjam')
            ->skip($offset)->take($rows)
            ->get();

        foreach ($data as $i => $row) {
            $row->no = $offset + $i + 1;
            $row->status = $row->reject ? 'reject' : ((int) $row->fca === 1 ? 'inspect' : ($row->kembali ? 'kembali' : 'lainnya'));
        }

        return response()->json(['total' => $total, 'rows' => $data]);
    }

    public function globalInspectAvailableCartons(Request $request)
    {
        $db = DB::connection('mysql');

        $excludeInspecpk = $request->query('exclude_inspecpk'); // BARU -- dikirim saat mode EDIT

        $qtyColumns  = collect(range(1, 40))->map(fn ($i) => "pack.qty{$i}")->implode(', ');
        $sizeColumns = collect(range(1, 40))->map(fn ($i) => "po.size{$i}")->implode(', ');

        $bundlepksInvolved = $db->table('pack')->where('fca', 1)->whereNotNull('bundlepk')->distinct()->pluck('bundlepk');
        $bundleNameMap = [];
        if ($bundlepksInvolved->isNotEmpty()) {
            $bundleNameMap = $db->table('carton_bundle')
                ->whereIn('bundlepk', $bundlepksInvolved)
                ->pluck('bundle_carton', 'bundlepk');
        }

        // BARU -- FIX UTAMA: cari nomor carton yang SUDAH pernah masuk ke
        // dokumen Inspect MANA PUN, KECUALI dokumen yang sedang diedit
        // (kalau ada) -- carton ini akan di-disable total di picker supaya
        // tidak bisa disample dobel ke dokumen terpisah.
        $documentedCartons = $db->table('inspecdt')
            ->join('inspec', 'inspec.inspecpk', '=', 'inspecdt.inspecpk')
            ->whereNull('inspec.enddate')
            ->when($excludeInspecpk, fn ($q) => $q->where('inspecdt.inspecpk', '!=', $excludeInspecpk))
            ->distinct()
            ->pluck('inspecdt.carton');

        $packRows = $db->table('pack')
            ->join('po', 'po.popk', '=', 'pack.popk')
            ->where('pack.fca', 1) // sudah otomatis mengecualikan carton yang sudah "dikembalikan" (fca di-null-kan)
            ->when($request->filled('search'), function ($q) use ($request) {
                $search = trim($request->search);
                $q->where(function ($x) use ($search) {
                    $x->where('pack.carton', 'like', "%{$search}%")
                        ->orWhere('pack.nobar', 'like', "%{$search}%")
                        ->orWhere('po.POno', 'like', "%{$search}%")
                        ->orWhere('po.OP', 'like', "%{$search}%");
                });
            })
            ->selectRaw("
                pack.packpk, pack.carton, pack.nobar, pack.material, pack.secsz, pack.part,
                pack.mixno, pack.bundlepk,
                po.POno, po.OP, po.mif,
                {$qtyColumns}, {$sizeColumns}
            ")
            ->orderBy('pack.carton')
            ->get();

        foreach ($packRows as $row) {
            $sizes = [];
            for ($i = 1; $i <= 40; $i++) {
                $label = $row->{"size{$i}"} ?? null;
                $qty   = $row->{"qty{$i}"} ?? null;
                if (!empty($label) && (int) $qty > 0) {
                    $sizes[] = ['label' => $label, 'qty' => (int) $qty];
                }
                unset($row->{"size{$i}"}, $row->{"qty{$i}"});
            }
            $row->sizes = $sizes;
            $row->bundle_carton = $row->bundlepk ? ($bundleNameMap[$row->bundlepk] ?? null) : null;
            $row->already_documented = $documentedCartons->contains($row->carton); // BARU
        }

        $cartonPoOpCount = $packRows->groupBy('carton')
            ->map(fn ($g) => $g->map(fn ($r) => $r->POno . '|' . $r->OP)->unique()->count());
        foreach ($packRows as $row) {
            $row->is_mix = ($cartonPoOpCount[$row->carton] ?? 1) > 1;
        }

        return response()->json(['rows' => $packRows]);
    }

    public function inspectEndDocument(Request $request, $inspecpk)
    {
        $db = DB::connection('mysql');
    
        $inspec = $db->table('inspec')->where('inspecpk', $inspecpk)->first();
        abort_unless($inspec, 404);
    
        if ($inspec->enddate) {
            return response()->json(['icon' => 'warning', 'title' => 'Dokumen ini sudah di-End sebelumnya.'], 422);
        }
    
        $db->table('inspec')->where('inspecpk', $inspecpk)->update(['enddate' => now()]);
    
        return response()->json([
            'icon'  => 'success',
            'title' => "Dokumen Inspect #{$inspecpk} sudah di-End. Carton di dalamnya bisa didokumentasikan ulang kalau masuk Inspect lagi nanti.",
        ]);
    }
}

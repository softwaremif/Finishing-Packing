<?php
namespace App\Http\Controllers\Inspection;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FinishgoodStuffing\FinishgoodStuffingController;
use App\Services\ShipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InspectionController extends Controller
{
    /** @var ShipService */
    protected $shipService;

    public function __construct(ShipService $shipService)
    {
        $this->shipService = $shipService;
    }

    public function index(Request $request)
    {
        return view('menu.inspection.index');
    }

    private function resolveConnection($mif): string
    {
        return ((int) $mif) === 1 ? 'mysql_andon' : 'mysql';
    }

    public function getList(Request $request)
    {
        $result = $this->shipService->getListShip($request, ['only_pinjam' => true]);
        return response()->json($result);
    }

    public function detail(Request $request)
    {
        $POno = (string) $request->query('pono');
        $OP   = (string) $request->query('op');
        $data = $this->shipService->getFinishedGoodsPrintGlobal($POno, $OP);
        if ($data === null) {
            abort(404, 'Data PO tidak ditemukan.');
        }
        $data['pono'] = $POno;
        $data['op']   = $OP;
        return view('menu.inspection.detail', $data);
    }

    public function bulkAction(Request $request)
    {
        $POno    = (string) $request->input('pono');
        $OP      = (string) $request->input('op');
        $action  = (string) $request->input('action');
        $cartons = (array) $request->input('cartons', []);
        if ($action !== 'stuffing') {
            return response()->json([
                'success' => false,
                'message' => 'Halaman inspection hanya mengizinkan aksi Kembalikan ke Stuffing.',
            ], 422);
        }
        $result = $this->shipService->bulkCartonActionGlobal($POno, $OP, $action, $cartons);
        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * FIX UTAMA: SEKARANG render lewat view gabungan
     * (menu.shared.packing-input-global) yang SAMA dipakai Packing dan
     * FG/Stuffing, dengan pageConfig khusus Inspection -- SEBELUMNYA
     * render ke blade custom 'menu.inspection.input-global' yang beda
     * struktur & kurang lengkap (tidak ada badge Reject, stamp Returning,
     * dsb).
     */
    public function inputGlobal(Request $request)
    {
        $fgController = app(FinishgoodStuffingController::class);
        $view = $fgController->inputPackingGlobal($request);
        $data = $view->getData(); // po, op, poref, mif, dt2, activeSizes, colorList, secszList, colorSecszCombos, dst.

        $data['pageConfig'] = [
            'mode'                => 'inspection',
            'pageTitlePrefix'     => 'Carton Sedang Inspect',
            'guserpkSegel'        => [],   // TIDAK BOLEH Seal/Unseal sama sekali dari sini
            'guserpkShipmentFlow' => [],   // TIDAK BOLEH Proses Inspect/Shipment dari sini
            'guserpkTerima'       => [],   // TIDAK BOLEH Terima Carton dari sini (itu punya FG/Stuffing)

            'showPlanningChips'   => false,
            'showShipmentChips'   => false, // chip Inspect/Shipped/Returning TIDAK relevan (SEMUA baris di tab 1 sudah pasti Inspect)
            'showSizeFilter'      => false,
            'showPartFilter'      => false,
            'showAddPacking'      => false,
            'showCtnManagement'   => false,
            'showScanNobar'       => false,
            'showShipmentPlan'    => false,
            'showShipmentActions' => false,
            'showEditButton'      => false, // TIDAK BOLEH edit carton dari sini
            'showSealAction'      => false, // BARU -- lihat catatan di blade: sembunyikan tombol Seal/Unseal SAMA SEKALI (bukan cuma disabled)
            'showKembalikanButton'=> true,  // BARU -- tombol khusus Inspection
            'showHistoryTab'      => true,  // BARU -- tampilkan tab History Inspect
            'showBaseChips' => false, 
            'backRouteName'       => 'inspection.index',

            'routes' => [
                'back'                   => route('inspection.index'),
                'listDetailGlobal'       => route('inspection.list.detail.global'),
                'historyListDetailGlobal'=> route('inspection.history.list.detail.global'),
                'breakdownSummaryGlobal' => route('finish-good-stuffing.breakdownSummaryGlobal'),
                'cardsInfoGlobal'        => route('finish-good-stuffing.cardsInfoGlobal'),
                'headerInfoGlobal'       => route('finish-good-stuffing.headerInfoGlobal'),
                'combosGlobal'           => route('finish-good-stuffing.combosGlobal'),
                'bulkShipAction'         => route('inspection.bulk-ship-action'),
                'headerPartial'          => 'menu.finishgood-stuffing.partials.header_info_global',
                'cardsInfoPartial'       => 'menu.finishgood-stuffing.partials.cards_info_global',
                'breakdownPartial'       => 'menu.finishgood-stuffing.partials.breakdown_summary_global',
                'modalKembalikanStuffing'=> 'menu.inspection.modal-kembalikan-stuffing-global',
            
        
                'modalInspectDocument'   => 'menu.inspection.modal-inspection',
                'inspectAvailableCartons'=> route('inspection.inspect-available-cartons'),
                'inspectStore'           => route('inspection.inspect-store'),
                'inspectDocumentsList' => route('inspection.inspect-documents-list'),
                'inspectDefectSubList' => route('inspection.inspect-defect-sub-list'),
                'inspectPdfBase' => url('/inspection/inspect-pdf'),
            ],
        ];

        return view('menu.shared.packing-input-global', $data);
    }

    /**
     * SAMA seperti sebelumnya -- delegasikan ke listDetailGlobal() FG/Stuffing,
     * status DIKUNCI ke 'inspect' (carton yang MASIH sedang Inspect saat ini).
     */
    public function listDetailGlobal(Request $request)
    {
        $request->validate([
            'po'    => 'nullable',
            'op'    => 'required',
            'poref' => 'nullable',
            'mif'   => 'nullable',
        ]);
        $request->merge(['status' => 'inspect']);
        $fgController = app(FinishgoodStuffingController::class);
        return $fgController->listDetailGlobal($request);
    }

    /**
     * BARU -- tab "History Inspect": status DIKUNCI ke 'inspect_history'
     * (carton yang PERNAH masuk Inspect kapan pun, TERMASUK yang sudah
     * dikembalikan/diterima balik/di-reject -- lihat fix di
     * FinishgoodStuffingController::listDetailGlobal()).
     */
    public function historyListDetailGlobal(Request $request)
    {
        $request->validate([
            'po'    => 'nullable',
            'op'    => 'required',
            'poref' => 'nullable',
            'mif'   => 'nullable',
        ]);
        $request->merge(['status' => 'inspect_history']);
        $fgController = app(FinishgoodStuffingController::class);
        return $fgController->listDetailGlobal($request);
    }

    public function bulkShipAction(Request $request)
    {
        $fgController = app(FinishgoodStuffingController::class);
        return $fgController->bulkShipAction($request);
    }

    public function inspectAvailableCartons(Request $request)
    {
        $POno = (string) $request->query('po');
        $OP   = (string) $request->query('op');
        $mif  = (int) $request->query('mif', session('pos'));
        $part = $request->query('part'); // BARU
    
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);
    
        $popks = $db->table('po')
            ->where('OP', $OP)
            ->where('mif', $mif)
            ->when($POno !== '', fn($q) => $q->where('POno', $POno))
            ->pluck('popk');
    
        if ($popks->isEmpty()) {
            return response()->json(['rows' => []]);
        }
    
        $shipRows = $db->table('ship')
            ->whereIn('popk', $popks)
            ->where('fca', 1)
            ->get(['shippk', 'packpk']);
    
        $shippkByPackpk = $shipRows->keyBy('packpk')->map(fn ($r) => $r->shippk);
        $packpks = $shipRows->pluck('packpk')->filter()->unique()->values();
    
        if ($packpks->isEmpty()) {
            return response()->json(['rows' => []]);
        }
    
        $qtyColumns  = collect(range(1, 40))->map(fn($i) => "pack.qty{$i}")->implode(', ');
        $sizeColumns = collect(range(1, 40))->map(fn($i) => "po.size{$i}")->implode(', ');
    
        $packRows = $db->table('pack')
            ->join('po', 'po.popk', '=', 'pack.popk')
            ->whereIn('pack.packpk', $packpks)
            ->when($part !== null && $part !== '', fn ($q) => $q->where('pack.part', $part)) // BARU -- FIX UTAMA
            ->selectRaw("
                pack.packpk, pack.carton, pack.nobar, pack.material, pack.secsz, pack.part,
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
            $row->sizes  = $sizes;
            $row->shippk = $shippkByPackpk[$row->packpk] ?? null;
        }
    
        $packRows = $packRows->filter(fn ($r) => $r->shippk !== null)->values();
    
        return response()->json(['rows' => $packRows]);
    }
    
    public function inspectStore(Request $request)
    {
        $validated = $request->validate([
            'aql'                  => 'required|numeric|min:0',
            'lines'                => 'required|array|min:1',
            'lines.*.shippk'       => 'required|integer',
            'lines.*.size'         => 'required|string',
            'lines.*.color'        => 'nullable|string',
            'lines.*.secsz'        => 'nullable|string',
            'lines.*.qty'          => 'required|numeric|min:0.01',
            'lines.*.stspass'      => 'required|in:0,1', // BARU -- WAJIB per baris
            'lines.*.defects'      => 'nullable|array',   // BARU -- hanya relevan kalau stspass=0
            'lines.*.defects.*'    => 'integer',
        ]);
    
        $mif = (int) $request->input('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);
    
        $totPcs = collect($validated['lines'])->sum('qty');
    
        // BARU -- FIX UTAMA: hitung hasil OTOMATIS dari jumlah baris Defect
        // vs AQL (dipakai sbg angka batas/reject number), BUKAN dari input
        // manual client.
        $defectCount = collect($validated['lines'])->where('stspass', 0)->count();
        $aql = (float) $validated['aql'];
        $hasilFinal = ($defectCount >= $aql) ? 0 : 1;
    
        try {
            $newInspecpk = DB::connection($connection)->transaction(function () use ($db, $validated, $totPcs, $hasilFinal) {
                $newInspecpk = (int) ($db->table('inspec')->lockForUpdate()->max('inspecpk')) + 1;
                $db->table('inspec')->insert([
                    'inspecpk' => $newInspecpk,
                    'tgl'      => now(),
                    'aql'      => $validated['aql'],
                    'totpcs'   => $totPcs,
                    'hasil'    => $hasilFinal, // BARU -- hasil auto dari server
                ]);
    
                $byShippk = collect($validated['lines'])->groupBy('shippk');
                $newInspecdtpk = (int) ($db->table('inspecdt')->lockForUpdate()->max('inspecdtpk'));
                $newInspecszpk = (int) ($db->table('inspecsz')->lockForUpdate()->max('inspecszpk'));
                $newInsdefpk   = (int) ($db->table('insdef')->lockForUpdate()->max('insdefpk'));
    
                foreach ($byShippk as $shippk => $lines) {
                    $newInspecdtpk++;
                    $db->table('inspecdt')->insert([
                        'inspecdtpk' => $newInspecdtpk,
                        'inspecpk'   => $newInspecpk,
                        'shippk'     => $shippk,
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
                            'stspass'    => (int) $line['stspass'], // BARU
                        ]);
    
                        // BARU -- kalau Defect, simpan defect(s) yang dipilih.
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
        $romanMonths = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        $date = \Carbon\Carbon::parse($tgl);
        $month = $romanMonths[$date->month - 1];
        return sprintf('%04d/INS/%s/%d', $inspecpk, $month, $date->year);
    }
    
    
    // ============================================================
    // 2) GANTI inspectDocumentsList() -- tambah 'no_inspec' per dokumen.
    // ============================================================
    public function inspectDocumentsList(Request $request)
    {
        $POno = (string) $request->query('po');
        $OP   = (string) $request->query('op');
        $mif  = (int) $request->query('mif', session('pos'));
    
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);
    
        $relevantShippks = $db->table('ship')
            ->whereRaw('TRIM(POno) = ?', [trim($POno)])
            ->whereRaw('TRIM(OP) = ?', [trim($OP)])
            ->pluck('shippk');
    
        if ($relevantShippks->isEmpty()) {
            return response()->json(['rows' => []]);
        }
    
        $inspecdtRows = $db->table('inspecdt')
            ->join('ship', 'ship.shippk', '=', 'inspecdt.shippk')
            ->whereIn('inspecdt.shippk', $relevantShippks)
            ->select('inspecdt.inspecdtpk', 'inspecdt.inspecpk', 'inspecdt.shippk', 'ship.carton', 'ship.part')
            ->get();
    
        $inspecpks = $inspecdtRows->pluck('inspecpk')->unique()->values();
        if ($inspecpks->isEmpty()) {
            return response()->json(['rows' => []]);
        }
    
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
                    'sizes'  => $sizes->map(fn ($s) => ['label' => $s->size, 'color' => $s->color, 'secsz' => $s->secsz, 'qty' => (float) $s->qty, 'stspass' => (int) $s->stspass])->values(),
                ];
            })->values();
    
            $result[] = [
                'inspecpk'  => $inspecpk,
                'no_inspec' => $this->buildNoInspec($inspecpk, $header->tgl), // BARU
                'tgl'       => $header->tgl,
                'aql'       => $header->aql,
                'totpcs'    => $header->totpcs,
                'hasil'     => (int) $header->hasil,
                'parts'     => $parts,
                'cartons'   => $cartons,
            ];
        }
    
        usort($result, fn ($a, $b) => $b['inspecpk'] <=> $a['inspecpk']);
    
        return response()->json(['rows' => $result]);
    }

    public function inspectDefectSubList(Request $request)
    {
        $mif = (int) $request->query('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);
    
        $subs = $db->table('sub')->orderBy('subnm')->get();
        $defects = $db->table('defect')->orderBy('defectnm')->get(['defectpk', 'code', 'subcode', 'defectnm', 'subpk']);
    
        return response()->json([
            'subs'    => $subs,
            'defects' => $defects,
        ]);
    }

    public function inspectPdfReport(Request $request, $inspecpk)
    {
        $mif = (int) $request->query('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);
    
        $inspec = $db->table('inspec')->where('inspecpk', $inspecpk)->first();
        abort_unless($inspec, 404);
    
        $noInspec = $this->buildNoInspec($inspec->inspecpk, $inspec->tgl);
    
        // Baris inspecdt (carton) + info PO/OP dari ship+po.
        $inspecdtRows = $db->table('inspecdt')
            ->join('ship', 'ship.shippk', '=', 'inspecdt.shippk')
            ->leftJoin('po', 'po.popk', '=', 'ship.popk')
            ->where('inspecdt.inspecpk', $inspecpk)
            ->select(
                'inspecdt.inspecdtpk', 'inspecdt.shippk',
                'ship.carton', 'ship.part', 'ship.nobar',
                'po.POno', 'po.OP', 'po.buyer', 'po.style', 'po.season', 'po.customer'
            )
            ->get();
    
        $poInfo = $inspecdtRows->first(); // representatif -- 1 dokumen inspect biasanya 1 PO/OP
    
        $inspecdtpks = $inspecdtRows->pluck('inspecdtpk')->values();
    
        // Baris inspecsz (per size/color per carton) + defect terkait.
        $inspecszRows = $db->table('inspecsz')
            ->whereIn('inspecdtpk', $inspecdtpks)
            ->get();
    
        $inspecszpks = $inspecszRows->pluck('inspecszpk')->values();
    
        $insdefRows = $db->table('insdef')
            ->join('defect', 'defect.defectpk', '=', 'insdef.defectpk')
            ->leftJoin('sub', 'sub.subpk', '=', 'defect.subpk')
            ->whereIn('insdef.inspecszpk', $inspecszpks)
            ->select('insdef.inspecszpk', 'defect.defectpk', 'defect.defectnm', 'defect.code', 'sub.subnm')
            ->get()
            ->groupBy('inspecszpk');
    
        // Susun baris detail per carton (utk tabel utama laporan).
        $detailRows = [];
        foreach ($inspecdtRows as $dt) {
            $szRows = $inspecszRows->where('inspecdtpk', $dt->inspecdtpk);
            foreach ($szRows as $sz) {
                $defects = $insdefRows->get($sz->inspecszpk, collect());
                $detailRows[] = [
                    'carton'  => $dt->carton,
                    'size'    => $sz->size,
                    'color'   => $sz->color,
                    'secsz'   => $sz->secsz,
                    'qty'     => $sz->qty,
                    'stspass' => (int) $sz->stspass,
                    'defects' => $defects->pluck('defectnm')->implode(', '),
                ];
            }
        }
    
        // Rekap defect per kategori (sub) -- standar laporan AQL garment.
        $defectSummary = $insdefRows->flatten(1)
            ->groupBy(fn ($d) => $d->subnm ?? 'Lainnya')
            ->map(function ($group, $subnm) {
                return [
                    'subnm' => $subnm,
                    'total' => $group->count(),
                    'detail' => $group->groupBy('defectnm')->map(fn ($g) => $g->count()),
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
        ]);
    }
}
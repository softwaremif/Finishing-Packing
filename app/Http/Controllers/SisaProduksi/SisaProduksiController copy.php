<?php

namespace App\Http\Controllers\SisaProduksi;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SisaProduksiController extends Controller
{
    public function index()
    {
        return view('menu.shared.transfer-index', [
            'pageConfig' => [
                'title'              => 'Daftar Data OP',
                'polibagColumnLabel' => 'Polibag',
                'finishingField'     => 'finishing', // SAMA seperti Stok Sisa -- properti bernama 'finishing'
                'showQtyColumn'      => true,
                'showPackingColumn'  => true,
                'showKeluarColumn'   => true,
                'showMifBadgeAlways' => true,  // BARU -- modul ini SELALU gabung 2 mif untuk semua user
                'showExportExcel'    => true,  // BARU
                'exportExcelRoute'   => route('sisa-produksi.export-excel'),
                'showPdfAction'      => true,  // BARU -- modal: PDF-only kalau balance<=0
                'pdfUrlBase'         => url('/sisa-produksi'), // BARU
                'routes' => [
                    'list'          => route('sisa-produksi.list'),
                    'detailByPoOp'  => route('sisa-produksi.detail-by-po-op'),
                    'modalView'     => 'menu.shared.transfer-detail-modal',
                    'navStateKey'   => 'sisaProduksiIndexState', // BEDA dari Stok Sisa's 'sisaProduksiListState' -- supaya tidak bentrok kalau kebuka bersamaan di tab lain
                    'inputUrlBase'  => url('/sisa-produksi/input'),
                ],
            ],
        ]);
    }

    private function isSuperUser(): bool
    {
        return auth()->check() && (auth()->user()->role ?? null) === 'super';
    }
 
    private function resolveConnection($mif): string
    {
        return ((int) $mif) === 1 ? 'mysql_andon' : 'mysql';
    }
 
    // ============================================================
    // GET LIST -- BEDA dari modul lain: SELALU gabung KEDUA mif untuk
    // SEMUA user (bukan cuma super user), karena akun cuma 1 untuk
    // seluruh mif. Kalau PONo+OP yang SAMA muncul di KEDUA database,
    // TIDAK digabung jadi 1 baris -- tetap 2 baris terpisah, ditandai
    // has_mif_duplicate supaya frontend bisa kasih badge "mif 1"/"mif 2".
    // ============================================================
    public function getList(Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;
 
        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
 
        $keysAndon = $this->fetchGroupKeys('mysql_andon', 1, $request);
        $keysMysql = $this->fetchGroupKeys('mysql', 2, $request);
        $allKeys   = $keysAndon->concat($keysMysql);
 
        // Deteksi PONo+OP yang muncul di KEDUA mif.
        $countByPoOp = $allKeys
            ->groupBy(fn ($k) => $k->POno . '|' . $k->OP)
            ->map(fn ($g) => $g->pluck('mif')->unique()->count());
 
        $allKeys = $allKeys->map(function ($k) use ($countByPoOp) {
            $comboKey = $k->POno . '|' . $k->OP;
            $k->has_mif_duplicate = ($countByPoOp[$comboKey] ?? 1) > 1;
            return $k;
        });
 
        $allKeys = $allKeys
            ->when(
                $sortDir === 'asc',
                fn ($c) => $c->sortBy('OP'),
                fn ($c) => $c->sortByDesc('OP')
            )
            ->values();
 
        $total    = $allKeys->count();
        $pageKeys = $allKeys->slice($offset, $rows)->values();
 
        $data = $this->fetchAggregatedForKeys($pageKeys, $request);
 
        foreach ($data as $i => $row) {
            $row->no = $offset + $i + 1;
        }
 
        return response()->json([
            'total' => $total,
            'rows'  => $data,
        ]);
    }
 
    /**
     * Detail per PO+OP+mif -- dibuka dari 1 baris SPESIFIK di index
     * (yang sudah pasti tahu mif-nya sendiri, karena tidak digabung).
     */
    public function detailByPoOp(Request $request)
    {
        $validated = $request->validate([
            'po'  => 'nullable',
            'op'  => 'required',
            'mif' => 'required',
        ]);
 
        $po  = $validated['po'] ?? null;
        $op  = $validated['op'];
        $mif = (int) $validated['mif'];
 
        $connection = $this->resolveConnection($mif);
 
        $key = (object) ['POno' => $po, 'OP' => $op, 'mif' => $mif, 'has_mif_duplicate' => false];
        $rows = $this->fetchDetailRowsForKeys($connection, $mif, collect([$key]), $request)->values();
 
        foreach ($rows as $i => $row) {
            $row->no = $i + 1;
        }
 
        return response()->json([
            'total' => $rows->count(),
            'rows'  => $rows,
        ]);
    }
 
    private function aggregateByPoOp($collection)
    {
        // FIX UTAMA: groupBy sekarang menyertakan `mif` -- PONo+OP yang
        // sama tapi mif BEDA harus tetap jadi baris TERPISAH, bukan
        // digabung/di-sum jadi 1.
        return $collection
            ->groupBy(fn ($row) => $row->POno . '|' . $row->OP . '|' . $row->mif)
            ->map(function ($group) {
                $representative = clone $group->sortByDesc('popk')->first();
 
                $representative->qty       = (float) $group->sum('qty');
                $representative->loading   = (float) $group->sum('loading');
                $representative->rq        = (float) $group->sum('rq');
                $representative->transfer  = (float) $group->sum('transfer');
                $representative->finishing = (float) $group->sum('finishing');
                $representative->packing   = (float) $group->sum('packing');
                $representative->keluar    = (float) $group->sum('keluar');
                $representative->balance   = $representative->transfer - $representative->packing;
 
                $representative->status = (int) $group->max('status');
                $representative->has_mif_duplicate = (bool) $group->first()->has_mif_duplicate;
 
                return $representative;
            })
            ->values();
    }
 
    /**
     * Query RINGAN -- cuma ambil kombinasi PONo+OP unik yang match
     * filter, DENGAN qualifying condition "ADA baris bj dengan grade
     * A-C" (SAMA seperti native: WHERE bj.grade BETWEEN 'A' AND 'C'),
     * BUKAN lagi "ada packing" seperti Stok Sisa.
     */
    private function fetchGroupKeys(string $connection, int $mif, Request $request)
    {
        $search = $request->search;
        $buyer  = $request->buyer;
        $year   = $request->year;
 
        $qualifyingBj = DB::connection($connection)->table('bj')
            ->select('popk')
            ->whereBetween('grade', ['A', 'C'])
            ->groupBy('popk');
 
        $query = DB::connection($connection)->table('po')
            ->joinSub($qualifyingBj, 'qbj', function ($join) {
                $join->on('po.popk', '=', 'qbj.popk');
            })
            ->where('po.mif', $mif)
            ->select('po.POno', 'po.OP')
            ->distinct();
 
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po.POno', 'like', "%{$search}%")
                    ->orWhere('po.OP', 'like', "%{$search}%")
                    ->orWhere('po.poref', 'like', "%{$search}%")
                    ->orWhere('po.customer', 'like', "%{$search}%")
                    ->orWhere('po.season', 'like', "%{$search}%")
                    ->orWhere('po.secsz', 'like', "%{$search}%")
                    ->orWhere('po.silhouette', 'like', "%{$search}%")
                    ->orWhere('po.style', 'like', "%{$search}%");
            });
        }
 
        if ($buyer) {
            $query->where('po.buyer', 'like', "%{$buyer}%");
        }
 
        if ($year) {
            $query->whereRaw('YEAR(po.shipdate1) >= ?', [(int) $year]);
        }
 
        return $query->get()->map(function ($r) use ($mif) {
            $r->mif = $mif;
            return $r;
        });
    }
 
    /**
     * Untuk key (PONo+OP+mif) yang MASUK 1 halaman, kelompokkan per
     * mif/koneksi, jalankan query detail lengkap, bawa has_mif_duplicate
     * dari key ke baris detail, lalu agregasi (per PONo+OP+mif).
     */
    private function fetchAggregatedForKeys($pageKeys, Request $request)
    {
        $keysByMif = $pageKeys->groupBy('mif');
 
        $allDetailRows = collect();
 
        foreach ($keysByMif as $mif => $keysForMif) {
            $connection = $this->resolveConnection($mif);
            $rows = $this->fetchDetailRowsForKeys($connection, (int) $mif, $keysForMif, $request);
            $allDetailRows = $allDetailRows->concat($rows);
        }
 
        // Bawa flag has_mif_duplicate dari key ke setiap baris detail
        // (match by PONo+OP+mif).
        $dupLookup = $pageKeys->keyBy(fn ($k) => $k->POno . '|' . $k->OP . '|' . $k->mif);
        foreach ($allDetailRows as $row) {
            $lookupKey = $row->POno . '|' . $row->OP . '|' . $row->mif;
            $row->has_mif_duplicate = (bool) ($dupLookup[$lookupKey]->has_mif_duplicate ?? false);
        }
 
        return $this->aggregateByPoOp($allDetailRows);
    }
 
    /**
     * Query detail LENGKAP -- SAMA pola dengan Stok Sisa, TAPI qualifying
     * condition-nya "ADA bj dengan grade A-C" (bukan SUM(pack.pcs)>0).
     */
    private function fetchDetailRowsForKeys(string $connection, int $mif, $keys, Request $request)
    {
        $bj = DB::connection($connection)->table('bj')
            ->selectRaw("
                popk,
                SUM(pcs)  AS transfer_pcs,
                SUM(pcsk) AS keluar_pcs,
                MAX(tglin)  AS tglin,
                MAX(tglout) AS tglout,
                MAX(status) AS status
            ")
            ->groupBy('popk');
 
        $pack = DB::connection($connection)->table('pack')
            ->selectRaw('popk, SUM(pcs) AS packing_pcs')
            ->groupBy('popk');
 
        // FIX UTAMA: qualifying condition -- ADA baris bj dengan grade
        // A-C (SAMA seperti native), BUKAN lagi SUM(pack.pcs)>0.
        $qualifyingBj = DB::connection($connection)->table('bj')
            ->select('popk')
            ->whereBetween('grade', ['A', 'C'])
            ->groupBy('popk');
 
        $query = DB::connection($connection)->table('po')
            ->joinSub($qualifyingBj, 'qbj', function ($join) {
                $join->on('po.popk', '=', 'qbj.popk');
            })
            ->leftJoinSub($bj, 'bj', function ($join) {
                $join->on('po.popk', '=', 'bj.popk');
            })
            ->leftJoinSub($pack, 'pack', function ($join) {
                $join->on('po.popk', '=', 'pack.popk');
            })
            ->where('po.mif', $mif)
            ->selectRaw("
                po.popk,
                po.moppk,
                po.POno,
                po.poref,
                po.OP,
                po.customer,
                po.season,
                po.style,
                po.material,
                po.buyer,
                po.qty,
                po.secsz,
                po.silhouette,
                po.shipdate1,
                po.mif,
 
                bj.tglin,
                bj.tglout,
                bj.status,
                COALESCE(bj.transfer_pcs, 0) AS transfer,
                COALESCE(bj.keluar_pcs, 0)   AS keluar,
                COALESCE(pack.packing_pcs,0) AS packing,
 
                (COALESCE(bj.transfer_pcs,0) - COALESCE(pack.packing_pcs,0)) AS balance
            ")
            ->where(function ($outer) use ($keys) {
                foreach ($keys as $k) {
                    $outer->orWhere(function ($inner) use ($k) {
                        $inner->where('po.OP', $k->OP);
 
                        if ($k->POno !== null && $k->POno !== '') {
                            $inner->where('po.POno', $k->POno);
                        } else {
                            $inner->where(function ($qq) {
                                $qq->whereNull('po.POno')->orWhere('po.POno', '');
                            });
                        }
                    });
                }
            });
 
        $rows = $query
            ->orderByDesc('po.shipdate1')
            ->orderByDesc('po.OP')
            ->orderByDesc('po.POno')
            ->orderByDesc('po.material')
            ->get();
 
        $popks = $rows->pluck('popk')->filter()->unique()->values();
 
        // ---- Polibag/Transfer = Manual (bj) + Barcode (output jnspk=4) ----
        $barcodeTransferMap = collect();
        if ($popks->isNotEmpty()) {
            $barcodeTransferMap = DB::connection('mysql_polibag')
                ->table('output')
                ->whereIn('popk', $popks)
                ->where('jnspk', 4)
                ->groupBy('popk')
                ->selectRaw('popk, SUM(jmlpcs) as total_barcode')
                ->pluck('total_barcode', 'popk');
        }
 
        foreach ($rows as $row) {
            $barcodeQty = (int) ($barcodeTransferMap[$row->popk] ?? 0);
            $row->transfer = (float) $row->transfer + $barcodeQty;
            $row->balance  = $row->transfer - $row->packing;
        }
 
        // ---- Transfer to Finishing = Manual (tfpb) + Barcode (jnspk=10) ----
        $this->addFinishingToRows($rows);
 
        // ---- Loading & RQ (mysql_sop) ----
        $moppks = $rows->pluck('moppk')->filter()->unique()->values();
 
        $loadingMap = collect();
        $rMap = collect();
        $qMap = collect();
 
        if ($moppks->isNotEmpty()) {
            $loadingMap = DB::connection('mysql_sop')->table('sop')
                ->select('moppk')->selectRaw('SUM(tot) as qty')
                ->whereIn('moppk', $moppks)
                ->groupBy('moppk')
                ->pluck('qty', 'moppk');
 
            $rMap = DB::connection('mysql_sop')->table('r')
                ->select('moppk')->selectRaw('SUM(tot) as qty')
                ->whereIn('moppk', $moppks)
                ->groupBy('moppk')
                ->pluck('qty', 'moppk');
 
            $qMap = DB::connection('mysql_sop')->table('q')
                ->select('moppk')->selectRaw('SUM(tot) as qty')
                ->whereIn('moppk', $moppks)
                ->groupBy('moppk')
                ->pluck('qty', 'moppk');
        }
 
        foreach ($rows as $row) {
            $row->loading = (float) ($loadingMap[$row->moppk] ?? 0);
            $row->rq      = (float) ($rMap[$row->moppk] ?? 0) + (float) ($qMap[$row->moppk] ?? 0);
        }
 
        return $rows;
    }
 
    private function addFinishingToRows($rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }
 
        $popks = $rows->pluck('popk')->filter()->unique()->values();
 
        if ($popks->isEmpty()) {
            foreach ($rows as $r) {
                $r->finishing = 0;
            }
            return;
        }
 
        $manualMap = DB::connection('mysql_finance_mif')
            ->table('tfpb')
            ->leftJoin('mop', 'mop.moppk', '=', 'tfpb.moppk')
            ->whereIn('mop.popk', $popks)
            ->groupBy('mop.popk')
            ->selectRaw('mop.popk, SUM(tfpb.tot) as total_manual')
            ->pluck('total_manual', 'popk');
 
        $barcodeMap = DB::connection('mysql_polibag')
            ->table('output')
            ->whereIn('popk', $popks)
            ->where('jnspk', 10)
            ->groupBy('popk')
            ->selectRaw('popk, SUM(jmlpcs) as total_barcode')
            ->pluck('total_barcode', 'popk');
 
        foreach ($rows as $r) {
            $manual  = (float) ($manualMap[$r->popk] ?? 0);
            $barcode = (float) ($barcodeMap[$r->popk] ?? 0);
            $r->finishing = $manual + $barcode;
        }
    }



    public function inputTransfer($popk, Request $request)
    {
        $mif        = $request->query('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);
    
        // FIX UTAMA: reuse breakdown yang SAMA dengan modul Transfer --
        // hasilnya: $dt, $activeSizes, $summary, $orderQty, $manualQty,
        // $manualTotalPcs, $barcodeQty, $barcodeTotalPcs, $readyQty,
        // $diffQty, $totalBalance, $finishingQty, $finishingTotalPcs.
        $breakdown = $this->getBreakdownDataTransfer($db, $popk);
        extract($breakdown);
    
        $cr = $request->cr;
    
        $lines = $db->table('line')
            ->where('mif', $mif)
            ->whereNull('stsbar')
            ->orderBy('linenm')
            ->get();
    
        // Detail Data Packing -- TETAP murni dari `bj` (BUKAN digabung
        // dengan output seperti breakdown), karena struktur & aksinya
        // (grade/status/complete/edit) memang spesifik workflow Sisa
        // Produksi, TIDAK diubah.
        $details = $db->table('bj')
            ->leftJoin('line', 'line.linepk', '=', 'bj.linepk')
            ->select('bj.*', 'line.linenm')
            ->where('bj.popk', $popk)
            ->when($cr, function ($q) use ($cr) {
                $q->where('line.linenm', $cr);
            })
            ->orderByDesc('bj.tanggal')
            ->get();
    
        $isSuper = $this->isSuperUser();
    
        return view('menu.sisa-produksi.input', compact(
            'dt', 'activeSizes', 'summary', 'lines',
            'orderQty', 'manualQty', 'manualTotalPcs',
            'barcodeQty', 'barcodeTotalPcs', 'readyQty', 'diffQty', 'totalBalance',
            'finishingQty', 'finishingTotalPcs',
            'cr', 'mif', 'connection', 'details', 'isSuper'
        ));
    }

    private function getBreakdownDataTransfer($db, $popk): array
    {
        $dt = $db->table('po')->where('popk', $popk)->first();
        if (!$dt) abort(404);
    
        $activeSizes = [];
        for ($i = 1; $i <= 40; $i++) {
            $sz = $dt->{"size$i"} ?? null;
            if (!empty($sz)) $activeSizes[$i] = $sz;
        }
    
        $sizeMap = [];
        foreach ($activeSizes as $i => $szName) {
            $sizeMap[$szName] = $i;
        }
    
        [$bjRows, $outputAggRows] = $this->getBjAndOutputRows($db, $popk, $sizeMap, null);
    
        $manualQty      = array_fill(1, 40, 0);
        $manualTotalPcs = 0;
        foreach ($bjRows as $row) {
            foreach ($activeSizes as $i => $sz) {
                $manualQty[$i] += (int) ($row->{"qty$i"} ?? 0);
            }
            $manualTotalPcs += (int) ($row->pcs ?? 0);
        }
    
        $barcodeQty      = array_fill(1, 40, 0);
        $barcodeTotalPcs = 0;
        foreach ($outputAggRows as $row) {
            foreach ($activeSizes as $i => $sz) {
                $barcodeQty[$i] += (int) ($row->{"qty$i"} ?? 0);
            }
            $barcodeTotalPcs += (int) ($row->pcs ?? 0);
        }
    
        $finishingQty      = $this->getFinishingQtyPerSizeForPopk($popk, $sizeMap);
        $finishingTotalPcs = array_sum($finishingQty);
    
        $orderQty = [];
        $readyQty = [];
        $diffQty  = [];
    
        for ($i = 1; $i <= 40; $i++) {
            $orderQty[$i] = $dt->{"qty$i"} ?? 0;
            $readyQty[$i] = $manualQty[$i] + $barcodeQty[$i];
            $diffQty[$i]  = $readyQty[$i] - $orderQty[$i];
        }
    
        $readyTotalPcs = $manualTotalPcs + $barcodeTotalPcs;
        $totalBalance  = $readyTotalPcs - ($dt->qty ?? 0);
    
        $summary = (object) ['pcs' => $manualTotalPcs];
    
        return compact(
            'dt', 'activeSizes', 'sizeMap', 'summary',
            'orderQty', 'manualQty', 'manualTotalPcs',
            'barcodeQty', 'barcodeTotalPcs', 'readyQty', 'diffQty', 'totalBalance',
            'finishingQty', 'finishingTotalPcs'
        );
    }

    private function getBjAndOutputRows($db, $popk, array $sizeMap, ?string $cr = null): array
    {
        $bjRows = $db->table('bj')
            ->leftJoin('line', 'line.linepk', '=', 'bj.linepk')
            ->select('bj.*', 'line.linenm')
            ->where('bj.popk', $popk)
            ->when($cr, fn ($q) => $q->where('line.linenm', $cr))
            ->get()
            ->map(function ($row) {
                $row->source = 'bj';
                return $row;
            });
    
        $outputRowsRaw = DB::connection('mysql_polibag')->table('output')
            ->leftJoin('line', 'line.linepk', '=', 'output.linepk')
            ->select('output.*', 'line.linenm')
            ->where('output.popk', $popk)
            ->where('output.jnspk', 4)
            ->when($cr, fn ($q) => $q->where('line.linenm', $cr))
            ->whereNotNull('output.hari')
            ->get();
    
        $outputByDate = $outputRowsRaw->groupBy(function ($row) {
            return \Illuminate\Support\Carbon::parse($row->hari)->format('Y-m-d');
        });
    
        $outputAggRows = collect();
    
        foreach ($outputByDate as $dateKey => $rowsOnDate) {
            $qty = array_fill(1, 40, 0);
            $totalPcs = 0;
    
            foreach ($rowsOnDate as $r) {
                $idx = $sizeMap[$r->size] ?? null;
                if ($idx !== null) {
                    $qty[$idx] += (int) ($r->jmlpcs ?? 0);
                }
                $totalPcs += (int) ($r->jmlpcs ?? 0);
            }
    
            $firstWaktu = $rowsOnDate->pluck('tanggal')->filter()->sort()->first();
    
            $row = (object) array_merge([
                'bjpk'    => null,
                'popk'    => $popk,
                'tanggal' => $dateKey,
                'waktu'   => $firstWaktu,
                'linenm'  => $rowsOnDate->first()->linenm ?? null,
                'linepk'  => null,
                'grade'   => null,
                'status'  => null,
                'pcs'     => $totalPcs,
                'source'  => 'output',
            ], collect($qty)->mapWithKeys(fn ($v, $i) => ["qty$i" => $v])->all());
    
            $outputAggRows->push($row);
        }
    
        return [$bjRows, $outputAggRows];
    }

    private function getFinishingQtyPerSizeForPopk($popk, array $sizeMap): array
    {
        $finishingQty = array_fill(1, 40, 0);
    
        $mopRow = DB::connection('mysql_finance_mif')->table('mop')
            ->where('popk', $popk)
            ->first();
    
        if ($mopRow) {
            $tfpbIds = DB::connection('mysql_finance_mif')->table('tfpb')
                ->where('moppk', $mopRow->moppk)
                ->pluck('tfpbpk');
    
            if ($tfpbIds->isNotEmpty()) {
                $tfpbdtRows = DB::connection('mysql_finance_mif')->table('tfpbdt')
                    ->whereIn('tfpbpk', $tfpbIds)
                    ->get(['ukuran', 'qty']);
    
                foreach ($tfpbdtRows as $r) {
                    $idx = $sizeMap[trim((string) $r->ukuran)] ?? null;
                    if ($idx !== null) {
                        $finishingQty[$idx] += (int) ($r->qty ?? 0);
                    }
                }
            }
        }
    
        $barcodeRows = DB::connection('mysql_polibag')->table('output')
            ->where('popk', $popk)
            ->where('jnspk', 10)
            ->get(['size', 'jmlpcs']);
    
        foreach ($barcodeRows as $r) {
            $idx = $sizeMap[$r->size] ?? null;
            if ($idx !== null) {
                $finishingQty[$idx] += (int) ($r->jmlpcs ?? 0);
            }
        }
    
        return $finishingQty;
    }



    public function complete(Request $request, $bjpk)
    {
        $mif        = $request->input('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db         = DB::connection($connection);

        try {
            $data = $db->table('bj')->where('bjpk', $bjpk)->first();

            if (!$data) {
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Data tidak ditemukan',
                ], 404);
            }

            $db->table('bj')->where('bjpk', $bjpk)->update([
                'status' => 2,
                'tglin'  => now(),
            ]);

            return response()->json([
                'icon'  => 'success',
                'title' => 'Data berhasil ditandai selesai',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal memproses data',
            ], 500);
        }
    }

    public function updateActual(Request $request, $bjpk)
    {
        $mif        = $request->input('mif', session('pos'));
        $connection = $this->resolveConnection($mif);
        $db         = DB::connection($connection);

        $bj = $db->table('bj')->where('bjpk', $bjpk)->first();
        if (!$bj) {
            return response()->json([
                'icon'  => 'warning',
                'title' => 'Data tidak ditemukan',
            ], 404);
        }

        $total = 0;
        for ($i = 1; $i <= 40; $i++) {
            $total += (int) $request->input("qty{$i}", 0);
        }

        $totalBaru = ($bj->pcsk ?? 0) + $total;

        if ($totalBaru > $bj->pcs) {
            return response()->json([
                'icon'  => 'error',
                'title' => 'Qty actual yang diinput melebihi sisa yang bisa diproses.',
                'errors' => [
                    'qty' => ["Maksimal tambahan yang masih bisa diinput adalah " . max(0, $bj->pcs - $bj->pcsk) . "."],
                ],
            ], 422);
        }

        $db->table('bj')->where('bjpk', $bjpk)->update([
            'tglout'     => $request->input('tglout'),
            'keterangan' => $request->input('keterangan'),
            'pcsk'       => $totalBaru,
        ]);

        return response()->json([
            'icon'  => 'success',
            'title' => 'Qty actual berhasil disimpan',
        ]);
    }

public function detailList($popk, Request $request)
{
    $page   = (int) ($request->page ?? 1);
    $rows   = (int) ($request->rows ?? 50);
    $offset = ($page - 1) * $rows;
    $cr     = $request->cr;
    $tab    = $request->input('tab', 'pending'); // 'pending' | 'received' | 'keluar'

    $mif        = $request->query('mif', session('pos'));
    $connection = $this->resolveConnection($mif);
    $db = DB::connection($connection);

    $poRow = $db->table('po')->where('popk', $popk)->first();
    $sizeMap = [];
    if ($poRow) {
        for ($i = 1; $i <= 40; $i++) {
            $szName = $poRow->{"size$i"} ?? null;
            if (!empty($szName)) {
                $sizeMap[$szName] = $i;
            }
        }
    }

    [$bjRows, $outputAggRows] = $this->getBjAndOutputRows($db, $popk, $sizeMap, $cr);

    // 1) Buang baris tanpa grade (itu data Polibag biasa, bukan Sisa Produksi).
    $bjRows = $bjRows->filter(function ($r) {
        $grade = trim((string) ($r->grade ?? ''));
        return $grade !== '';
    });

    // 2) Filter sesuai tab -- pending = SEMUA yang BUKAN status=2
    //    (termasuk NULL/0/1), received/keluar = status=2.
    $bjRows = $bjRows->filter(function ($r) use ($tab) {
        $isReceived = (int) $r->status === 2;
        if ($tab === 'pending')  return !$isReceived;
        if ($tab === 'received') return $isReceived;
        if ($tab === 'keluar')   return $isReceived;
        return true;
    })->values();

    // Baris hasil Barcode (source='output') HANYA relevan di tab 'received'.
    $outputAggRows = $tab === 'received' ? $outputAggRows : collect();

    $bjRowsSorted = $bjRows
        ->sortByDesc(fn ($r) => \Illuminate\Support\Carbon::parse($r->tanggal)->format('Y-m-d'))
        ->values();

    $outputRowsSorted = $outputAggRows
        ->sortByDesc(fn ($r) => \Illuminate\Support\Carbon::parse($r->tanggal)->format('Y-m-d'))
        ->values();

    $allRows = $bjRowsSorted->concat($outputRowsSorted)->values();

    $total = $allRows->count();
    $data  = $allRows->slice($offset, $rows)->values();

    foreach ($data as $i => $row) {
        $row->no = $offset + $i + 1;
        $pcs  = (float) ($row->pcs ?? 0);
        $pcsk = (float) ($row->pcsk ?? 0);
        $row->keluar_status = $pcsk <= 0 ? 'menunggu' : ($pcsk >= $pcs ? 'selesai' : 'sebagian');
    }

    return response()->json([
        'total' => $total,
        'rows'  => $data,
    ]);
}

    public function buyerList(Request $request)
    {
        $q = $request->q;

        $buyers = DB::table('po')
            ->select('buyer')
            ->whereNotNull('buyer')
            ->where('buyer', '<>', '')
            ->when($q, function ($query) use ($q) {
                $query->where('buyer', 'like', "%{$q}%");
            })
            ->distinct()
            ->orderBy('buyer')
            ->get()
            ->toArray();

        array_unshift($buyers, (object) [
            'buyer' => '',
            'buyer_name' => 'All Buyer'
        ]);

        foreach ($buyers as $item) {
            if (!isset($item->buyer_name)) {
                $item->buyer_name = $item->buyer;
            }
        }

        return response()->json($buyers);
    }

    public function pdf($popk)
    {
        $dt = DB::table('po')->where('popk', $popk)->first();
        if (!$dt) abort(404);

        $dt2 = DB::table('po')->where('OP', $dt->OP)->orderBy('popk')->first();

        $activeSizes = [];
        for ($i = 1; $i <= 40; $i++) {
            $size = $dt2->{"size$i"} ?? null;
            if (!empty($size)) {
                $activeSizes[$i] = $size;
            }
        }

        $qtySelectBj = collect(range(1, 40))->map(fn($i) => "SUM(qty$i) as qty$i")->implode(', ');

        $grades = DB::table('bj')
            ->select('grade')
            ->where('OP', $dt->OP)
            ->where('grade', '<>', '')
            ->distinct()
            ->orderBy('grade')
            ->pluck('grade');

        $stockByGrade = [];

        foreach ($grades as $grade) {
            $byMaterial = DB::table('bj')
                ->selectRaw("material, SUM(pcs) as pcs, {$qtySelectBj}")
                ->where('OP', $dt->OP)
                ->where('grade', $grade)
                ->groupBy('material')
                ->orderBy('material')
                ->get();

            $gradeTotal = DB::table('bj')
                ->selectRaw("SUM(pcs) as pcs, {$qtySelectBj}")
                ->where('OP', $dt->OP)
                ->where('grade', $grade)
                ->first();

            $stockByGrade[] = [
                'grade'      => $grade,
                'byMaterial' => $byMaterial,
                'total'      => $gradeTotal,
            ];
        }

        $qtyOrder = (int) DB::table('po')
            ->where('OP', $dt->OP)
            ->where('sts', 1)
            ->sum('qty');

        $qtySewing = (int) DB::table('bj')
            ->join('po', 'po.popk', '=', 'bj.popk')
            ->where('bj.OP', $dt->OP)
            ->where('po.sts', 1)
            ->sum('bj.pcs');

        $qtyExport = (int) DB::table('pack')
            ->join('po', 'po.popk', '=', 'pack.popk')
            ->where('pack.OP', $dt->OP)
            ->where('po.sts', 1)
            ->sum('pack.pcs');

        $qtySisa = $qtySewing - $qtyExport;

        $materials = DB::table('po')
            ->where('OP', $dt->OP)
            ->where('sts', 1)
            ->select('material')
            ->distinct()
            ->orderBy('material')
            ->pluck('material');

        $qtySelectPrefixed = fn($alias) => collect(range(1, 40))
            ->map(fn($i) => "SUM({$alias}.qty$i) as qty$i")
            ->implode(', ');

        $stockPerMaterial = [];
        $runningTotal = array_fill(1, 40, 0);
        $runningPcs = 0;

        foreach ($materials as $material) {
            $bjSum = DB::table('bj')
                ->join('po', 'po.popk', '=', 'bj.popk')
                ->selectRaw('SUM(bj.pcs) as pcs, ' . $qtySelectPrefixed('bj'))
                ->where('bj.material', $material)
                ->where('bj.OP', $dt->OP)
                ->where('po.sts', 1)
                ->first();

            $packSum = DB::table('pack')
                ->join('po', 'po.popk', '=', 'pack.popk')
                ->selectRaw('SUM(pack.pcs) as pcs, ' . $qtySelectPrefixed('pack'))
                ->where('pack.material', $material)
                ->where('pack.OP', $dt->OP)
                ->where('po.sts', 1)
                ->first();

            $rowQty = [];
            foreach ($activeSizes as $i => $size) {
                $v = ($bjSum->{"qty$i"} ?? 0) - ($packSum->{"qty$i"} ?? 0);
                $rowQty[$i] = $v;
                $runningTotal[$i] += $v;
            }

            $rowPcs = ($bjSum->pcs ?? 0) - ($packSum->pcs ?? 0);
            $runningPcs += $rowPcs;

            $stockPerMaterial[] = [
                'material' => $material,
                'qty'      => $rowQty,
                'pcs'      => $rowPcs,
            ];
        }

        return view('menu.sisa-produksi.pdf', compact(
            'dt',
            'dt2',
            'activeSizes',
            'stockByGrade',
            'qtyOrder',
            'qtySewing',
            'qtyExport',
            'qtySisa',
            'stockPerMaterial',
            'runningTotal',
            'runningPcs'
        ));
    }

    public function exportExcel(Request $request)
    {
        $search = $request->search;
        $buyer  = $request->buyer;
        $year   = $request->year;
    
        // Gate: PO yang punya minimal 1 record bj status>=2
        $qualifyingPopks = DB::table('bj')
            ->select('popk')
            ->whereNotNull('grade')
            ->where('grade', '<>', '')
            ->distinct();
    
        $bjAgg = DB::table('bj')
            ->selectRaw("
                popk,
                SUM(pcs)  AS pcs,
                SUM(pcsk) AS pcsk,
                SUM(CASE WHEN grade = 'A' THEN pcs ELSE 0 END) AS grade_a,
                SUM(CASE WHEN grade = 'C' THEN pcs ELSE 0 END) AS grade_c,
                MAX(tglin)  AS tglin,
                MAX(tglout) AS tglout
            ")
            ->groupBy('popk');
    
        $pack = DB::table('pack')
            ->selectRaw('popk, SUM(pcs) AS packing_pcs')
            ->groupBy('popk');

        $qualifyingGrade = DB::table('bj')
            ->select('popk')
            ->whereNotNull('grade')
            ->where('grade', '<>', '')
            ->distinct();
        
        $qualifyingPacking = DB::table('pack')
            ->select('popk')
            ->groupBy('popk')
            ->havingRaw('SUM(pcs) > 0');
        
        $query = DB::table('po')
            ->joinSub($qualifyingGrade, 'qp', function ($join) {
                $join->on('po.popk', '=', 'qp.popk');
            })
            ->joinSub($qualifyingPacking, 'qpk', function ($join) {
                $join->on('po.popk', '=', 'qpk.popk');
            })
            ->leftJoinSub($bjAgg, 'bj', function ($join) {
                $join->on('po.popk', '=', 'bj.popk');
            })
            ->leftJoinSub($pack, 'pack', function ($join) {
                $join->on('po.popk', '=', 'pack.popk');
            })
            // ->where('po.mif', 2)
            ->selectRaw("
                po.popk,
                po.moppk,
                po.POno,
                po.OP,
                po.customer,
                po.buyer,
                po.style,
                po.material,
                po.qty,
                po.silhouette,
                po.shipdate1,
    
                bj.tglin,
                bj.tglout,
                COALESCE(bj.pcs, 0)      AS pcs,
                COALESCE(bj.pcsk, 0)     AS pcsk,
                COALESCE(bj.grade_a, 0)  AS grade_a,
                COALESCE(bj.grade_c, 0)  AS grade_c,
                COALESCE(pack.packing_pcs, 0) AS packing,
    
                (COALESCE(bj.pcs,0) - COALESCE(pack.packing_pcs,0)) AS balance
            ");
    
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('po.POno', 'like', "%{$search}%")
                    ->orWhere('po.OP', 'like', "%{$search}%")
                    ->orWhere('po.poref', 'like', "%{$search}%")
                    ->orWhere('po.customer', 'like', "%{$search}%")
                    ->orWhere('po.season', 'like', "%{$search}%")
                    ->orWhere('po.style', 'like', "%{$search}%")
                    ->orWhere('po.silhouette', 'like', "%{$search}%");
            });
        }
    
        if ($buyer) {
            $query->where('po.buyer', 'like', "%{$buyer}%");
        }
    
        if ($year) {
            $query->whereRaw('YEAR(po.shipdate1) >= ?', [(int) $year]);
        }
    
        $rows = $query->orderByDesc('bj.tglin')->get();
    
        // Loading & R+Q per moppk (koneksi DB terpisah, spt getList())
        $moppks = $rows->pluck('moppk')->filter()->values();
    
        $loadingMap = collect();
        $rMap = collect();
        $qMap = collect();
    
        if ($moppks->isNotEmpty()) {
            $loadingMap = DB::connection('mysql_sop')->table('sop')
                ->select('moppk')->selectRaw('SUM(tot) as qty')
                ->whereIn('moppk', $moppks)->groupBy('moppk')->pluck('qty', 'moppk');
    
            $rMap = DB::connection('mysql_sop')->table('r')
                ->select('moppk')->selectRaw('SUM(tot) as qty')
                ->whereIn('moppk', $moppks)->groupBy('moppk')->pluck('qty', 'moppk');
    
            $qMap = DB::connection('mysql_sop')->table('q')
                ->select('moppk')->selectRaw('SUM(tot) as qty')
                ->whereIn('moppk', $moppks)->groupBy('moppk')->pluck('qty', 'moppk');
        }
    
        // Keterangan history per popk (status>=2, order tglout asc) -> digabung jadi teks
        $popks = $rows->pluck('popk')->values();
        $keteranganByPopk = collect();
    
        if ($popks->isNotEmpty()) {
            $keteranganByPopk = DB::table('bj')
                ->select('popk', 'tglout', 'keterangan')
                ->whereIn('popk', $popks)
                ->where('status', '>=', 2)
                ->orderBy('tglout')
                ->get()
                ->groupBy('popk');
        }
    
        $totals = [
            'qty' => 0, 'loading' => 0, 'rq' => 0, 'pcs' => 0, 'packing' => 0, 'balance' => 0,
        ];
    
        $exportRows = [];
    
        foreach ($rows as $row) {
            $loading = (float) ($loadingMap[$row->moppk] ?? 0);
            $rq      = (float) ($rMap[$row->moppk] ?? 0) + (float) ($qMap[$row->moppk] ?? 0);
    
            $keteranganText = '';
            if (isset($keteranganByPopk[$row->popk])) {
                $parts = [];
                foreach ($keteranganByPopk[$row->popk] as $k) {
                    if (!empty($k->keterangan)) {
                        $parts[] = ($k->tglout ?? '') . ' / ' . $k->keterangan;
                    }
                }
                $keteranganText = implode('; ', $parts);
            }
    
            $totals['qty']     += (float) $row->qty;
            $totals['loading'] += $loading;
            $totals['rq']      += $rq;
            $totals['pcs']     += (float) $row->pcs;
            $totals['packing'] += (float) $row->packing;
            $totals['balance'] += (float) $row->balance;
    
            $exportRows[] = [
                'tglin'      => $row->tglin,
                'tglout'     => $row->tglout,
                'shipdate1'  => $row->shipdate1,
                'customer'   => $row->customer,
                'POno'       => $row->POno,
                'OP'         => $row->OP,
                'buyer'      => $row->buyer,
                'style'      => $row->style,
                'material'   => $row->material,
                'qty'        => $row->qty,
                'loading'    => $loading,
                'rq'         => $rq,
                'pcs'        => $row->pcs,
                'packing'    => $row->packing,
                'balance'    => $row->balance,
                'grade_a'    => $row->grade_a,
                'grade_c'    => $row->grade_c,
                'pcsk'       => $row->pcsk,
                'silhouette' => $row->silhouette,
                'keterangan' => $keteranganText,
            ];
        }
    
        $html = view('menu.sisa-produksi.export-excel', [
            'rows'   => $exportRows,
            'totals' => $totals,
            'buyer'  => $buyer,
            'year'   => $year,
        ])->render();
    
        return response($html)
            ->header('Content-Type', 'application/vnd.ms-excel; charset=UTF-8')
            ->header('Content-Disposition', 'attachment; filename="sisa-produksi-' . now()->format('Ymd_His') . '.xls"');
    }

    public function checkBjStatus($popk, Request $request)
{
    $mif        = $request->query('mif', session('pos'));
    $connection = $this->resolveConnection($mif);
    $db = DB::connection($connection);
 
    $rows = $db->table('bj')
        ->where('popk', $popk)
        ->orderByDesc('tanggal')
        ->get(['bjpk', 'tanggal', 'grade', 'status', 'pcs', 'pcsk', 'tglin', 'tglout']);
 
    if ($rows->isEmpty()) {
        return response()->json([
            'popk'            => $popk,
            'koneksi_dipakai' => $connection,
            'kesimpulan'      => "Tidak ada baris 'bj' sama sekali untuk popk {$popk} di koneksi {$connection}. Cek apakah popk benar dan mif-nya sesuai.",
        ]);
    }
 
    $breakdown = [
        'total_baris'        => $rows->count(),
        'status_null'        => $rows->filter(fn($r) => $r->status === null)->count(),
        'status_0'           => $rows->filter(fn($r) => (int) $r->status === 0 && $r->status !== null)->count(),
        'status_1'           => $rows->filter(fn($r) => (int) $r->status === 1)->count(),
        'status_2'           => $rows->filter(fn($r) => (int) $r->status === 2)->count(),
        'akan_masuk_pending'  => $rows->filter(fn($r) => (int) $r->status !== 2)->count(),
        'akan_masuk_received' => $rows->filter(fn($r) => (int) $r->status === 2)->count(),
    ];
 
    return response()->json([
        'popk'            => $popk,
        'koneksi_dipakai' => $connection,
        'breakdown'       => $breakdown,
        'kesimpulan'      => $breakdown['akan_masuk_pending'] > 0
            ? "Ada {$breakdown['akan_masuk_pending']} baris yang SEHARUSNYA muncul di tab Menunggu Konfirmasi."
            : "TIDAK ADA baris dengan status != 2 -- semua baris sudah status=2 (sudah confirmed) atau tabel memang kosong untuk kondisi ini.",
        'rincian'         => $rows->toArray(),
    ]);
}
}
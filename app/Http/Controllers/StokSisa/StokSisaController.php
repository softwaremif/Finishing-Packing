<?php

namespace App\Http\Controllers\StokSisa;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class StokSisaController extends Controller
{
    public function index()
    {
        return view('menu.shared.transfer-index', [
            'pageConfig' => [
                'title'              => 'Daftar Data OP',
                'polibagColumnLabel' => 'Polibag',
                'finishingField'     => 'finishing',
                'showQtyColumn'      => true,
                'showPackingColumn'  => true,
                'showKeluarColumn'   => true,
                'routes' => [
                    'list'          => route('stok-sisa.list'),
                    'detailByPoOp'  => route('stok-sisa.detail-by-po-op'),
                    'modalView'     => 'menu.shared.transfer-detail-modal',
                    'navStateKey'   => 'sisaProduksiListState',
                    'inputUrlBase'  => url('/stok-sisa/input'), // BARU -- FIX UTAMA
                ],
            ],
        ]);
    }

    // FIX #1: sama pola dengan modul lain (Transfer/Packing) --
    // pisahkan koneksi berdasarkan mif.
    private function resolveConnection($mif): string
    {
        return ((int) $mif) === 1 ? 'mysql_andon' : 'mysql';
    }

    // ============================================================
    // GET LIST (index) -- FIX #1 (mif) + FIX #2 (pagination SQL-level).
    // ============================================================
    public function getList(Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;

        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        $isSuper = session('guserpk') == 34;

        if ($isSuper) {
            $keysAndon = $this->fetchGroupKeys('mysql_andon', 1, $request);
            $keysMysql = $this->fetchGroupKeys('mysql', 2, $request);
            $allKeys   = $keysAndon->concat($keysMysql);
        } else {
            $mif        = session('pos') == 1 ? 1 : 2;
            $connection = $this->resolveConnection($mif);
            $allKeys    = $this->fetchGroupKeys($connection, $mif, $request);
        }

        // FIX #2: sort & paginate di sini -- TAPI $allKeys cuma berisi
        // key ringan (POno/OP/mif), BELUM ada join bj/pack/mysql_sop
        // sama sekali. Jauh lebih murah dibanding sort atas data hasil
        // agregasi penuh seperti sebelumnya.
        $allKeys = $allKeys
            ->when(
                $sortDir === 'asc',
                fn ($c) => $c->sortBy('OP'),
                fn ($c) => $c->sortByDesc('OP')
            )
            ->values();

        $total    = $allKeys->count();
        $pageKeys = $allKeys->slice($offset, $rows)->values();

        // Detail LENGKAP (join bj/pack/mysql_sop) HANYA dijalankan untuk
        // key yang ADA di halaman ini -- bukan seluruh dataset.
        $data = $this->fetchAggregatedForKeys($pageKeys, $request);
        $this->addOrderImageToRows($data);
        foreach ($data as $i => $row) {
            $row->no = $offset + $i + 1;
        }

        return response()->json([
            'total' => $total,
            'rows'  => $data,
        ]);
    }

    /**
     * Detail SEMUA baris (per color/material, TIDAK digabung) untuk satu
     * PO+OP -- dipakai modal rincian di index. FIX: sekarang terima $mif
     * juga, supaya query lewat koneksi yang benar.
     */
    public function detailByPoOp(Request $request)
    {
        $validated = $request->validate([
            'po'  => 'nullable',
            'op'  => 'required',
            'mif' => 'nullable',
        ]);

        $po  = $validated['po'] ?? null;
        $op  = $validated['op'];
        $mif = (int) ($validated['mif'] ?? session('pos'));

        $connection = $this->resolveConnection($mif);

        $key = (object) ['POno' => $po, 'OP' => $op, 'mif' => $mif];
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
        return $collection
            ->groupBy(fn ($row) => $row->POno . '|' . $row->OP)
            ->map(function ($group) {
                $representative = clone $group->sortByDesc('popk')->first();
    
                $representative->qty       = (float) $group->sum('qty');
                $representative->loading   = (float) $group->sum('loading');
                $representative->rq        = (float) $group->sum('rq');
                $representative->transfer  = (float) $group->sum('transfer');
                $representative->finishing = (float) $group->sum('finishing'); // BARU
                $representative->packing   = (float) $group->sum('packing');
                $representative->keluar    = (float) $group->sum('keluar');
                $representative->balance   = $representative->transfer - $representative->packing;
    
                $representative->status = (int) $group->max('status');
    
                return $representative;
            })
            ->values();
    }

    /**
     * BARU (bagian dari FIX #2): query RINGAN -- cuma ambil kombinasi
     * PONo+OP unik yang match filter, TANPA join bj/pack/mysql_sop sama
     * sekali. Dipakai untuk hitung total + tentukan key mana saja yang
     * masuk ke halaman yang diminta, SEBELUM query berat dijalankan.
     */
    private function fetchGroupKeys(string $connection, int $mif, Request $request)
{
    $search = $request->search;
    $buyer  = $request->buyer;
    $year   = $request->year;
 
    $qualifyingPacking = DB::connection($connection)->table('pack')
        ->select('popk')
        ->groupBy('popk')
        ->havingRaw('SUM(pcs) > 0');
 
    // BARU: kumpulkan SEMUA popk yang Transfer to Finishing-nya > 0 --
    // manual (tfpb via mop.popk, di mysql_finance_mif) ATAU barcode
    // (output jnspk=10, di mysql_polibag). Query ini GLOBAL (tidak
    // di-scope per mif/connection), tapi cuma dijalankan 1x per request
    // (bukan per baris), jadi tetap murah.
    $popksWithManualFinishing = DB::connection('mysql_finance_mif')->table('tfpb')
        ->join('mop', 'mop.moppk', '=', 'tfpb.moppk')
        ->select('mop.popk')
        ->groupBy('mop.popk')
        ->havingRaw('SUM(tfpb.tot) > 0')
        ->pluck('popk');
 
    $popksWithBarcodeFinishing = DB::connection('mysql_polibag')->table('output')
        ->where('jnspk', 10)
        ->select('popk')
        ->groupBy('popk')
        ->havingRaw('SUM(jmlpcs) > 0')
        ->pluck('popk');
 
    $popksWithFinishing = $popksWithManualFinishing
        ->concat($popksWithBarcodeFinishing)
        ->unique()
        ->values();
 
    $query = DB::connection($connection)->table('po')
        ->joinSub($qualifyingPacking, 'qpk', function ($join) {
            $join->on('po.popk', '=', 'qpk.popk');
        })
        ->where('po.mif', $mif)
        // BARU: FIX UTAMA -- buang PO+OP yang finishing-nya masih 0/kosong.
        ->whereIn('po.popk', $popksWithFinishing->isEmpty() ? [0] : $popksWithFinishing->all())
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
     * BARU (bagian dari FIX #2): untuk key (PONo+OP+mif) yang SUDAH
     * dipastikan masuk 1 halaman, kelompokkan per mif/koneksi, jalankan
     * query detail LENGKAP (bj/pack/mysql_sop) untuk key-key itu SAJA,
     * lalu agregasi per PONo+OP.
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

        return $this->aggregateByPoOp($allDetailRows);
    }

    /**
     * Query detail LENGKAP (join bj/pack + lookup mysql_sop) -- SAMA
     * seperti fetchAll() versi lama, TAPI di-scope ke daftar key
     * (PONo+OP) tertentu saja lewat parameter $keys, BUKAN seluruh tabel.
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

        $qualifyingPacking = DB::connection($connection)->table('pack')
            ->select('popk')
            ->groupBy('popk')
            ->havingRaw('SUM(pcs) > 0');

        $query = DB::connection($connection)->table('po')
            ->joinSub($qualifyingPacking, 'qpk', function ($join) {
                $join->on('po.popk', '=', 'qpk.popk');
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
                po.ordpk,
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

        // ---- BARU: Transfer to Finishing = Manual (tfpb) + Barcode
        //      (output jnspk=10) ----
        $this->addFinishingToRows($rows);

        // ---- Loading & RQ (mysql_sop) -- TIDAK diubah ----
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

    private function addOrderImageToRows($rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }
        $gisFotoBase        = rtrim(config('services.foto.gis_base'), '/');
        $productionFotoBase = rtrim(config('services.foto.production_base'), '/');
        $sampleFotoBase     = rtrim(config('services.foto.sample_base'), '/');
        $noImageUrl = asset('public/css/images/no-img.png');
    
        foreach ($rows as $r) {
            $r->order_image = $noImageUrl;
        }
    
        $ordpks = $rows->pluck('ordpk')->filter()->unique()->values()->all();
        if (empty($ordpks)) {
            return;
        }
    
        $ordRows = DB::connection('mysql_gis')->table('ord')
            ->whereIn('ordpk', $ordpks)
            ->get(['ordpk', 'srno', 'foto', 'foto2', 'stsfoto']);
        $ordByOrdpk = $ordRows->keyBy('ordpk');
    
        $srnos = $ordRows->pluck('srno')->filter()->unique()->values()->all();
        $srpkBySrno = [];
        $fotoBySrpk = [];
        if (!empty($srnos)) {
            $reqRows = DB::connection('mysql_sample')->table('request')
                ->whereIn('srno', $srnos)
                ->get(['srno', 'srpk']);
            foreach ($reqRows as $r) {
                $srpkBySrno[$r->srno] = $r->srpk;
            }
            $srpks = array_values(array_unique(array_values($srpkBySrno)));
            if (!empty($srpks)) {
                $statusRows = DB::connection('mysql_sample')->table('status')
                    ->whereIn('srpk', $srpks)
                    ->orderByDesc('statuspk')
                    ->get(['srpk', 'foto', 'statuspk']);
                foreach ($statusRows as $r) {
                    if (!isset($fotoBySrpk[$r->srpk])) {
                        $fotoBySrpk[$r->srpk] = $r->foto;
                    }
                }
            }
        }
    
        foreach ($rows as $r) {
            $ord = $ordByOrdpk->get($r->ordpk);
            if (!$ord) {
                continue;
            }
            $stsfoto = $ord->stsfoto ?? null;
            $foto1   = $ord->foto ?? null;
            $srno    = $ord->srno ?? null;
            $srpk  = $srno ? ($srpkBySrno[$srno] ?? null) : null;
            $foto2 = $srpk ? ($fotoBySrpk[$srpk] ?? null) : ($ord->foto2 ?? null);
    
            if ($stsfoto == 1) {
                $r->order_image = !empty($foto1) ? "{$gisFotoBase}/{$foto1}" : $noImageUrl;
            } elseif ($stsfoto == 2) {
                $r->order_image = !empty($foto1) ? "{$productionFotoBase}/{$foto1}" : $noImageUrl;
            } else {
                $r->order_image = !empty($foto2) ? "{$sampleFotoBase}/{$foto2}" : $noImageUrl;
            }
        }
    }
}
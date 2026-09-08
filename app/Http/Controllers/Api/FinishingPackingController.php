<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class FinishingPackingController extends Controller
{
    // ===============================================================
    //  KONEKSI & TRANSLASI POPK (root cause fix: mop.popk NULL utk mif=1)
    // ===============================================================

    /** mif 1 = mysql_andon (host .11), mif 2 = mysql (host .19 / kanonik). */
    private function resolveConnection(int $mif): string
    {
        return $mif === 1 ? 'mysql_andon' : 'mysql';
    }

    /**
     * mop.popk SELALU merujuk ke popk versi 'mysql' (database 19/kanonik),
     * APA PUN mif aslinya. po dari 'mysql_andon' (mif=1) punya nomor popk
     * SENDIRI yang beda -- TIDAK PERNAH cocok langsung ke mop.popk atau
     * output.popk.
     *
     * Kunci penghubung antar database adalah 'bdownpk' (SAMA PERSIS di
     * kedua database untuk baris logis yang sama).
     *
     * @param  array<int>  $mif1Popks  popk dari mysql_andon
     * @return array<int, int>  [popk_mif1 => popk_kanonik]
     */
    private function resolveCanonicalPopks(array $mif1Popks): array
    {
        if (empty($mif1Popks)) {
            return [];
        }

        $bdownpkByPopk = DB::connection('mysql_andon')->table('po')
            ->whereIn('popk', $mif1Popks)
            ->pluck('bdownpk', 'popk');

        $bdownpks = $bdownpkByPopk->filter()->unique()->values()->all();
        if (empty($bdownpks)) {
            return [];
        }

        $canonicalByBdownpk = DB::connection('mysql')->table('po')
            ->whereIn('bdownpk', $bdownpks)
            ->pluck('popk', 'bdownpk');

        $result = [];
        foreach ($bdownpkByPopk as $mif1Popk => $bdownpk) {
            if ($bdownpk !== null && isset($canonicalByBdownpk[$bdownpk])) {
                $result[$mif1Popk] = $canonicalByBdownpk[$bdownpk];
            }
        }

        return $result;
    }

    /**
     * Resolve $dt (baris po) + $mop (baris mop) yang BENAR dari popk+mif.
     * TIDAK PERNAH cari 'mop' lewat 'popk' langsung (mop.popk NULL utk
     * mif=1) -- selalu cari 'po' dulu (dari koneksi sesuai mif), ambil
     * 'moppk'-nya, baru cari 'mop' lewat 'moppk' (SAMA PERSIS di kedua
     * database).
     *
     * @return array{0: object, 1: object, 2: string} [$dt, $mop, $connection]
     */
    private function resolvePoAndMop($popk, int $mif): array
    {
        $connection = $this->resolveConnection($mif);
        $dt = DB::connection($connection)->table('po')->where('popk', $popk)->first();
        abort_unless($dt, 404, "Data po untuk popk {$popk} (mif={$mif}) tidak ditemukan.");

        $mop = DB::connection('mysql_finance_mif')->table('mop')
            ->where('moppk', $dt->moppk)
            ->first();
        abort_unless($mop, 404, "Data mop untuk moppk {$dt->moppk} tidak ditemukan.");

        return [$dt, $mop, $connection];
    }

    // ===============================================================
    //  1) SEMUA DATA -- getListGlobal()
    // ===============================================================

    /**
     * API global: SELALU gabung mysql_andon (mif 1) + mysql (mif 2),
     * TANPA syarat superuser/session. Agregasi berdasarkan OP SAJA
     * (bukan POno+OP+poref), supaya OP yang sama muncul di KEDUA
     * database ikut DIJUMLAHKAN jadi satu baris.
     */
    public function getListGlobal(Request $request)
    {
        $page    = (int) ($request->page ?? 1);
        $rows    = (int) ($request->rows ?? 50);
        $offset  = ($page - 1) * $rows;
        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        $rowsAndon = $this->fetchAll('mysql_andon', 1, $request);
        $rowsMysql = $this->fetchAll('mysql', 2, $request);

        // Semua proses per-baris dijalankan TERPISAH per koneksi SEBELUM
        // concat -- popk/ordpk yang collide antar database tidak pernah
        // dicampur dalam satu query.
        $this->addRQToRows($rowsAndon);
        $this->addRQToRows($rowsMysql);
        $this->addTransferFinishingToRows($rowsAndon);
        $this->addTransferFinishingToRows($rowsMysql);
        $this->addOrderImageToRows($rowsAndon);
        $this->addOrderImageToRows($rowsMysql);

        $combined = $rowsAndon->concat($rowsMysql);

        $aggregated = $this->applyPostAggregationFilters(
            $this->aggregateByOpGlobal($combined),
            $request
        )
            ->filter(fn ($r) => (float) ($r->transfer ?? 0) > 0)
            ->values();

        foreach ($aggregated as $r) {
            $r->gac_sort_ts = $this->normalizeGacForSort($r->GAC);
        }

        $aggregated = $aggregated
            ->when(
                $sortDir === 'asc',
                fn ($c) => $c->sortBy('gac_sort_ts'),
                fn ($c) => $c->sortByDesc('gac_sort_ts')
            )
            ->values();

        $total = $aggregated->count();
        $data  = $aggregated->slice($offset, $rows)->values();

        foreach ($data as $i => $row) {
            $row->no = $offset + $i + 1;
        }

        return response()->json([
            'total' => $total,
            'rows'  => $data,
        ]);
    }

    // ===============================================================
    //  2/3) BY OP, BY OP+PO -- getByOp() / getDetail()
    // ===============================================================

    /**
     * Lookup detail 3-lapis (summary/po_list/detail) khusus by OP,
     * boleh dipersempit dengan 'po'. Response SAMA persis dengan
     * getDetail(), cuma 'op' wajib diisi di sini.
     */
    public function getByOp(Request $request)
    {
        $validated = $request->validate([
            'op'         => 'required|string|max:50',
            'po'         => 'nullable|string|max:50',
            'with_daily' => 'nullable|boolean',
        ]);
        $op        = trim($validated['op']);
        $po        = isset($validated['po']) ? trim($validated['po']) : null;
        $withDaily = (bool) ($validated['with_daily'] ?? false);

        $rowsAndon = $this->fetchAllByOpOrPo('mysql_andon', 1, $op, $po);
        $rowsMysql = $this->fetchAllByOpOrPo('mysql', 2, $op, $po);

        $this->addRQToRows($rowsAndon);
        $this->addRQToRows($rowsMysql);
        $this->addTransferFinishingToRows($rowsAndon);
        $this->addTransferFinishingToRows($rowsMysql);

        $combined = $rowsAndon->concat($rowsMysql);

        if ($combined->isEmpty()) {
            return response()->json([
                'op'    => $op,
                'po'    => $po,
                'found' => false,
                'data'  => null,
            ], 404);
        }

        return response()->json($this->buildOpDetailedResponse($combined, $op, $withDaily));
    }

    /**
     * Lookup detail GENERIK: terima 'op' ATAU 'po' (salah satu wajib).
     */
    public function getDetail(Request $request)
    {
        $validated = $request->validate([
            'op'         => 'nullable|string|max:50',
            'po'         => 'nullable|string|max:50',
            'with_daily' => 'nullable|boolean',
        ]);
        $op        = $validated['op'] ?? null;
        $po        = $validated['po'] ?? null;
        $withDaily = (bool) ($validated['with_daily'] ?? false);

        if (empty($op) && empty($po)) {
            return response()->json([
                'message' => 'Isi salah satu: parameter "op" atau "po".',
            ], 422);
        }

        $rowsAndon = $this->fetchAllByOpOrPo('mysql_andon', 1, $op, $po);
        $rowsMysql = $this->fetchAllByOpOrPo('mysql', 2, $op, $po);

        $this->addRQToRows($rowsAndon);
        $this->addRQToRows($rowsMysql);
        $this->addTransferFinishingToRows($rowsAndon);
        $this->addTransferFinishingToRows($rowsMysql);

        $combined = $rowsAndon->concat($rowsMysql);

        if ($combined->isEmpty()) {
            return response()->json([
                'op'    => $op,
                'po'    => $po,
                'found' => false,
                'data'  => null,
            ], 404);
        }

        $resolvedOp = $op ?? trim((string) $combined->first()->OP);

        return response()->json($this->buildOpDetailedResponse($combined, $resolvedOp, $withDaily));
    }

    /**
     * Susun response 3 LAPIS dari $combined (per baris = 1 kombinasi
     * PO+material+secsz):
     *   1) 'summary'  -- total level OP
     *   2) 'po_list'  -- subtotal per PO
     *   3) 'detail'   -- rincian per material/secsz (+ 'daily' kalau diminta)
     */
    private function buildOpDetailedResponse($combined, string $op, bool $withDaily = false): array
    {
        $poGroups = $combined->groupBy(fn ($r) => trim((string) $r->POno));

        $poList = $poGroups->map(function ($rowsForPo, $poNo) use ($withDaily) {
            $detail = $rowsForPo->map(function ($r) use ($withDaily) {
                $item = [
                    'popk'               => (int) $r->popk,
                    'material'           => $r->material,
                    'secsz'              => $r->secsz ?: null,
                    'customer'           => $r->customer,
                    'qty'                => (int) $r->qty,
                    'r_q'                => (int) $r->transfer,
                    'transfer_finishing' => (int) $r->transfer_finishing,
                    'balance'            => (int) $r->transfer_finishing - (int) $r->qty,
                    'mif'                => (int) $r->mif,
                ];

                // Kalau with_daily=1 diminta, tempelkan riwayat harian --
                // WAJIB kirim mif juga (popk-nya bisa dari mysql_andon).
                if ($withDaily) {
                    $daily = $this->buildDailyDetailForPopk($r->popk, (int) $r->mif);
                    $item['daily'] = $daily['rows'] ?? [];
                }

                return $item;
            })
                ->sortBy(fn ($d) => $d['material'] . '|' . $d['secsz'])
                ->values();

            $rep = $rowsForPo->sortByDesc('popk')->first();

            $qty      = (int) $rowsForPo->sum('qty');
            $rq       = (int) $rowsForPo->sum('transfer');
            $transfer = (int) $rowsForPo->sum('transfer_finishing');

            return [
                'POno'               => $poNo,
                'buyer'              => $rep->buyer,
                'GAC'                => $rep->GAC,
                'mif'                => (int) $rep->mif,
                'qty'                => $qty,
                'r_q'                => $rq,
                'transfer_finishing' => $transfer,
                'balance'            => $transfer - $qty,
                'material_count'     => $detail->count(),
                'detail'             => $detail,
            ];
        })
            ->sortByDesc(fn ($p) => $p['qty'])
            ->values();

        $repOp    = $combined->sortByDesc('popk')->first();
        $totalQty = (int) $combined->sum('qty');
        $totalRq  = (int) $combined->sum('transfer');
        $totalTf  = (int) $combined->sum('transfer_finishing');

        return [
            'op'    => $op,
            'found' => true,
            'summary' => [
                'OP'                       => $op,
                'buyer'                    => $repOp->buyer ?? null,
                'GAC'                      => $repOp->GAC ?? null,
                'total_qty'                => $totalQty,
                'total_r_q'                => $totalRq,
                'total_transfer_finishing' => $totalTf,
                'total_balance'            => $totalTf - $totalQty,
                'po_count'                 => $poList->count(),
            ],
            'po_list' => $poList,
        ];
    }

    // ===============================================================
    //  4) DETAIL HARIAN -- getDailyDetail() / buildDailyDetailForPopk()
    // ===============================================================

    /**
     * Endpoint standalone: GET /tf-finishing/{popk}/daily-detail?mif=1
     * WAJIB kirim 'mif' kalau popk-nya dari mysql_andon (mif=1) --
     * tanpa itu, popk mif=1 tidak akan pernah ketemu mop-nya.
     */
    public function getDailyDetail($popk, Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;
        $filterLinepk = $request->cr;
        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $mif = (int) ($request->query('mif', 2)); // default 2 (kompatibel mundur)

        $result = $this->buildDailyDetailForPopk($popk, $mif, $filterLinepk, $sortDir);
        abort_unless($result !== null, 404, "Data mop untuk popk {$popk} (mif={$mif}) tidak ditemukan.");

        $allRows = collect($result['rows']);
        $total   = $allRows->count();
        $paged   = $allRows->slice($offset, $rows)->values();

        return response()->json([
            'popk'  => (int) $popk,
            'mif'   => $mif,
            'secsz' => $result['secsz'],
            'sizes' => $result['sizes'],
            'total' => $total,
            'rows'  => $paged,
        ]);
    }

    /**
     * Bangun riwayat harian (manual + barcode) untuk 1 popk+mif --
     * TANPA pagination/HTTP response, dipakai standalone endpoint DAN
     * buildOpDetailedResponse() (with_daily=1).
     *
     * GANTI TOTAL -- FIX UTAMA: SEKARANG terima $mif, dan cari mop lewat
     * po.moppk (BUKAN cari mop lewat popk langsung) -- supaya popk dari
     * mysql_andon (mif=1) bisa ketemu mop-nya dengan benar.
     */
    private function buildDailyDetailForPopk($popk, int $mif, $filterLinepk = null, string $sortDir = 'desc'): ?array
    {
        $connection = $this->resolveConnection($mif);
        $dt = DB::connection($connection)->table('po')->where('popk', $popk)->first();
        if (!$dt) {
            return null;
        }

        $mop = DB::connection('mysql_finance_mif')->table('mop')
            ->where('moppk', $dt->moppk)
            ->first();
        if (!$mop) {
            return null;
        }

        $fin = DB::connection('mysql_finance_mif');

        [$sizes, $canonicalMap,] = $this->getDedupedSizes($mop->moppk);
        $normalize = fn ($v) => mb_strtoupper(trim((string) $v));
        $ukuranToMopdtpk = $sizes->pluck('mopdtpk', 'ukuran')
            ->mapWithKeys(fn ($mopdtpk, $ukuran) => [$normalize($ukuran) => $mopdtpk])
            ->all();
        $mopdtpkToUkuran = $sizes->pluck('ukuran', 'mopdtpk')->all();

        // ---- MANUAL (tfpb) -- via moppk, SAMA di kedua database. ----
        $tfpbRows = $fin->table('tfpb')
            ->where('moppk', $mop->moppk)
            ->when($filterLinepk, fn ($q) => $q->where('linepk', $filterLinepk))
            ->get();
        $tfpbpks = $tfpbRows->pluck('tfpbpk')->values();
        $tfpbdtRows = $tfpbpks->isNotEmpty()
            ? $fin->table('tfpbdt')->whereIn('tfpbpk', $tfpbpks)->orderBy('mopdtpk')->get()
            : collect();
        $tfpbdtByTfpbpk = $tfpbdtRows->groupBy('tfpbpk');
        $manualRows = $tfpbRows->map(function ($row) use ($tfpbdtByTfpbpk, $canonicalMap, $ukuranToMopdtpk, $normalize) {
            $sizesForRow = $tfpbdtByTfpbpk->get($row->tfpbpk, collect());
            $flat = [];
            foreach ($sizesForRow as $d) {
                $ukuranKey = $normalize($d->ukuran);
                $targetMopdtpk = $ukuranToMopdtpk[$ukuranKey] ?? ($canonicalMap[$d->mopdtpk] ?? $d->mopdtpk);
                $key = "qty_{$targetMopdtpk}";
                if ($d->qty === null) {
                    if (!array_key_exists($key, $flat)) {
                        $flat[$key] = null;
                    }
                    continue;
                }
                $flat[$key] = ($flat[$key] ?? 0) + $d->qty;
            }
            return (object) array_merge([
                'tfpbpk'  => $row->tfpbpk,
                'tanggal' => $row->tgl,
                'waktu'   => $row->jam,
                'linenm'  => $row->line,
                'linepk'  => $row->linepk,
                'pcs'     => $row->tot,
                'source'  => 'manual',
            ], $flat);
        });

        // ---- BARCODE (output jnspk=10) -- join 'po' DI DALAM koneksi
        // mysql_polibag sendiri (host 19) -- moppk di situ OTOMATIS
        // kanonik, TIDAK PERLU translasi tambahan. ----
        $barcodeRowsRaw = DB::connection('mysql_polibag')->table('output')
            ->leftJoin('po', 'po.popk', '=', 'output.popk')
            ->leftJoin('line', 'line.linepk', '=', 'output.linepk')
            ->where('po.moppk', $mop->moppk)
            ->where('output.linepk', '>', 0)
            ->where('output.jnspk', 10)
            ->when($filterLinepk, fn ($q) => $q->where('output.linepk', $filterLinepk))
            ->select('output.*', 'line.linenm')
            ->get();
        $barcodeByDate = $barcodeRowsRaw->groupBy(function ($row) {
            return Carbon::parse($row->hari ?? $row->tanggal)->format('Y-m-d');
        });
        $barcodeRows = collect();
        foreach ($barcodeByDate as $dateKey => $rowsOnDate) {
            $flat = [];
            $totalPcs = 0;
            foreach ($rowsOnDate as $r) {
                $mopdtpk = $this->findMopdtpkBySizeText((string) $r->size, $sizes);
                if ($mopdtpk !== null) {
                    $flat["qty_{$mopdtpk}"] = ($flat["qty_{$mopdtpk}"] ?? 0) + (float) ($r->jmlpcs ?? 0);
                }
                $totalPcs += (float) ($r->jmlpcs ?? 0);
            }
            $barcodeRows->push((object) array_merge([
                'tfpbpk'  => null,
                'tanggal' => $dateKey,
                'waktu'   => $rowsOnDate->first()->tanggal ?? null,
                'linenm'  => $rowsOnDate->first()->linenm ?? null,
                'linepk'  => null,
                'pcs'     => $totalPcs,
                'source'  => 'barcode',
            ], $flat));
        }

        $allRows = $manualRows->concat($barcodeRows)
            ->sortBy(
                fn ($r) => Carbon::parse($r->tanggal)->format('Y-m-d'),
                SORT_REGULAR,
                $sortDir === 'desc'
            )
            ->values();

        $formattedRows = $allRows->map(function ($row, $i) use ($mopdtpkToUkuran) {
            $sizeBreakdown = [];
            foreach ($mopdtpkToUkuran as $mopdtpk => $ukuran) {
                $key = "qty_{$mopdtpk}";
                $sizeBreakdown[] = [
                    'mopdtpk' => (int) $mopdtpk,
                    'ukuran'  => $ukuran,
                    'qty'     => property_exists($row, $key) ? $row->{$key} : null,
                ];
            }
            return [
                'no'      => $i + 1,
                'tfpbpk'  => $row->tfpbpk,
                'tanggal' => $row->tanggal,
                'waktu'   => $row->waktu,
                'linenm'  => $row->linenm,
                'linepk'  => $row->linepk,
                'pcs'     => $row->pcs,
                'source'  => $row->source,
                'sizes'   => $sizeBreakdown,
            ];
        })->values();

        return [
            'secsz' => $mop->secsz ?? null,
            'sizes' => $sizes->map(fn ($s) => [
                'mopdtpk' => (int) $s->mopdtpk,
                'ukuran'  => $s->ukuran,
            ])->values(),
            'total' => $formattedRows->count(),
            'rows'  => $formattedRows,
        ];
    }

    // ===============================================================
    //  LOOKUP RINGAN (autocomplete)
    // ===============================================================

    public function lookupList(Request $request)
    {
        $q     = trim((string) $request->query('q', ''));
        $limit = min(100, max(1, (int) $request->query('limit', 20)));

        $result = collect();
        foreach (['mysql_andon' => 1, 'mysql' => 2] as $connection => $mif) {
            $query = DB::connection($connection)->table('po')
                ->where('sts', 0)
                ->where('qty', '>', 0)
                ->where('mif', $mif)
                ->select('POno', 'OP', 'buyer', 'customer', 'GAC')
                ->distinct();

            if ($q !== '') {
                $query->where(function ($sub) use ($q) {
                    $sub->where('POno', 'like', "%{$q}%")
                        ->orWhere('OP', 'like', "%{$q}%")
                        ->orWhere('buyer', 'like', "%{$q}%")
                        ->orWhere('customer', 'like', "%{$q}%");
                });
            }

            $rows = $query->limit($limit)->get();
            foreach ($rows as $r) {
                $r->mif = $mif;
            }
            $result = $result->concat($rows);
        }

        $grouped = $result
            ->groupBy(fn ($r) => trim((string) $r->OP))
            ->map(function ($rows, $op) {
                $first = $rows->first();
                return [
                    'OP'       => $op,
                    'buyer'    => $first->buyer,
                    'customer' => $first->customer,
                    'GAC'      => $first->GAC,
                    'po_list'  => $rows->pluck('POno')->unique()->values()->all(),
                ];
            })
            ->take($limit)
            ->values();

        return response()->json([
            'query'   => $q,
            'total'   => $grouped->count(),
            'results' => $grouped,
        ]);
    }

    // ===============================================================
    //  QUERY DASAR (fetchAll / fetchAllByOpOrPo) -- attach canonical_popk
    // ===============================================================

    private function fetchAll(string $connection, int $mif, Request $request)
    {
        $query = DB::connection($connection)->table('po')
            ->leftJoin('bj as bj_line', 'bj_line.popk', '=', 'po.popk')
            ->leftJoin('line', 'line.linepk', '=', 'bj_line.linepk')
            ->where('po.sts', 0)
            ->where('po.qty', '>', 0)
            ->where('po.mif', $mif)
            ->selectRaw("
                po.popk, po.ordpk, po.moppk, po.POno, po.poref, po.OP, po.customer, po.season,
                po.style, po.material, po.buyer, po.qty, po.mif, po.secsz, po.GAC, po.silhouette,
                GROUP_CONCAT(
                    DISTINCT TRIM(SUBSTRING(line.linenm,6,3))
                    ORDER BY line.linenm
                    SEPARATOR ';'
                ) AS linenm
            ")
            ->groupBy(
                'po.popk', 'po.ordpk', 'po.moppk', 'po.POno', 'po.poref', 'po.OP',
                'po.customer', 'po.season', 'po.style', 'po.material', 'po.buyer',
                'po.qty', 'po.mif', 'po.secsz', 'po.GAC', 'po.silhouette'
            );

        if ($request->filled('search')) {
            $search = trim($request->search);
            $query->where(function ($q) use ($search) {
                $q->where('po.POno', 'like', "%{$search}%")
                    ->orWhere('po.OP', 'like', "%{$search}%")
                    ->orWhere('po.poref', 'like', "%{$search}%")
                    ->orWhere('po.customer', 'like', "%{$search}%")
                    ->orWhere('po.season', 'like', "%{$search}%")
                    ->orWhere('po.material', 'like', "%{$search}%")
                    ->orWhere('po.secsz', 'like', "%{$search}%")
                    ->orWhere('po.silhouette', 'like', "%{$search}%")
                    ->orWhere('po.style', 'like', "%{$search}%");
            });
        }
        if ($request->filled('buyer')) {
            $query->where('po.buyer', $request->buyer);
        }
        if ($request->filled('year')) {
            $year = (int) $request->year;
            $shortYear = $year - 2000;
            $query->whereRaw("LEFT(TRIM(po.OP),2) = ?", [sprintf('%02d', $shortYear)]);
        }
        if ($request->filled('ex_factory')) {
            $range = $this->resolveExFactoryRange($request->ex_factory);
            if ($range) {
                $query->whereBetween('po.GAC', [
                    $range['start']->format('Y-m-d 00:00:00'),
                    $range['end']->format('Y-m-d 23:59:59'),
                ]);
            }
        }

        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';
        $rows = $query->orderBy('po.popk', $sortDir)->get();

        $this->attachCanonicalPopk($rows, $connection);

        return $this->filterRowsWithMop($rows);
    }

    private function fetchAllByOpOrPo(string $connection, int $mif, ?string $op, ?string $po)
    {
        $query = DB::connection($connection)->table('po')
            ->leftJoin('bj as bj_line', 'bj_line.popk', '=', 'po.popk')
            ->leftJoin('line', 'line.linepk', '=', 'bj_line.linepk')
            ->where('po.sts', 0)
            ->where('po.qty', '>', 0)
            ->where('po.mif', $mif)
            ->selectRaw("
                po.popk, po.ordpk, po.moppk, po.POno, po.poref, po.OP, po.customer, po.season,
                po.style, po.material, po.buyer, po.qty, po.mif, po.secsz, po.GAC, po.silhouette,
                GROUP_CONCAT(
                    DISTINCT TRIM(SUBSTRING(line.linenm,6,3))
                    ORDER BY line.linenm
                    SEPARATOR ';'
                ) AS linenm
            ")
            ->groupBy(
                'po.popk', 'po.ordpk', 'po.moppk', 'po.POno', 'po.poref', 'po.OP',
                'po.customer', 'po.season', 'po.style', 'po.material', 'po.buyer',
                'po.qty', 'po.mif', 'po.secsz', 'po.GAC', 'po.silhouette'
            );

        if (!empty($op)) {
            $query->where('po.OP', trim($op));
        }
        if (!empty($po)) {
            $query->where('po.POno', trim($po));
        }

        $rows = $query->get();

        $this->attachCanonicalPopk($rows, $connection);

        return $this->filterRowsWithMop($rows);
    }

    /**
     * Tempel 'canonical_popk' ke tiap baris -- untuk mysql_andon (mif=1),
     * terjemahkan popk lewat bdownpk; untuk mysql (mif=2), popk-nya
     * SENDIRI sudah kanonik.
     */
    private function attachCanonicalPopk($rows, string $connection): void
    {
        if ($rows->isEmpty()) {
            return;
        }

        if ($connection === 'mysql_andon') {
            $popks = $rows->pluck('popk')->unique()->values()->all();
            $canonicalMap = $this->resolveCanonicalPopks($popks);
            foreach ($rows as $r) {
                $r->canonical_popk = $canonicalMap[$r->popk] ?? null;
            }
        } else {
            foreach ($rows as $r) {
                $r->canonical_popk = $r->popk;
            }
        }
    }

    // ===============================================================
    //  DEDUP SIZE, MATCH SIZE TEXT
    // ===============================================================

    private function getDedupedSizes($moppk): array
    {
        $fin = DB::connection('mysql_finance_mif');
        $sizesRaw = $fin->table('mopdt')
            ->where('moppk', $moppk)
            ->orderBy('mopdtpk')
            ->get(['mopdtpk', 'ukuran', 'qty']);
        $normalize = fn ($v) => trim(mb_strtoupper((string) $v));
        $groupedByUkuran = $sizesRaw->groupBy(fn ($s) => $normalize($s->ukuran));
        $sizes = $groupedByUkuran
            ->map(fn ($group) => $group->sortBy('mopdtpk')->first())
            ->sortBy('mopdtpk')
            ->values();
        $canonicalMap = [];
        foreach ($groupedByUkuran as $group) {
            $representative = $group->sortBy('mopdtpk')->first()->mopdtpk;
            foreach ($group as $row) {
                $canonicalMap[$row->mopdtpk] = $representative;
            }
        }
        $mopdtpksByUkuran = $groupedByUkuran->map(fn ($g) => $g->pluck('mopdtpk')->values());
        return [$sizes, $canonicalMap, $mopdtpksByUkuran];
    }

    private function findMopdtpkBySizeText(string $sizeText, $sizes)
    {
        foreach ($sizes as $s) {
            $ukuran = trim((string) $s->ukuran);
            if ($ukuran !== '' && stripos($sizeText, $ukuran) === 0) {
                return $s->mopdtpk;
            }
        }
        return null;
    }

    // ===============================================================
    //  AGREGASI & FILTER
    // ===============================================================

    private function aggregateByOpGlobal($collection)
    {
        return $collection
            ->groupBy(fn ($row) => trim((string) $row->OP))
            ->map(function ($group) {
                $representative = clone $group->sortByDesc('popk')->first();
                $representative->qty                = (int) $group->sum('qty');
                $representative->transfer            = (int) $group->sum('transfer');
                $representative->transfer_finishing  = (int) $group->sum('transfer_finishing');
                $representative->balance             = $representative->transfer_finishing - $representative->qty;
                $representative->po_list = $group->pluck('POno')->filter()->unique()->values()->all();
                $representative->po_count = count($representative->po_list);
                return $representative;
            })
            ->values();
    }

    /** GANTI TOTAL -- FIX UTAMA: pakai 'moppk', BUKAN popk (mop.popk NULL utk mif=1). */
    private function filterRowsWithMop($rows)
    {
        if ($rows->isEmpty()) {
            return $rows;
        }

        $moppks = $rows->pluck('moppk')->filter()->unique()->values()->all();
        if (empty($moppks)) {
            return $rows->filter(fn () => false)->values();
        }

        $moppksWithMop = DB::connection('mysql_finance_mif')
            ->table('mop')
            ->whereIn('moppk', $moppks)
            ->pluck('moppk')
            ->unique()
            ->flip();

        return $rows->filter(fn ($r) => isset($moppksWithMop[$r->moppk]))->values();
    }

    private function applyPostAggregationFilters($rows, Request $request)
    {
        if ($request->filled('year')) {
            $year = (int) $request->year;
            $shortYear = sprintf('%02d', $year - 2000);
            $rows = $rows->filter(function ($r) use ($shortYear) {
                return strtoupper(substr(trim((string) $r->OP), 0, 2)) === $shortYear;
            });
        }
        if ($request->filled('ex_factory')) {
            $range = $this->resolveExFactoryRange($request->ex_factory);
            if ($range) {
                $start = $range['start']->format('Y-m-d');
                $end   = $range['end']->format('Y-m-d');
                $rows = $rows->filter(function ($r) use ($start, $end) {
                    $gac = !empty($r->GAC) ? substr((string) $r->GAC, 0, 10) : null;
                    return $gac && $gac !== '0000-00-00' && $gac >= $start && $gac <= $end;
                });
            }
        }
        if ($request->filled('buyer')) {
            $buyer = $request->buyer;
            $rows = $rows->filter(fn ($r) => (string) $r->buyer === (string) $buyer);
        }
        return $rows->values();
    }

    // ===============================================================
    //  R+Q & TRANSFER FINISHING -- pakai canonical_popk utk output
    // ===============================================================

    /**
     * GANTI TOTAL -- FIX UTAMA: kelompokkan popk KANONIK (bukan popk
     * mentah) per orderKey -- popk kanonik inilah yang cocok dengan
     * output.popk. Assign balik TETAP pakai orderKey dari ordpk/OP/POno
     * (SUDAH konsisten lintas database).
     */
    private function addRQToRows($rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }
        $normalize = fn ($v) => trim(mb_strtoupper((string) $v));

        $popksByOrderKey = $rows
            ->groupBy(fn ($r) => implode('|', [$r->ordpk, $r->OP, $r->POno]))
            ->map(function ($group) {
                return $group
                    ->map(fn ($r) => $r->canonical_popk ?? $r->popk)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();
            });

        $allPopks = $popksByOrderKey->flatten()->unique()->values();

        if ($allPopks->isEmpty()) {
            foreach ($rows as $r) {
                $r->transfer = 0;
            }
            return;
        }

        $outputRows = DB::connection('mysql_polibag')->table('output')
            ->whereIn('popk', $allPopks)
            ->whereIn('jnspk', [2, 6])
            ->where('linepk', '>', 0)
            ->where('statuspk', '>', 0)
            ->get(['popk', 'material', 'jmlpcs']);

        $orderKeyByPopk = [];
        foreach ($popksByOrderKey as $orderKey => $popksInGroup) {
            foreach ($popksInGroup as $p) {
                $orderKeyByPopk[$p] = $orderKey;
            }
        }

        $rqByOrderMaterial = [];
        foreach ($outputRows as $o) {
            $orderKey = $orderKeyByPopk[$o->popk] ?? null;
            if ($orderKey === null) {
                continue;
            }
            $key = $orderKey . '||' . $normalize($o->material);
            $rqByOrderMaterial[$key] = ($rqByOrderMaterial[$key] ?? 0) + (float) $o->jmlpcs;
        }

        foreach ($rows as $r) {
            $orderKey = implode('|', [$r->ordpk, $r->OP, $r->POno]);
            $key = $orderKey . '||' . $normalize($r->material);
            $r->transfer = (int) ($rqByOrderMaterial[$key] ?? 0);
        }
    }

    /**
     * GANTI TOTAL -- FIX UTAMA: manual (tfpb) via 'moppk' (SAMA di kedua
     * database), barcode (output) via 'canonical_popk'.
     */
    private function addTransferFinishingToRows($rows): void
    {
        if ($rows->isEmpty()) {
            return;
        }
        foreach ($rows as $r) {
            $r->transfer_finishing = 0;
        }

        $moppks = $rows->pluck('moppk')->filter()->unique()->values()->all();
        $manualByMoppk = [];
        if (!empty($moppks)) {
            $manualByMoppk = DB::connection('mysql_finance_mif')
                ->table('tfpb')
                ->whereIn('moppk', $moppks)
                ->groupBy('moppk')
                ->selectRaw('moppk, SUM(tot) as total_manual')
                ->pluck('total_manual', 'moppk')
                ->all();
        }

        $canonicalPopks = $rows->map(fn ($r) => $r->canonical_popk ?? $r->popk)->filter()->unique()->values()->all();
        $barcodeByCanonicalPopk = [];
        if (!empty($canonicalPopks)) {
            $barcodeByCanonicalPopk = DB::connection('mysql_polibag')
                ->table('output')
                ->whereIn('popk', $canonicalPopks)
                ->where('jnspk', 10)
                ->groupBy('popk')
                ->selectRaw('popk, SUM(jmlpcs) as total_barcode')
                ->pluck('total_barcode', 'popk')
                ->all();
        }

        foreach ($rows as $r) {
            $manual = (int) ($manualByMoppk[$r->moppk] ?? 0);
            $canonicalPopk = $r->canonical_popk ?? $r->popk;
            $barcode = (int) ($barcodeByCanonicalPopk[$canonicalPopk] ?? 0);
            $r->transfer_finishing = $manual + $barcode;
        }
    }

    // ===============================================================
    //  FOTO ORDER
    // ===============================================================

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

        try {
            $ordRows = DB::connection('mysql_gis')->table('ord')
                ->whereIn('ordpk', $ordpks)
                ->get(['ordpk', 'srno', 'foto', 'foto2', 'stsfoto']);
        } catch (\Throwable $e) {
            report($e);
            return; // server foto down -- semua tetap no-image, TIDAK crash
        }

        $ordByOrdpk = $ordRows->keyBy('ordpk');

        $srnos = $ordRows->pluck('srno')->filter()->unique()->values()->all();
        $srpkBySrno = [];
        $fotoBySrpk = [];
        if (!empty($srnos)) {
            try {
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
            } catch (\Throwable $e) {
                report($e);
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

    // ===============================================================
    //  HELPER TANGGAL
    // ===============================================================

    private function normalizeGacForSort($gac): int
    {
        if (empty($gac)) {
            return 0;
        }
        $gacStr = (string) $gac;
        if ($gacStr === '0000-00-00' || $gacStr === '0000-00-00 00:00:00') {
            return 0;
        }
        try {
            return Carbon::parse($gacStr)->timestamp;
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function resolveExFactoryRange(string $preset): ?array
    {
        $today = Carbon::today();
        switch ($preset) {
            case 'today':
                return ['start' => $today->copy(), 'end' => $today->copy()];
            case 'this_week':
                return ['start' => $today->copy()->startOfWeek(), 'end' => $today->copy()->endOfWeek()];
            case 'next_2_weeks':
                return ['start' => $today->copy(), 'end' => $today->copy()->addWeeks(2)];
            case 'this_month':
                return ['start' => $today->copy()->startOfMonth(), 'end' => $today->copy()->endOfMonth()];
            case 'next_month':
                return [
                    'start' => $today->copy()->addMonthNoOverflow()->startOfMonth(),
                    'end'   => $today->copy()->addMonthNoOverflow()->endOfMonth(),
                ];
            case 'next_3_months':
                return ['start' => $today->copy(), 'end' => $today->copy()->addMonths(3)];
            case 'next_6_months':
                return ['start' => $today->copy(), 'end' => $today->copy()->addMonths(6)];
            case 'this_year':
                return ['start' => $today->copy()->startOfYear(), 'end' => $today->copy()->endOfYear()];
            default:
                return null;
        }
    }
}
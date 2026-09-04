<?php

namespace App\Http\Controllers\Transfer;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class TransferController extends Controller
{
    public function index()
    {
        return view('menu.transfer.index');
    }

    public function getList(Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;

        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        $isSuper = session('guserpk') == 34;

        if ($isSuper) {
            $rowsAndon = $this->fetchAll('mysql_andon', 1, $request);
            $rowsMysql = $this->fetchAll('mysql', 2, $request);

            $combined = $rowsAndon->concat($rowsMysql);
        } else {
            $mif        = session('pos') == 1 ? 1 : 2;
            $connection = session('pos') == 1 ? 'mysql_andon' : 'mysql';

            $combined = $this->fetchAll($connection, $mif, $request);
        }

        // PENTING: ini yang berubah -- sebelumnya cuma unique() (pilih SATU
        // baris representatif per PO+OP, qty/transfer/balance-nya jadi milik
        // baris itu doang). Sekarang benar-benar di-SUM dari SEMUA baris
        // (color/secsz) yang tergabung dalam PO+OP yang sama.
        $aggregated = $this->aggregateByPoOp($combined)
            ->when(
                $sortDir === 'asc',
                fn ($c) => $c->sortBy('popk'),
                fn ($c) => $c->sortByDesc('popk')
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

    private function aggregateByPoOp($collection)
    {
        return $collection
            ->groupBy(fn ($row) => $row->POno . '|' . $row->OP)
            ->map(function ($group) {
                $representative = clone $group->sortByDesc('popk')->first();

                $representative->qty         = (int) $group->sum('qty');
                $representative->transfer    = (int) $group->sum('transfer');
                $representative->checked_qty = (int) $group->sum('checked_qty');
                $representative->balance     = $representative->transfer
                    - $representative->checked_qty
                    - $representative->qty;

                return $representative;
            })
            ->values();
    }

    private function fetchAll(string $connection, int $mif, Request $request)
    {
        $bj = DB::connection($connection)->table('bj')
            ->selectRaw("
                popk,
                SUM(CASE WHEN check2 = 0 THEN pcs ELSE 0 END) AS transfer,
                SUM(CASE WHEN check2 = 1 THEN pcs ELSE 0 END) AS checked_qty
            ")
            ->groupBy('popk');

        $query = DB::connection($connection)->table('po')
            ->leftJoinSub($bj, 'bj', function ($join) {
                $join->on('po.popk', '=', 'bj.popk');
            })
            ->leftJoin('bj as bj_line', 'bj_line.popk', '=', 'po.popk')
            ->leftJoin('line', 'line.linepk', '=', 'bj_line.linepk')
            ->where('po.sts', 0)
            ->where('po.qty', '>', 0)
            ->where('po.mif', $mif)
            ->selectRaw("
                po.popk,
                po.POno,
                po.poref,
                po.OP,
                po.customer,
                po.season,
                po.style,
                po.material,
                po.buyer,
                po.qty,
                po.mif,
                po.secsz,
                po.silhouette,

                GROUP_CONCAT(
                    DISTINCT TRIM(SUBSTRING(line.linenm,6,3))
                    ORDER BY line.linenm
                    SEPARATOR ';'
                ) AS linenm,

                COALESCE(bj.transfer,0) AS transfer,
                COALESCE(bj.checked_qty,0) AS checked_qty,

                (
                    COALESCE(bj.transfer,0)
                    - COALESCE(bj.checked_qty,0)
                    - po.qty
                ) AS balance
            ")
            ->groupBy(
                'po.popk',
                'po.POno',
                'po.poref',
                'po.OP',
                'po.customer',
                'po.season',
                'po.style',
                'po.material',
                'po.buyer',
                'po.qty',
                'po.mif',
                'po.secsz',
                'po.silhouette',
                'bj.transfer',
                'bj.checked_qty'
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

        // Parameter sort dari tombol toggle di komponen table-default.
        // Default 'desc' (Terbaru), sesuai data-value bawaan tombolnya.
        $sortDir = $request->input('sort', 'desc') === 'asc' ? 'asc' : 'desc';

        return $query->orderBy('po.popk', $sortDir)->get();
    }

    private function resolveConnection($mif): string
    {
        return ((int) $mif) === 1 ? 'mysql_andon' : 'mysql';
    }

    public function inputTransfer($popk, Request $request)
    {
        $mif        = $request->query('mif', session('pos'));
        $connection = $this->resolveConnection($mif);

        $dt = DB::connection($connection)->table('po')->where('popk', $popk)->first();
        if (!$dt) abort(404);

        $activeSizes = [];
        for ($i = 1; $i <= 40; $i++) {
            $size = $dt->{"size$i"} ?? null;
            if (!empty($size)) {
                $activeSizes[$i] = $size;
            }
        }

        $cr = $request->cr;

        // ================= SUMMARY =================
        $summary = DB::connection($connection)->table('bj')
            ->selectRaw(
                "popk, SUM(pcs) as pcs, " .
                    collect(range(1, 40))->map(fn($i) => "SUM(qty$i) as qty$i")->implode(', ')
            )
            ->where('popk', $popk)
            ->first();

        $check = DB::connection($connection)->table('bj')
            ->selectRaw("SUM(pcs) as pcs")
            ->where('popk', $popk)
            ->where('check2', 1)
            ->first();

        $bjpk = $request->bjpk;

        $edit = null;
        if ($bjpk) {
            $edit = DB::connection($connection)->table('bj')
                ->where('bjpk', $bjpk)
                ->first();
        }

        $lines = DB::connection($connection)->table('line')
            ->where('mif', $mif)
            ->whereNull('stsbar')
            ->orderBy('linenm')->get();

        // ============================================================
        // Opsi filter Line untuk komponen table-default (dipakai di
        // atribut data-dg-options pada select filter "cr" di halaman
        // menu.transfer.input). Format: array asosiatif {value, text},
        // siap di-json_encode via @json() di blade.
        // ============================================================
        $lineFilterOptions = collect([['value' => '', 'text' => 'Semua Line']])
            ->concat($lines->map(fn ($l) => ['value' => $l->linenm, 'text' => $l->linenm]))
            ->values();

        // ================= DETAIL FIXED =================
        $details = DB::connection($connection)->table('bj')
            ->leftJoin('line', 'line.linepk', '=', 'bj.linepk')
            ->select(
                'bj.*',
                'line.linenm'
            )
            ->where('bj.popk', $popk)
            ->when($cr, function ($q) use ($cr) {
                $q->where('line.linenm', $cr);
            })
            ->orderByDesc('bj.tanggal')
            ->get();

        // ================= SIZE ARRAY =================
        $orderQty = [];
        $readyQty = [];
        $diffQty  = [];
        for ($i = 1; $i <= 40; $i++) {
            $orderQty[$i] = $dt->{"qty$i"} ?? 0;
            $readyQty[$i] = $summary->{"qty$i"} ?? 0;
            $diffQty[$i]  = $readyQty[$i] - $orderQty[$i];
        }
        $totalBalance = ($summary->pcs ?? 0) - $dt->qty;

        return view('menu.transfer.input', compact(
            'dt',
            'activeSizes',
            'summary',
            'edit',
            'lines',
            'lineFilterOptions',
            'orderQty',
            'readyQty',
            'diffQty',
            'totalBalance',
            'cr',
            'details',
            'mif',
            'connection'
        ));
    }

    public function saveTransfer(Request $request)
    {
        // FIX (baru): grade WAJIB diisi khusus untuk guserpk 35, selain itu
        // tetap opsional seperti sebelumnya.
        $gradeRule = session('guserpk') == 35 ? 'required|in:A,B,C' : 'nullable|in:A,B,C';

        $validator = Validator::make(
            $request->all(),
            [
                'popk'    => 'required',
                'tanggal' => 'required|date',
                'linepk'  => 'required',
                'grade'   => $gradeRule,
            ],
            [
                'tanggal.required' => 'Tanggal masuk harus diisi.',
                'tanggal.date'     => 'Format tanggal tidak valid.',
                'linepk.required'  => 'Line harus dipilih.',
                'grade.required'   => 'Grade wajib diisi.',
                'grade.in'         => 'Grade harus salah satu dari A, B, atau C.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'icon'   => 'error',
                'title'  => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $mif        = $request->input('mif', session('pos'));
        $connection = $this->resolveConnection($mif);

        DB::connection($connection)->beginTransaction();
        try {
            $tanggal = Carbon::parse($request->tanggal)->format('Y-m-d');
            $data = [
                'popk'    => $request->popk,
                'POno'    => $request->po,
                'OP'      => $request->op,
                'linepk'  => $request->linepk,
                'tanggal' => $tanggal,
                'grade'   => $request->filled('grade') ? $request->grade : null,
                'check2'  => 0,
                'waktu'   => now(),
            ];

            $total = 0;
            $hasValue = false;
            for ($i = 1; $i <= 40; $i++) {
                $qty = $request->filled("qty{$i}") ? (int) $request->input("qty{$i}") : 0;

                if ($qty > 0) {
                    $data["qty{$i}"] = $qty;
                    $total += $qty;
                    $hasValue = true;
                } else {
                    $data["qty{$i}"] = null;
                }
            }
            $data['pcs'] = $hasValue ? $total : null;

            if ($request->filled('bjpk')) {
                DB::connection($connection)->table('bj')
                    ->where('bjpk', $request->bjpk)
                    ->update($data);
                $msg = 'Data berhasil diupdate';
            } else {
                DB::connection($connection)->table('bj')->insert($data);
                $msg = 'Data berhasil disimpan';
            }

            DB::connection($connection)->commit();

            return response()->json([
                'icon'  => 'success',
                'title' => $msg,
            ]);
        } catch (\Throwable $e) {
            DB::connection($connection)->rollBack();

            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menyimpan data.',
                'text'  => config('app.debug')
                    ? $e->getMessage()
                    : 'Terjadi kesalahan pada sistem.',
            ], 500);
        }
    }

    public function delete($bjpk, Request $request)
    {
        // PENTING: mif dikirim dari frontend (query string ?mif=...) supaya
        // tahu baris bjpk ini ada di koneksi mana. bjpk juga TIDAK unik
        // lintas 2 database, sama seperti popk.
        $mif        = $request->query('mif', session('pos'));
        $connection = $this->resolveConnection($mif);

        try {
            $data = DB::connection($connection)->table('bj')->where('bjpk', $bjpk)->first();

            if (!$data) {
                return response()->json([
                    'icon'  => 'warning',
                    'title' => 'Data tidak ditemukan',
                ], 404);
            }

            DB::connection($connection)->table('bj')->where('bjpk', $bjpk)->delete();

            return response()->json([
                'icon'  => 'success',
                'title' => 'Data berhasil dihapus',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'icon'  => 'error',
                'title' => 'Gagal menghapus data',
            ], 500);
        }
    }

    public function breakdownSummary($popk, Request $request)
    {
        // PENTING: sama seperti method lain, koneksi mengikuti mif milik
        // popk ini, bukan session('pos') user yang login.
        $mif        = $request->query('mif', session('pos'));
        $connection = $this->resolveConnection($mif);

        $dt = DB::connection($connection)->table('po')->where('popk', $popk)->first();
        if (!$dt) abort(404);

        $activeSizes = [];
        for ($i = 1; $i <= 40; $i++) {
            $size = $dt->{"size$i"} ?? null;
            if (!empty($size)) {
                $activeSizes[$i] = $size;
            }
        }

        $summary = DB::connection($connection)->table('bj')
            ->selectRaw(
                "popk, SUM(pcs) as pcs, " .
                    collect(range(1, 40))->map(fn($i) => "SUM(qty$i) as qty$i")->implode(', ')
            )
            ->where('popk', $popk)
            ->first();

        $orderQty = [];
        $readyQty = [];
        $diffQty = [];
        for ($i = 1; $i <= 40; $i++) {
            $orderQty[$i] = $dt->{"qty$i"} ?? 0;
            $readyQty[$i] = $summary->{"qty$i"} ?? 0;
            $diffQty[$i] = $readyQty[$i] - $orderQty[$i];
        }

        $totalBalance = ($summary->pcs ?? 0) - $dt->qty;

        return view('menu.transfer.partials.breakdown_summary', compact(
            'dt',
            'activeSizes',
            'summary',
            'orderQty',
            'readyQty',
            'diffQty',
            'totalBalance'
        ));
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

    public function detailList($popk, Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;
        $cr     = $request->cr;

        $mif        = $request->query('mif', session('pos'));
        $connection = $this->resolveConnection($mif);

        $query = DB::connection($connection)->table('bj')
            ->leftJoin('line', 'line.linepk', '=', 'bj.linepk')
            ->select('bj.*', 'line.linenm')
            ->where('bj.popk', $popk)
            ->when($cr, function ($q) use ($cr) {
                $q->where('line.linenm', $cr);
            });

        $total = $query->count();

        $data = $query
            ->orderByDesc('bj.tanggal')
            ->offset($offset)
            ->limit($rows)
            ->get();

        foreach ($data as $i => $row) {
            $row->no = $offset + $i + 1;
        }

        $hasShipped = DB::connection($connection)->table('pack')
            ->where('popk', $popk)
            ->where('status', 5)
            ->exists();

        return response()->json([
            'total'       => $total,
            'rows'        => $data,
            'has_shipped' => $hasShipped,
        ]);
    }

    public function detailByPoOp(Request $request)
    {
        $validated = $request->validate([
            'po'  => 'nullable',
            'op'  => 'required',
            'mif' => 'nullable',
        ]);

        $po = $validated['po'] ?? null;
        $op = $validated['op'];

        $mif        = $validated['mif'] ?? session('pos');
        $connection = $this->resolveConnection($mif);

        $rows = $this->fetchAll($connection, (int) $mif, $request)
            ->where('POno', $po)
            ->where('OP', $op)
            ->values();

        // Gabung HANYA kalau Color (material) + Secondary Size (secsz) +
        // License PO Ref (poref) + Place (customer) SAMA PERSIS. Kalau ada
        // satu saja yang beda, baris tetap terpisah (tidak digabung).
        $grouped = $rows
            ->groupBy(function ($r) {
                return implode('|', [
                    $r->material,
                    $r->secsz,
                    $r->poref,
                    $r->customer,
                ]);
            })
            ->map(function ($group) {
                $representative = clone $group->first();

                $representative->qty         = (int) $group->sum('qty');
                $representative->transfer    = (int) $group->sum('transfer');
                $representative->checked_qty = (int) $group->sum('checked_qty');
                $representative->balance     = $representative->transfer
                    - $representative->checked_qty
                    - $representative->qty;

                return $representative;
            })
            ->sortBy('material')
            ->values();

        return response()->json([
            'total' => $grouped->count(),
            'rows'  => $grouped,
        ]);
    }
}

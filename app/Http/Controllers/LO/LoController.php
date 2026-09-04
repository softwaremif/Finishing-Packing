<?php
namespace App\Http\Controllers\LO;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;

class LoController extends Controller
{
    private function resolveConnection($mif): string
    {
        return ((int) $mif) === 1 ? 'mysql_andon' : 'mysql';
    }

    private function currentMif(): int
    {
        return session('pos') == 1 ? 1 : 2;
    }

    private function isSuperUser(): bool
    {
        return session('guserpk') == 34;
    }

    /**
     * Cari 1 baris 'lo' by lopk -- coba koneksi sesuai $mifHint dulu
     * (kalau dikirim), baru fallback coba KEDUA koneksi kalau tidak
     * ketemu / tidak dikirim. Return null kalau tidak ketemu di manapun.
     */
    private function findLo($lopk, $mifHint = null): ?array
    {
        $tryOrder = $mifHint !== null ? [(int) $mifHint] : [];
        foreach ([2, 1] as $m) {
            if (!in_array($m, $tryOrder, true)) $tryOrder[] = $m;
        }

        foreach ($tryOrder as $mif) {
            $connection = $this->resolveConnection($mif);
            $lo = DB::connection($connection)->table('lo')->where('lopk', $lopk)->first();
            if ($lo) {
                $lo->mif = $mif;
                return [$lo, $connection, $mif];
            }
        }
        return null;
    }

    // ============================================================
    // INDEX
    // ============================================================
    public function index(Request $request)
    {
        return view('menu.shared.lo-index', [
            'pageConfig' => [
                'mode'            => 'kirim',
                'title'           => 'Daftar LO - Kirim Sisa ke Gudang',
                'showAddButton'   => true,
                'showStatusFilter'=> true,
                'routes' => [
                    'list'          => route('lo.list'),
                    'detailByLopk'  => route('lo.detail', ['lopk' => '__LOPK__']),
                    'availableItems'=> route('lo.available-items'),
                    'store'         => route('lo.store'),
                    'cancelBase'    => url('/kirim-sisa'),
                    'modalCreate'   => 'menu.lo.modal-create',
                ],
            ],
        ]);
    }

    public function indexGudang(Request $request)
    {
        return view('menu.shared.lo-index', [
            'pageConfig' => [
                'mode'            => 'terima',
                'title'           => 'Daftar LO - Terima Sisa ke Gudang',
                'showAddButton'   => false,
                'showStatusFilter'=> false,
                'routes' => [
                    'list'          => route('lo.gudang.list'),
                    'detailByLopk'  => route('lo.gudang.detail', ['lopk' => '__LOPK__']),
                    'availableItems'=> null,
                    'store'         => null,
                    'cancelBase'    => null,
                    'modalCreate'   => null,
                ],
            ],
        ]);
    }

    private function buildNoLo($lopk, $mif, $lodate): string
    {
        $romanMonths = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        $date = \Carbon\Carbon::parse($lodate);
        $month = $romanMonths[$date->month - 1];
        return sprintf('%04d/MSK-PRD/MIF%d/%s/%d', $lopk, $mif, $month, $date->year);
    }
    
    private function buildNoOutsisa($outpk, $mif, $tglout): string
    {
        $romanMonths = ['I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII'];
        $date = \Carbon\Carbon::parse($tglout);
        $month = $romanMonths[$date->month - 1];
        return sprintf('%04d/KLR-PRD/MIF%d/%s/%d', $outpk, $mif, $month, $date->year);
    }
    
    // ============================================================
    // helper canEditLo() -- edit/batalkan HANYA kalau BELUM ADA
    // approval yang masuk sama sekali (ketiganya masih NULL).
    // ============================================================
    private function canEditLo($lo): bool
    {
        return $lo->stsapv1 === null && $lo->stsapv2 === null && $lo->stsapv3 === null;
    }

    /**
     * getList() -- MODE KIRIM. FIX UTAMA: 'lo' sekarang di 2 database --
     * loop per koneksi (super user = keduanya, user biasa = mif sendiri
     * saja -- SAMA pola dengan availableItems()), gabung, sort, paginate
     * di memori.
     */
    public function getList(Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;
        $lopkSearch = $request->input('lopk');
        $status     = $request->input('status');
    
        $mifsToQuery = $this->isSuperUser() ? [1, 2] : [$this->currentMif()];
        $allRows = collect();
    
        foreach ($mifsToQuery as $mif) {
            $db = DB::connection($this->resolveConnection($mif));
    
            $rows_ = $db->table('lo')
                ->where('mif', $mif)
                ->when($lopkSearch, fn($q) => $q->where('lopk', 'like', "%{$lopkSearch}%"))
                ->when($status, function ($q) use ($status) {
                    switch ($status) {
                        case 'pending1': $q->whereNull('stsapv1'); break;
                        case 'pending2': $q->whereNull('stsapv2'); break;
                        case 'pending3': $q->whereNull('stsapv3'); break;
                        case 'approved':
                            $q->where('stsapv1', 1)->where('stsapv2', 1)->where('stsapv3', 1);
                            break;
                        case 'rejected':
                            $q->where(function ($qq) {
                                $qq->where('stsapv1', 0)->orWhere('stsapv2', 0)->orWhere('stsapv3', 0);
                            });
                            break;
                    }
                })
                ->get();
    
            foreach ($rows_ as $lo) {
                $lo->mif = $mif;
            }
    
            $allRows = $allRows->concat($rows_);
        }
    
        $allRows = $allRows->sortByDesc('lopk')->values();
        $total = $allRows->count();
        $data  = $allRows->slice($offset, $rows)->values();
    
        foreach ($data as $lo) {
            $db = DB::connection($this->resolveConnection($lo->mif));
            $lo->jumlah_item  = $db->table('lodt')->where('lopk', $lo->lopk)->count();
            $lo->status_label = $this->resolveStatusLabel($lo);
            $lo->no_lo        = $this->buildNoLo($lo->lopk, $lo->mif, $lo->lodate); // BARU
            $lo->can_edit     = $this->canEditLo($lo); // BARU
        }
    
        return response()->json(['total' => $total, 'rows' => $data]);
    }

    private function resolveStatusLabel($lo): string
    {
        $labels = [1 => 'Manager', 2 => 'PPIC', 3 => 'Purchasing'];
        $rejected = [];
        $pending = [];
        $approvedCount = 0;
    
        foreach ([1, 2, 3] as $lvl) {
            $val = $lo->{"stsapv{$lvl}"};
            if ($this->isRejected($val)) $rejected[] = $labels[$lvl];
            elseif ((int) $val === 1) $approvedCount++;
            else $pending[] = $labels[$lvl];
        }
    
        if (!empty($rejected)) {
            return 'Ditolak (' . implode(', ', $rejected) . ')';
        }
        if ($approvedCount === 3) {
            return 'Selesai (Approved)';
        }
        if (empty($pending)) {
            return 'Selesai (Approved)';
        }
        return 'Menunggu Approve ' . implode(', ', $pending);
    }

    private function isRejected($val): bool
    {
        return $val !== null && (int) $val === 0;
    }

    /**
     * AJAX modal Buat LO -- TIDAK berubah dari versi sebelumnya (sudah
     * benar loop per koneksi + super user).
     */
    public function availableItems(Request $request)
    {
        $isSuper = $this->isSuperUser();
        $search  = $request->search;
        $mifsToQuery = $isSuper ? [1, 2] : [$this->currentMif()];

        $qtyColumns  = collect(range(1, 40))->map(fn($i) => "bj.qty{$i}")->implode(', ');
        $sizeColumns = collect(range(1, 40))->map(fn($i) => "po.size{$i}")->implode(', ');

        $allRows = collect();

        foreach ($mifsToQuery as $mif) {
            $db = DB::connection($this->resolveConnection($mif));

            // FIX UTAMA: 'lodt'/'lo' SEKARANG di koneksi $db juga (BUKAN
            // DB::table() default lagi).
            $lockedBjpks = $db->table('lodt')
                ->join('lo', 'lo.lopk', '=', 'lodt.lopk')
                ->where('lo.mif', $mif) // BARU -- konsistensi, sama alasan getList/getListGudang
                ->pluck('lodt.bjpk');

            $rows = $db->table('bj')
                ->join('po', 'po.popk', '=', 'bj.popk')
                ->where('po.mif', $mif)
                ->whereBetween('bj.grade', ['A', 'C'])
                ->whereNull('bj.tglin')
                ->whereNotIn('bj.bjpk', $lockedBjpks->isEmpty() ? [0] : $lockedBjpks->all())
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($qq) use ($search) {
                        $qq->where('po.POno', 'like', "%{$search}%")
                            ->orWhere('po.OP', 'like', "%{$search}%")
                            ->orWhere('po.buyer', 'like', "%{$search}%")
                            ->orWhere('bj.material', 'like', "%{$search}%");
                    });
                })
                ->selectRaw("
                    bj.bjpk, bj.popk, bj.grade, bj.pcs, bj.tanggal,
                    po.POno, po.OP, po.poref, po.customer, po.buyer, po.material, po.secsz,
                    {$qtyColumns}, {$sizeColumns}
                ")
                ->orderByDesc('bj.tanggal')
                ->get();

            foreach ($rows as $row) {
                $row->mif = $mif;
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

            $allRows = $allRows->concat($rows);
        }

        $grouped = $allRows->groupBy(fn($r) => $r->mif . '|' . $r->POno . '|' . $r->OP)
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'POno'  => $first->POno,
                    'OP'    => $first->OP,
                    'buyer' => $first->buyer,
                    'mif'   => $first->mif,
                    'items' => $items->values(),
                ];
            })->values();

        return response()->json(['groups' => $grouped]);
    }

    /**
     * STORE -- FIX UTAMA: insert 'lo'/'lodt' SEKARANG ke koneksi mif
     * yang sama dengan 'bj' (BUKAN default lagi), transaksi juga di
     * koneksi itu.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'keterangan' => 'nullable|string|max:250',
            'bjpks'      => 'required|array|min:1',
            'bjpks.*'    => 'integer',
        ]);

        $mif        = $this->currentMif();
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);

        $bjpks = collect($validated['bjpks'])->unique()->values();

        $validBjpks = $db->table('bj')
            ->whereIn('bjpk', $bjpks)
            ->whereBetween('grade', ['A', 'C'])
            ->pluck('bjpk');

        $alreadyLocked = $db->table('lodt')
            ->join('lo', 'lo.lopk', '=', 'lodt.lopk')
            ->where('lo.mif', $mif) // BARU
            ->whereIn('lodt.bjpk', $validBjpks)
            ->pluck('lodt.bjpk');

        $finalBjpks = $validBjpks->diff($alreadyLocked)->values();

        if ($finalBjpks->isEmpty()) {
            return response()->json([
                'icon'  => 'warning',
                'title' => 'Semua item yang dipilih sudah tidak valid (mungkin sudah terkunci LO lain atau grade berubah). Silakan refresh dan pilih ulang.',
            ], 422);
        }

        try {
            $newLopk = DB::connection($connection)->transaction(function () use ($db, $validated, $finalBjpks, $mif) {
                $newLopk = (int) ($db->table('lo')->lockForUpdate()->max('lopk')) + 1;
                $db->table('lo')->insert([
                    'lopk'       => $newLopk,
                    'mif'        => $mif,
                    'lodate'     => now(),
                    'keterangan' => $validated['keterangan'] ?? null,
                    'stsapv1'    => null,
                    'stsapv2'    => null,
                    'stsapv3'    => null,
                ]);
            
                $newLodtpk = (int) ($db->table('lodt')->lockForUpdate()->max('lodtpk'));
                $insertRows = [];
                foreach ($finalBjpks as $bjpk) {
                    $newLodtpk++;
                    $insertRows[] = ['lodtpk' => $newLodtpk, 'lopk' => $newLopk, 'bjpk' => $bjpk];
                }
                $db->table('lodt')->insert($insertRows);
                return $newLopk;
            });

            $skippedCount = $bjpks->count() - $finalBjpks->count();
            $msg = "LO #{$newLopk} berhasil dibuat dengan " . $finalBjpks->count() . " item.";
            if ($skippedCount > 0) {
                $msg .= " ({$skippedCount} item dilewati karena sudah tidak valid.)";
            }

            return response()->json(['icon' => 'success', 'title' => $msg, 'lopk' => $newLopk]);
        } catch (\Throwable $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal menyimpan LO.'], 500);
        }
    }

    /**
     * DETAIL -- FIX UTAMA: terima $mif dari query string (dikirim
     * frontend karena sudah tersedia dari row list), fallback coba
     * kedua koneksi kalau tidak dikirim.
     */
    public function detail(Request $request, $lopk)
    {
        $found = $this->findLo($lopk, $request->query('mif'));
        abort_unless($found, 404);
        [$lo, $connection, $mif] = $found;
    
        $db = DB::connection($connection);
        $bjpks = $db->table('lodt')->where('lopk', $lopk)->pluck('bjpk');
    
        $qtyColumns  = collect(range(1, 40))->map(fn($i) => "bj.qty{$i}")->implode(', ');
        $sizeColumns = collect(range(1, 40))->map(fn($i) => "po.size{$i}")->implode(', ');
    
        $items = $db->table('bj')
            ->join('po', 'po.popk', '=', 'bj.popk')
            ->whereIn('bj.bjpk', $bjpks)
            ->selectRaw("
                bj.bjpk, bj.grade, bj.pcs, bj.tanggal, bj.tglin,
                po.POno, po.OP, po.poref, po.customer, po.buyer, po.material, po.secsz,
                {$qtyColumns}, {$sizeColumns}
            ")
            ->orderBy('po.OP')
            ->get();
    
        foreach ($items as $item) {
            $sizes = [];
            for ($i = 1; $i <= 40; $i++) {
                $label = $item->{"size{$i}"} ?? null;
                $qty   = $item->{"qty{$i}"} ?? null;
                if (!empty($label) && (int) $qty > 0) {
                    $sizes[] = ['label' => $label, 'qty' => (int) $qty];
                }
                unset($item->{"size{$i}"}, $item->{"qty{$i}"});
            }
            $item->sizes = $sizes;
        }
    
        $lo->sudah_diterima = $items->isNotEmpty() && $items->every(fn($i) => $i->tglin !== null);
        $lo->status_label = $this->resolveStatusLabel($lo);
        $lo->no_lo    = $this->buildNoLo($lo->lopk, $lo->mif, $lo->lodate); // BARU
        $lo->can_edit = $this->canEditLo($lo); // BARU
    
        return response()->json(['lo' => $lo, 'items' => $items]);
    }

    public function approve(Request $request, $lopk, $level)
    {
        $level = (int) $level;
        if (!in_array($level, [1, 2, 3], true)) {
            return $this->respondApproveResult($request, false, 'Level approval tidak valid.');
        }
    
        $found = $this->findLo($lopk, $request->input('mif'));
        if (!$found) {
            return $this->respondApproveResult($request, false, 'LO tidak ditemukan.');
        }
        [$lo, $connection] = $found;
        $db = DB::connection($connection);
    
        if ($lo->{"stsapv{$level}"} !== null) {
            return $this->respondApproveResult($request, false, "Level {$level} sudah pernah diproses sebelumnya.");
        }
    
        $db->table('lo')->where('lopk', $lopk)->update(["stsapv{$level}" => 1]);
    
        return $this->respondApproveResult($request, true, "Level {$level} berhasil di-approve.");
    }
    
    public function reject(Request $request, $lopk, $level)
    {
        $level = (int) $level;
        if (!in_array($level, [1, 2, 3], true)) {
            return $this->respondApproveResult($request, false, 'Level approval tidak valid.');
        }
    
        $found = $this->findLo($lopk, $request->input('mif'));
        if (!$found) {
            return $this->respondApproveResult($request, false, 'LO tidak ditemukan.');
        }
        [$lo, $connection] = $found;
        $db = DB::connection($connection);
    
        if ($lo->{"stsapv{$level}"} !== null) {
            return $this->respondApproveResult($request, false, "Level {$level} sudah pernah diproses sebelumnya.");
        }
    
        $db->table('lo')->where('lopk', $lopk)->update(["stsapv{$level}" => 0]);
    
        return $this->respondApproveResult($request, true, "Level {$level} ditolak.");
    }

    public function cancel(Request $request, $lopk)
    {
        $found = $this->findLo($lopk, $request->input('mif'));
        abort_unless($found, 404);
        [$lo, $connection] = $found;
    
        if (!$this->canEditLo($lo)) {
            return response()->json(['icon' => 'warning', 'title' => 'LO ini sudah ada approval yang masuk, tidak bisa dibatalkan lagi.'], 422);
        }
    
        $db = DB::connection($connection);
        try {
            DB::connection($connection)->transaction(function () use ($db, $lopk) {
                $db->table('lodt')->where('lopk', $lopk)->delete();
                $db->table('lo')->where('lopk', $lopk)->delete();
            });
            return response()->json(['icon' => 'success', 'title' => 'LO dibatalkan, semua item ter-unlock kembali.']);
        } catch (\Throwable $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal membatalkan LO.'], 500);
        }
    }

    public function removeItem(Request $request, $lopk, $bjpk)
    {
        $found = $this->findLo($lopk, $request->input('mif'));
        abort_unless($found, 404);
        [$lo, $connection] = $found;
    
        if (!$this->canEditLo($lo)) {
            return response()->json(['icon' => 'warning', 'title' => 'LO ini sudah ada approval yang masuk, tidak bisa diedit lagi.'], 422);
        }
    
        $db = DB::connection($connection);
        $db->table('lodt')->where('lopk', $lopk)->where('bjpk', $bjpk)->delete();
        return response()->json(['icon' => 'success', 'title' => 'Item dihapus dari LO, sudah bisa dipilih lagi.']);
    }

    public function updateLo(Request $request, $lopk)
    {
        $validated = $request->validate([
            'keterangan' => 'nullable|string|max:250',
            'bjpks'      => 'required|array|min:1',
            'bjpks.*'    => 'integer',
        ]);
    
        $found = $this->findLo($lopk, $request->input('mif'));
        abort_unless($found, 404);
        [$lo, $connection, $mif] = $found;
    
        if (!$this->canEditLo($lo)) {
            return response()->json(['icon' => 'warning', 'title' => 'LO ini sudah ada approval yang masuk, tidak bisa diedit lagi.'], 422);
        }
    
        $db = DB::connection($connection);
        $newBjpks = collect($validated['bjpks'])->unique()->values();
    
        $validBjpks = $db->table('bj')
            ->whereIn('bjpk', $newBjpks)
            ->whereBetween('grade', ['A', 'C'])
            ->pluck('bjpk');
    
        // Kunci oleh LO LAIN (BUKAN LO ini sendiri -- item yang sudah ada
        // di LO ini boleh tetap dipertahankan).
        $lockedByOtherLo = $db->table('lodt')
            ->join('lo', 'lo.lopk', '=', 'lodt.lopk')
            ->where('lo.mif', $mif)
            ->where('lodt.lopk', '!=', $lopk)
            ->whereIn('lodt.bjpk', $validBjpks)
            ->pluck('lodt.bjpk');
    
        $finalBjpks = $validBjpks->diff($lockedByOtherLo)->values();
    
        if ($finalBjpks->isEmpty()) {
            return response()->json(['icon' => 'warning', 'title' => 'Semua item tidak valid (terkunci LO lain / grade berubah).'], 422);
        }
    
        try {
            DB::connection($connection)->transaction(function () use ($db, $lopk, $validated, $finalBjpks) {
                $db->table('lo')->where('lopk', $lopk)->update([
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);
    
                // Sinkron total: hapus semua baris lama, insert ulang sesuai
                // pilihan terbaru (sederhana & aman karena lodt tidak ada FK
                // ke tabel lain yang perlu dipertahankan).
                $db->table('lodt')->where('lopk', $lopk)->delete();
    
                $newLodtpk = (int) ($db->table('lodt')->lockForUpdate()->max('lodtpk'));
                $insertRows = [];
                foreach ($finalBjpks as $bjpk) {
                    $newLodtpk++;
                    $insertRows[] = ['lodtpk' => $newLodtpk, 'lopk' => $lopk, 'bjpk' => $bjpk];
                }
                $db->table('lodt')->insert($insertRows);
            });
    
            return response()->json(['icon' => 'success', 'title' => "LO #{$lopk} berhasil diperbarui."]);
        } catch (\Throwable $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal memperbarui LO.'], 500);
        }
    }

    public function sendLoEmail(Request $request, $lopk)
    {
        $found = $this->findLo($lopk, $request->input('mif'));
        abort_unless($found, 404);
        [$lo, $connection, $mif] = $found;
        $db = DB::connection($connection);
    
        $emailConnection = $this->resolveConnection($this->currentMif());
        $emailRow = DB::connection($emailConnection)->table('email')->where('jnsemail', 1)->first();
    
        if (!$emailRow) {
            return response()->json(['icon' => 'error', 'title' => 'Data email approver (jnsemail=1) tidak ditemukan.'], 404);
        }
    
        $noLo = $this->buildNoLo($lo->lopk, $lo->mif, $lo->lodate);
    
        $bjpks = $db->table('lodt')->where('lopk', $lopk)->pluck('bjpk');
        $qtyColumns  = collect(range(1, 40))->map(fn($i) => "bj.qty{$i}")->implode(', ');
        $sizeColumns = collect(range(1, 40))->map(fn($i) => "po.size{$i}")->implode(', ');
    
        $itemsRaw = $db->table('bj')
            ->join('po', 'po.popk', '=', 'bj.popk')
            ->whereIn('bj.bjpk', $bjpks)
            ->selectRaw("bj.grade, bj.pcs, po.POno, po.OP, po.material, po.secsz, {$qtyColumns}, {$sizeColumns}")
            ->orderBy('po.OP')
            ->get();
    
        // BARU -- FIX UTAMA: bangun ARRAY (bukan objek), pakai key 'color'
        // generik (BUKAN 'material') supaya cocok dgn view shared.
        $items = collect($itemsRaw)->map(function ($item) {
            $sizes = [];
            for ($i = 1; $i <= 40; $i++) {
                $label = $item->{"size{$i}"} ?? null;
                $qty   = $item->{"qty{$i}"} ?? null;
                if (!empty($label) && (int) $qty > 0) {
                    $sizes[] = ['label' => $label, 'qty' => (int) $qty];
                }
            }
            return [
                'grade' => $item->grade,
                'POno'  => $item->POno,
                'OP'    => $item->OP,
                'color' => $item->material,
                'secsz' => $item->secsz,
                'pcs'   => $item->pcs,
                'sizes' => $sizes,
            ];
        });
    
        $approvers = [
            1 => ['name' => $emailRow->name1, 'email' => $emailRow->apv1, 'label' => 'Manager'],
            2 => ['name' => $emailRow->name2, 'email' => $emailRow->apv2, 'label' => 'PPIC'],
            3 => ['name' => $emailRow->name3, 'email' => $emailRow->apv3, 'label' => 'Purchasing'],
        ];
    
        $sentCount = 0;
        $failed = [];
    
        foreach ($approvers as $level => $approver) {
            if (empty($approver['email'])) continue;
            if ($lo->{"stsapv{$level}"} !== null) continue;
    
            $approveUrl = URL::temporarySignedRoute(
                'lo.email-approve.page', now()->addDays(14),
                ['lopk' => $lo->lopk, 'level' => $level, 'mif' => $lo->mif]
            );
    
            try {
                Mail::send('menu.shared.lo-email-approval', [
                    'docNo'        => $noLo,           // BARU -- generik
                    'docTitle'     => 'Finishing Kirim Sisa ke Gudang', // BARU
                    'penerima'     => null,             // BARU -- LO tidak punya penerima
                    'keterangan'   => $lo->keterangan,
                    'approverName' => $approver['name'],
                    'levelLabel'   => $approver['label'],
                    'approveUrl'   => $approveUrl,
                    'items'        => $items,
                ], function ($message) use ($approver, $noLo) {
                    $message->to($approver['email'], $approver['name'])
                        ->subject("Approval Finishing Kirim Sisa ke Gudang - {$noLo}");
                });
                $sentCount++;
            } catch (\Throwable $e) {
                $failed[] = $approver['label'] . ' (' . $approver['email'] . '): ' . $e->getMessage();
            }
        }
    
        if ($sentCount === 0 && empty($failed)) {
            return response()->json(['icon' => 'warning', 'title' => 'Tidak ada email yang dikirim (semua level sudah diproses, atau alamat email kosong).']);
        }
        if (!empty($failed)) {
            return response()->json([
                'icon'  => $sentCount > 0 ? 'warning' : 'error',
                'title' => "Berhasil kirim ke {$sentCount} approver. Gagal ke " . count($failed) . ' approver.',
                'errors' => $failed,
            ]);
        }
        return response()->json(['icon' => 'success', 'title' => "Email approval berhasil dikirim ke {$sentCount} approver."]);
    }

    public function emailApprovePage(Request $request, $lopk, $level)
    {
        abort_unless($request->hasValidSignature(), 403, 'Link tidak valid atau sudah kedaluwarsa.');
    
        $mif = $request->query('mif');
        $found = $this->findLo($lopk, $mif);
        abort_unless($found, 404);
        [$lo, $connection] = $found;
    
        $level = (int) $level;
        $alreadyDone = $lo->{"stsapv{$level}"} !== null;
        $noLo = $this->buildNoLo($lo->lopk, $lo->mif, $lo->lodate);
        $levelLabel = [1 => 'Manager', 2 => 'PPIC', 3 => 'Purchasing'][$level] ?? "Level {$level}";
    
        $db = DB::connection($connection);
        $bjpks = $db->table('lodt')->where('lopk', $lopk)->pluck('bjpk');
        $qtyColumns  = collect(range(1, 40))->map(fn($i) => "bj.qty{$i}")->implode(', ');
        $sizeColumns = collect(range(1, 40))->map(fn($i) => "po.size{$i}")->implode(', ');
    
        $itemsRaw = $db->table('bj')
            ->join('po', 'po.popk', '=', 'bj.popk')
            ->whereIn('bj.bjpk', $bjpks)
            ->selectRaw("bj.grade, bj.pcs, po.POno, po.OP, po.material, po.secsz, {$qtyColumns}, {$sizeColumns}")
            ->orderBy('po.OP')
            ->get();
    
        $items = collect($itemsRaw)->map(function ($item) {
            $sizes = [];
            for ($i = 1; $i <= 40; $i++) {
                $label = $item->{"size{$i}"} ?? null;
                $qty   = $item->{"qty{$i}"} ?? null;
                if (!empty($label) && (int) $qty > 0) {
                    $sizes[] = ['label' => $label, 'qty' => (int) $qty];
                }
            }
            return [
                'grade' => $item->grade, 'POno' => $item->POno, 'OP' => $item->OP,
                'color' => $item->material, 'secsz' => $item->secsz, 'pcs' => $item->pcs, 'sizes' => $sizes,
            ];
        });
    
        $doApproveUrl = URL::temporarySignedRoute(
            'lo.email-approve.do-approve', now()->addDays(14), ['lopk' => $lopk, 'level' => $level]
        );
        $doRejectUrl = URL::temporarySignedRoute(
            'lo.email-approve.do-reject', now()->addDays(14), ['lopk' => $lopk, 'level' => $level]
        );
    
        return view('menu.shared.lo-email-confirm', [
            'docNo'        => $noLo,
            'penerima'     => null,
            'keterangan'   => $lo->keterangan,
            'alreadyDone'  => $alreadyDone,
            'levelLabel'   => $levelLabel,
            'items'        => $items,
            'doApproveUrl' => $doApproveUrl,
            'doRejectUrl'  => $doRejectUrl,
        ]);
    }
    
    
    // ============================================================
    // BARU -- emailDoApprove()/emailDoReject() -- TANPA session/login SAMA
    // SEKALI, keamanan murni dari signature URL. Route param TIDAK
    // menyertakan 'mif' (dicari via findLo() fallback coba kedua koneksi).
    // ============================================================
    public function emailDoApprove(Request $request, $lopk, $level)
    {
        abort_unless($request->hasValidSignature(), 403, 'Link tidak valid atau sudah kedaluwarsa.');
    
        $level = (int) $level;
        if (!in_array($level, [1, 2, 3], true)) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => 'Approval tidak valid.']);
        }
    
        $found = $this->findLo($lopk); // tanpa mif hint -- coba kedua koneksi
        if (!$found) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => 'LO tidak ditemukan.']);
        }
        [$lo, $connection] = $found;
        $db = DB::connection($connection);
    
        if ($lo->{"stsapv{$level}"} !== null) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => "Approval sudah pernah diproses sebelumnya."]);
        }
    
        $db->table('lo')->where('lopk', $lopk)->update(["stsapv{$level}" => 1]);
    
        return view('menu.shared.lo-email-approve-done', ['success' => true, 'message' => "Berhasil approve LO."]);
    }
    
    public function emailDoReject(Request $request, $lopk, $level)
    {
        abort_unless($request->hasValidSignature(), 403, 'Link tidak valid atau sudah kedaluwarsa.');
    
        $level = (int) $level;
        if (!in_array($level, [1, 2, 3], true)) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => 'Approval tidak valid.']);
        }
    
        $found = $this->findLo($lopk);
        if (!$found) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => 'LO tidak ditemukan.']);
        }
        [$lo, $connection] = $found;
        $db = DB::connection($connection);
    
        if ($lo->{"stsapv{$level}"} !== null) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => "Sudah pernah diproses sebelumnya."]);
        }
    
        $db->table('lo')->where('lopk', $lopk)->update(["stsapv{$level}" => 0]);
    
        return view('menu.shared.lo-email-approve-done', ['success' => true, 'message' => "LO ditolak."]);
    }

    private function respondApproveResult(Request $request, bool $success, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'icon'  => $success ? 'success' : ($success === false && str_contains($message, 'tidak valid') ? 'error' : 'warning'),
                'title' => $message,
            ], $success ? 200 : 422);
        }
    
        return view('menu.shared.lo-email-approve-done', ['success' => $success, 'message' => $message]);
    }

    /**
     * getListGudang() -- FIX UTAMA:
     * 1. 'lo' di 2 database -- loop KEDUA mif TANPA syarat guserpk super
     *    sama sekali (gudang SELALU lihat semua, beda dari getList()).
     * 2. "Sudah diterima" dicek dari bj.tglin (JOIN lodt+bj DALAM
     *    koneksi yang SAMA, karena sekarang lo/lodt/bj satu database),
     *    BUKAN kolom tglterima.
     */

    private function canEditOutsisa($out): bool
    {
        return $out->stsapv1 === null && $out->stsapv2 === null && $out->stsapv3 === null;
    }

    public function getListGudang(Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;
        $lopkSearch = $request->input('lopk');
    
        $allRows = collect();
    
        foreach ([1, 2] as $mif) {
            $db = DB::connection($this->resolveConnection($mif));
    
            // FIX UTAMA: HAPUS filter stsapv1/2/3 -- tampilkan SEMUA LO,
            // apa pun status approval-nya.
            $rows_ = $db->table('lo')
                ->where('mif', $mif)
                ->when($lopkSearch, fn($q) => $q->where('lopk', 'like', "%{$lopkSearch}%"))
                ->get();
    
            foreach ($rows_ as $lo) {
                $lo->mif = $mif;
    
                $fullyApproved = (int) $lo->stsapv1 === 1 && (int) $lo->stsapv2 === 1 && (int) $lo->stsapv3 === 1;
    
                if ($fullyApproved) {
                    $belumTerimaCount = $db->table('lodt')
                        ->join('bj', 'bj.bjpk', '=', 'lodt.bjpk')
                        ->where('lodt.lopk', $lo->lopk)
                        ->whereNull('bj.tglin')
                        ->count();
                    $lo->sudah_diterima = $belumTerimaCount === 0;
                } else {
                    $lo->sudah_diterima = false; // belum full approve -- pasti belum bisa diterima
                }
            }
    
            $allRows = $allRows->concat($rows_);
        }
    
        $allRows = $allRows->sortByDesc('lopk')->values();
        $total = $allRows->count();
        $data  = $allRows->slice($offset, $rows)->values();
    
        foreach ($data as $lo) {
            $db = DB::connection($this->resolveConnection($lo->mif));
            $lo->jumlah_item = $db->table('lodt')->where('lopk', $lo->lopk)->count();
    
            $fullyApproved = (int) $lo->stsapv1 === 1 && (int) $lo->stsapv2 === 1 && (int) $lo->stsapv3 === 1;
    
            // BARU -- FIX UTAMA: status_label SEKARANG sesuai kondisi
            // sebenarnya -- kalau belum full approve, pakai label approval
            // biasa (Menunggu Approve X / Ditolak), BUKAN selalu
            // "Siap Diterima".
            if (!$fullyApproved) {
                $lo->status_label = $this->resolveStatusLabel($lo);
            } elseif ($lo->sudah_diterima) {
                $lo->status_label = 'Sudah Diterima';
            } else {
                $lo->status_label = 'Siap Diterima';
            }
    
            $lo->no_lo = $this->buildNoLo($lo->lopk, $lo->mif, $lo->lodate);
        }
    
        return response()->json(['total' => $total, 'rows' => $data]);
    }

    /**
     * TERIMA -- FIX UTAMA: SET bj.tglin (BUKAN kolom tglterima yang
     * tidak ada) untuk SEMUA bjpk dalam LO ini, dalam 1 transaksi, di
     * koneksi yang benar sesuai mif LO tersebut.
     */
    public function terimaItem(Request $request, $lopk, $bjpk)
    {
        $found = $this->findLo($lopk, $request->input('mif'));
        abort_unless($found, 404);
        [$lo, $connection] = $found;
        $db = DB::connection($connection);
    
        if ((int) $lo->stsapv1 !== 1 || (int) $lo->stsapv2 !== 1 || (int) $lo->stsapv3 !== 1) {
            return response()->json([
                'icon'  => 'warning',
                'title' => 'LO ini belum selesai approve 3 level, belum bisa diterima.',
            ], 422);
        }
    
        // Pastikan bjpk ini memang bagian dari LO ini (jangan asal terima
        // bjpk yang tidak terdaftar di lodt-nya).
        $isPartOfLo = $db->table('lodt')->where('lopk', $lopk)->where('bjpk', $bjpk)->exists();
        if (!$isPartOfLo) {
            return response()->json(['icon' => 'error', 'title' => 'Item ini bukan bagian dari LO ini.'], 422);
        }
    
        try {
            $data = $db->table('bj')->where('bjpk', $bjpk)->first();
    
            if (!$data) {
                return response()->json(['icon' => 'warning', 'title' => 'Data tidak ditemukan'], 404);
            }
    
            if ($data->tglin !== null) {
                return response()->json(['icon' => 'warning', 'title' => 'Item ini sudah diterima sebelumnya.'], 422);
            }
    
            $db->table('bj')->where('bjpk', $bjpk)->update([
                'status' => 2,
                'tglin'  => now(),
            ]);
    
            return response()->json(['icon' => 'success', 'title' => 'Item berhasil ditandai diterima.']);
        } catch (\Exception $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal memproses data'], 500);
        }
    }

    public function debugCheckLoDuplicates()
    {
        $result = [];
    
        // Cek koneksi DEFAULT (kalau tabel 'lo' masih ada disitu & ada isinya,
        // itu SISA DATA LAMA sebelum fix -- harus dibersihkan/dihapus).
        try {
            $countDefault = DB::table('lo')->count();
            $result['default_connection'] = $countDefault;
        } catch (\Throwable $e) {
            $result['default_connection'] = 'Tabel tidak ada / error: ' . $e->getMessage();
        }
    
        $result['mysql (mif=2)'] = DB::connection('mysql')->table('lo')->count();
        $result['mysql_andon (mif=1)'] = DB::connection('mysql_andon')->table('lo')->count();
    
        $result['detail_mysql'] = DB::connection('mysql')->table('lo')->orderByDesc('lopk')->limit(10)->get();
        $result['detail_mysql_andon'] = DB::connection('mysql_andon')->table('lo')->orderByDesc('lopk')->limit(10)->get();
    
        return response()->json($result);
    }


    // ============================================================
    // TAMBAHKAN SEMUA method ini ke LoController (class yang sudah ada).
    // Table 'outsisa'/'outsisadt' -- SAMA aturan koneksi dengan 'lo'/'lodt'
    // (hidup di mysql/mysql_andon per mif, WAJIB resolveConnection()).
    // ============================================================
    
    /**
     * Cari 1 baris 'outsisa' by outpk -- SAMA pola findLo(), karena outpk
     * juga TIDAK unik lintas mif.
     */
    private function findOutsisa($outpk, $mifHint = null): ?array
    {
        $tryOrder = $mifHint !== null ? [(int) $mifHint] : [];
        foreach ([2, 1] as $m) {
            if (!in_array($m, $tryOrder, true)) $tryOrder[] = $m;
        }
    
        foreach ($tryOrder as $mif) {
            $connection = $this->resolveConnection($mif);
            $out = DB::connection($connection)->table('outsisa')->where('outpk', $outpk)->first();
            if ($out) {
                $out->mif = $mif;
                return [$out, $connection, $mif];
            }
        }
        return null;
    }
    
    private function resolveOutsisaStatusLabel($out): string
    {
        $labels = [1 => 'Purchasing', 2 => 'HRD', 3 => 'HRD2'];
        $rejected = [];
        $pending = [];
        $approvedCount = 0;
    
        foreach ([1, 2, 3] as $lvl) {
            $val = $out->{"stsapv{$lvl}"};
            if ($val !== null && (int) $val === 0) $rejected[] = $labels[$lvl];
            elseif ((int) $val === 1) $approvedCount++;
            else $pending[] = $labels[$lvl];
        }
    
        if (!empty($rejected)) return 'Ditolak (' . implode(', ', $rejected) . ')';
        if ($approvedCount === 3 || empty($pending)) return 'Selesai (Approved)';
        return 'Menunggu Approve ' . implode(', ', $pending);
    }
    
    // ============================================================
    // INDEX
    // ============================================================
    public function indexKeluarGudang(Request $request)
    {
        return view('menu.keluar-gudang.index');
    }
    
    public function getListKeluarGudang(Request $request)
    {
        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;
        $outpkSearch = $request->input('outpk');
        $status      = $request->input('status');
    
        $mifsToQuery = $this->isSuperUser() ? [1, 2] : [$this->currentMif()];
        $allRows = collect();
    
        foreach ($mifsToQuery as $mif) {
            $db = DB::connection($this->resolveConnection($mif));
    
            $rows_ = $db->table('outsisa')
                ->where('mif', $mif)
                ->when($outpkSearch, fn($q) => $q->where('outpk', 'like', "%{$outpkSearch}%"))
                ->when($status, function ($q) use ($status) {
                    switch ($status) {
                        case 'pending1': $q->whereNull('stsapv1'); break;
                        case 'pending2': $q->whereNull('stsapv2'); break;
                        case 'pending3': $q->whereNull('stsapv3'); break;
                        case 'approved':
                            $q->where('stsapv1', 1)->where('stsapv2', 1)->where('stsapv3', 1);
                            break;
                        case 'rejected':
                            $q->where(function ($qq) {
                                $qq->where('stsapv1', 0)->orWhere('stsapv2', 0)->orWhere('stsapv3', 0);
                            });
                            break;
                    }
                })
                ->get();
    
            foreach ($rows_ as $out) {
                $out->mif = $mif;
            }
    
            $allRows = $allRows->concat($rows_);
        }
    
        $allRows = $allRows->sortByDesc('outpk')->values();
        $total = $allRows->count();
        $data  = $allRows->slice($offset, $rows)->values();
    
        foreach ($data as $out) {
            $db = DB::connection($this->resolveConnection($out->mif));
            $out->jumlah_item  = $db->table('outsisadt')->where('outpk', $out->outpk)->count();
            $out->status_label = $this->resolveOutsisaStatusLabel($out);
            $out->no_out       = $this->buildNoOutsisa($out->outpk, $out->mif, $out->tglout);
            $out->can_edit     = $this->canEditOutsisa($out); // BARU
        }
    
        return response()->json(['total' => $total, 'rows' => $data]);
    }
    
    /**
     * AJAX modal "Add Barang Keluar" -- cari bjpk yang SUDAH DITERIMA
     * gudang (bj.tglin IS NOT NULL DAN bj.status = 2), breakdown per size
     * dengan SISA yang belum dikeluarkan (qty asli - SUM(outsisadt.qty)
     * yang sudah pernah dicatat utk size itu).
     */
    public function outAvailableItems(Request $request)
    {
        $isSuper = $this->isSuperUser();
        $search  = $request->search;
        $excludeOutpk = $request->input('exclude_outpk'); // BARU
        $mifsToQuery = $isSuper ? [1, 2] : [$this->currentMif()];
    
        $qtyColumns  = collect(range(1, 40))->map(fn($i) => "bj.qty{$i}")->implode(', ');
        $sizeColumns = collect(range(1, 40))->map(fn($i) => "po.size{$i}")->implode(', ');
    
        $allRows = collect();
    
        foreach ($mifsToQuery as $mif) {
            $db = DB::connection($this->resolveConnection($mif));
    
            $rows = $db->table('bj')
                ->join('po', 'po.popk', '=', 'bj.popk')
                ->where('po.mif', $mif)
                ->whereNotNull('bj.tglin')
                ->where('bj.status', 2)
                ->when($search, function ($q) use ($search) {
                    $q->where(function ($qq) use ($search) {
                        $qq->where('po.POno', 'like', "%{$search}%")
                            ->orWhere('po.OP', 'like', "%{$search}%")
                            ->orWhere('po.buyer', 'like', "%{$search}%")
                            ->orWhere('bj.material', 'like', "%{$search}%");
                    });
                })
                ->selectRaw("
                    bj.bjpk, bj.popk, bj.grade, bj.pcs, bj.pcsk, bj.tanggal,
                    po.POno, po.OP, po.poref, po.customer, po.buyer, po.material, po.secsz,
                    {$qtyColumns}, {$sizeColumns}
                ")
                ->orderByDesc('bj.tanggal')
                ->get();
    
            $bjpks = $rows->pluck('bjpk')->values();
    
            // BARU -- FIX UTAMA: kalau exclude_outpk dikirim (mode edit),
            // JANGAN hitung baris outsisadt milik outpk itu sebagai
            // "sudah terpakai" -- supaya kapasitas penuh kembali terlihat
            // utk dialokasikan ulang.
            $releasedMap = collect();
            if ($bjpks->isNotEmpty()) {
                $releasedMap = $db->table('outsisadt')
                    ->whereIn('bjpk', $bjpks)
                    ->when($excludeOutpk, fn($q) => $q->where('outpk', '!=', $excludeOutpk)) // BARU
                    ->select('bjpk', 'size')
                    ->selectRaw('SUM(qty) as released')
                    ->groupBy('bjpk', 'size')
                    ->get()
                    ->groupBy('bjpk');
            }
    
            foreach ($rows as $row) {
                $row->mif = $mif;
                $sizes = [];
                for ($i = 1; $i <= 40; $i++) {
                    $label = $row->{"size{$i}"} ?? null;
                    $qty   = (int) ($row->{"qty{$i}"} ?? 0);
                    unset($row->{"size{$i}"}, $row->{"qty{$i}"});
                    if (empty($label) || $qty <= 0) continue;
    
                    $releasedForThisSize = 0;
                    if (isset($releasedMap[$row->bjpk])) {
                        $match = $releasedMap[$row->bjpk]->firstWhere('size', $label);
                        $releasedForThisSize = $match ? (float) $match->released : 0;
                    }
    
                    $remaining = $qty - $releasedForThisSize;
                    if ($remaining > 0) {
                        $sizes[] = ['label' => $label, 'original' => $qty, 'remaining' => $remaining];
                    }
                }
                $row->sizes = $sizes;
            }
    
            $rows = $rows->filter(fn($r) => count($r->sizes) > 0)->values();
            $allRows = $allRows->concat($rows);
        }
    
        $grouped = $allRows->groupBy(fn($r) => $r->mif . '|' . $r->POno . '|' . $r->OP)
            ->map(function ($items) {
                $first = $items->first();
                return [
                    'POno'  => $first->POno,
                    'OP'    => $first->OP,
                    'buyer' => $first->buyer,
                    'mif'   => $first->mif,
                    'items' => $items->values(),
                ];
            })->values();
    
        return response()->json(['groups' => $grouped]);
    }
    
    /**
     * STORE -- validasi ULANG di server (sisa per bjpk+size TIDAK BOLEH
     * dilewati), insert outsisa+outsisadt, lalu PROPAGASI keterangan/
     * tglout/pcsk ke tabel bj untuk setiap bjpk yang terlibat.
     */
    public function storeOutsisa(Request $request)
    {
        $validated = $request->validate([
            'penerima'   => 'nullable|string|max:100',
            'keterangan' => 'nullable|string|max:250',
            'lines'      => 'required|array|min:1',
            'lines.*.bjpk'  => 'required|integer',
            'lines.*.size'  => 'required|string',
            'lines.*.color' => 'nullable|string',
            'lines.*.secsz' => 'nullable|string',
            'lines.*.qty'   => 'required|numeric|min:0.01',
        ]);
    
        $mif        = $this->currentMif();
        $connection = $this->resolveConnection($mif);
        $db = DB::connection($connection);
    
        $bjpks = collect($validated['lines'])->pluck('bjpk')->unique()->values();
    
        // Ambil qty1..40 asli utk validasi ulang batas sisa per bjpk+size.
        $qtyColumns  = collect(range(1, 40))->map(fn($i) => "bj.qty{$i}")->implode(', ');
        $sizeColumns = collect(range(1, 40))->map(fn($i) => "po.size{$i}")->implode(', ');
        $bjRows = $db->table('bj')
            ->join('po', 'po.popk', '=', 'bj.popk')
            ->whereIn('bj.bjpk', $bjpks)
            ->whereNotNull('bj.tglin')
            ->where('bj.status', 2)
            ->selectRaw("bj.bjpk, {$qtyColumns}, {$sizeColumns}")
            ->get()
            ->keyBy('bjpk');
    
        $releasedMap = $db->table('outsisadt')
            ->whereIn('bjpk', $bjpks)
            ->select('bjpk', 'size')
            ->selectRaw('SUM(qty) as released')
            ->groupBy('bjpk', 'size')
            ->get()
            ->groupBy('bjpk');
    
        $validLines = [];
        $skipped = 0;
    
        foreach ($validated['lines'] as $line) {
            $bjRow = $bjRows->get($line['bjpk']);
            if (!$bjRow) { $skipped++; continue; }
    
            // Cari qty asli utk size ini.
            $originalQty = null;
            for ($i = 1; $i <= 40; $i++) {
                if (($bjRow->{"size{$i}"} ?? null) === $line['size']) {
                    $originalQty = (int) ($bjRow->{"qty{$i}"} ?? 0);
                    break;
                }
            }
            if ($originalQty === null) { $skipped++; continue; }
    
            $alreadyReleased = 0;
            if (isset($releasedMap[$line['bjpk']])) {
                $match = $releasedMap[$line['bjpk']]->firstWhere('size', $line['size']);
                $alreadyReleased = $match ? (float) $match->released : 0;
            }
    
            $remaining = $originalQty - $alreadyReleased;
    
            // FIX UTAMA: TOLAK kalau qty yang diminta MELEBIHI sisa.
            if ((float) $line['qty'] > $remaining) {
                $skipped++;
                continue;
            }
    
            $validLines[] = $line;
        }
    
        if (empty($validLines)) {
            return response()->json([
                'icon'  => 'warning',
                'title' => 'Semua baris tidak valid (melebihi sisa yang tersedia, atau data sudah berubah). Silakan refresh dan coba lagi.',
            ], 422);
        }
    
        try {
            $newOutpk = DB::connection($connection)->transaction(function () use ($db, $validated, $validLines, $mif) {
                $newOutpk = (int) ($db->table('outsisa')->lockForUpdate()->max('outpk')) + 1;
                $db->table('outsisa')->insert([
                    'outpk'      => $newOutpk,
                    'mif'        => $mif,
                    'tglout'     => now(),
                    'penerima'   => $validated['penerima'] ?? null,
                    'keterangan' => $validated['keterangan'] ?? null,
                    'stsapv1'    => null,
                    'stsapv2'    => null,
                    'stsapv3'    => null,
                ]);
    
                $newOutdtpk = (int) ($db->table('outsisadt')->lockForUpdate()->max('outdtpk'));
                $insertRows = [];
                foreach ($validLines as $line) {
                    $newOutdtpk++;
                    $insertRows[] = [
                        'outdtpk' => $newOutdtpk,
                        'outpk'   => $newOutpk,
                        'size'    => $line['size'],
                        'color'   => $line['color'] ?? null,
                        'secsz'   => $line['secsz'] ?? null,
                        'qty'     => $line['qty'],
                        'bjpk'    => $line['bjpk'],
                    ];
                }
                $db->table('outsisadt')->insert($insertRows);
    
                // FIX UTAMA: propagasi keterangan/tglout ke 'bj', DAN
                // tambahkan qty yang keluar ke pcsk (akumulatif, SAMA pola
                // updateActual() di Sisa Produksi).
                $qtyByBjpk = collect($validLines)->groupBy('bjpk')
                    ->map(fn($lines) => collect($lines)->sum('qty'));
    
                foreach ($qtyByBjpk as $bjpk => $qtyOut) {
                    $currentPcsk = (float) ($db->table('bj')->where('bjpk', $bjpk)->value('pcsk') ?? 0);
                    $db->table('bj')->where('bjpk', $bjpk)->update([
                        'tglout'     => now(),
                        'keterangan' => $validated['keterangan'] ?? null,
                        'pcsk'       => $currentPcsk + $qtyOut,
                    ]);
                }
    
                return $newOutpk;
            });
    
            $msg = "Outsisa #{$newOutpk} berhasil dibuat dengan " . count($validLines) . " baris.";
            if ($skipped > 0) {
                $msg .= " ({$skipped} baris dilewati karena melebihi sisa/data tidak valid.)";
            }
    
            return response()->json(['icon' => 'success', 'title' => $msg, 'outpk' => $newOutpk]);
        } catch (\Throwable $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal menyimpan data keluar.'], 500);
        }
    }
    
    public function approveOutsisa(Request $request, $outpk, $level)
    {
        $level = (int) $level;
        if (!in_array($level, [1, 2, 3], true)) {
            return response()->json(['icon' => 'error', 'title' => 'Level approval tidak valid.'], 422);
        }
    
        $found = $this->findOutsisa($outpk, $request->input('mif'));
        abort_unless($found, 404);
        [$out, $connection] = $found;
        $db = DB::connection($connection);
    
        if ($out->{"stsapv{$level}"} !== null) {
            return response()->json(['icon' => 'warning', 'title' => "Level {$level} sudah pernah diproses sebelumnya."], 422);
        }
    
        $db->table('outsisa')->where('outpk', $outpk)->update(["stsapv{$level}" => 1]);
        return response()->json(['icon' => 'success', 'title' => "Level {$level} berhasil di-approve."]);
    }
    
    public function rejectOutsisa(Request $request, $outpk, $level)
    {
        $level = (int) $level;
        if (!in_array($level, [1, 2, 3], true)) {
            return response()->json(['icon' => 'error', 'title' => 'Level approval tidak valid.'], 422);
        }
    
        $found = $this->findOutsisa($outpk, $request->input('mif'));
        abort_unless($found, 404);
        [$out, $connection] = $found;
        $db = DB::connection($connection);
    
        if ($out->{"stsapv{$level}"} !== null) {
            return response()->json(['icon' => 'warning', 'title' => "Level {$level} sudah pernah diproses sebelumnya."], 422);
        }
    
        $db->table('outsisa')->where('outpk', $outpk)->update(["stsapv{$level}" => 0]);
        return response()->json(['icon' => 'success', 'title' => "Level {$level} ditolak."]);
    }
    
    public function cancelOutsisa(Request $request, $outpk)
    {
        $found = $this->findOutsisa($outpk, $request->input('mif'));
        abort_unless($found, 404);
        [$out, $connection] = $found;
    
        if (!$this->canEditOutsisa($out)) {
            return response()->json(['icon' => 'warning', 'title' => 'Data ini sudah ada approval yang masuk, tidak bisa dibatalkan lagi.'], 422);
        }
    
        $db = DB::connection($connection);
        try {
            DB::connection($connection)->transaction(function () use ($db, $outpk) {
                $lines = $db->table('outsisadt')->where('outpk', $outpk)->get();
                $qtyByBjpk = $lines->groupBy('bjpk')->map(fn($l) => $l->sum('qty'));
                foreach ($qtyByBjpk as $bjpk => $qtyOut) {
                    $currentPcsk = (float) ($db->table('bj')->where('bjpk', $bjpk)->value('pcsk') ?? 0);
                    $db->table('bj')->where('bjpk', $bjpk)->update(['pcsk' => max(0, $currentPcsk - $qtyOut)]);
                }
                $db->table('outsisadt')->where('outpk', $outpk)->delete();
                $db->table('outsisa')->where('outpk', $outpk)->delete();
            });
            return response()->json(['icon' => 'success', 'title' => 'Data keluar dibatalkan, sisa ter-unlock kembali.']);
        } catch (\Throwable $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal membatalkan.'], 500);
        }
    }
    
    public function removeOutsisaItem(Request $request, $outpk, $outdtpk)
    {
        $found = $this->findOutsisa($outpk, $request->input('mif'));
        abort_unless($found, 404);
        [$out, $connection] = $found;
    
        if (!$this->canEditOutsisa($out)) {
            return response()->json(['icon' => 'warning', 'title' => 'Data ini sudah ada approval yang masuk, tidak bisa diedit lagi.'], 422);
        }
    
        $db = DB::connection($connection);
        $line = $db->table('outsisadt')->where('outdtpk', $outdtpk)->where('outpk', $outpk)->first();
        if ($line) {
            $currentPcsk = (float) ($db->table('bj')->where('bjpk', $line->bjpk)->value('pcsk') ?? 0);
            $db->table('bj')->where('bjpk', $line->bjpk)->update(['pcsk' => max(0, $currentPcsk - $line->qty)]);
            $db->table('outsisadt')->where('outdtpk', $outdtpk)->delete();
        }
        return response()->json(['icon' => 'success', 'title' => 'Baris dihapus, sisa ter-unlock kembali.']);
    }

    public function detailOutsisa(Request $request, $outpk)
    {
        $found = $this->findOutsisa($outpk, $request->query('mif'));
        abort_unless($found, 404);
        [$out, $connection] = $found;
        $db = DB::connection($connection);
    
        $lines = $db->table('outsisadt')
            ->join('bj', 'bj.bjpk', '=', 'outsisadt.bjpk')
            ->join('po', 'po.popk', '=', 'bj.popk')
            ->where('outsisadt.outpk', $outpk)
            ->select(
                'outsisadt.outdtpk', 'outsisadt.bjpk', 'outsisadt.size', 'outsisadt.color',
                'outsisadt.secsz', 'outsisadt.qty', 'bj.grade',
                'po.POno', 'po.OP', 'po.poref', 'po.customer', 'po.buyer'
            )
            ->orderBy('po.OP')
            ->get();
    
        $out->status_label = $this->resolveOutsisaStatusLabel($out);
        $out->no_out = $this->buildNoOutsisa($out->outpk, $out->mif, $out->tglout);
        $out->can_edit = $this->canEditOutsisa($out); // BARU
    
        return response()->json(['out' => $out, 'lines' => $lines]);
    }
    
    
    // ============================================================
    // 7) BARU -- updateOutsisa() -- edit data keluar yang sudah ada.
    // HANYA boleh kalau canEditOutsisa(). Rollback pcsk lama, insert
    // ulang baris baru, propagasi ulang ke bj (SAMA pola storeOutsisa()).
    // ============================================================
    public function updateOutsisa(Request $request, $outpk)
    {
        $validated = $request->validate([
            'penerima'      => 'nullable|string|max:100',
            'keterangan'    => 'nullable|string|max:250',
            'lines'         => 'required|array|min:1',
            'lines.*.bjpk'  => 'required|integer',
            'lines.*.size'  => 'required|string',
            'lines.*.color' => 'nullable|string',
            'lines.*.secsz' => 'nullable|string',
            'lines.*.qty'   => 'required|numeric|min:0.01',
        ]);
    
        $found = $this->findOutsisa($outpk, $request->input('mif'));
        abort_unless($found, 404);
        [$out, $connection] = $found;
    
        if (!$this->canEditOutsisa($out)) {
            return response()->json(['icon' => 'warning', 'title' => 'Data ini sudah ada approval yang masuk, tidak bisa diedit lagi.'], 422);
        }
    
        $db = DB::connection($connection);
        $bjpks = collect($validated['lines'])->pluck('bjpk')->unique()->values();
    
        $qtyColumns  = collect(range(1, 40))->map(fn($i) => "bj.qty{$i}")->implode(', ');
        $sizeColumns = collect(range(1, 40))->map(fn($i) => "po.size{$i}")->implode(', ');
        $bjRows = $db->table('bj')
            ->join('po', 'po.popk', '=', 'bj.popk')
            ->whereIn('bj.bjpk', $bjpks)
            ->selectRaw("bj.bjpk, {$qtyColumns}, {$sizeColumns}")
            ->get()->keyBy('bjpk');
    
        // Sisa dihitung TANPA menghitung baris outsisadt milik outpk INI
        // sendiri (supaya bisa "geser" qty tanpa dianggap melebihi sisa).
        $releasedMap = $db->table('outsisadt')
            ->whereIn('bjpk', $bjpks)
            ->where('outpk', '!=', $outpk)
            ->select('bjpk', 'size')
            ->selectRaw('SUM(qty) as released')
            ->groupBy('bjpk', 'size')
            ->get()->groupBy('bjpk');
    
        $validLines = [];
        $skipped = 0;
    
        foreach ($validated['lines'] as $line) {
            $bjRow = $bjRows->get($line['bjpk']);
            if (!$bjRow) { $skipped++; continue; }
    
            $originalQty = null;
            for ($i = 1; $i <= 40; $i++) {
                if (($bjRow->{"size{$i}"} ?? null) === $line['size']) {
                    $originalQty = (int) ($bjRow->{"qty{$i}"} ?? 0);
                    break;
                }
            }
            if ($originalQty === null) { $skipped++; continue; }
    
            $alreadyReleased = 0;
            if (isset($releasedMap[$line['bjpk']])) {
                $match = $releasedMap[$line['bjpk']]->firstWhere('size', $line['size']);
                $alreadyReleased = $match ? (float) $match->released : 0;
            }
    
            $remaining = $originalQty - $alreadyReleased;
            if ((float) $line['qty'] > $remaining) { $skipped++; continue; }
    
            $validLines[] = $line;
        }
    
        if (empty($validLines)) {
            return response()->json(['icon' => 'warning', 'title' => 'Semua baris tidak valid (melebihi sisa/data berubah).'], 422);
        }
    
        try {
            DB::connection($connection)->transaction(function () use ($db, $outpk, $validated, $validLines) {
                // Rollback pcsk dari baris LAMA sebelum dihapus.
                $oldLines = $db->table('outsisadt')->where('outpk', $outpk)->get();
                $oldQtyByBjpk = $oldLines->groupBy('bjpk')->map(fn($l) => $l->sum('qty'));
                foreach ($oldQtyByBjpk as $bjpk => $qtyOut) {
                    $currentPcsk = (float) ($db->table('bj')->where('bjpk', $bjpk)->value('pcsk') ?? 0);
                    $db->table('bj')->where('bjpk', $bjpk)->update(['pcsk' => max(0, $currentPcsk - $qtyOut)]);
                }
                $db->table('outsisadt')->where('outpk', $outpk)->delete();
    
                $db->table('outsisa')->where('outpk', $outpk)->update([
                    'penerima'   => $validated['penerima'] ?? null,
                    'keterangan' => $validated['keterangan'] ?? null,
                ]);
    
                $newOutdtpk = (int) ($db->table('outsisadt')->lockForUpdate()->max('outdtpk'));
                $insertRows = [];
                foreach ($validLines as $line) {
                    $newOutdtpk++;
                    $insertRows[] = [
                        'outdtpk' => $newOutdtpk, 'outpk' => $outpk, 'size' => $line['size'],
                        'color' => $line['color'] ?? null, 'secsz' => $line['secsz'] ?? null,
                        'qty' => $line['qty'], 'bjpk' => $line['bjpk'],
                    ];
                }
                $db->table('outsisadt')->insert($insertRows);
    
                $qtyByBjpk = collect($validLines)->groupBy('bjpk')->map(fn($l) => collect($l)->sum('qty'));
                foreach ($qtyByBjpk as $bjpk => $qtyOut) {
                    $currentPcsk = (float) ($db->table('bj')->where('bjpk', $bjpk)->value('pcsk') ?? 0);
                    $db->table('bj')->where('bjpk', $bjpk)->update([
                        'tglout'     => now(),
                        'keterangan' => $validated['keterangan'] ?? null,
                        'pcsk'       => $currentPcsk + $qtyOut,
                    ]);
                }
            });
    
            return response()->json(['icon' => 'success', 'title' => "Data keluar #{$outpk} berhasil diperbarui."]);
        } catch (\Throwable $e) {
            return response()->json(['icon' => 'error', 'title' => 'Gagal memperbarui data.'], 500);
        }
    }
    
    
    // ============================================================
    // 8) BARU -- sendOutsisaEmail() -- SAMA pola sendLoEmail(), TAPI pakai
    // jnsemail=2, label Purchasing/HRD/HRD2.
    // ============================================================
    public function sendOutsisaEmail(Request $request, $outpk)
    {
        $found = $this->findOutsisa($outpk, $request->input('mif'));
        abort_unless($found, 404);
        [$out, $connection] = $found;
        $db = DB::connection($connection);
    
        $emailConnection = $this->resolveConnection($this->currentMif());
        $emailRow = DB::connection($emailConnection)->table('email')->where('jnsemail', 2)->first();
    
        if (!$emailRow) {
            return response()->json(['icon' => 'error', 'title' => 'Data email approver (jnsemail=2) tidak ditemukan.'], 404);
        }
    
        $noOut = $this->buildNoOutsisa($out->outpk, $out->mif, $out->tglout);
    
        $linesRaw = $db->table('outsisadt')
            ->join('bj', 'bj.bjpk', '=', 'outsisadt.bjpk')
            ->join('po', 'po.popk', '=', 'bj.popk')
            ->where('outsisadt.outpk', $outpk)
            ->select('outsisadt.bjpk', 'bj.grade', 'po.POno', 'po.OP', 'outsisadt.color', 'outsisadt.secsz', 'outsisadt.size', 'outsisadt.qty')
            ->get();
    
        // BARU -- FIX UTAMA: group per bjpk jadi struktur 'items' SAMA
        // dengan LO (grade/POno/OP/color/secsz/pcs/sizes), supaya bisa
        // pakai VIEW YANG SAMA PERSIS.
        $items = $linesRaw->groupBy('bjpk')->map(function ($group) {
            $first = $group->first();
            return [
                'grade' => $first->grade,
                'POno'  => $first->POno,
                'OP'    => $first->OP,
                'color' => $first->color,
                'secsz' => $first->secsz,
                'pcs'   => $group->sum('qty'),
                'sizes' => $group->map(fn ($l) => ['label' => $l->size, 'qty' => (float) $l->qty])->values()->all(),
            ];
        })->values();
    
        $approvers = [
            1 => ['name' => $emailRow->name1, 'email' => $emailRow->apv1, 'label' => 'Purchasing'],
            2 => ['name' => $emailRow->name2, 'email' => $emailRow->apv2, 'label' => 'HRD'],
            3 => ['name' => $emailRow->name3, 'email' => $emailRow->apv3, 'label' => 'HRD2'],
        ];
    
        $sentCount = 0;
        $failed = [];
    
        foreach ($approvers as $level => $approver) {
            if (empty($approver['email'])) continue;
            if ($out->{"stsapv{$level}"} !== null) continue;
    
            $approveUrl = URL::temporarySignedRoute(
                'outsisa.email-approve.page', now()->addDays(14),
                ['outpk' => $out->outpk, 'level' => $level, 'mif' => $out->mif]
            );
    
            try {
                // BARU -- FIX UTAMA: pakai VIEW YANG SAMA dengan LO.
                Mail::send('menu.shared.lo-email-approval', [
                    'docNo'        => $noOut,
                    'docTitle'     => 'Keluar Sisa dari Gudang',
                    'penerima'     => $out->penerima,
                    'keterangan'   => $out->keterangan,
                    'approverName' => $approver['name'],
                    'levelLabel'   => $approver['label'],
                    'approveUrl'   => $approveUrl,
                    'items'        => $items,
                ], function ($message) use ($approver, $noOut) {
                    $message->to($approver['email'], $approver['name'])
                        ->subject("Approval Keluar Sisa dari Gudang - {$noOut}");
                });
                $sentCount++;
            } catch (\Throwable $e) {
                $failed[] = $approver['label'] . ' (' . $approver['email'] . '): ' . $e->getMessage();
            }
        }
    
        if ($sentCount === 0 && empty($failed)) {
            return response()->json(['icon' => 'warning', 'title' => 'Tidak ada email yang dikirim (semua level sudah diproses, atau alamat email kosong).']);
        }
        if (!empty($failed)) {
            return response()->json([
                'icon' => $sentCount > 0 ? 'warning' : 'error',
                'title' => "Berhasil kirim ke {$sentCount} approver. Gagal ke " . count($failed) . ' approver.',
                'errors' => $failed,
            ]);
        }
        return response()->json(['icon' => 'success', 'title' => "Email approval berhasil dikirim ke {$sentCount} approver."]);
    }
    
    // ============================================================
    // 9) BARU -- halaman landing + aksi approve/reject TANPA LOGIN,
    // SAMA pola emailApprovePage()/emailDoApprove()/emailDoReject() di
    // modul LO.
    // ============================================================
    public function outsisaEmailApprovePage(Request $request, $outpk, $level)
    {
        abort_unless($request->hasValidSignature(), 403, 'Link tidak valid atau sudah kedaluwarsa.');
    
        $mif = $request->query('mif');
        $found = $this->findOutsisa($outpk, $mif);
        abort_unless($found, 404);
        [$out, $connection] = $found;
    
        $level = (int) $level;
        $alreadyDone = $out->{"stsapv{$level}"} !== null;
        $noOut = $this->buildNoOutsisa($out->outpk, $out->mif, $out->tglout);
        $levelLabel = [1 => 'Purchasing', 2 => 'HRD', 3 => 'HRD2'][$level] ?? "Level {$level}";
    
        $db = DB::connection($connection);
        $linesRaw = $db->table('outsisadt')
            ->join('bj', 'bj.bjpk', '=', 'outsisadt.bjpk')
            ->join('po', 'po.popk', '=', 'bj.popk')
            ->where('outsisadt.outpk', $outpk)
            ->select('outsisadt.bjpk', 'bj.grade', 'po.POno', 'po.OP', 'outsisadt.color', 'outsisadt.secsz', 'outsisadt.size', 'outsisadt.qty')
            ->get();
    
        $items = $linesRaw->groupBy('bjpk')->map(function ($group) {
            $first = $group->first();
            return [
                'grade' => $first->grade, 'POno' => $first->POno, 'OP' => $first->OP,
                'color' => $first->color, 'secsz' => $first->secsz, 'pcs' => $group->sum('qty'),
                'sizes' => $group->map(fn ($l) => ['label' => $l->size, 'qty' => (float) $l->qty])->values()->all(),
            ];
        })->values();
    
        $doApproveUrl = URL::temporarySignedRoute(
            'outsisa.email-approve.do-approve', now()->addDays(14), ['outpk' => $outpk, 'level' => $level]
        );
        $doRejectUrl = URL::temporarySignedRoute(
            'outsisa.email-approve.do-reject', now()->addDays(14), ['outpk' => $outpk, 'level' => $level]
        );
    
        // BARU -- FIX UTAMA: view SAMA dengan LO.
        return view('menu.shared.lo-email-confirm', [
            'docNo'        => $noOut,
            'penerima'     => $out->penerima,
            'keterangan'   => $out->keterangan,
            'alreadyDone'  => $alreadyDone,
            'levelLabel'   => $levelLabel,
            'items'        => $items,
            'doApproveUrl' => $doApproveUrl,
            'doRejectUrl'  => $doRejectUrl,
        ]);
    }
    
    public function outsisaEmailDoApprove(Request $request, $outpk, $level)
    {
        abort_unless($request->hasValidSignature(), 403, 'Link tidak valid atau sudah kedaluwarsa.');
    
        $level = (int) $level;
        if (!in_array($level, [1, 2, 3], true)) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => 'Level approval tidak valid.']);
        }
    
        $found = $this->findOutsisa($outpk);
        if (!$found) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => 'Data keluar tidak ditemukan.']);
        }
        [$out, $connection] = $found;
        $db = DB::connection($connection);
    
        if ($out->{"stsapv{$level}"} !== null) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => "Level {$level} sudah pernah diproses sebelumnya."]);
        }
    
        $db->table('outsisa')->where('outpk', $outpk)->update(["stsapv{$level}" => 1]);
        return view('menu.shared.lo-email-approve-done', ['success' => true, 'message' => "Berhasil approve LO."]);
    }
    
    public function outsisaEmailDoReject(Request $request, $outpk, $level)
    {
        abort_unless($request->hasValidSignature(), 403, 'Link tidak valid atau sudah kedaluwarsa.');
    
        $level = (int) $level;
        if (!in_array($level, [1, 2, 3], true)) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => 'Level approval tidak valid.']);
        }
    
        $found = $this->findOutsisa($outpk);
        if (!$found) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => 'Data keluar tidak ditemukan.']);
        }
        [$out, $connection] = $found;
        $db = DB::connection($connection);
    
        if ($out->{"stsapv{$level}"} !== null) {
            return view('menu.shared.lo-email-approve-done', ['success' => false, 'message' => "Level {$level} sudah pernah diproses sebelumnya."]);
        }
    
        $db->table('outsisa')->where('outpk', $outpk)->update(["stsapv{$level}" => 0]);
        return view('menu.shared.lo-email-approve-done', ['success' => true, 'message' => "Data keluar ditolak."]);
    }

    
}
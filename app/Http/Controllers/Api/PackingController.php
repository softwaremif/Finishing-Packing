<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PackingController extends Controller
{
    /**
     * BARU -- API dipanggil OLEH sistem EXIM (bukan lagi EXIM yang query
     * langsung ke DB packing). Packing yang MEMILIKI tabel pack/ship, jadi
     * Packing yang menulis, EXIM tinggal kirim contpk/exportpk/packpk[].
     *
     * mif DITENTUKAN PER PACKPK (query ke tabel po), BUKAN session -- EXIM
     * tidak punya konsep "session mif" seperti Packing, jadi tidak boleh
     * pakai resolveConnection(session('pos')). Packpk yang dikirim EXIM
     * bisa saja campuran mif 1 & 2 sekaligus.
     */

    // public function apiLoadCartonToContainer(Request $request)
    // {
    //     $validated = $request->validate([
    //         'contpk'   => 'required|integer',
    //         'exportpk' => 'required|integer',
    //         'packpk'   => 'required|array|min:1',
    //         'packpk.*' => 'integer',
    //     ]);

    //     // BARU -- FIX UTAMA: tentukan mif per packpk dengan CEK KEDUA
    //     // koneksi (bukan asumsi semua packpk dari 1 mif seperti kode lama
    //     // yang hardcode 'mysql_andon').
    //     $packpks = collect($validated['packpk'])->unique()->values();
    //     $packsByConn = [];
    //     foreach (['mysql_andon', 'mysql'] as $conn) {
    //         $found = DB::connection($conn)->table('pack')
    //             ->whereIn('packpk', $packpks)
    //             ->get();
    //         if ($found->isNotEmpty()) {
    //             $packsByConn[$conn] = $found;
    //         }
    //     }

    //     if (empty($packsByConn)) {
    //         return response()->json(['message' => 'Data carton tidak ditemukan.'], 404);
    //     }

    //     $allLoadedItems = [];
    //     $anyError = null;

    //     foreach ($packsByConn as $connName => $packs) {
    //         DB::connection($connName)->beginTransaction();
    //         try {
    //             $packs->each(function ($pack) {
    //                 $pack->t_cbm = ($pack->panjang !== null && $pack->lebar !== null && $pack->tinggi !== null)
    //                     ? ($pack->panjang * $pack->lebar * $pack->tinggi) / 1000000
    //                     : 0;
    //             });

    //             $popks = $packs->pluck('popk')->unique();
    //             $popkCartonInfo = [];
    //             foreach ($popks as $popk) {
    //                 $totalCarton = DB::connection($connName)->table('pack')
    //                     ->where('popk', $popk)->distinct()->count('carton');
    //                 $loadedCarton = DB::connection($connName)->table('pack')
    //                     ->where('popk', $popk)->whereNotNull('exportpk')->distinct()->count('carton');
    //                 $loadingNowCarton = $packs->where('popk', $popk)->pluck('carton')->unique()->count();
    //                 $popkCartonInfo[$popk] = [
    //                     'total' => $totalCarton, 'loaded' => $loadedCarton, 'now' => $loadingNowCarton,
    //                 ];
    //             }

    //             $lastPartCache = [];

    //             foreach ($packs as $pack) {
    //                 // BARU -- FIX UTAMA: 'exportdt' TIDAK LAGI ditulis di sini.
    //                 // pack.exportpk/pack.contpk/pack.part SUDAH CUKUP sebagai
    //                 // sumber kebenaran "carton ini dimuat di container mana" --
    //                 // EXIM tinggal baca lewat API getLoadedCartonsByContainer(),
    //                 // bukan menyimpan mirror-nya sendiri di exportdt.
    //                 $carton = $pack->carton;
    //                 if (!array_key_exists($carton, $lastPartCache)) {
    //                     $packsInCarton = $packs->where('carton', $carton);
    //                     $popksInCarton = $packsInCarton->pluck('popk')->unique();

    //                     $isFinalLoad = true;
    //                     foreach ($popksInCarton as $p) {
    //                         $info = $popkCartonInfo[$p];
    //                         if (($info['loaded'] + $info['now']) < $info['total']) {
    //                             $isFinalLoad = false;
    //                             break;
    //                         }
    //                     }

    //                     if ($isFinalLoad) {
    //                         $lastPartCache[$carton] = 10;
    //                     } else {
    //                         $candidateParts = [];
    //                         foreach ($popksInCarton as $p) {
    //                             $existingPart = DB::connection($connName)->table('pack')
    //                                 ->where('popk', $p)->where('exportpk', $validated['exportpk'])->value('part');
    //                             if ($existingPart) {
    //                                 $candidateParts[] = $existingPart;
    //                             } else {
    //                                 $maxPart = DB::connection($connName)->table('pack')
    //                                     ->where('popk', $p)->where('part', '!=', 10)->max('part');
    //                                 $candidateParts[] = ($maxPart ?? 0) + 1;
    //                             }
    //                         }
    //                         $lastPartCache[$carton] = max($candidateParts);
    //                     }
    //                 }
    //                 $part = $lastPartCache[$carton];

    //                 DB::connection($connName)->table('pack')
    //                     ->where('packpk', $pack->packpk)
    //                     ->update([
    //                         'exportpk' => $validated['exportpk'],
    //                         'contpk'   => $validated['contpk'],
    //                         'part'     => $part,
    //                     ]);

    //                 $insertColumns = ['packpk', 'popk', 'urut', 'carton', 'nobar', 'OP', 'POno', 'customer', 'material', 'nw', 'gw', 'meas', 'secsz'];
    //                 for ($i = 1; $i <= 40; $i++) $insertColumns[] = "qtyp{$i}";
    //                 $insertColumns[] = 'pcsp';
    //                 for ($i = 1; $i <= 40; $i++) $insertColumns[] = "qty{$i}";
    //                 $insertColumns = array_merge($insertColumns, ['pcs', 'jmlpcs', 'waktu', 'tanggal', 'status', 'part', 'keterangan', 'pinjam', 'kembali', 'fca']);

    //                 DB::connection($connName)->table('ship')->insertUsing(
    //                     $insertColumns,
    //                     DB::connection($connName)->table('pack')->select($insertColumns)->where('packpk', $pack->packpk)
    //                 );

    //                 $allLoadedItems[] = [
    //                     'exportdtpk' => null, // BARU -- sudah tidak ada exportdt, referensi cukup pakai packpk
    //                     'packpk'     => $pack->packpk,
    //                     'carton'     => $pack->carton,
    //                     'pcsp'       => $pack->pcsp,
    //                     't_cbm'      => $pack->t_cbm ?? 0,
    //                 ];
    //             }

    //             DB::connection($connName)->commit();
    //         } catch (\Throwable $e) {
    //             DB::connection($connName)->rollBack();
    //             $anyError = $e;
    //             report($e);
    //         }
    //     }

    //     if ($anyError && empty($allLoadedItems)) {
    //         return response()->json(['message' => 'Terjadi kesalahan saat menyimpan data.'], 500);
    //     }

    //     $summary = $this->getContainerSummary($validated['exportpk'], $validated['contpk']);

    //     return response()->json([
    //         'message'      => count(collect($allLoadedItems)->pluck('carton')->unique()) . ' carton berhasil dimuat ke container.',
    //         'loaded_items' => $allLoadedItems,
    //         'totqty'       => $summary['totqty'],
    //         'totctn'       => $summary['totctn'],
    //     ]);
    // }

    public function apiLoadCartonToContainer(Request $request)
    {
        $validated = $request->validate([
            'contpk'   => 'required|integer',
            'exportpk' => 'required|integer',
            'packpk'   => 'required|array|min:1',
            'packpk.*' => 'integer',
        ]);

        $packpks = collect($validated['packpk'])->unique()->values();
        $packsByConn = [];
        foreach (['mysql_andon', 'mysql'] as $conn) {
            $found = DB::connection($conn)->table('pack')
                ->whereIn('packpk', $packpks)
                ->get();
            if ($found->isNotEmpty()) {
                $packsByConn[$conn] = $found;
            }
        }

        if (empty($packsByConn)) {
            return response()->json(['message' => 'Data carton tidak ditemukan.'], 404);
        }

        $foundPackpks = collect();
        foreach ($packsByConn as $found) {
            $foundPackpks = $foundPackpks->merge($found->pluck('packpk'));
        }
        $missingPackpks = $packpks->diff($foundPackpks);
        if ($missingPackpks->isNotEmpty()) {
            return response()->json([
                'message' => 'Sebagian carton tidak ditemukan di database: ' . $missingPackpks->implode(', '),
            ], 404);
        }

        $allLoadedItems = [];
        $failedConnections = [];

        foreach ($packsByConn as $connName => $packs) {
            DB::connection($connName)->beginTransaction();
            $connLoadedItems = [];

            try {
                $packs->each(function ($pack) {
                    $pack->t_cbm = ($pack->panjang !== null && $pack->lebar !== null && $pack->tinggi !== null)
                        ? ($pack->panjang * $pack->lebar * $pack->tinggi) / 1000000
                        : 0;
                });

                // GANTI: group berdasarkan POno (1 PO), BUKAN popk (bisa banyak popk per PO)
                $poNos = $packs->pluck('POno')->unique();

                /*
                |------------------------------------------------------------
                | TENTUKAN NOMOR PART BERJALAN PER PO (POno), BUKAN PER POPK
                |------------------------------------------------------------
                */
                $poPartCache = [];
                foreach ($poNos as $poNo) {
                    $existingPart = DB::connection($connName)->table('pack')
                        ->where('POno', $poNo)
                        ->where('exportpk', $validated['exportpk'])
                        ->value('part');

                    if ($existingPart) {
                        $poPartCache[$poNo] = $existingPart;
                    } else {
                        $maxPart = DB::connection($connName)->table('pack')
                            ->where('POno', $poNo)
                            ->where('part', '!=', 10)
                            ->max('part');

                        $poPartCache[$poNo] = ($maxPart ?? 0) + 1;
                    }
                }

                foreach ($packs as $pack) {
                    $part = $poPartCache[$pack->POno];

                    DB::connection($connName)->table('pack')
                        ->where('packpk', $pack->packpk)
                        ->update([
                            'exportpk' => $validated['exportpk'],
                            'contpk'   => $validated['contpk'],
                            'part'     => $part,
                        ]);

                    $insertColumns = ['packpk', 'popk', 'urut', 'carton', 'nobar', 'OP', 'POno', 'customer', 'material', 'nw', 'gw', 'meas', 'secsz'];
                    for ($i = 1; $i <= 40; $i++) $insertColumns[] = "qtyp{$i}";
                    $insertColumns[] = 'pcsp';
                    for ($i = 1; $i <= 40; $i++) $insertColumns[] = "qty{$i}";
                    $insertColumns = array_merge($insertColumns, ['pcs', 'jmlpcs', 'waktu', 'tanggal', 'status', 'part', 'keterangan', 'pinjam', 'kembali', 'fca']);

                    DB::connection($connName)->table('ship')->insertUsing(
                        $insertColumns,
                        DB::connection($connName)->table('pack')->select($insertColumns)->where('packpk', $pack->packpk)
                    );

                    $connLoadedItems[] = [
                        'exportdtpk' => null,
                        'packpk'     => $pack->packpk,
                        'carton'     => $pack->carton,
                        'pcsp'       => $pack->pcsp,
                        't_cbm'      => $pack->t_cbm ?? 0,
                    ];
                }

                /*
                |------------------------------------------------------------
                | RECALCULATION: cek complete per PO (POno), BUKAN per popk
                |------------------------------------------------------------
                */
                foreach ($poNos as $poNo) {
                    $totalCarton = DB::connection($connName)->table('pack')
                        ->where('POno', $poNo)
                        ->distinct()
                        ->count('carton');

                    $totalLoadedNow = DB::connection($connName)->table('pack')
                        ->where('POno', $poNo)
                        ->whereNotNull('exportpk')
                        ->distinct()
                        ->count('carton');

                    if ($totalLoadedNow >= $totalCarton) {
                        $packpksToFinalize = DB::connection($connName)->table('pack')
                            ->where('POno', $poNo)
                            ->where('exportpk', $validated['exportpk'])
                            ->pluck('packpk');

                        DB::connection($connName)->table('pack')
                            ->whereIn('packpk', $packpksToFinalize)
                            ->update(['part' => 10]);

                        DB::connection($connName)->table('ship')
                            ->whereIn('packpk', $packpksToFinalize)
                            ->update(['part' => 10]);
                    }
                }

                DB::connection($connName)->commit();
                $allLoadedItems = array_merge($allLoadedItems, $connLoadedItems);

            } catch (\Throwable $e) {
                DB::connection($connName)->rollBack();
                $failedConnections[$connName] = $e->getMessage() . ' | ' . $e->getFile() . ':' . $e->getLine();
                report($e);
            }
        }

        if (!empty($failedConnections)) {
            return response()->json([
                'message' => 'Sebagian carton GAGAL dimuat (koneksi: ' . implode(', ', array_keys($failedConnections)) . '). '
                    . 'Carton yang berhasil dimuat: ' . count($allLoadedItems) . ' dari ' . $packpks->count() . '.',
                'partial_success' => !empty($allLoadedItems),
                'loaded_items'    => $allLoadedItems,
                'failed_details'  => $failedConnections,
            ], 500);
        }

        if (empty($allLoadedItems)) {
            return response()->json(['message' => 'Tidak ada carton yang berhasil dimuat.'], 500);
        }

        $summary = $this->getContainerSummary($validated['exportpk'], $validated['contpk']);

        return response()->json([
            'message'      => count(collect($allLoadedItems)->pluck('carton')->unique()) . ' carton berhasil dimuat ke container.',
            'loaded_items' => $allLoadedItems,
            'totqty'       => $summary['totqty'],
            'totctn'       => $summary['totctn'],
        ]);
    }

    // public function apiGetLoadedCartonsByContainer(Request $request)
    // {
    //     $contpk   = (int) $request->query('contpk');
    //     $exportpk = (int) $request->query('exportpk');

    //     $rows = collect();
    //     foreach (['mysql_andon', 'mysql'] as $conn) {
    //         $found = DB::connection($conn)->table('pack')
    //             ->where('contpk', $contpk)
    //             ->where('exportpk', $exportpk)
    //             ->get();
    //         $rows = $rows->merge($found);
    //     }

    //     $rows = $rows->unique('packpk')->values();

    //     // BARU -- FIX UTAMA: ambil status ship (utk deteksi "sudah Shipped",
    //     // status = 6) per packpk, dicek di kedua koneksi.
    //     $shipStatusByPackpk = collect();
    //     foreach (['mysql_andon', 'mysql'] as $conn) {
    //         $packpks = $rows->pluck('packpk')->unique()->values();
    //         if ($packpks->isEmpty()) continue;
    //         $shipRows = DB::connection($conn)->table('ship')
    //             ->whereIn('packpk', $packpks)
    //             ->get(['packpk', 'status']);
    //         foreach ($shipRows as $sr) {
    //             $shipStatusByPackpk->put($sr->packpk, (int) $sr->status);
    //         }
    //     }

    //     $grouped = $rows->groupBy(function ($r) {
    //         return $r->carton . '|' . $r->POno . '|' . $r->OP;
    //     })->map(function ($items) use ($shipStatusByPackpk) {
    //         $first = $items->first();

    //         // BARU -- FIX UTAMA: shipped = true kalau SALAH SATU packpk di
    //         // grup ini sudah berstatus 6 (Shipped) di tabel ship.
    //         $shipped = $items->contains(function ($item) use ($shipStatusByPackpk) {
    //             return $shipStatusByPackpk->get($item->packpk) === 6;
    //         });

    //         return [
    //             'carton'  => $first->carton,
    //             'POno'    => $first->POno,
    //             'OP'      => $first->OP,
    //             'pcsp'    => $items->sum('pcsp'),
    //             't_cbm'   => $first->panjang && $first->lebar && $first->tinggi
    //                 ? ($first->panjang * $first->lebar * $first->tinggi) / 1000000
    //                 : 0,
    //             'packpks' => $items->pluck('packpk')->unique()->values(),
    //             'shipped' => $shipped, // BARU
    //         ];
    //     })->values();

    //     return response()->json(['cartons' => $grouped]);
    // }

    
    public function apiGetLoadedCartonsByContainer(Request $request)
    {
        $contpk   = (int) $request->query('contpk');
        $exportpk = (int) $request->query('exportpk');

        $rows = collect();

        foreach (['mysql_andon', 'mysql'] as $conn) {
            $found = DB::connection($conn)
                ->table('pack')
                ->where('contpk', $contpk)
                ->where('exportpk', $exportpk)
                ->get();

            $rows = $rows->merge($found);
        }

        $rows = $rows->unique('packpk')->values();

        // Ambil status ship berdasarkan packpk
        $shipStatusByPackpk = collect();

        foreach (['mysql_andon', 'mysql'] as $conn) {
            $packpks = $rows->pluck('packpk')->unique()->values();

            if ($packpks->isEmpty()) {
                continue;
            }

            $shipRows = DB::connection($conn)
                ->table('ship')
                ->whereIn('packpk', $packpks)
                ->get(['packpk', 'status']);

            foreach ($shipRows as $sr) {
                $shipStatusByPackpk->put(
                    $sr->packpk,
                    (int) $sr->status
                );
            }
        }

        $grouped = $rows
            ->groupBy(function ($r) {
                return $r->carton . '|' . $r->POno . '|' . $r->OP;
            })
            ->map(function ($items) use ($shipStatusByPackpk) {

                $first = $items->first();

                // Ambil status tertinggi dari semua packpk dalam grup
                $maxStatus = $items
                    ->map(function ($item) use ($shipStatusByPackpk) {
                        return $shipStatusByPackpk->get($item->packpk, 0);
                    })
                    ->max();

                // Tentukan progress
                if ($maxStatus >= 8) {
                    $shipStatus = 'shipped';
                } elseif ($maxStatus >= 6) {
                    $shipStatus = 'loading';
                } elseif ($maxStatus >= 3) {
                    $shipStatus = 'progress';
                } else {
                    $shipStatus = null;
                }

                return [
                    'carton'     => $first->carton,
                    'POno'       => $first->POno,
                    'OP'         => $first->OP,
                    'pcsp'       => $items->sum('pcsp'),

                    't_cbm'      => $first->panjang && $first->lebar && $first->tinggi
                        ? ($first->panjang * $first->lebar * $first->tinggi) / 1000000
                        : 0,

                    'packpks'    => $items->pluck('packpk')->unique()->values(),

                    'ship_status' => $shipStatus,
                    'ship_status_code' => $maxStatus,
                ];
            })
            ->values();

        return response()->json([
            'cartons' => $grouped
        ]);
    }
    private function getContainerSummary(int $exportpk, int $contpk): array
    {
        $totqty = 0;
        $totctnCartons = collect();
        foreach (['mysql'] as $conn) {
            $rows = DB::connection($conn)->table('pack')
                ->where('exportpk', $exportpk)
                ->where('contpk', $contpk)
                ->get(['carton', 'pcsp']);
            $totqty += $rows->sum('pcsp');
            $totctnCartons = $totctnCartons->merge($rows->pluck('carton'));
        }
        return ['totqty' => $totqty, 'totctn' => $totctnCartons->unique()->count()];
    }

    public function apiUnloadCartonFromContainer(Request $request)
    {
        $validated = $request->validate([
            'packpk'   => 'required|array|min:1',
            'packpk.*' => 'integer',
            'exportpk' => 'required|integer',
            'contpk'   => 'required|integer',
        ]);

        $remainingPackpks = collect($validated['packpk'])->unique()->values();
        $unloadedCount = 0;
        $anyFound = false;

        foreach (['mysql_andon', 'mysql'] as $connName) {
            if ($remainingPackpks->isEmpty()) break; // BARU -- FIX UTAMA: semua sudah diproses, tidak perlu cek koneksi lain

            $found = DB::connection($connName)->table('pack')
                ->whereIn('packpk', $remainingPackpks)
                ->pluck('packpk');

            if ($found->isEmpty()) continue;
            $anyFound = true;

            DB::connection($connName)->beginTransaction();
            try {
                DB::connection($connName)->table('pack')
                    ->whereIn('packpk', $found)
                    ->update([
                        'exportpk' => null,
                        'contpk'   => null,
                        'part'     => null,
                    ]);

                DB::connection($connName)->table('ship')
                    ->whereIn('packpk', $found)
                    ->delete();

                DB::connection($connName)->commit();
                $unloadedCount += $found->count();

                // BARU -- FIX UTAMA: buang packpk yang SUDAH diproses dari daftar
                // pencarian -- mencegah koneksi berikutnya (kalau kebetulan
                // menunjuk ke DB fisik yang sama, seperti saat testing) ikut
                // "menemukan" & menghitung baris yang SAMA lagi.
                $remainingPackpks = $remainingPackpks->diff($found)->values();
            } catch (\Throwable $e) {
                DB::connection($connName)->rollBack();
                report($e);
                return response()->json(['message' => 'Gagal mengeluarkan carton.'], 500);
            }
        }

        if (!$anyFound) {
            return response()->json(['message' => 'Data carton tidak ditemukan.'], 404);
        }

        $summary = $this->getContainerSummary($validated['exportpk'], $validated['contpk']);

        return response()->json([
            'success' => true,
            'message' => "{$unloadedCount} carton berhasil dikeluarkan dari container.",
            'totqty'  => $summary['totqty'],
            'totctn'  => $summary['totctn'],
        ]);
    }

    public function apiGetAllLoadedCartons(Request $request)
    {
        $rows = collect();
        foreach (['mysql_andon' => 1, 'mysql' => 2] as $connName => $mifVal) {
            $found = DB::connection($connName)->table('pack')
                ->whereNotNull('exportpk')
                ->whereNotNull('contpk')
                ->get(['packpk', 'popk', 'carton', 'exportpk', 'contpk', 'pcsp']);
    
            $found = $found->map(function ($r) use ($mifVal) {
                $r->mif = $mifVal;
                return $r;
            });
    
            $rows = $rows->merge($found);
        }
    
        $rows = $rows->unique('packpk')->values();
    
        return response()->json(['rows' => $rows]);
    }
}

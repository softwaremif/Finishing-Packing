<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Resolve URL foto order (ordpk -> URL) -- SATU sumber kebenaran, dipakai
 * SEMUA halaman index di sistem yang butuh foto order (sebelumnya logic
 * ini terduplikasi di banyak controller, mis. TransferFinishingController,
 * FinishingPackingController, dst -- masing-masing punya salinan sendiri).
 *
 * Aturan sumber foto per ordpk:
 *   stsfoto = 1 -> foto dari GIS_FOTO_BASE_URL
 *   stsfoto = 2 -> foto dari PRODUCTION_FOTO_BASE_URL
 *   lainnya     -> fallback ke foto sample TERBARU (SOM_FOTO_BASE_URL)
 *   tidak ketemu / server foto down -> no-image lokal aplikasi ini
 */
class OrderImageService
{
    /**
     * @param  array<int>  $ordpks
     * @return array<int, string>  [ordpk => image_url]
     */
    public function resolve(array $ordpks): array
    {
        $noImageUrl = asset('public/css/images/no-img.png');

        $ordpks = collect($ordpks)->filter()->unique()->values()->all();
        if (empty($ordpks)) {
            return [];
        }

        // Default semua ordpk -> no-image dulu.
        $result = array_fill_keys($ordpks, $noImageUrl);

        $gisFotoBase        = rtrim(config('services.foto.gis_base'), '/');
        $productionFotoBase = rtrim(config('services.foto.production_base'), '/');
        $sampleFotoBase     = rtrim(config('services.foto.sample_base'), '/');

        // GANTI -- FIX UTAMA: try/catch -- kalau server foto (mysql_gis)
        // sedang down/connection refused, jangan sampai melempar exception
        // ke pemanggil -- cukup return semua no-image (sudah di-default di
        // atas), TIDAK menghentikan halaman yang memanggil service ini.
        try {
            $ordRows = DB::connection('mysql_gis')->table('ord')
                ->whereIn('ordpk', $ordpks)
                ->get(['ordpk', 'srno', 'foto', 'foto2', 'stsfoto']);
        } catch (\Throwable $e) {
            report($e);
            return $result; // semua tetap no-image
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
                report($e); // mysql_sample down -- foto1/GIS/Production masih bisa jalan
            }
        }

        foreach ($ordpks as $ordpk) {
            $ord = $ordByOrdpk->get($ordpk);
            if (!$ord) {
                continue; // tetap no-image (default)
            }
            $stsfoto = $ord->stsfoto ?? null;
            $foto1   = $ord->foto ?? null;
            $srno    = $ord->srno ?? null;
            $srpk    = $srno ? ($srpkBySrno[$srno] ?? null) : null;
            $foto2   = $srpk ? ($fotoBySrpk[$srpk] ?? null) : ($ord->foto2 ?? null);

            if ($stsfoto == 1) {
                $result[$ordpk] = !empty($foto1) ? "{$gisFotoBase}/{$foto1}" : $noImageUrl;
            } elseif ($stsfoto == 2) {
                $result[$ordpk] = !empty($foto1) ? "{$productionFotoBase}/{$foto1}" : $noImageUrl;
            } else {
                $result[$ordpk] = !empty($foto2) ? "{$sampleFotoBase}/{$foto2}" : $noImageUrl;
            }
        }

        return $result;
    }

    /**
     * Helper untuk pola lama "addOrderImageToRows($rows)" -- terima
     * Collection baris yang punya properti ->ordpk, isi ->order_image
     * langsung di tiap baris (mutasi in-place, SAMA seperti versi lama).
     * Memudahkan migrasi dari controller yang masih pakai pola ini.
     */
    public function attachToRows($rows, string $ordpkField = 'ordpk', string $targetField = 'order_image'): void
    {
        if ($rows->isEmpty()) {
            return;
        }
        $noImageUrl = asset('public/css/images/no-img.png');
        foreach ($rows as $r) {
            $r->{$targetField} = $noImageUrl;
        }
        $ordpks = $rows->pluck($ordpkField)->filter()->unique()->values()->all();
        if (empty($ordpks)) {
            return;
        }
        $images = $this->resolve($ordpks);
        foreach ($rows as $r) {
            $ordpk = $r->{$ordpkField} ?? null;
            if ($ordpk && isset($images[$ordpk])) {
                $r->{$targetField} = $images[$ordpk];
            }
        }
    }
}
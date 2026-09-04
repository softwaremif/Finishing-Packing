<?php

namespace App\Http\Controllers\FinishedGoods;

use App\Http\Controllers\Controller;
use App\Services\ShipService;
use Illuminate\Http\Request;

/**
 * Controller tipis: hanya menerima request, delegasi ke ShipService,
 * dan mengembalikan response/view.
 */
class FinishedGoodsController extends Controller
{
    /** @var ShipService */
    protected $shipService;

    public function __construct(ShipService $shipService)
    {
        $this->shipService = $shipService;
    }

    public function index(Request $request)
    {
        return view('menu.finGoods.index');
    }

    public function getList(Request $request)
    {
        $result = $this->shipService->getListShip($request);

        return response()->json($result);
    }

    /**
     * Rincian satu grup POno+OP per popk+part — dipanggil popup di grid
     * saat tombol detail baris agregat diklik.
     * Route (tambahkan di routes/web.php):
     *   Route::get('finGoods/popup', [FinishedGoodsController::class, 'popupList'])
     *       ->name('finGoods.popup');
     */
    public function popupList(Request $request)
    {
        $rows = $this->shipService->getPoOpBreakdown(
            (string) $request->query('pono'),
            (string) $request->query('op'),
            (string) $request->query('poref')
        );

        return response()->json(['rows' => $rows]);
    }

    /**
     * Halaman detail -- GLOBAL MURNI: mencakup SELURUH baris dalam satu
     * PO+OP, lintas semua place/customer, color/material, dan secondary
     * size. Tidak lagi terikat satu popk+part tertentu -- breakdown,
     * scan barcode, dan bulk action SEMUA beroperasi di scope PO+OP.
     * Datanya SAMA dengan printGlobal() (getFinishedGoodsPrintGlobal),
     * bedanya view di sini interaktif (scan + multi-select), sedangkan
     * print-global read-only.
     * Route (tambahkan di routes/web.php, GANTI route lama {popk}/{part}):
     *   Route::get('finGoods/detail', [FinishedGoodsController::class, 'detail'])
     *       ->name('finGoods.detail');
     * Dipanggil sebagai: finGoods/detail?pono=...&op=...
     */
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

        return view('menu.finGoods.detail', $data);
    }

    /**
     * Halaman LAPORAN (print) — dipakai saat baris sudah dikunci
     * (semua carton status 7): tombol detail di grid diganti tombol
     * laporan yang membuka halaman ini. Datanya sama dengan detail,
     * tapi view-nya polos ala print (tanpa scan / multi-select /
     * sticky bar), lengkap dengan info PO + kolom tanda tangan.
     * Route (tambahkan di routes/web.php):
     *   Route::get('finGoods/print/{popk}/{part}', [FinishedGoodsController::class, 'print'])
     *       ->name('finGoods.print');
     */
    public function print(int $popk, int $part, Request $request)
    {
        $data = $this->shipService->getFinishedGoodsDetail($popk, $part, $request);

        if ($data === null) {
            abort(404, 'Data PO tidak ditemukan.');
        }

        return view('menu.finGoods.print', $data);
    }

    /**
     * Laporan GLOBAL: mencakup SELURUH baris dalam satu PO+OP -- lintas
     * semua place/customer, color/material, dan secondary size. Berbeda
     * dari print() biasa yang scoped popk+part. Dipanggil dari tombol
     * "Cetak Global" di popup rincian grid (index.blade.php), memakai
     * parameter query ?pono=..&op=.. (bukan path popk/part).
     * Route (tambahkan di routes/web.php):
     *   Route::get('finGoods/printGlobal', [FinishedGoodsController::class, 'printGlobal'])
     *       ->name('finGoods.printGlobal');
     */
    public function printGlobal(Request $request)
    {
        $POno = (string) $request->query('pono');
        $OP   = (string) $request->query('op');

        $data = $this->shipService->getFinishedGoodsPrintGlobal($POno, $OP);

        if ($data === null) {
            abort(404, 'Data PO tidak ditemukan.');
        }

        return view('menu.finGoods.laporan.pdf-global', $data);
    }

    /**
     * Endpoint scan barcode / input manual nobar -- GLOBAL (scoped
     * PO+OP, bukan popk+part). Operator bisa scan carton color/customer
     * manapun dalam PO+OP yang sama tanpa pindah halaman.
     * Route (tambahkan di routes/web.php, GANTI route lama {popk}/{part}):
     *   Route::post('finGoods/scan', [FinishedGoodsController::class, 'scan'])
     *       ->name('finGoods.scan');
     */
    public function scan(Request $request)
    {
        $POno = (string) $request->input('pono');
        $OP   = (string) $request->input('op');

        $result = $this->shipService->scanNobarGlobal($POno, $OP, (string) $request->input('nobar'));

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Endpoint aksi massal carton terpilih (Proses Inspect / Proses
     * Shipment / Kembalikan ke Stuffing) -- GLOBAL (scoped PO+OP).
     * Route (tambahkan di routes/web.php, GANTI route lama {popk}/{part}):
     *   Route::post('finGoods/bulk', [FinishedGoodsController::class, 'bulkAction'])
     *       ->name('finGoods.bulk');
     */
    public function bulkAction(Request $request)
    {
        $POno    = (string) $request->input('pono');
        $OP      = (string) $request->input('op');
        $action  = (string) $request->input('action');
        $cartons = (array) $request->input('cartons', []);

        $result = $this->shipService->bulkCartonActionGlobal($POno, $OP, $action, $cartons);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Kunci shipment: semua carton popk+part dinaikkan ke status = 7.
     * Dipanggil dari tombol gembok di grid (hanya tampil kalau semua
     * carton sudah berstatus >= 6).
     * Route (tambahkan di routes/web.php):
     *   Route::post('finGoods/{popk}/{part}/lock', [FinishedGoodsController::class, 'lockShipment'])
     *       ->name('finGoods.lock');
     */
    public function lockShipment(int $popk, int $part, Request $request)
    {
        $result = $this->shipService->lockShipment($popk, $part);

        return response()->json($result, $result['success'] ? 200 : 422);
    }

    /**
     * Buka kunci shipment: hanya user dengan guserpk = 34 yang boleh.
     * Semua carton popk+part berstatus 7 dikembalikan ke status 6.
     * Route (tambahkan di routes/web.php):
     *   Route::post('finGoods/{popk}/{part}/unlock', [FinishedGoodsController::class, 'unlockShipment'])
     *       ->name('finGoods.unlock');
     */
    public function unlockShipment(int $popk, int $part, Request $request)
    {
        if ((int) session('guserpk') !== 34) {
            return response()->json([
                'success' => false,
                'message' => 'Anda tidak punya izin untuk membuka kunci shipment.',
            ], 403);
        }

        $result = $this->shipService->unlockShipment($popk, $part);

        return response()->json($result, $result['success'] ? 200 : 422);
    }
}
<?php

use App\Http\Controllers\Aktifitas\BarangController;
use App\Http\Controllers\Aktifitas\PembelianCashTempoController;
use App\Http\Controllers\Aktifitas\PpkController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\FinishedGoods\FinishedGoodsController;
use App\Http\Controllers\FinishgoodStuffing\FinishgoodStuffingController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Inspection\InspectionController;
use App\Http\Controllers\Laporan\LaporanController;
use App\Http\Controllers\Laporan\LapPoController;
use App\Http\Controllers\Laporan\LapPrController;
use App\Http\Controllers\Laporan\LapStokController;
use App\Http\Controllers\Laporan\PembayaranCGController;
use App\Http\Controllers\Laporan\PembelianCTController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\OrderListController;
use App\Http\Controllers\Packing\PackingController;
use App\Http\Controllers\Purchase\PoOrderController;
use App\Http\Controllers\Purchase\PoReqController;
use App\Http\Controllers\SegelPacking\SegelPackingController;
use App\Http\Controllers\SimpanController;
use App\Http\Controllers\SisaProduksi\SisaProduksiController;
use App\Http\Controllers\SisaSample\SisaSampleController;
use App\Http\Controllers\Tabel\KategoriBarangController;
use App\Http\Controllers\Tabel\StokBarangController;
use App\Http\Controllers\Tabel\SupplierController;
use App\Http\Controllers\Transfer\TransferController;
use App\Http\Controllers\TransferFinishing\TransferFinishingController;
use Illuminate\Support\Facades\Route;


    // 17: QA, FCA
    // 23: Adm Finishing
    // 34: Support
    // 35: Stuffing
    // 36: LO, Gudang LO
    // 38: Fingoods, Adm Fingoods

Route::get('/', [LoginController::class, 'index'])->name('login.index');
Route::post('login', [LoginController::class, 'login'])->name('login');
Route::get('loading', [LoginController::class, 'loading'])->name('loading');
Route::get('logout', [LoginController::class, 'logout'])->name('logout');


Route::middleware(['check', 'db.pos'])->group(function () {
    Route::post('updtpswdb', [LoginController::class, 'updtpswdb'])->name('updtpswdb');
    Route::get('password', [LoginController::class, 'password'])->name('password');

    // stuffing n shipment
    Route::middleware(['guser:35,34'])->group(function () {
        include 'StuffnShip.php';
    });

    // ppp
    Route::get('/getListPack/list', [FinishedGoodsController::class, 'getListPack'])->name('getListPack.list');
    Route::get('/getListPack/detail-by-po-op', [PackingController::class, 'detailByPoOp'])->name('getListPack.detail-by-po-op');
    // ppp
    Route::get('tf-finishing/detail-by-po-op', [TransferFinishingController::class, 'detailByPoOp'])->name('tf_finishing.detail-by-po-op');
    Route::get('polibag/detail-by-po-op', [TransferController::class, 'detailByPoOp'])->name('transfer.detail-by-po-op');
    Route::get('/packing/list', [PackingController::class, 'getList'])->name('packing.list');
    Route::get('/stuffing/popup', [FinishedGoodsController::class, 'popupList'])->name('finGoods.popup');
    Route::get('/packing/detail-by-po-op', [PackingController::class, 'detailByPoOp'])->name('packing.detail-by-po-op');

    // MENU SEGEL PACKING
    Route::post('/packing/segel', [SegelPackingController::class, 'store'])->name('packing.segel.store');
    Route::get('packing/segel/partial-status/{popk}', [SegelPackingController::class, 'partialStatus'])->name('packing.segel.partial-status');
    Route::post('/packing/segel/cancel', [SegelPackingController::class, 'cancel'])->name('packing.segel.cancel');

    Route::middleware(['guser:23,34,35'])->group(function () {
        // MENU TRANSFER TO FINISHING
        Route::get('/tf-finishing', [TransferFinishingController::class, 'index'])->name('tf_finishing.index');
        Route::get('/tf-finishing/list', [TransferFinishingController::class, 'getList'])->name('tf_finishing.list');

        Route::get('/tf-finishing/input/{popk}', [TransferFinishingController::class, 'inputTransfer'])->name('tf_finishing.input');
        Route::post('/tf-finishing/save', [TransferFinishingController::class, 'saveTransfer'])->name('tf_finishing.save');
        Route::delete('/tf-finishing/delete/{bjpk}', [TransferFinishingController::class, 'delete'])->name('tf_finishing.delete');
        Route::get('tf-finishing/{popk}/detail-list', [TransferFinishingController::class, 'detailList'])->name('tf_finishing.detail.list');
        Route::get('tf-finishing/{popk}/breakdown-summary', [TransferFinishingController::class, 'breakdownSummary'])->name('tf_finishing.breakdown-summary');

        Route::get('/tf-finishing/debug/size-format', [TransferFinishingController::class, 'debugSizeFormat']);

        Route::get('/tf-finishing/api/output-by-op', [TransferFinishingController::class, 'outputByOp'])->name('tf_finishing.output_by_op');

        // MENU POLIBAG
        Route::get('/polibag', [TransferController::class, 'index'])->name('transfer.index');
        Route::get('/polibag/list', [TransferController::class, 'getList'])->name('transfer.list');

        Route::get('/polibag/input/{popk}', [TransferController::class, 'inputTransfer'])->name('transfer.input');
        Route::post('/polibag/save', [TransferController::class, 'saveTransfer'])->name('transfer.save');
        Route::delete('/polibag/delete/{bjpk}', [TransferController::class, 'delete'])->name('transfer.delete');
        Route::get('polibag/{popk}/detail-list', [TransferController::class, 'detailList'])->name('transfer.detail.list');
        Route::get('polibag/{popk}/breakdown-summary', [TransferController::class, 'breakdownSummary'])->name('transfer.breakdown-summary');

        // MENU PACKING
        Route::get('/packing', [PackingController::class, 'index'])->name('packing.index');
        Route::get('/packing/input/{popk}', [PackingController::class, 'inputPacking'])->name('packing.input');
        Route::get('/packing/{popk}/breakdown-summary', [PackingController::class, 'breakdownSummary'])->name('packing.breakdownSummary');
        Route::get('/packing/list-detail/{popk}', [PackingController::class, 'listDetail'])->name('packing.list.detail');
        Route::post('/packing/save-header', [PackingController::class, 'saveHeader'])->name('packing.saveHeader');
        Route::post('/packing/store', [PackingController::class, 'store'])->name('packing.store');
        Route::post('/packing/update-ctn', [PackingController::class, 'updateCtn'])->name('packing.update-ctn');
        Route::post('/packing/urut', [PackingController::class, 'urutCtn'])->name('packing.urut');
        Route::delete('/packing/delete-multiple', [PackingController::class, 'deleteMultiple'])->name('packing.delete-selected');
        Route::post('/packing/copy-selected', [PackingController::class, 'copyMultiple'])->name('packing.copy-selected');
        Route::delete('/packing/delete-actual', [PackingController::class, 'deleteActual'])->name('packing.delete-actual');
        Route::delete('/packing/delete-pack/{packpk}', [PackingController::class, 'deletePack'])->name('packing.delete-pack');
        Route::get('/packing/gabung-list', [PackingController::class, 'gabungList'])->name('packing.gabung-list');
        Route::post('/packing/update-gabung', [PackingController::class, 'updateGabung'])->name('packing.update-gabung');
        Route::get('/packing/history/{packpk}', [PackingController::class, 'historyActual'])->name('packing.history-actual');

        Route::post('/packing/update-segel', [PackingController::class, 'updateSegelStatus'])->name('packing.update-segel');

        // MENU PRINT LAPORAN PDF PACKING
        Route::get('/packing/laporan/pdf/{popk}', [LaporanController::class, 'print'])->name('laporan.pdf');
        Route::get('/packing/laporan-global/pdf', [LaporanController::class, 'printGlobal'])->name('laporan.pdf.global');

        // PACKING NEW V2
        Route::get('/packing/input-global', [PackingController::class, 'inputPackingGlobal'])->name('packing.input.global');
        Route::get('/packing/breakdown-summary-global', [PackingController::class, 'breakdownSummaryGlobal'])->name('packing.breakdownSummaryGlobal');
        Route::get('/packing/cards-info-global', [PackingController::class, 'cardsInfoGlobal'])->name('packing.cardsInfoGlobal');
        Route::get('/packing/list-detail-global', [PackingController::class, 'listDetailGlobal'])->name('packing.list.detail.global');
        Route::post('/packing/store-global', [PackingController::class, 'storeGlobal'])->name('packing.store.global');
        Route::post('/packing/update-ctn-global', [PackingController::class, 'updateCtnGlobal'])->name('packing.update-ctn.global');
        Route::delete('/packing/delete-actual-global', [PackingController::class, 'deleteActualGlobal'])->name('packing.delete-actual.global');
        Route::delete('/packing/delete-multiple-global', [PackingController::class, 'deleteMultipleGlobal'])->name('packing.delete-selected.global');
        Route::post('/packing/copy-selected-global', [PackingController::class, 'copyMultipleGlobal'])->name('packing.copy-selected.global');
        Route::post('/packing/update-segel-global', [PackingController::class, 'updateSegelStatusGlobal'])->name('packing.update-segel.global');
        Route::post('/packing/urut-global', [PackingController::class, 'urutCtnGlobal'])->name('packing.urut.global');
        Route::get('/packing/combos-global', [PackingController::class, 'combosGlobal'])->name('packing.combosGlobal');
        Route::post('/packing/save-header-global', [PackingController::class, 'saveHeaderGlobal'])->name('packing.saveHeaderGlobal');
        Route::get('/packing/header-info-global', [PackingController::class, 'headerInfoGlobal'])->name('packing.headerInfoGlobal');

        Route::get('/polibag/check-finishing', [TransferController::class, 'checkFinishing'])->name('transfer.check-finishing');
        Route::get('/polibag/check-finishing-list', [TransferController::class, 'checkFinishingList'])->name('transfer.check-finishing-list');
        Route::get('/polibag/check-bj/{popk}', [TransferController::class, 'checkBjRows']);
    });

    Route::middleware(['guser:34,35,38'])->group(function () {
        // MENU FG/STUFFING
        Route::get('/finish-good-stuffing', [FinishgoodStuffingController::class, 'index'])->name('finish-good-stuffing.index');
        Route::get('/finish-good-stuffing/list', [FinishgoodStuffingController::class, 'getList'])->name('finish-good-stuffing.list');
        Route::get('/finish-good-stuffing/input-global', [FinishgoodStuffingController::class, 'inputPackingGlobal'])->name('finish-good-stuffing.input.global');
        Route::get('/finish-good-stuffing/header-info-global', [FinishgoodStuffingController::class, 'headerInfoGlobal'])->name('finish-good-stuffing.headerInfoGlobal');
        Route::get('/finish-good-stuffing/cards-info-global', [FinishgoodStuffingController::class, 'cardsInfoGlobal'])->name('finish-good-stuffing.cardsInfoGlobal');
        Route::get('/finish-good-stuffing/breakdown-summary-global', [FinishgoodStuffingController::class, 'breakdownSummaryGlobal'])->name('finish-good-stuffing.breakdownSummaryGlobal');
        Route::get('/finish-good-stuffing/combos-global', [FinishgoodStuffingController::class, 'combosGlobal'])->name('finish-good-stuffing.combosGlobal');
        Route::get('/finish-good-stuffing/list-detail-global', [FinishgoodStuffingController::class, 'listDetailGlobal'])->name('finish-good-stuffing.list.detail.global');
        Route::post('/finish-good-stuffing/update-ctn', [FinishgoodStuffingController::class, 'updateCtn'])->name('finish-good-stuffing.update-ctn');
        Route::post('/finish-good-stuffing/bulk-ship-action', [FinishgoodStuffingController::class, 'bulkShipAction'])->name('finish-good-stuffing.bulk-ship-action');
        Route::post('/finish-good-stuffing/update-segel-global', [FinishgoodStuffingController::class, 'updateSegelStatusGlobal'])->name('finish-good-stuffing.update-segel.global');
        Route::get('/finish-good-stuffing/cards-summary-global', [FinishgoodStuffingController::class, 'cardsSummaryGlobal'])->name('finish-good-stuffing.cardsSummaryGlobal');
        Route::post('/finish-good-stuffing/scan-nobar', [FinishgoodStuffingController::class, 'scanNobarGlobal'])->name('finish-good-stuffing.scan-nobar');
        Route::get('/finish-good-stuffing/part-summary-global', [FinishgoodStuffingController::class, 'partSummaryGlobal'])->name('finish-good-stuffing.partSummaryGlobal');
    });

    // MENU INSPECTION
    Route::middleware(['guser:17,34'])->group(function () {
        Route::get('inspection', [InspectionController::class, 'index'])->name('inspection.index');
        Route::get('inspection/list', [InspectionController::class, 'getList'])->name('inspection.list');
        Route::get('inspection/detail/{popk}/{part}', [InspectionController::class, 'detail'])->name('inspection.detail');
        // Route::post('inspection/{popk}/{part}/bulk', [InspectionController::class, 'bulkAction'])->name('inspection.bulk');
        Route::get('inspection/detail', [InspectionController::class, 'detail'])->name('inspection.detail');
        Route::post('inspection/bulk', [InspectionController::class, 'bulkAction'])->name('inspection.bulk-ship-action');

        Route::get('/inspection/input-global', [InspectionController::class, 'inputGlobal'])->name('inspection.input.global');
        Route::get('/inspection/list-detail-global', [InspectionController::class, 'listDetailGlobal'])->name('inspection.list.detail.global');
    });

    Route::middleware(['guser:36,34'])->group(function () {
        // MENU SISA PRODUKSI
        Route::get('/sisa-produksi', [SisaProduksiController::class, 'index'])->name('sisa-produksi.index');
        Route::get('/sisa-produksi/list', [SisaProduksiController::class, 'getList'])->name('sisa-produksi.list');
        Route::get('/sisa-produksi/input/{popk}', [SisaProduksiController::class, 'inputTransfer'])->name('sisa-produksi.input');
        Route::get('sisa-produksi/{popk}/detail-list', [SisaProduksiController::class, 'detailList'])->name('sisa-produksi.detail.list');
        Route::get('sisa-produksi/{popk}/breakdown-summary', [SisaProduksiController::class, 'breakdownSummary'])->name('sisa-produksi.breakdown-summary');
        Route::get('sisa-produksi/{popk}/pdf', [SisaProduksiController::class, 'pdf'])->name('sisa-produksi.pdf');
        Route::post('sisa-produksi/{bjpk}/complete', [SisaProduksiController::class, 'complete'])->name('sisa-produksi.complete');
        Route::post('sisa-produksi/{bjpk}/update-actual', [SisaProduksiController::class, 'updateActual'])->name('sisa-produksi.update-actual');
        Route::get('sisa-produksi/export-excel', [SisaProduksiController::class, 'exportExcel'])->name('sisa-produksi.export-excel');
        Route::get('sisa-produksi/detail-by-po-op', [SisaProduksiController::class, 'detailByPoOp'])->name('sisa-produksi.detail-by-po-op');

        // MENU SISA SAMPLE
        Route::prefix('sisa-sample')->group(function () {
            // Index -- HANYA tampilkan yang SUDAH di-Add (size.masuk=1)
            Route::get('/', [SisaSampleController::class, 'index'])->name('sisa-sample.index');
            Route::get('/list', [SisaSampleController::class, 'getList'])->name('sisa-sample.list');

            // BARU: dipakai Step 1 MODAL (cari SR) -- TIDAK ADA lagi halaman
            // /cari, cuma endpoint JSON.
            Route::get('/cari/list', [SisaSampleController::class, 'cariList'])->name('sisa-sample.cari.list');

            // BARU: halaman DETAIL (dari action index) -- ganti nama dari
            // /tambah/... , sekarang punya tab Sisa Masuk & Sisa Keluar.
            Route::get('/detail/{srpk}/{statuspk}', [SisaSampleController::class, 'detail'])->name('sisa-sample.detail');
            Route::get('/detail/{srpk}/{statuspk}/sizes', [SisaSampleController::class, 'sizeList'])->name('sisa-sample.sizes');

            // Aksi per baris size
            Route::post('/size/{sizepk}/add', [SisaSampleController::class, 'addSize'])->name('sisa-sample.size.add');
            // BARU: add banyak size sekaligus, dipakai Step 2 MODAL.
            Route::post('/size/add-multiple', [SisaSampleController::class, 'addMultipleSizes'])->name('sisa-sample.size.add-multiple');
            Route::delete('/size/{sizepk}', [SisaSampleController::class, 'deleteSize'])->name('sisa-sample.size.delete');

            // Simpan Qty Sisa + TglOutGdg + Keterangan
            Route::post('/save/{statuspk}', [SisaSampleController::class, 'save'])->name('sisa-sample.save');
            Route::get('/foto/{statuspk}', [SisaSampleController::class, 'foto'])->name('sisa-sample.foto');
            Route::post('/size/{sizepk}/update-qty', [SisaSampleController::class, 'updateQtySize'])->name('sisa-sample.size.update-qty');
        });
    });
});

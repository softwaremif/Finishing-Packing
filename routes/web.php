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
use App\Http\Controllers\LO\LoController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\OrderImageController;
use App\Http\Controllers\OrderListController;
use App\Http\Controllers\Packing\PackingController;
use App\Http\Controllers\Purchase\PoOrderController;
use App\Http\Controllers\Purchase\PoReqController;
use App\Http\Controllers\SegelPacking\SegelPackingController;
use App\Http\Controllers\SimpanController;
use App\Http\Controllers\SisaProduksi\SisaProduksiController;
use App\Http\Controllers\SisaSample\SisaSampleController;
use App\Http\Controllers\StokSisa\StokSisaController;
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
    // 37: Packing
    // 38: Fingoods, Adm Fingoods

Route::get('/', [LoginController::class, 'index'])->name('login.index');
Route::post('login', [LoginController::class, 'login'])->name('login');
Route::get('loading', [LoginController::class, 'loading'])->name('loading');
Route::get('logout', [LoginController::class, 'logout'])->name('logout');

Route::get('/lo/email-approve/{lopk}/{level}', [LoController::class, 'emailApprovePage'])->name('lo.email-approve.page')->middleware('signed');
Route::post('/lo/email-approve/{lopk}/{level}/do-approve', [LoController::class, 'emailDoApprove'])->name('lo.email-approve.do-approve')->middleware('signed');
Route::post('/lo/email-approve/{lopk}/{level}/do-reject', [LoController::class, 'emailDoReject'])->name('lo.email-approve.do-reject')->middleware('signed');

Route::get('/outsisa/email-approve/{outpk}/{level}', [LoController::class, 'outsisaEmailApprovePage'])->name('outsisa.email-approve.page')->middleware('signed');
Route::post('/outsisa/email-approve/{outpk}/{level}/do-approve', [LoController::class, 'outsisaEmailDoApprove'])->name('outsisa.email-approve.do-approve')->middleware('signed');
Route::post('/outsisa/email-approve/{outpk}/{level}/do-reject', [LoController::class, 'outsisaEmailDoReject'])->name('outsisa.email-approve.do-reject')->middleware('signed');

Route::get('/sisa-sample-keluar/email-approve/{outpk}/{level}', [SisaSampleController::class, 'keluarEmailApprovePage'])->name('sisa-sample.keluar.email-approve.page')->middleware('signed');
Route::post('/sisa-sample-keluar/email-approve/{outpk}/{level}/do-approve', [SisaSampleController::class, 'keluarEmailDoApprove'])->name('sisa-sample.keluar.email-approve.do-approve')->middleware('signed');
Route::post('/sisa-sample-keluar/email-approve/{outpk}/{level}/do-reject', [SisaSampleController::class, 'keluarEmailDoReject'])->name('sisa-sample.keluar.email-approve.do-reject')->middleware('signed');

Route::post('/exim/lock-shipment', [SegelPackingController::class, 'handleEximLockShipment'])->name('exim.lock-shipment');
Route::get('/lpb/verify-posting', [SegelPackingController::class, 'verifyLpbPosting'])->name('lpb.verify-posting');

Route::get('/order-images', [OrderImageController::class, 'index'])->name('order-images.index');
Route::get('/order-image/{ordpk}', [OrderImageController::class, 'show'])->name('order-images.show');


Route::middleware(['check'])->group(function () {
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

    Route::middleware(['guser:23,34,35,37'])->group(function () {
        // MENU TRANSFER TO FINISHING
        Route::get('/tf-finishing', [TransferFinishingController::class, 'index'])->name('tf_finishing.index');
        Route::get('/tf-finishing/list', [TransferFinishingController::class, 'getList'])->name('tf_finishing.list');

        Route::get('/tf-finishing/input/{popk}', [TransferFinishingController::class, 'inputTransfer'])->name('tf_finishing.input');
        Route::post('/tf-finishing/save', [TransferFinishingController::class, 'saveTransfer'])->name('tf_finishing.save');
        Route::delete('/tf-finishing/delete/{bjpk}', [TransferFinishingController::class, 'delete'])->name('tf_finishing.delete');
        Route::get('tf-finishing/{popk}/detail-list', [TransferFinishingController::class, 'detailList'])->name('tf_finishing.detail.list');
        Route::get('tf-finishing/{popk}/breakdown-summary', [TransferFinishingController::class, 'breakdownSummary'])->name('tf_finishing.breakdown-summary');

        Route::get('/tf-finishing/debug/size-format', [TransferFinishingController::class, 'debugSizeFormat']);

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
        Route::get('/packing/distinct-dimensi-ctn', [PackingController::class, 'distinctDimensiCtn'])->name('packing.distinct-dimensi-ctn');
        Route::post('/packing/bulk-update-dimensi-ctn', [PackingController::class, 'bulkUpdateDimensiCtn'])->name('packing.bulk-update-dimensi-ctn');

        // Packing Polibag mix PO/OP
        Route::get('/packing/cross-po-op-lookup', [PackingController::class, 'crossPoOpLookup'])->name('packing.crossPoOpLookup');
        Route::get('/packing/cross-po-combo-lookup', [PackingController::class, 'crossPoComboLookup'])->name('packing.crossPoComboLookup');
        Route::get('/packing/cross-po-carton-list', [PackingController::class, 'crossPoCartonList'])->name('packing.crossPoCartonList');
        Route::get('/packing/cross-po-combo-info', [PackingController::class, 'crossPoComboInfo'])->name('packing.crossPoComboInfo');

        // Packing mix PO/OP, mix carton inner outer
        Route::get('/packing/bundle-op-lookup', [PackingController::class, 'bundleOpLookup'])->name('packing.bundleOpLookup');
        Route::get('/packing/bundle-carton-list', [PackingController::class, 'bundleCartonList'])->name('packing.bundleCartonList');
        Route::post('/packing/bundle-carton-store', [PackingController::class, 'storeCartonBundle'])->name('packing.storeCartonBundle');
        Route::get('/packing/bundle-detail', [PackingController::class, 'bundleDetail'])->name('packing.bundleDetail');


        // MENU STOK SISA(GRADE)
        Route::get('/stok-sisa', [StokSisaController::class, 'index'])->name('stok-sisa.index');
        Route::get('/stok-sisa/list', [StokSisaController::class, 'getList'])->name('stok-sisa.list');
        Route::get('stok-sisa/detail-by-po-op', [StokSisaController::class, 'detailByPoOp'])->name('stok-sisa.detail-by-po-op');
        Route::get('/stok-sisa/input/{popk}', [TransferController::class, 'inputTransfer'])->name('stok-sisa.input');  
        
        // MENU GUDANG LO
        Route::get('/kirim-sisa',                    [LoController::class, 'index'])->name('lo.index');
        Route::get('/kirim-sisa/list',                [LoController::class, 'getList'])->name('lo.list'); // BARU -- AJAX datagrid index
        Route::get('/kirim-sisa/available-items',    [LoController::class, 'availableItems'])->name('lo.available-items'); // AJAX modal Buat LO
        Route::post('/kirim-sisa',                   [LoController::class, 'store'])->name('lo.store');
        Route::get('/kirim-sisa/{lopk}',             [LoController::class, 'detail'])->name('lo.detail'); // GANTI: sekarang JSON (dipakai modal), bukan view
        Route::post('/kirim-sisa/{lopk}/approve/{level}', [LoController::class, 'approve'])->name('lo.approve');
        Route::post('/kirim-sisa/{lopk}/reject/{level}',  [LoController::class, 'reject'])->name('lo.reject');
        Route::delete('/kirim-sisa/{lopk}',          [LoController::class, 'cancel'])->name('lo.cancel');
        Route::delete('/kirim-sisa/{lopk}/item/{bjpk}', [LoController::class, 'removeItem'])->name('lo.remove-item');

        Route::put('/kirim-sisa/{lopk}', [LoController::class, 'updateLo'])->name('lo.update');
        Route::post('/kirim-sisa/{lopk}/send-email', [LoController::class, 'sendLoEmail'])->name('lo.send-email');
    });

    Route::middleware(['guser:34,35,38'])->group(function () {
        // MENU FG/STUFFING
        Route::get('/finish-good-stuffing', [FinishgoodStuffingController::class, 'index'])->name('finish-good-stuffing.index');
        Route::get('/finish-good-stuffing/list', [FinishgoodStuffingController::class, 'getList'])->name('finish-good-stuffing.list');
        Route::get('/finish-good-stuffing/input-global', [FinishgoodStuffingController::class, 'inputPackingGlobal'])->name('finish-good-stuffing.input.global');
        Route::get('/finish-good-stuffing/header-info-global', [FinishgoodStuffingController::class, 'headerInfoGlobal'])->name('finish-good-stuffing.headerInfoGlobal');
        Route::get('/finish-good-stuffing/combos-global', [FinishgoodStuffingController::class, 'combosGlobal'])->name('finish-good-stuffing.combosGlobal');
        Route::get('/finish-good-stuffing/list-detail-global', [FinishgoodStuffingController::class, 'listDetailGlobal'])->name('finish-good-stuffing.list.detail.global');
        Route::post('/finish-good-stuffing/update-ctn', [FinishgoodStuffingController::class, 'updateCtn'])->name('finish-good-stuffing.update-ctn');
        Route::post('/finish-good-stuffing/bulk-ship-action', [FinishgoodStuffingController::class, 'bulkShipAction'])->name('finish-good-stuffing.bulk-ship-action');
        Route::post('/finish-good-stuffing/update-segel-global', [FinishgoodStuffingController::class, 'updateSegelStatusGlobal'])->name('finish-good-stuffing.update-segel.global');
        Route::get('/finish-good-stuffing/cards-summary-global', [FinishgoodStuffingController::class, 'cardsSummaryGlobal'])->name('finish-good-stuffing.cardsSummaryGlobal');
        Route::post('/finish-good-stuffing/scan-nobar', [FinishgoodStuffingController::class, 'scanNobarGlobal'])->name('finish-good-stuffing.scan-nobar');
        Route::post('/finish-good-stuffing/exim-update-shipment', [FinishgoodStuffingController::class, 'eximUpdateShipment'])->name('finish-good-stuffing.exim-update-shipment');
        
        Route::get('/finish-good-stuffing/list-containers-global', [FinishgoodStuffingController::class, 'listContainersGlobal'])->name('finish-good-stuffing.listContainersGlobal');

        Route::get('/finish-good-stuffing/shipment-plan-detail-global', [FinishgoodStuffingController::class, 'shipmentPlanDetailGlobal'])->name('finish-good-stuffing.shipmentPlanDetailGlobal');
    });

    Route::get('/packing/laporan-global/pdf', [LaporanController::class, 'printGlobal'])->name('laporan.pdf.global');
    Route::get('/finish-good-stuffing/cards-info-global', [FinishgoodStuffingController::class, 'cardsInfoGlobal'])->name('finish-good-stuffing.cardsInfoGlobal');
    Route::get('/finish-good-stuffing/breakdown-summary-global', [FinishgoodStuffingController::class, 'breakdownSummaryGlobal'])->name('finish-good-stuffing.breakdownSummaryGlobal');
    Route::get('/finish-good-stuffing/part-summary-global', [FinishgoodStuffingController::class, 'partSummaryGlobal'])->name('finish-good-stuffing.partSummaryGlobal');

    // MENU INSPECTION
    Route::middleware(['guser:17,34'])->group(function () {
        Route::get('inspection', [InspectionController::class, 'index'])->name('inspection.index');
        Route::get('inspection/list', [InspectionController::class, 'getList'])->name('inspection.list');
        Route::get('inspection/detail', [InspectionController::class, 'detail'])->name('inspection.detail');
        Route::post('inspection/bulk', [InspectionController::class, 'bulkAction'])->name('inspection.bulk');

        Route::post('inspection/bulk-ship-action', [InspectionController::class, 'bulkShipAction'])->name('inspection.bulk-ship-action');
        Route::get('/inspection/input-global', [InspectionController::class, 'inputGlobal'])->name('inspection.input.global');
        Route::get('/inspection/list-detail-global', [InspectionController::class, 'listDetailGlobal'])->name('inspection.list.detail.global');
        Route::get('/inspection/history-list-detail-global', [InspectionController::class, 'historyListDetailGlobal'])->name('inspection.history.list.detail.global');

        Route::get('/inspection/inspect-available-cartons', [InspectionController::class, 'inspectAvailableCartons'])->name('inspection.inspect-available-cartons');
        Route::post('/inspection/inspect-store', [InspectionController::class, 'inspectStore'])->name('inspection.inspect-store');
        Route::get('/inspection/inspect-documents-list', [InspectionController::class, 'inspectDocumentsList'])->name('inspection.inspect-documents-list');

        Route::get('/inspection/inspect-defect-sub-list', [InspectionController::class, 'inspectDefectSubList'])->name('inspection.inspect-defect-sub-list');
        Route::get('/inspection/inspect-pdf/{inspecpk}', [InspectionController::class, 'inspectPdfReport'])->name('inspection.inspect-pdf');

        // Route::get('/inspect/available-cartons', [FinishgoodStuffingController::class, 'inspectAvailableCartons'])->name('inspect.available-cartons');
        // Route::post('/inspect/store', [FinishgoodStuffingController::class, 'storeInspecDocument'])->name('inspect.store');
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
        Route::get('/sisa-produksi/{popk}/check-bj-status', [SisaProduksiController::class, 'checkBjStatus'])->name('sisa-produksi.check-bj-status');

        // MENU TERIMA SISA PRODUKSI
        Route::get('/terima-sisa',           [LoController::class, 'indexGudang'])->name('lo.gudang.index');
        Route::get('/terima-sisa/list',       [LoController::class, 'getListGudang'])->name('lo.gudang.list');
        Route::get('/terima-sisa/{lopk}',    [LoController::class, 'detail'])->name('lo.gudang.detail'); 
        Route::post('/terima-sisa/{lopk}/item/{bjpk}/terima', [LoController::class, 'terimaItem'])->name('lo.gudang.terima-item');
        Route::get('/debug-lo-duplicates', [LoController::class, 'debugCheckLoDuplicates']);
        
        // MENU KELUARKAN SISA PRODUKSI
        Route::get('/keluarkan-sisa',                    [LoController::class, 'indexKeluarGudang'])->name('lo.keluargudang.index');
        Route::get('/keluarkan-sisa/list',                [LoController::class, 'getListKeluarGudang'])->name('lo.keluargudang.list');
        Route::get('/keluarkan-sisa/available-items',    [LoController::class, 'outAvailableItems'])->name('lo.keluargudang.available-items');
        Route::post('/keluarkan-sisa',                   [LoController::class, 'storeOutsisa'])->name('lo.keluargudang.store');
        Route::get('/keluarkan-sisa/{outpk}',            [LoController::class, 'detailOutsisa'])->name('lo.keluargudang.detail');
        Route::post('/keluarkan-sisa/{outpk}/approve/{level}', [LoController::class, 'approveOutsisa'])->name('lo.keluargudang.approve');
        Route::post('/keluarkan-sisa/{outpk}/reject/{level}',  [LoController::class, 'rejectOutsisa'])->name('lo.keluargudang.reject');
        Route::delete('/keluarkan-sisa/{outpk}',         [LoController::class, 'cancelOutsisa'])->name('lo.keluargudang.cancel');
        Route::delete('/keluarkan-sisa/{outpk}/item/{outdtpk}', [LoController::class, 'removeOutsisaItem'])->name('lo.keluargudang.remove-item');

        Route::put('/keluarkan-sisa/{outpk}', [LoController::class, 'updateOutsisa'])->name('lo.keluargudang.update');
        Route::post('/keluarkan-sisa/{outpk}/send-email', [LoController::class, 'sendOutsisaEmail'])->name('lo.keluargudang.send-email');
        
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

            Route::get('/buyer-list', [SisaSampleController::class, 'buyerList'])->name('sisa-sample.buyer-list');


            Route::get('/keluar/available-sizes/{statuspk}', [SisaSampleController::class, 'keluarAvailableSizes'])->name('sisa-sample.keluar.available-sizes');
            Route::post('/keluar/store',                     [SisaSampleController::class, 'keluarStore'])->name('sisa-sample.keluar.store');
            Route::get('/keluar/list/{statuspk}',            [SisaSampleController::class, 'keluarList'])->name('sisa-sample.keluar.list');
            Route::get('/keluar/{outpk}',                    [SisaSampleController::class, 'keluarDetail'])->name('sisa-sample.keluar.detail');
            Route::post('/keluar/{outpk}/approve/{level}',   [SisaSampleController::class, 'keluarApprove'])->name('sisa-sample.keluar.approve');
            Route::post('/keluar/{outpk}/reject/{level}',    [SisaSampleController::class, 'keluarReject'])->name('sisa-sample.keluar.reject');
            Route::delete('/keluar/{outpk}',                 [SisaSampleController::class, 'keluarCancel'])->name('sisa-sample.keluar.cancel');
            Route::delete('/keluar/{outpk}/item/{outdtpk}',  [SisaSampleController::class, 'keluarRemoveItem'])->name('sisa-sample.keluar.remove-item');

            Route::put('/keluar/{outpk}', [SisaSampleController::class, 'keluarUpdate'])->name('sisa-sample.keluar.update');
            Route::post('/keluar/{outpk}/send-email', [SisaSampleController::class, 'sendKeluarEmail'])->name('sisa-sample.keluar.send-email');
        });
    });
});

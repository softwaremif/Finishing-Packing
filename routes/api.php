<?php

use App\Http\Controllers\Api\ApiPoController;
use App\Http\Controllers\Api\ApiPrController;
use App\Http\Controllers\Api\FinishingPackingController;
use App\Http\Controllers\Api\MasterController;
use App\Http\Controllers\Api\PackingController;
use App\Http\Controllers\Cash\PoCashTempoController;
use App\Http\Controllers\Transfer\TransferController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/buyer-list', [TransferController::class, 'buyerList'])->name('api.buyer-list');

// Exim load data
Route::post('/packing/load-carton-to-container', [PackingController::class, 'apiLoadCartonToContainer'])->name('api.packing.loadCartonToContainer');
Route::get('/packing/loaded-cartons-by-container', [PackingController::class, 'apiGetLoadedCartonsByContainer'])->name('api.packing.loadedCartonsByContainer');

Route::post('/packing/unload-carton-from-container', [PackingController::class, 'apiUnloadCartonFromContainer'])->name('api.packing.unloadCartonFromContainer');

Route::get('/packing/all-loaded-cartons', [PackingController::class, 'apiGetAllLoadedCartons'])->name('api.packing.allLoadedCartons');




// 1) Semua data (agregasi per OP, gabung mif 1+2)
Route::get('/tf-finishing/list-global', [FinishingPackingController::class, 'getListGlobal'])->name('tf_finishing.list_global');
// 2) By OP (boleh dipersempit dgn ?po=...)
Route::get('/tf-finishing/by-op', [FinishingPackingController::class, 'getByOp'])->name('tf_finishing.by_op');
// 3) By OP dan/atau PO (generik, salah satu wajib)
Route::get('/tf-finishing/detail', [FinishingPackingController::class, 'getDetail'])->name('tf_finishing.detail');
// 4) Detail harian per popk (WAJIB ?mif=1 kalau popk dari mysql_andon)
Route::get('/tf-finishing/{popk}/daily-detail', [FinishingPackingController::class, 'getDailyDetail'])->name('tf_finishing.daily_detail');
// Lookup/browse ringan
Route::get('/tf-finishing/lookup-list', [FinishingPackingController::class, 'lookupList'])->name('tf_finishing.lookup_list');


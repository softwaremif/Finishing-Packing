<?php

use App\Http\Controllers\Api\ApiPoController;
use App\Http\Controllers\Api\ApiPrController;
use App\Http\Controllers\Api\MasterController;
use App\Http\Controllers\Cash\PoCashTempoController;
use App\Http\Controllers\Api\PackingController;
use App\Http\Controllers\Transfer\TransferController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/buyer-list', [TransferController::class, 'buyerList'])->name('api.buyer-list');

Route::post('/packing/load-carton-to-container', [PackingController::class, 'apiLoadCartonToContainer'])->name('api.packing.loadCartonToContainer');
Route::get('/packing/loaded-cartons-by-container', [PackingController::class, 'apiGetLoadedCartonsByContainer'])->name('api.packing.loadedCartonsByContainer');

Route::post('/packing/unload-carton-from-container', [PackingController::class, 'apiUnloadCartonFromContainer'])->name('api.packing.unloadCartonFromContainer');

Route::get('/packing/all-loaded-cartons', [PackingController::class, 'apiGetAllLoadedCartons'])->name('api.packing.allLoadedCartons');


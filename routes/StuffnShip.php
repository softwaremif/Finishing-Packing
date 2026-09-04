<?php

use App\Http\Controllers\Ctpat\CtpatController;
use App\Http\Controllers\FinishedGoods\FinishedGoodsController;
use App\Http\Controllers\Inspection\InspectionController;
use App\Http\Controllers\Packing\PackingController;
use App\Http\Controllers\StokSisa\StokSisaController;
use App\Http\Controllers\Stuffing\StuffingController;
use Illuminate\Support\Facades\Route;

// finished goods
Route::get('/finGoods', [PackingController::class, 'finGoods'])->name('stuff.index');

// 
Route::get('/stuffing', [FinishedGoodsController::class, 'index'])->name('finGoods.index');
Route::get('/stuffing/list', [FinishedGoodsController::class, 'getList'])->name('finGoods.list');
// Route::get('/stuffing/detail/{po}/{part}', [FinishedGoodsController::class, 'detail'])->name('finGoods.detail');
// Route::post('/stuffing/{popk}/{part}/scan', [FinishedGoodsController::class, 'scan'])->name('finGoods.scan');
// Route::post('/stuffing/{popk}/{part}/bulk', [FinishedGoodsController::class, 'bulkAction'])->name('finGoods.bulk');
Route::post('/stuffing/{popk}/{part}/lock', [FinishedGoodsController::class, 'lockShipment'])->name('finGoods.lock');
Route::post('/stuffing/{popk}/{part}/unlock', [FinishedGoodsController::class, 'unlockShipment'])->name('finGoods.unlock');
Route::get('/stuffing/print/{popk}/{part}', [FinishedGoodsController::class, 'print'])->name('finGoods.print');
Route::get('/stuffing/popup', [FinishedGoodsController::class, 'popupList'])->name('finGoods.popup');
Route::get('stuffing/printGlobal', [FinishedGoodsController::class, 'printGlobal'])->name('finGoods.printGlobal');
Route::get('stuffing/detail', [FinishedGoodsController::class, 'detail'])->name('finGoods.detail');
Route::post('stuffing/scan', [FinishedGoodsController::class, 'scan'])->name('finGoods.scan');
Route::post('stuffing/bulk', [FinishedGoodsController::class, 'bulkAction'])->name('finGoods.bulk');
// inspect



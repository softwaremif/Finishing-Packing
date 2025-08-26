<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Aktifitas\TandaTerimaController;

Route::get('/tanda-terima', [TandaTerimaController::class, 'PageTandaTerima'])->name('page.tanda-terima');
Route::get('/get-tanda-terima', [TandaTerimaController::class, 'getListTandaTerima'])->name('get.tt');
Route::get('/tanda-terima/{ttpk}', [TandaTerimaController::class, 'PageDetailTt'])->name('page.detail-tt');
Route::get('/get-tanda-terima/detail/{ttpk}', [TandaTerimaController::class, 'GetDetailTt'])->name('get.detail-tt');
Route::get('/tanda-terima/print/{ttpk}', [TandaTerimaController::class, 'PrintTandaTerima'])->name('print.tt');
Route::post('/get-last-tt', [TandaTerimaController::class, 'GetLastTT'])->name('get.lasttt');
Route::post('back-tt', [TandaTerimaController::class, 'BackTT'])->name('back.tt');
Route::get('tanda-terima/add-tt/{ttpk}', [TandaTerimaController::class, 'PageAddTt'])->name('page.add-tt');
Route::get('tanda-terima/detail/{ttpk}', [TandaTerimaController::class, 'PageDetailTt2'])->name('page.detailtt2');
//
Route::get('get-ttdt/{ttpk}', [TandaTerimaController::class, 'GetTtdt'])->name('api.get-ttdt');
Route::post('/insert-ttdt/{prpk}', [TandaTerimaController::class, 'InsertTtdt'])->name('api.insert-ttdt');
Route::post('/update-ttdt', [TandaTerimaController::class, 'UpdateTtdt'])->name('api.update-ttdt');
Route::post('/edit-save-header-ttdt', [TandaTerimaController::class, 'SaveEditHeaderTtdt'])->name('api.edit-save-header-ttdt');
Route::post('/delete-ttdt', [TandaTerimaController::class, 'DeleteTtdt'])->name('api.delete-ttdt');
//Get data Beli on modal TT
Route::get('get-lookup-detbeli-on-tt', [TandaTerimaController::class, 'GetDetBeliOnTT'])->name('get.lookup-detbeli-on-tt');
Route::post('add-beli-to-tt', [TandaTerimaController::class, 'AddBeliToTt'])->name('add-beli-to-tt');
//Get data PO on modal TT
Route::post('add-po-to-tt', [TandaTerimaController::class, 'AddPoToTt'])->name('add-po-to-tt');


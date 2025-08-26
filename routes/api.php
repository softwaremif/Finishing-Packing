<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiPrController;
use App\Http\Controllers\Api\ApiPoController;
use App\Http\Controllers\Api\MasterController;
use App\Http\Controllers\Cash\PoCashTempoController;

Route::get('get-prdt/{prpk}', [ApiPrController::class, 'GetPrdt'])->name('api.get-prdt');
Route::post('/insert-prdt/{prpk}', [ApiPrController::class, 'InsertPrdt'])->name('api.insert-prdt');
Route::post('/update-prdt', [ApiPrController::class, 'UpdatePrdt'])->name('api.update-prdt');
Route::post('/edit-save-header-prdt', [ApiPrController::class, 'SaveEditHeaderPrdt'])->name('api.edit-save-header-prdt');
Route::post('/delete-prdt', [ApiPrController::class, 'DeletePrdt'])->name('api.delete-prdt');

Route::get('get-notran', [ApiPoController::class, 'GetNotran'])->name('api.get-notran');
Route::get('get-total/{popk}', [ApiPoController::class, 'GetTotHrgBeli'])->name('api.get-tothrgbeli');
Route::post('/update-total', [ApiPoController::class, 'updateTotBeli'])->name('api.update-totbeli');
Route::get('get-podt/{popk}', [ApiPoController::class, 'GetPodt'])->name('api.get-podt');
Route::post('/insert-podt/{popk}', [ApiPoController::class, 'InsertPodt'])->name('api.insert-podt');
Route::post('/update-podt', [ApiPoController::class, 'UpdatePodt'])->name('api.update-podt');
Route::post('/edit-save-header-podt', [ApiPoController::class, 'SaveEditHeaderPodt'])->name('api.edit-save-header-podt');
Route::post('/delete-podt', [ApiPoController::class, 'DeletePodt'])->name('api.delete-podt');

Route::get('get-ab', [MasterController::class, 'get_ab'])->name('api.get-ab');
Route::get('get-sup', [MasterController::class, 'get_sup'])->name('api.get-sup');
Route::get('get-cur', [MasterController::class, 'get_cur'])->name('api.get-cur');
Route::get('get-user', [MasterController::class, 'get_user'])->name('api.get-user');
Route::get('get-penerima', [MasterController::class, 'get_penerima'])->name('api.get-penerima');
Route::get('get-dep', [MasterController::class, 'get_dep'])->name('api.get-dep');
Route::get('get-jenis', [MasterController::class, 'get_jenis'])->name('api.get-dep');
Route::get('get-mif', [MasterController::class, 'get_mif'])->name('api.get-mif');
Route::get('get-jnsbrg', [MasterController::class, 'get_jnsbrg'])->name('api.get-jnsbrg');

Route::get('get-belidt/{belidtpk}', [ApiPoController::class, 'GetBelidt'])->name('api.get-belidt');

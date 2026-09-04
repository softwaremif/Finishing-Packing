<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Aktifitas\PemasukanBarangNBBController;

Route::post('/get-last-nbb', [PemasukanBarangNBBController::class, 'GetLastNbb'])->name('get.lastnbb');
Route::get('/pemasukan-barang-nbb', [PemasukanBarangNBBController::class, 'PagePemasukanBarangNBB'])->name('page.pemasukan-barang-nbb');
Route::get('/get-pbnbb', [PemasukanBarangNBBController::class, 'getListPbnbb'])->name('get.pbnbb');
Route::get('pemasukan-barang-nbb/add-nbb/{msnmpk}', [PemasukanBarangNBBController::class, 'PageAddNbb'])->name('page.add-msnm');
Route::get('pemasukan-barang-nbb/detail/{msnmpk}', [PemasukanBarangNBBController::class, 'PageDetail'])->name('page.detail-msnm');
Route::post('/cancel-msnm/{msnmpk}', [PemasukanBarangNBBController::class, 'ConfirmCancelNbb'])->name('confirm.cancel.msnm');
Route::post('/pemasukan-barang-nbb/posting/{msnmpk}', [PemasukanBarangNBBController::class, 'ConfirmPostingNbb'])->name('confirm.posting-nbb');
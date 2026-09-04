<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Aktifitas\PembayaranGiroController;

Route::get('/pembayaran-giro', [PembayaranGiroController::class, 'PagePembayaranGiro'])->name('page.pby-giro');
Route::get('/get-pembayaran-giro', [PembayaranGiroController::class, 'getListPembayaranGiro'])->name('get.pby-giro');
Route::get('/pdf-pembayaran-giro', [PembayaranGiroController::class, 'PrintPembayaranGiro'])->name('pdf.pembayaran-giro');
// Route::get('/pembayaran-giro/detail/{table}/{pk}', [PembayaranGiroController::class, 'PageDetailPg'])->name('page.detail-pg');
Route::post('/pembayaran-giro/posting/{table}/{id}', [PembayaranGiroController::class, 'PostingPg'])->name('posting.pg');
Route::get('/pembayaran-giro/add/{table}/{pk}', [PembayaranGiroController::class, 'PageAddByPembayarantGiro'])->name('page.add-by-pemb-giro');
Route::get('/pembayaran-giro/detail/{table}/{pk}', [PembayaranGiroController::class, 'PageDetailByPembayaranGiro'])->name('page.detail-by-pemb-giro');
Route::get('get-bgdt/{bgpk}', [PembayaranGiroController::class, 'GetBgdt'])->name('get.bgdt');
Route::post('/edit-save-header-pemb-giro', [PembayaranGiroController::class, 'SaveEditHeaderBg'])->name('edit.save-header-bg');
Route::post('/insert-bgdt/{bgpk}', [PembayaranGiroController::class, 'InsertBgdt'])->name('insert-bgdt');
Route::post('/update-bgdt', [PembayaranGiroController::class, 'UpdateBgdt'])->name('update-bgdt');
Route::post('/delete-bgdt', [PembayaranGiroController::class, 'DeleteBgdt'])->name('delete-bgdt');
Route::get('get-lookup-beli-on-bg', [PembayaranGiroController::class, 'GetBeliOnBg'])->name('get.lookup-beli-on-bg');
Route::post('add-beli-to-bg', [PembayaranGiroController::class, 'AddBeliToBg'])->name('add-beli-to-bg');
Route::get('get-tot-inv-bg/{bgpk}', [PembayaranGiroController::class, 'GetTotInvBg'])->name('get.tot-inv-bg');
Route::post('/update-tot-inv-bg', [PembayaranGiroController::class, 'updateTotInvBg'])->name('get.update-totinvbg');
Route::post('/get-last-bg', [PembayaranGiroController::class, 'GetLastBg'])->name('get.last-bg');
Route::post('back-to-list-pembayaran-giro', [PembayaranGiroController::class, 'BackToListPembGiro'])->name('back.to-list-pembayaran-giro');

// HD Add,detail--------------------------->
Route::post('/get-last-psup', [PembayaranGiroController::class, 'GetLastPsup'])->name('get.last-psup');
Route::post('to-list-pembayaran-giro', [PembayaranGiroController::class, 'BackToListPembGiroHd'])->name('to.list-pembayaran-giro2');
Route::get('/pembayaran-giro/add-{table}/{pk}', [PembayaranGiroController::class, 'PageAddHdPembayarantGiro'])->name('page.add-hd-pemb-giro');
Route::get('/pembayaran-giro/detail-{table}/{pk}', [PembayaranGiroController::class, 'PageDetailHdPembayaranGiro'])->name('page.detail-hd-pemb-giro');
Route::get('get-psupdt/{psuppk}', [PembayaranGiroController::class, 'GetPsupdt'])->name('get.psupdt');
Route::post('/header-pemb-giro-psup', [PembayaranGiroController::class, 'SaveEditHeaderPsup'])->name('edit.save-header-psup');
Route::post('/insert-psupdt/{psuppk}', [PembayaranGiroController::class, 'InsertPsupdt'])->name('insert-psupdt');
Route::post('/update-psupdt', [PembayaranGiroController::class, 'UpdatePsupdt'])->name('update-psupdt');
Route::post('/delete-psupdt', [PembayaranGiroController::class, 'DeletePsupdt'])->name('delete-psupdt');
Route::get('get-tot-inv-psup/{psuppk}', [PembayaranGiroController::class, 'GetTotInvPsup'])->name('get.tot-inv-psup');
Route::post('/update-tot-inv-psup', [PembayaranGiroController::class, 'updateTotInvPsup'])->name('get.update-totinvpsup');
Route::get('get-lookup-inv-on-psup', [PembayaranGiroController::class, 'GetInvOnPsup'])->name('get.lookup-inv-on-psup');
Route::post('add-inv-to-psup', [PembayaranGiroController::class, 'AddInvToPsup'])->name('add-inv-to-psup');

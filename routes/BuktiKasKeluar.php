<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Laporan\BuktiKasKelController;

Route::group(['prefix' => 'laporan', 'middleware' => ['check']], function () {
    // Route::get('/bukti-kas-keluar/awal', [BuktiKasKelController::class, 'PageBuktiKasKeluarAwal'])->name('page.bkk.awal');
    // Route::get('/pdf-bukti-kas-keluar/awal;', [BuktiKasKelController::class, 'PdfListBkkAwal'])->name('pdf.list-bkk');
    // Route::get('/bukti-kas-keluar/awal/{tglInv}/{suppk}', [BuktiKasKelController::class, 'PageDetailBkkAwal'])->name('page.detail.bkk.awal');

    // BKK2
    Route::get('/bukti-kas-keluar', [BuktiKasKelController::class, 'PageBuktiKasKeluar'])->name('page.bkk');
    Route::get('/bukti-kas-keluar/{tglInv}/{suppk}', [BuktiKasKelController::class, 'PageDetailBkk'])->name('page.detail.bkk');
    Route::get('/pdf-bukti-kas-keluar', [BuktiKasKelController::class, 'PdfListBkk'])->name('pdf.list-bkk');
    Route::get('/excel-bukti-kas-keluar', [BuktiKasKelController::class, 'ExcelListBkk'])->name('excel.list-bkk');
});

Route::get('get-total', [BuktiKasKelController::class, 'HitungTotal'])->name('get.total');
Route::get('/get-status-pembayaran', [BuktiKasKelController::class, 'FilterByStatusPembayaran'])->name('get.status-pembayaran');
Route::get('/get-supp-bkk/{tglInv}', [BuktiKasKelController::class, 'getSuppInDetailBkk'])->name('get.supp-bkk');

Route::get('/get-bkkawal', [BuktiKasKelController::class, 'getListBuktiKasKelAwal'])->name('get.bkk.awal');
Route::get('/get-cek-cashawal', [BuktiKasKelController::class, 'getListCekCashAwal'])->name('get.list-cek-cash.awal');
Route::get('/get-bkk/detail/awal/{tglInv}/{suppk}', [BuktiKasKelController::class, 'GetDetailBkkAwal'])->name('get.detail-bkk.awal');
Route::get('/get-bkk/detail/awal/pdf/{tglInv}/{suppk}', [BuktiKasKelController::class, 'PdfBkkAwal'])->name('pdf.bkk.awal');
Route::get('pdf-cek-cash/awal', [BuktiKasKelController::class, 'pdf_cek_cash_by_modal_awal'])->name('pdf.cek-cash-by-modal.awal');

Route::get('/get-cek-cash', [BuktiKasKelController::class, 'getListCekCash'])->name('get.list-cek-cash');
Route::get('/get-bkk', [BuktiKasKelController::class, 'getListBuktiKasKel'])->name('get.bkk');
Route::get('/get-bkk/detail{tglInv}/{suppk}', [BuktiKasKelController::class, 'GetDetailBkk'])->name('get.detail-bkk');
Route::get('/get-detail-beli/{belipk}', [BuktiKasKelController::class, 'GetDetailBeli'])->name('get.detail-beli');
Route::get('/get-bkk/detail/pdf/{tglInv}/{suppk}', [BuktiKasKelController::class, 'PdfDetailBkk'])->name('pdf.detail.bkk');
Route::get('pdf-cek-cash', [BuktiKasKelController::class, 'PdfCekCashByModal'])->name('pdf.cek-cash-by-modal');

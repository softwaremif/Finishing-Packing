<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Cash\PoCashTempoController;
use App\Http\Controllers\Stok\StokController;

// input add kasiih spasi
Route::middleware(['check'])->group(function () {
    Route::get('/purchase-cash-tempo', [PoCashTempoController::class, 'index'])->name('po-cash-tempo.index');
    Route::get('/purchase-cash-tempo/detail/{id}', [PoCashTempoController::class, 'show'])->name('po-cash-tempo.show');
    Route::get('/get-purchase-cash-tempo', [PoCashTempoController::class, 'GetBeli'])->name('api.po-cash-tempo.get');
    Route::post('paid-tempo/{belipk}', [PoCashTempoController::class, 'PaidTempo'])->name('post.paid-tempo');
    Route::post('unpaid-tempo/{belipk}', [PoCashTempoController::class, 'UnPaidTempo'])->name('post.unpaid-tempo');
    Route::get('/purchase-cash-tempo/add', [PoCashTempoController::class, 'create'])->name('po-cash-tempo.create');
    Route::post('/purchase-cash-tempo/store', [PoCashTempoController::class, 'store'])->name('po-cash-tempo.store');
    Route::put('/purchase-cash-tempo/update/{id}', [PoCashTempoController::class, 'update'])->name('po-cash-tempo.update');
    Route::post('/notran-purchase-cash-tempo', [PoCashTempoController::class, 'notran'])->name('po-cash-tempo.notran');

    Route::get('/stock/upload', [StokController::class, 'showUploadForm'])->name('stock.upload');
    Route::post('/stock/import', [StokController::class, 'import'])->name('stock.import');
});

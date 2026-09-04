<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Aktifitas\DataAsetController;

Route::get('/get-data-aset', [DataAsetController::class, 'getListDataAset'])->name('get.data-aset');
Route::resource('data-aset', DataAsetController::class)->except(['create', 'destroy', 'edit']);
Route::get('generate-asetid', [DataAsetController::class, 'generateAsetId'])->name('generate-asetid');
// Route::post('/barcode-asetid/process', [DataAsetController::class, 'onProcessAsetId'])->name('barcode.asetid');
Route::get('/barcode-aset/preview', [DataAsetController::class, 'previewAsetId'])->name('barcode.aset.preview');
Route::get('/barcode-aset/preview-lokasi',[DataAsetController::class, 'previewByLokasi'])->name('barcode.aset.preview.lokasi');
Route::get('/barcode-aset/preview-jaset',[DataAsetController::class, 'previewByJaset'])->name('barcode.aset.preview.jaset');
Route::get('/barcode-aset/pdf-lokasi',[DataAsetController::class, 'PdfLokasi'])->name('pdf.lokasi');
Route::get('export-assets', [DataAsetController::class, 'exportAssets'])->name('export.assets');

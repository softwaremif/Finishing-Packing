<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Aktifitas\ShipmentController;

Route::get('/shipment', [ShipmentController::class, 'PageShipment'])->name('page.shipment');
Route::get('/get-shipment', [ShipmentController::class, 'getListShipment'])->name('get.shipment');
Route::get('/pdf-shipment', [ShipmentController::class, 'PrintShipment'])->name('pdf.shipment');
// Menu Payment Shipment
Route::get('/payment-shipment', [ShipmentController::class, 'PagePaymentShipment'])->name('page.payment-shipment');
Route::get('/get-payment-shipment', [ShipmentController::class, 'getListPaymentShipment'])->name('get.payment-shipment');
Route::get('/pdf-payment-shipment', [ShipmentController::class, 'PrintPaymentShipment'])->name('pdf.payment-shipment');
Route::post('/get-last-pay', [ShipmentController::class, 'GetLastPay'])->name('get.last-pay');
Route::post('back-to-list', [ShipmentController::class, 'BackToListPaymentShipment'])->name('back.to-list-payment-shipment');
Route::get('/payment-shipment/add/{paypk}', [ShipmentController::class, 'PageAddPaymentShipment'])->name('page.add-payment-shipment');
Route::get('/payment-shipment/detail/{paypk}', [ShipmentController::class, 'PageDetailPaymentShipment'])->name('page.detail-payment-shipment');
Route::get('get-paydt/{paypk}', [ShipmentController::class, 'GetPaydt'])->name('get.paydt');
Route::post('/edit-save-header-payment-shipment', [ShipmentController::class, 'SaveEditHeaderPaydt'])->name('edit.save-header-paydt');
Route::post('/insert-paydt/{paypk}', [ShipmentController::class, 'InsertPaydt'])->name('insert-paydt');
Route::post('/update-paydt', [ShipmentController::class, 'UpdatePaydt'])->name('update-paydt');
Route::post('/delete-paydt', [ShipmentController::class, 'DeletePaydt'])->name('delete-paydt');
Route::get('get-lookup-ship-on-pay', [ShipmentController::class, 'GetShipOnPay'])->name('get.lookup-ship-on-pay');
Route::post('add-ship-to-pay', [ShipmentController::class, 'AddShipToPay'])->name('add-ship-to-pay');
Route::get('get-total-inv/{paypk}', [ShipmentController::class, 'GetTotHrg'])->name('get.tot-inv');
Route::post('/update-total-inv', [ShipmentController::class, 'updateTotHrg'])->name('get.update-totinv');


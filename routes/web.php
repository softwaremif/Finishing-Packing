<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\SimpanController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Purchase\PoReqController;
use App\Http\Controllers\Purchase\PoOrderController;
use App\Http\Controllers\Laporan\PembayaranCGController;
use App\Http\Controllers\Laporan\PembelianCTController;
use App\Http\Controllers\Laporan\LapPrController;
use App\Http\Controllers\Laporan\LapPoController;
use App\Http\Controllers\Laporan\LapStokController;
use App\Http\Controllers\Aktifitas\PpkController;
use App\Http\Controllers\Aktifitas\BarangController;
use App\Http\Controllers\Aktifitas\PembelianCashTempoController;
use App\Http\Controllers\Tabel\StokBarangController;
use App\Http\Controllers\Tabel\SupplierController;
use App\Http\Controllers\Tabel\KategoriBarangController;

Route::get('/', [LoginController::class, 'index'])->name('login.index');
Route::post('login', [LoginController::class, 'login'])->name('login');
Route::get('loading', [LoginController::class, 'loading'])->name('loading');
Route::get('logout', [LoginController::class, 'logout'])->name('logout');

Route::middleware(['check'])->group(function () {
    Route::post('updtpswdb', [LoginController::class, 'updtpswdb'])->name('updtpswdb');
    Route::get('password', [LoginController::class, 'password'])->name('password');
});

Route::post('/get-last-pr', [PoReqController::class, 'GetLastPr'])->name('get.lastpr');
Route::post('back-pr', [PoReqController::class, 'BackPr'])->name('back.pr');
Route::get('purchase-request/add-pr/{prpk}', [PoReqController::class, 'AddPr'])->name('add.pr');
Route::get('purchase-request', [PoReqController::class, 'index'])->name('index.poreq');
Route::get('get-pr', [PoReqController::class, 'GetPr'])->name('api.get-pr');
Route::get('purchase-request/detail/{prpk}', [PoReqController::class, 'DetailPoReq'])->name('detail.poreq');
Route::get('purchase-request/print/{prpk}', [PoReqController::class, 'PrintPr'])->name('print.pr');
Route::post('purchase-request/posting/{prpk}', [PoReqController::class, 'ConfirmPosting'])->name('confirm.posting');
Route::post('add-barang-to-prdt', [PoReqController::class, 'AddBarangToPrdt'])->name('add-barang-to-prdt');

Route::post('/get-last-po', [PoOrderController::class, 'GetLastPo'])->name('get.lastspo');
Route::post('back-po', [PoOrderController::class, 'BackPo'])->name('back.po');
Route::get('get-po', [PoOrderController::class, 'GetPo'])->name('api.get-po');
Route::group(['prefix' => 'purchase-order', 'middleware' => ['check']], function () {
    Route::get('/add-po/{popk}', [PoOrderController::class, 'AddPo'])->name('add.po');
    Route::get('/list', [PoOrderController::class, 'index'])->name('index.porder');
    Route::get('/detail/{popk}', [PoOrderController::class, 'DetailPorder'])->name('detail.poorder');
    Route::get('/print/{popk}', [PoOrderController::class, 'PrintPo'])->name('print.po');
    Route::post('/posting/{popk}', [PoOrderController::class, 'ConfirmPosting'])->name('confirm.posting.po');
});
Route::get('get-lookup-detpo', [PoOrderController::class, 'GetDetPo'])->name('get.lookup-detpo');
Route::post('add-po-to-po', [PoOrderController::class, 'AddPoToPo'])->name('add-po-to-po');

Route::get('get-lookup-detpr', [PoOrderController::class, 'GetDetPr'])->name('get-lookup-detpr');
Route::get('get-lookup-pr', [PoOrderController::class, 'GetLookUpPr'])->name('get-lookup-pr');
// Route::post('add-lookup-po', [PoOrderController::class, 'AddLookUpPo'])->name('add-lookup-po');
Route::post('add-pr-to-po', [PoOrderController::class, 'AddPrToPo'])->name('add-pr-to-po');

// Menu Pembelian Cash Tempo
Route::get('/pembelian-cash-tempo', [PembelianCashTempoController::class, 'PagePembelianCt'])->name('page.pembelian-ct');
Route::get('get-pembelian-ct', [PembelianCashTempoController::class, 'GetPembelianCT'])->name('get.pembelian-ct');
Route::post('/get-last-pembelian-ct', [PembelianCashTempoController::class, 'GetLastPembelianCT'])->name('get.last-pembelian');
Route::get('/pembelian-cash-tempo/add-pembelian/{belipk}', [PembelianCashTempoController::class, 'AddPembelian'])->name('add.pembelian');
Route::get('/pembelian-cash-tempo/detail/{belipk}', [PembelianCashTempoController::class, 'PageDetailPembelian'])->name('page.detail-pembelian');
Route::post('/pembelian-cash-tempo/back-pembelian', [PembelianCashTempoController::class, 'BackPembelian'])->name('back.pembelian');
Route::post('/pembelian-cash-tempo/posting/{belipk}', [PembelianCashTempoController::class, 'ConfirmPostingPembelian'])->name('confirm.posting.pembelian');
// Route::get('get-notran', [ApiPoController::class, 'GetNotran'])->name('api.get-notran');
Route::get('get-total-pembelian/{belipk}', [PembelianCashTempoController::class, 'GetTotPembelian'])->name('get.total-pembelian');
Route::post('/update-total-pembelian', [PembelianCashTempoController::class, 'updateTotBeli'])->name('get.update-total-pembelian');
Route::get('get-belidt/{belipk}', [PembelianCashTempoController::class, 'GetBelidt'])->name('get.belidt');
Route::post('/insert-belidt/{belipk}', [PembelianCashTempoController::class, 'InsertBelidt'])->name('get.insert-belidt');
Route::post('/update-belidt', [PembelianCashTempoController::class, 'UpdateBelidt'])->name('get.update-belidt');
Route::post('/edit-save-header-belidt', [PembelianCashTempoController::class, 'SaveEditHeaderBelidt'])->name('get.edit-save-header-belidt');
Route::post('/delete-belidt', [PembelianCashTempoController::class, 'DeleteBelidt'])->name('get.delete-belidt');
Route::get('get-lookup-detpr-in-pembelian', [PembelianCashTempoController::class, 'GetDetPrInPembelian'])->name('get-lookup-detpr-in-pembelian');
Route::post('add-pr-to-pembelian', [PembelianCashTempoController::class, 'AddPrToPembelian'])->name('add-pr-to-pembelian');
//tutup menu pembelian cash tempo//

Route::get('get-cash-giro', [PembayaranCGController::class, 'getListPembayaranCashGiro'])->name('get-cg');
Route::get('get-cash-tempo', [PembelianCTController::class, 'getListPembelianCashTempo'])->name('get-ct');
Route::get('get-lap-pr', [LapPrController::class, 'getListLapPr'])->name('get.lap-pr');
Route::get('get-lap-po', [LapPoController::class, 'getListLapPo'])->name('get.lap-po');
Route::get('get-lap-stok', [LapStokController::class, 'getListLapStok'])->name('get.lap-stok');

Route::group(['prefix' => 'laporan', 'middleware' => ['check']], function () {
    Route::get('/pembayaran-cash-giro', [PembayaranCGController::class, 'PagePembayaranCashGiro'])->name('page.cg');
    Route::get('/pdf-pembayaran-cash-giro', [PembayaranCGController::class, 'pdfPembayaranCG'])->name('pdf.cg');

    Route::get('/pembelian/cash-tempo', [PembelianCTController::class, 'PagePembelianCashTempo'])->name('page.ct');
    Route::get('/pdf-pembelian-cash-tempo', [PembelianCTController::class, 'pdfPembelianCT'])->name('pdf.ct');

    Route::get('/purchase-request', [LapPrController::class, 'PageLapPr'])->name('page.lap-pr');
    Route::get('/pdf-purchase-request', [LapPrController::class, 'PdfLapPr'])->name('pdf.lap-pr');

    Route::get('/purchase-order', [LapPoController::class, 'PageLapPo'])->name('page.lap-po');
    Route::get('/pdf-purchase-order', [LapPoController::class, 'PdfLapPo'])->name('pdf.lap-po');
    Route::get('/pdf-purchase-order2', [LapPoController::class, 'PdfLapPoKe2'])->name('pdf.lap-po-ke2');

    Route::get('/stok-barang', [LapStokController::class, 'PageLapStok'])->name('page.lap-stok');
    Route::get('/pdf-stok-barang', [LapStokController::class, 'PdfLapStok'])->name('pdf.lap-stok');
});

Route::get('get-ppk', [PpkController::class, 'getListPkk'])->name('get-ppk');
Route::get('get-ppk/detail/{kmspk}', [PpkController::class, 'DetailPpk'])->name('detail.ppk');
Route::post('get-ppk/store', [PpkController::class, 'StorePpk'])->name('store.ppk');
Route::post('get-ppk/update/{kmspk}', [PpkController::class, 'UpdatePpk'])->name('update.ppk');
Route::get('/pengisian-pengembalian-kas', [PpkController::class, 'PagePpk'])->name('page.kas-ppk');

Route::get('/barang', [BarangController::class, 'PageBarang'])->name('page.barang');
Route::get('get-list-barang', [BarangController::class, 'getListBarang'])->name('get-barang');
Route::post('barang/upload-file', [BarangController::class, 'upload_excel'])->name('upload_excel.barang');
Route::post('add-barang-to-podt', [BarangController::class, 'AddBarangToPodt'])->name('add-barang-to-podt');

Route::get('/stok-barang', [StokBarangController::class, 'PageStokBarang'])->name('page.stok-barang');
Route::get('/get-stok-barang', [StokBarangController::class, 'getListStokBarang'])->name('get.list-stok');
Route::get('get-stok-barang/detail/{stokpk}', [StokBarangController::class, 'DetailBarang'])->name('detail.stok-barang');
Route::post('get-stok-barang/store', [StokBarangController::class, 'StoreBarang'])->name('store.stok-barang');
Route::post('get-stok-barang/update/{stokpk}', [StokBarangController::class, 'UpdateBarang'])->name('update.stok-barang');

Route::resource('supplier', SupplierController::class)->except(['create', 'destroy', 'edit']);;
Route::get('/get-supplier', [SupplierController::class, 'getListSupplier'])->name('get.list-supplier');

Route::resource('kategori-barang', KategoriBarangController::class)->except(['create', 'destroy', 'edit']);;
Route::get('/get-ktb', [KategoriBarangController::class, 'getListKtb'])->name('get.list-ktb');

include 'BuktiKasKeluar.php';
include 'tandaterima.php';
include 'keuangan.php';

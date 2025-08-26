<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use PDF;
use Illuminate\Support\Facades\Session;

class LapStokController extends Controller
{
    function PageLapStok(){
        return view('menu.laporan.stok.list-stok');
    }

    function getListLapStok(Request $request)
	{

		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInput = $request->searchByInput;

		$data_barang = DB::table('brg')
		->leftJoin('jnsbrg', 'jnsbrg.jnsbrgpk', '=', 'brg.jnsbrgpk')
		->select(
			'brg.brgpk',
			'brg.brgnm',
            'brg.noseri',
            'brg.merknm',
            'brg.qtyawal',
            // 'brg.lastupdate',
            'jnsbrg.jnsbrgpk',
            'jnsbrg.jnsbrgnm',
            DB::raw('DATE_FORMAT(brg.lastupdate, "%d %b %Y %H:%i:%s") as lastupdate'),
		)
        ->orderBy('brg.brgpk', 'DESC');

		if (!empty($searchByInput)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(brg.brgnm, ''),
                COALESCE(brg.noseri, ''),
                COALESCE(brg.merknm, ''),
                COALESCE(brg.jnsbrgnm, '')
            )");
			$data_barang = $data_barang->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}

		$AllDataBrg = $data_barang->get();
		$offset = ($page - 1) * $rows;
		$data_barang = $data_barang->skip($offset)->take($rows)->get();

		$result = array();
		$result['total'] = $AllDataBrg->count();
		$result['page'] = $page;
		$result['rows'] = $rows;
		$result['offset'] = $offset;
		$row = array();
		$index = $offset + 1;


		$brgpkList = $data_barang->pluck('brgpk');

		// Ambil data masuk sekaligus
		// $masukPodt = DB::table('podt')
		// 	->whereIn('brgpk', $brgpkList)
		// 	->select('brgpk', DB::raw('SUM(jmlbeli) as total'))
		// 	->groupBy('brgpk')
		// 	->pluck('total', 'brgpk');

		$masukPodt = DB::table('podt')
			->join('po', 'po.popk', '=', 'podt.popk')
			->where('po.posting', 1)
			->whereIn('podt.brgpk', $brgpkList)
			->select('podt.brgpk', DB::raw('SUM(podt.jmlbeli) as total'))
			->groupBy('podt.brgpk')
			->pluck('total', 'podt.brgpk');

		// $masukBelidt = DB::table('belidt')
		// 	->whereIn('brgpk', $brgpkList)
		// 	->select('brgpk', DB::raw('SUM(jmlbeli) as total'))
		// 	->groupBy('brgpk')
		// 	->pluck('total', 'brgpk');

		$masukBelidt = DB::table('belidt')
			->join('beli', 'beli.belipk', '=', 'belidt.belipk')
			->where('beli.posting', 1)
			->whereIn('belidt.brgpk', $brgpkList)
			->select('belidt.brgpk', DB::raw('SUM(belidt.jmlbeli) as total'))
			->groupBy('belidt.brgpk')
			->pluck('total', 'belidt.brgpk');


		// Ambil semua podtpk untuk semua brgpk
		$podtMap = DB::table('podt')
			->whereIn('brgpk', $brgpkList)
			->select('brgpk', 'podtpk')
			->get()
			->groupBy('brgpk')
			->map(fn($items) => $items->pluck('podtpk'));
	
		$belidtMap = DB::table('belidt')
			->whereIn('brgpk', $brgpkList)
			->select('brgpk', 'belidtpk')
			->get()
			->groupBy('brgpk')
			->map(fn($items) => $items->pluck('belidtpk'));

		// Ambil data keluar sekaligus
		$keluarPodt = DB::table('ttdt')
			->whereIn('podtpk', $podtMap->flatten())
			->select('podtpk', DB::raw('SUM(jumlah) as total'))
			->groupBy('podtpk')
			->pluck('total', 'podtpk');

		$keluarBelidt = DB::table('ttdt')
			->whereIn('belidtpk', $belidtMap->flatten())
			->select('belidtpk', DB::raw('SUM(jumlah) as total'))
			->groupBy('belidtpk')
			->pluck('total', 'belidtpk');

		foreach ($data_barang as $d) {

			$QtyMasuk = ($masukPodt[$d->brgpk] ?? 0) + ($masukBelidt[$d->brgpk] ?? 0);

			$QtyKeluar = collect($podtMap[$d->brgpk] ?? [])->sum(fn($pk) => $keluarPodt[$pk] ?? 0) + collect($belidtMap[$d->brgpk] ?? [])->sum(fn($pk) => $keluarBelidt[$pk] ?? 0);

			$QtyAkhir = $d->qtyawal + $QtyMasuk - $QtyKeluar;

			//Batas code awal
			// $MasukByPodt = DB::table('podt')
            // ->where('brgpk', $d->brgpk)
            // ->sum('jmlbeli');

			// $MasukByBelidt = DB::table('belidt')
			// 	->where('brgpk', $d->brgpk)
			// 	->sum('jmlbeli');
			
			// $QtyMasuk = $MasukByPodt + $MasukByBelidt;

			// $GetPodt = DB::table('podt')
			// 	->where('brgpk', $d->brgpk)
			// 	->pluck('podtpk'); // Ambil semua podtpk untuk brgpk tersebut

			// $GetBelidt = DB::table('belidt')
			// 	->where('brgpk', $d->brgpk)
			// 	->pluck('belidtpk');

			// $KeluarByPodt = DB::table('ttdt')
			// 	->whereIn('podtpk', $GetPodt) // Gunakan whereIn untuk semua podtpk yang ditemukan
			// 	->sum('jumlah');

			// $KeluarByBelidt = DB::table('ttdt')
			// 	->whereIn('belidtpk', $GetBelidt) // Gunakan whereIn untuk semua podtpk yang ditemukan
			// 	->sum('jumlah');
			
			// $QtyKeluar = $KeluarByPodt + $KeluarByBelidt;


			// $QtyAkhir = $d->qtyawal + $QtyMasuk - $QtyKeluar;

			$row[] = array(
				'brgpk' => $d->brgpk,
				'index' => $index,
                'noseri' => !empty($d->noseri) ? $d->noseri : '-',
				'merknm' => !empty($d->merknm) ? $d->merknm : '-',
                'qtyawal' => !empty($d->qtyawal) ? $d->qtyawal : '-',
                'jnsbrgnm' => !empty($d->jnsbrgnm) ? $d->jnsbrgnm : '-',
				'brgnm' => $d->brgnm,
                'qtymasuk' => $QtyMasuk,
                'qtykeluar' => $QtyKeluar,
                'qtyakhir' => $QtyAkhir,
				'lastupdate' => $d->lastupdate ?? '-',
			);
			$index++;
		}
		$result = array_merge($result, array('rows' => $row));
		return json_encode($result);
	}
}

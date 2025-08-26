<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use PDF;
use Illuminate\Support\Collection;
use App\Helpers\FormatRupiahHelper;

class BuktiKasKelController extends Controller
{
    function PageBuktiKasKeluarAwal(){
        return view('menu.laporan.bukti.awal.list-bkk');
    }

    function getListBuktiKasKelAwal(Request $request)
	{

		$tahun = date("Y");
		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInput = $request->searchByInput;
		$SelectDate = $request->SelectDate;
		// $SelectDate = '2025-01-02';

		$data_pr = DB::table('belidt')
		->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
        ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
        ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
        ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
        ->leftJoin('cur', 'cur.curpk', '=', 'beli.curpk')
        ->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
		->select(
			'belidt.belidtpk',
            'user.userpk',
            'user.guserpk',
            'user.login',
            'sup.suppk',
            'sup.supnm',
            'cur.curpk',
            'cur.curid',
            'ab.abpk',
            'ab.abnm',
            'kel.kelpk',
            'kel.kelnm',
			'beli.belipk',
            'beli.nobukti',
            'beli.noinv',
            'beli.totbeli',
			'belidt.brgnm',
			'belidt.unit',
			'belidt.hrgbeli',
			'belidt.jmlbeli',
			'belidt.jmlbayar',
			'belidt.cg',
			'belidt.tglbayar',
            'beli.tglinv',
            DB::raw('DATE_FORMAT(beli.tglinv, "%d %b %Y") as tanggal'),
			// DB::raw('SUM(beli.totbeli) as total_hrgbeli')
			DB::raw('SUM(belidt.jmlbayar) as total_hrgbeli')
		)
		->orderBy('belidt.belidtpk', 'DESC')
		->where('ab.abpk', 1)
		// ->whereIn('beli.userpk', ['4', '6'])
		->where('belidt.jmlbayar', '>', 0)
		->where('beli.kelpk', '<>', 63)
		// ->where('beli.tglinv', $SelectDate)
		->groupBy('sup.suppk');

		if (!empty($searchByInput)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(belidt.brgnm, '')
            )");
			$data_pr = $data_pr->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}
		
		if (!empty($SelectDate)) {
			$SelectDate = Carbon::createFromFormat('d/m/Y', $SelectDate)->format('Y-m-d');
			$data_pr = $data_pr->whereDate('beli.tglinv', $SelectDate);
		} else {
			$data_pr = $data_pr->whereDate('beli.tglinv', Carbon::today());
		}

		$AllDataPo = $data_pr->get();
		$offset = ($page - 1) * $rows;
		$data_pr = $data_pr->skip($offset)->take($rows)->get();

		$result = array();
		$result['total'] = $AllDataPo->count();
		$result['page'] = $page;
		$result['rows'] = $rows;
		$result['offset'] = $offset;
		$row = array();
		$index = $offset + 1;

		foreach ($data_pr as $d) {

			$barang_list = collect(DB::table('belidt')
			->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
			->where('beli.suppk', $d->suppk)
			->whereDate('beli.tglinv', $SelectDate)
			->select('belidt.brgnm')
			->get());

			// Hilangkan duplikat nama barang
			$barang_str = $barang_list
				->unique('brgnm')                 // <-- Group by brgnm
				->map(function ($item) {
					return trim($item->brgnm);    // Hilangkan spasi berlebih
				})
				->implode(', ');
		

			$dt_index = "<p>" . $index . "</p>";
			$dt_supnm = "<p>" .'BAYAR '. $d->supnm . " (" . $barang_str . ")</p>";
			$dt_hrgbeli = "<p>" . number_format($d->total_hrgbeli, 0, '.', ',') . "</p>";

			$baseUrl = url('/laporan/bukti-kas-keluar/awal/');

			$row[] = array(
				'belidtpk' => $d->belidtpk,
				'belipk' => $d->belipk,
                'index' => '<a href="'.$baseUrl.'/'. $SelectDate .'/'. $d->suppk .'" target="_blank" style="color:#000; cursor:pointer; text-decoration:none;">' . $dt_index . '</a>',
                'supnm' => '<a href="'.$baseUrl.'/'. $SelectDate .'/'. $d->suppk .'" target="_blank" style="color:#000; cursor:pointer; text-decoration:none;">' . $dt_supnm . '</a>',
                'hrgbeli' => '<a href="'.$baseUrl.'/'. $SelectDate .'/'. $d->suppk .'" target="_blank" style="color:#000; cursor:pointer; text-decoration:none;">' . $dt_hrgbeli . '</a>',
			);
			$index++;
		}
		$result = array_merge($result, array('rows' => $row));
		return json_encode($result);
	}

	public function PdfListBkkAwal(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'SelectDate' => 'nullable|date',
		]);

		if ($validator->fails()) {
			return response()->json(['error' => $validator->errors()->first()], 400);
		}

		$SelectDate = $request->input('SelectDate');
		$formattedDate = Carbon::parse($SelectDate)->format('Y-m-d');

		$query = DB::table('belidt')
			->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
			->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
			->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
			->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
			->leftJoin('cur', 'cur.curpk', '=', 'beli.curpk')
			->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
			->select(
				'belidt.belidtpk',
				'user.userpk',
				'sup.suppk',
				'sup.supnm',
				'beli.belipk',
				'beli.noinv',
				'beli.tglinv',
				DB::raw('SUM(belidt.jmlbayar) as total_hrgbeli')
			)
			->whereDate('beli.tglinv', $formattedDate)
			->where('ab.abpk', 1)
			// ->where('user.userpk', 4)
			->where('beli.kelpk', '<>', 63)
			->groupBy('sup.suppk')
			->orderBy('sup.supnm', 'asc');

		$data = $query->get();
		$formattedData = [];
		$index = 1;

		foreach ($data as $d) {
			$barang_list = DB::table('belidt')
				->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
				->where('beli.suppk', $d->suppk)
				->whereDate('beli.tglinv', $SelectDate)
				->select('belidt.brgnm')
				->distinct()
				->get()
				->pluck('brgnm')
				->map(function ($item) {
					return trim($item);
				})
				->implode(', ');

			$formattedData[] = [
				'index' => $index,
				'supnm' => 'BAYAR ' . $d->supnm . ' (' . $barang_list . ')',
				'hrgbeli' => number_format($d->total_hrgbeli, 0, '.', ',')
			];
			$index++;
		}

		$totalAll = array_sum(array_map(function ($item) {
			return (float) str_replace(',', '', $item['hrgbeli']);
		}, $formattedData));

		$pdf = Pdf::loadView('menu.laporan.bukti.awal.pdf-list-bkk', compact('formattedData', 'SelectDate', 'totalAll'))->setPaper('a4', 'potrait');
		return $pdf->stream('Laporan-bukti-kas.pdf');
	}

	public function PageDetailBkkAwal(Request $request, $tglInv, $suppk){
		$tglInv = $request->segment(4);
		$suppk = $request->segment(5);
		return view('menu.laporan.bukti.awal.detail-bkk', compact('tglInv', 'suppk'));
	}

	public function GetDetailBkkAwal(Request $request, $tglInv, $suppk)
    {

		$data_beli = DB::table('beli')
            ->where('tglinv', '=', $tglInv)
			->where('suppk', '=', $suppk);
        $data_beli = $data_beli->get();

        $result = array();
        $row = array();
        $index = +1;
		$total_jmlbayar = 0;
		$total_jmlhrg = 0;


		foreach ($data_beli as $d) {
			$barang_list = DB::table('belidt')
				->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
				->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
				->where('belidt.belipk', $d->belipk)
				->select('belidt.belidtpk','belidt.belipk', 'kel.kelpk', 'kel.kelnm', 'belidt.brgnm', 'belidt.unit', 'belidt.jmlbeli', 'belidt.hrgbeli', 'belidt.jmlhrg', 'belidt.jmlbayar')
				->get();

			foreach ($barang_list as $barang) {
				// $total_jmlbayar += $barang->jmlbayar;
				$total_jmlhrg += $barang->jmlhrg;

				$row[] = array(
					'belipk' => $d->belipk,
					'belidtpk' => $barang->belidtpk,
					'index' => $index,
					'kelnm' => $barang->kelnm,
					'nobukti' => $d->nobukti,
					'tglinv' => $d->tglinv,
					'noinv' => trim($d->noinv),
					'brgnm' => trim($barang->brgnm),
					'jmlbeli' => number_format($barang->jmlbeli, 0, '.', ','),
					'unit' => trim($barang->unit),
					'hrgbeli' => number_format($barang->hrgbeli, 2, '.', ','),
					'jmlhrg' => number_format($barang->jmlhrg, 0, '.', ','),
					// 'jmlbayar' => number_format($barang->jmlbayar, 0, '.', ','),
				);
				$index++;
			}
		}

		$row2[] = [
			'index' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
			'nobukti' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
			'tglinv' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
			'noinv' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
			'brgnm' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
			'jmlbeli' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
			'unit' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
			'hrgbeli' => '<b> Grand Total </b>',
    		'jmlhrg' => number_format($total_jmlhrg, 0, '.', ','),
		];

		$row3 = [
			// [
			// 	'hrgbeli' => '<b> Terbilang </b>',
			// 	'jmlhrg' => ucwords(FormatRupiahHelper::terbilang($total_jmlhrg)) . ' Rupiah',
			// ]
		];

		$footer = array_merge($row2, $row3);

        $result = array_merge($result, array('rows' => $row), array('footer' => $footer));
        return json_encode($result);
    }

	public function PdfBkkAwal(Request $request, $tglInv, $suppk)
    {

		$data_beli = DB::table('beli')
			->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
			->select('beli.belipk', 'beli.nobukti', 'beli.noinv','beli.tglinv', 'sup.suppk', 'sup.supnm')
            ->where('beli.tglinv', '=', $tglInv)
			->where('beli.suppk', '=', $suppk);
        $data = $data_beli->get();


        $formattedData = [];

		foreach ($data as $index => $d) {
			$barang_list = DB::table('belidt')
				->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
				->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
				->where('belidt.belipk', $d->belipk)
				->select('belidt.belidtpk','belidt.belipk', 'sup.suppk', 'sup.supnm','belidt.brgnm', 'belidt.unit', 'belidt.jmlbeli', 'belidt.hrgbeli', 'belidt.jmlhrg', 'belidt.jmlbayar')
				->get();
			

			foreach ($barang_list as $barang) {
				$formattedData[] = [
					'belipk' => $d->belipk,
					'nobukti' => $d->nobukti,
					'noinv' => trim($d->noinv),
					'tglinv' => $d->tglinv,
					'brgnm' => trim($barang->brgnm),
					'unit' => trim($barang->unit),
					'jmlbeli' => number_format($barang->jmlbeli, 2, '.', ','),
					'hrgbeli' => number_format($barang->hrgbeli, 0, '.', ','),
					'jmlhrg' => number_format($barang->jmlhrg, 2, '.', ','),
					// 'jmlbayar' => number_format($barang->jmlbayar, 0, '.', ','),
				];
			}
		}

		$supnm = DB::table('sup')->where('suppk', $suppk)->value('supnm');

		$pdf = Pdf::loadView('menu.laporan.bukti.awal.detail-bkk-pdf2', compact('formattedData', 'tglInv', 'supnm'));
        return $pdf->stream('Laporan Bukti Kas Keluar.pdf');
    }

	function getListCekCashAwal(Request $request)
	{

		$tahun = date("Y");
		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInput = $request->searchByInput;
		$SelectStart = $request->SelectStart;
		$SelectFinish = $request->SelectFinish;
		$filterByStatusPembayaran = $request->filterByStatusPembayaran;

		// $SelectStart = '2025-01-02';
		// $SelectFinish = '2025-01-02';


		$data_cekcash = DB::table('belidt')
		->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
        ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
        ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
        ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
        ->leftJoin('cur', 'cur.curpk', '=', 'beli.curpk')
        ->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
		->select(
			'belidt.belidtpk',
            'user.userpk',
            'user.guserpk',
            'user.login',
            'sup.suppk',
            'sup.supnm',
            'cur.curpk',
            'cur.curid',
            'ab.abpk',
            'ab.abnm',
            'kel.kelpk',
            'kel.kelnm',
			'beli.belipk',
            'beli.nobukti',
            'beli.noinv',
            'beli.totbeli',
			'belidt.brgnm',
			'belidt.unit',
			'belidt.hrgbeli',
			'belidt.jmlbeli',
			'belidt.jmlhrg',
			'belidt.jmlbayar',
			'belidt.cg',
			'belidt.cg as cashgiro',
			'belidt.tglbayar',
			'belidt.nobg',
			'belidt.tglaju',
            'beli.tglinv',
            DB::raw('DATE_FORMAT(beli.tglinv, "%d %b %Y") as tanggal'),
		)->orderBy('beli.tglinv', 'ASC')->orderBy('belidt.belidtpk', 'ASC');

		// if (!empty($filterByStatusPembayaran)) {
		// if ($filterByStatusPembayaran == 1) {
        //     $data_cekcash = $data_cekcash->where('belidt.cg', null);
        // } elseif ($filterByStatusPembayaran == 2) {
        //     $data_cekcash = $data_cekcash->where('belidt.cg', '=', 'C')->whereDate('belidt.tglbayar', null);
        // }elseif ($filterByStatusPembayaran == 3) {
        //     $data_cekcash = $data_cekcash->where('belidt.cg', '=', 'BG')->whereDate('belidt.tglbayar', null);
        // }elseif ($filterByStatusPembayaran == 4) {
        //     $data_cekcash = $data_cekcash->where('belidt.cg', '=', 'C');
        // }elseif ($filterByStatusPembayaran == 5) {
        //     $data_cekcash = $data_cekcash->where('belidt.cg', '=', 'BG');
        // }
		// // else{
		// // 	 $data_cekcash = $data_cekcash->where('belidt.cg', '=', '');
		// // }
		// }


		if (!empty($filterByStatusPembayaran)) {
			if ($filterByStatusPembayaran == 1) {
				// Semua (tidak filter cg)
				// $data_cekcash->get();
			} elseif ($filterByStatusPembayaran == 2) {
				$data_cekcash = $data_cekcash->where('beli.abpk', 1)->where('belidt.tglbayar', null);
			} elseif ($filterByStatusPembayaran == 3) {
				$data_cekcash = $data_cekcash->where('beli.abpk', 2);
			} elseif ($filterByStatusPembayaran == 4) {
				$data_cekcash = $data_cekcash->where('belidt.cg', '=', 'C');
			} elseif ($filterByStatusPembayaran == 5) {
				$data_cekcash = $data_cekcash->where('belidt.cg', '=', 'G');
			} 
		}

		if (!empty($searchByInput)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(sup.supnm, ''),
                COALESCE(user.login, ''),
                COALESCE(beli.noinv, ''),
                COALESCE(belidt.brgnm, '')
            )");

			$data_cekcash = $data_cekcash->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}
		
		// if (!empty($SelectStart)) {
		// 	$SelectStart = Carbon::createFromFormat('d/m/Y', $SelectStart)->format('Y-m-d');
		// 	$data_cekcash = $data_cekcash->whereDate('beli.tglinv', '>=', $SelectStart)->orderBy('beli.tglinv', 'ASC');
		// }

		// if (!empty($SelectFinish)) {
		// 	$SelectFinish = Carbon::createFromFormat('d/m/Y', $SelectFinish)->format('Y-m-d');
		// 	$data_cekcash = $data_cekcash->whereDate('beli.tglinv', '<=', $SelectFinish)->orderBy('beli.tglinv', 'ASC');
		// }

		if (!empty($SelectStart) && !empty($SelectFinish)) {
			$SelectStart = Carbon::createFromFormat('d/m/Y', $SelectStart)->format('Y-m-d');
			$SelectFinish = Carbon::createFromFormat('d/m/Y', $SelectFinish)->format('Y-m-d');

			$data_cekcash = $data_cekcash->where(function ($query) use ($SelectStart, $SelectFinish) {
				$query->whereBetween('beli.tglinv', [$SelectStart, $SelectFinish])
					->orWhereBetween('belidt.tglbayar', [$SelectStart, $SelectFinish]);
			});
		}

		$AllDataPo = $data_cekcash->get();
		$offset = ($page - 1) * $rows;
		$data_cekcash = $data_cekcash->skip($offset)->take($rows)->get();

		$result = array();
		$result['total'] = $AllDataPo->count();
		$result['page'] = $page;
		$result['rows'] = $rows;
		$result['offset'] = $offset;
		$row = array();
		$index = $offset + 1;

		foreach ($data_cekcash as $d) {
			
			if($d->cashgiro == "C"){
				$nama_cg = 'CASH';
			}elseif($d->cashgiro == "G"){
				$nama_cg = 'GIRO';
			}else{
				$nama_cg = '';
			}

	
			if($d->abpk == 1){
				$dt_index = "<p>" . $index . "</p>";
				$dt_supnm = "<p>" . $d->supnm . "</p>";
   				$dt_tanggal = "<p>" . $d->tanggal . "</p>";
                $dt_noinv = "<p>" . $d->noinv . "</p>";
				$dt_brgnm = "<p>" . $d->brgnm . "</p>";
				$dt_unit = "<p>" . $d->unit . "</p>";
				$dt_hrgbeli = "<p>" . number_format($d->hrgbeli, 0, '.', ',') . "</p>";
				$dt_jmlbeli = "<p>" . $d->jmlbeli . "</p>";
				$dt_jmlhrg = "<p>" . number_format($d->jmlhrg, 2, '.', ',') . "</p>";
				$dt_tglbayar = "<p>" . $d->tglbayar . "</p>";
                $dt_jmlbayar = "<p>" . number_format($d->jmlbayar, 2, '.', ',') . "</p>";
				$dt_cg = "<p>" . $nama_cg . "</p>";
                $dt_login = "<p>" . strtoupper($d->login) . "</p>";
				$dt_nobg = "<p>" . $d->nobg . "</p>";
				$dt_tglaju = "<p>" . $d->tglaju . "</p>";
			}else{
				$dt_index = "<p style='color:red'>" . $index . "</p>";
                $dt_noinv = "<p style='color:red'>" .$d->noinv . "</p>";
				$dt_brgnm = "<p style='color:red'>" .$d->brgnm . "</p>";
				$dt_supnm = "<p style='color:red'>" .$d->supnm . "</p>";
				$dt_tanggal = "<p style='color:red'>" .$d->tanggal . "</p>";
				$dt_unit = "<p style='color:red'>" .$d->unit . "</p>";
				$dt_hrgbeli = "<p style='color:red'>" . number_format($d->hrgbeli, 0, '.', ',') . "</p>";
				$dt_jmlbeli = "<p style='color:red'>" .$d->jmlbeli . "</p>";
				$dt_jmlhrg = "<p style='color:red'>" . number_format($d->jmlhrg, 2, '.', ',') . "</p>";
				$dt_tglbayar = "<p style='color:red'>" .$d->tglbayar . "</p>";
				$dt_jmlbayar = "<p style='color:red'>" . number_format($d->jmlbayar, 2, '.', ',') . "</p>";
				$dt_cg = "<p style='color:red'>" .$nama_cg . "</p>";
                $dt_login = "<p style='color:red'>" . strtoupper($d->login) . "</p>";
				$dt_nobg = "<p style='color:red'>" .$d->nobg . "</p>";
				$dt_tglaju = "<p style='color:red'>" .$d->tglaju  . "</p>";
			}

			$row[] = array(
				'belidtpk' => $d->belidtpk,
                'belipk' => $d->belipk,
                'belidtpk' => $d->belidtpk,
				'index' => $dt_index,
				'noinv' => $dt_noinv,
				'supnm' => $dt_supnm,
				'tanggal' => $dt_tanggal,
				'brgnm' => $dt_brgnm,
				'unit' => $dt_unit,
				'hrgbeli' => $dt_hrgbeli,
				'jmlbeli' => $dt_jmlbeli,
				'jmlhrg' => $dt_jmlhrg,
				'tglbayar' => $dt_tglbayar,
				'jmlbayar' => $dt_jmlbayar,
				'cg' => $dt_cg,
                'user' => $dt_login,
                'nobg' => $dt_nobg,
                'tglaju' => $dt_tglaju,
			);
			$index++;
		}
		$result = array_merge($result, array('rows' => $row));
		return json_encode($result);
	}

	public function pdf_cek_cash_by_modal_awal(Request $request)
    {
		ini_set('memory_limit', '512M'); // atau 1024M jika masih error
		set_time_limit(300);

        $filterByStatusPembayaran = $request->input('filterByStatusPembayaran');
        $SelectStart = $request->input('SelectStart');
        $SelectFinish = $request->input('SelectFinish');

		$data_cekcash = DB::table('belidt')
		->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
        ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
        ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
        ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
        ->leftJoin('cur', 'cur.curpk', '=', 'beli.curpk')
        ->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
		->select(
			'belidt.belidtpk',
            'user.userpk',
            'user.guserpk',
            'user.login',
            'sup.suppk',
            'sup.supnm',
            'cur.curpk',
            'cur.curid',
            'ab.abpk',
            'ab.abnm',
            'kel.kelpk',
            'kel.kelnm',
			'beli.belipk',
            'beli.nobukti',
            'beli.noinv',
            'beli.totbeli',
			'belidt.brgnm',
			'belidt.unit',
			'belidt.hrgbeli',
			'belidt.jmlbeli',
			'belidt.jmlhrg',
			'belidt.jmlbayar',
			'belidt.cg',
			'belidt.cg as cashgiro',
			'belidt.nobg',
			'belidt.tglaju',
            'beli.tglinv',
            DB::raw('DATE_FORMAT(beli.tglinv, "%d %b %Y") as tanggal'),
            // DB::raw('DATE_FORMAT(beli.tglinv, "%d-%m-%Y") as tanggal'),
            DB::raw('DATE_FORMAT(belidt.tglbayar, "%d-%m-%Y") as tglbayar'),
		)->orderBy('beli.tglinv', 'ASC')->orderBy('belidt.belidtpk', 'ASC');

		if (!empty($searchByInput)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(sup.supnm, ''),
                COALESCE(user.login, ''),
                COALESCE(beli.noinv, ''),
                COALESCE(belidt.brgnm, '')
            )");

			$data_cekcash = $data_cekcash->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}

		$tanggalLabel = '';
        if (!empty($SelectStart) && !empty($SelectFinish)) {
            $tglStart = Carbon::parse($SelectStart)->format('m-d-Y');
            $tglFinish = Carbon::parse($SelectFinish)->format('m-d-Y');

            $tanggalLabel = $SelectStart === $SelectFinish
                ? "TANGGAL : $tglStart"
                : "TANGGAL : $tglStart S/D $tglFinish";
        }
		
		if (!empty($SelectStart)) {
			$SelectStart = Carbon::createFromFormat('d/m/Y', $SelectStart)->format('Y-m-d');
			$data_cekcash = $data_cekcash->whereDate('beli.tglinv', '>=', $SelectStart)->orderBy('beli.tglinv', 'ASC');
		}

		if (!empty($SelectFinish)) {
			$SelectFinish = Carbon::createFromFormat('d/m/Y', $SelectFinish)->format('Y-m-d');
			$data_cekcash = $data_cekcash->whereDate('beli.tglinv', '<=', $SelectFinish)->orderBy('beli.tglinv', 'ASC');
		}

		if (!empty($filterByStatusPembayaran)) {
			if ($filterByStatusPembayaran == 1) {
			} elseif ($filterByStatusPembayaran == 2) {
				$data_cekcash = $data_cekcash->where('beli.abpk', 1)->where('belidt.tglbayar', null);
			} elseif ($filterByStatusPembayaran == 3) {
				$data_cekcash = $data_cekcash->where('beli.abpk', 2);
			} elseif ($filterByStatusPembayaran == 4) {
				$data_cekcash = $data_cekcash->where('belidt.cg', '=', 'C');
			} elseif ($filterByStatusPembayaran == 5) {
				$data_cekcash = $data_cekcash->where('belidt.cg', '=', 'G');
			} 
		}

        // $data_Cash = $data_cekcash->where('beli.abpk', 1)->get();
		$data_cekcash_cash = clone $data_cekcash;
		$data_cekcash_tempo = clone $data_cekcash;


		$data_Cash = $data_cekcash_cash->where('beli.abpk', 1)->get();
		$count_data_Cash = $data_Cash->count();

		$data_Tempo = $data_cekcash_tempo->where('beli.abpk', 2)->get();
		$count_data_Tempo = $data_Tempo->count();

        $formattedData_Cash = [];

        foreach ($data_Cash as $index => $d) {
	
            $formattedData_Cash[] = [
				'belidtpk' => $d->belidtpk,
                'belipk' => $d->belipk,
                'belidtpk' => $d->belidtpk,
				'index' => $index,
				'noinv' => $d->noinv,
				'supnm' => $d->supnm,
				'tanggal' => $d->tanggal,
				'brgnm' => $d->brgnm,
				'unit' => $d->unit,
				'hrgbeli' => number_format($d->hrgbeli, 0, '.', ','),
				'jmlbeli' => number_format($d->jmlbeli, 0, '.', ','),
				'jmlhrg' => number_format($d->jmlhrg, 0, '.', ','),
				'tglbayar' => $d->tglbayar,
				'jmlbayar' => number_format($d->jmlbayar, 0, '.', ','),
            ];
        }
		

        $formattedData_Tempo = [];

        foreach ($data_Tempo as $index => $d) {
	
            $formattedData_Tempo[] = [
				'belidtpk' => $d->belidtpk,
                'belipk' => $d->belipk,
                'belidtpk' => $d->belidtpk,
				'index' => $index,
				'noinv' => $d->noinv,
				'supnm' => $d->supnm,
				'tanggal' => $d->tanggal,
				'brgnm' => $d->brgnm,
				'unit' => $d->unit,
				'hrgbeli' => number_format($d->hrgbeli, 0, '.', ','),
				'jmlbeli' => number_format($d->jmlbeli, 0, '.', ','),
				'jmlhrg' => number_format($d->jmlhrg, 0, '.', ','),
				'tglbayar' => $d->tglbayar,
				'jmlbayar' => number_format($d->jmlbayar, 0, '.', ','),
            ];
        }

		$subTotalCash = $data_Cash->sum('jmlhrg');
		$grandTotalCash = $data_Cash->sum('jmlbayar');

		$subTotalTempo = $data_Tempo->sum('jmlhrg');
		$grandTotalTempo = $data_Tempo->sum('jmlbayar');

		$SubTotalCashTempoJmlHrg = $subTotalCash + $subTotalTempo;
		$TotalGrandCashTempo = $grandTotalTempo + $grandTotalCash;


        $pdf = Pdf::loadView('menu.laporan.bukti.awal.modal.pdf-modal-cek-cash', 
		compact('subTotalCash', 'grandTotalCash', 'subTotalTempo', 'grandTotalTempo',  'SubTotalCashTempoJmlHrg', 'TotalGrandCashTempo', 
		'formattedData_Cash', 'formattedData_Tempo', 'tanggalLabel', 'count_data_Cash', 'count_data_Tempo'))->setPaper('a4', 'landscape');
        return $pdf->stream('Laporan Pembayaran Cash-Tempo.pdf');
    }

	public function FilterByStatusPembayaran()
	{
		$data = [
			[ 'statuspk' => '1', 'status' => 'SEMUA' ],
			[ 'statuspk' => '2', 'status' => 'CASH BELUM DIBAYAR' ],
			[ 'statuspk' => '3', 'status' => 'TEMPO BELUM DIBAYAR' ],
			[ 'statuspk' => '4', 'status' => 'PEMBAYARAN CASH' ],
			[ 'statuspk' => '5', 'status' => 'PEMBAYARAN GIRO' ],
		];
		return json_encode($data);
	}

	// BKK Version 2
	function PageBuktiKasKeluar(){
        return view('menu.laporan.bukti.2.list-bkk2');
    }

	function getListBuktiKasKel(Request $request)
	{

		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInput = $request->searchByInput;
		$SelectDate = $request->SelectDate;

		$data_pr = DB::table('belidt')
		->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
        ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
        ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
        ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
        ->leftJoin('cur', 'cur.curpk', '=', 'beli.curpk')
        ->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
		->select(
			'belidt.belidtpk',
            'user.userpk',
            'user.guserpk',
            'user.login',
            'sup.suppk',
            'sup.supnm',
            'cur.curpk',
            'cur.curid',
            'ab.abpk',
            'ab.abnm',
            'kel.kelpk',
            'kel.kelnm',
			'beli.belipk',
            'beli.nobukti',
            'beli.noinv',
            'beli.totbeli',
			'belidt.brgnm',
			'belidt.unit',
			'belidt.hrgbeli',
			'belidt.jmlbeli',
			'belidt.jmlbayar',
			'belidt.cg',
			'belidt.tglbayar',
            'beli.tglinv',
			DB::raw('SUM(belidt.jmlbayar) as total_hrgbeli')
		)
		->orderBy('belidt.belidtpk', 'DESC')
		->where('ab.abpk', 1)
		->where('belidt.jmlbayar', '>', 0)
		->where('beli.kelpk', '<>', 63)
		->groupBy('sup.suppk');


		if (!empty($searchByInput)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(belidt.brgnm, ''),
				COALESCE(sup.supnm, '')
            )");
			$data_pr = $data_pr->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}
		
		if (!empty($SelectDate)) {
			$SelectDate = Carbon::createFromFormat('d/m/Y', $SelectDate)->format('Y-m-d');
			$data_pr = $data_pr->whereDate('beli.tglinv', $SelectDate);
		} else {
			$data_pr = $data_pr->whereDate('beli.tglinv', Carbon::today());
		}

		$AllDataPo = $data_pr->get();
		$offset = ($page - 1) * $rows;
		$data_pr = $data_pr->skip($offset)->take($rows)->get();

		$result = array();
		$result['total'] = $AllDataPo->count();
		$result['page'] = $page;
		$result['rows'] = $rows;
		$result['offset'] = $offset;
		$row = array();
		$index = $offset + 1;

		foreach ($data_pr as $d) {

			$barang_list = collect(DB::table('belidt')
			->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
			->where('beli.suppk', $d->suppk)
			->whereDate('beli.tglinv', $SelectDate)
			->select('belidt.brgnm')
			->get());

			// Hilangkan duplikat nama barang
			$barang_str = $barang_list
				->unique('brgnm')                 // <-- Group by brgnm
				->map(function ($item) {
					return trim($item->brgnm);    // Hilangkan spasi berlebih
				})
				->implode(', ');

			$total_bayar = DB::table('belidt')
			->join('beli', 'beli.belipk', '=', 'belidt.belipk')
			->where('beli.suppk', $d->suppk)
			->whereDate('beli.tglinv', $SelectDate)
			// ->where('belidt.jmlbayar', '>', 0)
			->sum('belidt.jmlbayar');

			$dt_index = "<p>" . $index . "</p>";
			$dt_supnm = "<p>" .'BAYAR '. $d->supnm . " (" . $barang_str . ")</p>";
			$dt_hrgbeli = "<p>" . number_format($total_bayar, 0, '.', ',') . "</p>";
			$dt_hrgbeli = "<p>" . number_format(round($total_bayar, -2), 0, '.', ',') . "</p>";

			$baseUrl = url('/laporan/bukti-kas-keluar/');

			$row[] = array(
				'belidtpk' => $d->belidtpk,
				'belipk' => $d->belipk,
                'index' => '<a href="'.$baseUrl.'/'. $SelectDate .'/'. $d->suppk .'" style="color:#000; cursor:pointer; text-decoration:none;">' . $dt_index . '</a>',
                'supnm' => '<a href="'.$baseUrl.'/'. $SelectDate .'/'. $d->suppk .'" style="color:#000; cursor:pointer; text-decoration:none;">' . $dt_supnm . '</a>',
                'hrgbeli' => '<a href="'.$baseUrl.'/'. $SelectDate .'/'. $d->suppk .'" style="color:#000; cursor:pointer; text-decoration:none;">' . $dt_hrgbeli . '</a>',
			);
			$index++;
		}
		$result = array_merge($result, array('rows' => $row));
		return json_encode($result);
	}

	public function PdfListBkk(Request $request)
	{
		$validator = Validator::make($request->all(), [
			'SelectDate' => 'nullable|date',
		]);

		if ($validator->fails()) {
			return response()->json(['error' => $validator->errors()->first()], 400);
		}

        $searchByInput = $request->searchByInput;
		$SelectDate = $request->input('SelectDate');
		$formattedDate = Carbon::parse($SelectDate)->format('Y-m-d');

		$query = DB::table('belidt')
			->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
			->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
			->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
			->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
			->leftJoin('cur', 'cur.curpk', '=', 'beli.curpk')
			->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
			->select(
				'belidt.belidtpk',
				'belidt.brgnm',
				'user.userpk',
				'sup.suppk',
				'sup.supnm',
				'beli.belipk',
				'beli.noinv',
				'beli.tglinv',
				DB::raw('SUM(belidt.jmlbayar) as total_hrgbeli')
			)
			->whereDate('beli.tglinv', $formattedDate)
			->where('ab.abpk', 1)
			// ->where('user.userpk', 4)
			->where('beli.kelpk', '<>', 63)
			->groupBy('sup.suppk')
			->where('belidt.jmlbayar', '>', 0)
			->orderBy('sup.supnm', 'asc');

		if(!empty($searchByInput)) {
            $concatenatedValue = DB::raw("CONCAT(
                IFNULL(sup.supnm, ''),
                IFNULL(belidt.brgnm, '')
            )");
            $query = $query->where(DB::raw($concatenatedValue), 'like', '%' . $searchByInput . '%');
        }

		$data = $query->get();
		$formattedData = [];
		$index = 1;

		foreach ($data as $d) {
			$barang_list = DB::table('belidt')
				->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
				->where('beli.suppk', $d->suppk)
				->whereDate('beli.tglinv', $SelectDate)
				->select('belidt.brgnm')
				->distinct()
				->get()
				->pluck('brgnm')
				->map(function ($item) {
					return trim($item);
				})
				->implode(', ');

			$formattedData[] = [
				'index' => $index,
				'supnm' => 'BAYAR ' . $d->supnm . ' (' . $barang_list . ')',
				// 'hrgbeli' => number_format($d->total_hrgbeli, 0, '.', ',')
				'hrgbeli' => number_format(round($d->total_hrgbeli, -2), 0, '.', ',')
			];
			$index++;
		}

		$totalAll = array_sum(array_map(function ($item) {
			return (float) str_replace(',', '', $item['hrgbeli']);
		}, $formattedData));

		$pdf = Pdf::loadView('menu.laporan.bukti.2.pdf-list-bkk2', compact('formattedData', 'SelectDate', 'totalAll'))->setPaper('a4', 'potrait');
		return $pdf->stream('Laporan-bukti-kas.pdf');
	}

	public function PageDetailBkk(Request $request, $tglInv, $suppk){
		$tglInv = $request->segment(3);
		$suppk = $request->segment(4);

		return view('menu.laporan.bukti.2.detail-bkk2', compact('tglInv', 'suppk'));
	}

	public function GetDetailBkk(Request $request, $tglInv, $suppk)
    {

		$data_beli = DB::table('beli')
            ->where('tglinv', '=', $tglInv)
			->where('suppk', '=', $suppk);
        $data_beli = $data_beli->get();

        $result = array();
        $row = array();
        $index = 1;
		$total_jmlbayar = 0;
		$total_jmlhrg = 0;


		foreach ($data_beli as $d) {
			$barang_list = DB::table('belidt')
				->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
				->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
				->where('belidt.belipk', $d->belipk)
				->select('belidt.belidtpk','belidt.belipk', 'kel.kelpk', 'kel.kelnm', 'belidt.brgnm', 'belidt.unit', 'belidt.jmlbeli', 'belidt.hrgbeli', 'belidt.jmlhrg', 'belidt.jmlbayar')
				->get();

			foreach ($barang_list as $barang) {
				// $total_jmlbayar += $barang->jmlbayar;
				// $total_jmlhrg += $barang->jmlhrg;
				$hitung_jmlharga = ($barang->hrgbeli * $barang->jmlbeli);
				$total_jmlhrg += $hitung_jmlharga;
				$row_index = "<a href='javascript:void(0)' onclick='KlikDetail($barang->belipk);' style='text-decoration: none; color:black;'>
                            $index
                          </a>";

				$row_nobukti = "<a href='javascript:void(0)' onclick='KlikDetail($barang->belipk);' style='text-decoration: none; color:black;'>
								$d->nobukti
							</a>";

				$row_tglinv = "<a href='javascript:void(0)' onclick='KlikDetail($barang->belipk);' style='text-decoration: none; color:black;'>" .
                				Carbon::parse($d->tglinv)->format('d-m-Y') .
              				"</a>";

				$row_noinv = "<a href='javascript:void(0)' onclick='KlikDetail($barang->belipk);' style='text-decoration: none; color:black;'>" .
                				trim($d->noinv) .
              				"</a>";

				$row_brgnm = "<a href='javascript:void(0)' onclick='KlikDetail($barang->belipk);' style='text-decoration: none; color:black;'>" .
								trim($barang->brgnm) .
							"</a>";

				$row_jmlbeli = "<a href='javascript:void(0)' onclick='KlikDetail($barang->belipk);' style='text-decoration: none; color:black;'>" .
								number_format($barang->jmlbeli, 0, '.', ',') .
							"</a>";
				
				$row_unit = "<a href='javascript:void(0)' onclick='KlikDetail($barang->belipk);' style='text-decoration: none; color:black;'>" .
								trim($barang->unit) .
							"</a>";
				
				$row_hrgbeli = "<a href='javascript:void(0)' onclick='KlikDetail($barang->belipk);' style='text-decoration: none; color:black;'>" .
								number_format($barang->hrgbeli, 2, '.', ',') .
							"</a>";

				$row_jmlhrg = "<a href='javascript:void(0)' onclick='KlikDetail($barang->belipk);' style='text-decoration: none; color:black;'>" .
								number_format(round($hitung_jmlharga, -2), 0, '.', ',') .
							"</a>";

				$row_kelnm = "<a href='javascript:void(0)' onclick='KlikDetail($barang->belipk);' style='text-decoration: none; color:black;'>" .
								$barang->kelnm .
							"</a>";

				$row[] = array(
					'belipk' => $d->belipk,
					'belidtpk' => $barang->belidtpk,
					'index' => $row_index,
					'nobukti' => $row_nobukti,
					'tglinv' => $row_tglinv,
					'noinv' => $row_noinv,
					'brgnm' => $row_brgnm,
					'jmlbeli' => $row_jmlbeli,
					'unit' => $row_unit,
					'hrgbeli' => $row_hrgbeli,
					// 'jmlbayar' => number_format($barang->jmlbayar, 0, '.', ','),
					// 'jmlhrg' => number_format($barang->jmlhrg, 0, '.', ','),
					// 'jmlhrg' => number_format(round($barang->jmlhrg, -2), 0, '.', ','),
					'jmlhrg' => $row_jmlhrg,
					'kelnm' => $row_kelnm,
				);
				$index++;
			}
		}

		$row2[] = [
			'index' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
			'nobukti' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
			'tglinv' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
			'noinv' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
			'brgnm' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
			'jmlbeli' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
			'unit' => '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;',
			'hrgbeli' => '<b> Grand Total </b>',
    		'jmlhrg' => number_format(round($total_jmlhrg, -2), 0, '.', ','),
		];

		$row3 = [
			// [
			// 	'hrgbeli' => '<b> Terbilang </b>',
			// 	'jmlhrg' => ucwords(FormatRupiahHelper::terbilang($total_jmlhrg)) . ' Rupiah',
			// ]
		];

		$footer = array_merge($row2, $row3);

        $result = array_merge($result, array('rows' => $row), array('footer' => $footer));
        return json_encode($result);
    }

	public function GetDetailBeli(Request $request, $belipk)
    {
		// $belipk = 164457;
		// $data_beli = DB::table('beli')->where('belipk', $belipk)->get();

		$data_beli = DB::table('belidt')
		->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
        ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
        ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
		 ->leftJoin('mif', 'mif.mifpk', '=', 'beli.mifpk')
        ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
        ->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
		->select(
			'belidt.belidtpk',
			'beli.belipk',
			'beli.nobukti',
	        DB::raw('TRIM(beli.noinv) as noinv'),
			'mif.mifnm as untuk',
		
        	DB::raw('DATE_FORMAT(beli.tglinv, "%d-%m-%Y") as tglinv'),
			// 'beli.tglinv',
			
			DB::raw('TRIM(sup.supnm) as supnm'),
			'ab.abnm',
			'kel.kelnm',
       		DB::raw("FORMAT(belidt.jmlbayar, 0) as total"),
			DB::raw('TRIM(belidt.brgnm) as brgnm'),
			DB::raw('TRIM(belidt.unit) as satuan'),
      		DB::raw("FORMAT(belidt.jmlbeli, 0) as qty"),
       		DB::raw("FORMAT(belidt.hrgbeli, 0) as hrg_satuan"),
       		DB::raw("FORMAT(belidt.jmlhrg, 0) as jml_hrg"),
     		DB::raw('DATE_FORMAT(belidt.tglbayar, "%d-%m-%Y") as tgl_byr'),
       		DB::raw("FORMAT(belidt.jmlbayar, 0) as jml_byr"),
			'belidt.cg',
		)->where('beli.belipk', $belipk)->get();

		return response()->json([
            'success' => true,
            'data' => $data_beli
        ]);
    }

	public function PdfDetailBkk(Request $request, $tglInv, $suppk)
    {

		$data_beli = DB::table('beli')
			->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
			->select('beli.belipk', 'beli.nobukti', 'beli.noinv','beli.tglinv', 'sup.suppk', 'sup.supnm')
            ->where('beli.tglinv', '=', $tglInv)
			->where('beli.suppk', '=', $suppk);
        $data = $data_beli->get();


        $formattedData = [];

		foreach ($data as $index => $d) {
			$barang_list = DB::table('belidt')
				->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
				->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
				->where('belidt.belipk', $d->belipk)
				->select('belidt.belidtpk','belidt.belipk', 'sup.suppk', 'sup.supnm','belidt.brgnm', 'belidt.unit', 'belidt.jmlbeli', 'belidt.hrgbeli', 'belidt.jmlhrg', 'belidt.jmlbayar')
				->get();
			

			foreach ($barang_list as $barang) {
				$hitung_jmlharga = ($barang->hrgbeli * $barang->jmlbeli);

				$formattedData[] = [
					'belipk' => $d->belipk,
					'nobukti' => $d->nobukti,
					'noinv' => trim($d->noinv),
					'tglinv' => $d->tglinv,
					'brgnm' => trim($barang->brgnm),
					'unit' => trim($barang->unit),
					'jmlbeli' => number_format($barang->jmlbeli, 2, '.', ','),
					'hrgbeli' => number_format($barang->hrgbeli, 0, '.', ','),
					// 'jmlbayar' => number_format($barang->jmlbayar, 0, '.', ','),
					// 'jmlhrg' => number_format($barang->jmlhrg, 2, '.', ','),
					'jmlhrg' => number_format($hitung_jmlharga, 2, '.', ','),
				];
			}
		}

		$supnm = DB::table('sup')->where('suppk', $suppk)->value('supnm');

		$pdf = Pdf::loadView('menu.laporan.bukti.2.detail-bkk-pdf2', compact('formattedData', 'tglInv', 'supnm'));
        return $pdf->stream('Laporan Bukti Kas Keluar.pdf');
    }

	function getListCekCash(Request $request)
	{
		$tahun = date("Y");
		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInputCekCash = $request->searchByInputCekCash;
		$SelectStart = $request->SelectStart;
		$SelectFinish = $request->SelectFinish;
		$filterByStatusPembayaran = $request->filterByStatusPembayaran;

		$data_cekcash = DB::table('belidt')
		->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
        ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
        ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
        ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
        ->leftJoin('cur', 'cur.curpk', '=', 'beli.curpk')
        ->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
		->select(
			'belidt.belidtpk',
            'user.userpk',
            'user.guserpk',
            'user.login',
            'sup.suppk',
            'sup.supnm',
            'cur.curpk',
            'cur.curid',
            'ab.abpk',
            'ab.abnm',
            'kel.kelpk',
            'kel.kelnm',
			'beli.belipk',
            'beli.nobukti',
            'beli.noinv',
            'beli.totbeli',
			'belidt.brgnm',
			'belidt.unit',
			'belidt.hrgbeli',
			'belidt.jmlbeli',
			'belidt.jmlhrg',
			'belidt.jmlbayar',
			'belidt.cg',
			'belidt.cg as cashgiro',
			'belidt.tglbayar',
			'belidt.nobg',
			'belidt.tglaju',
            'beli.tglinv',
		)->orderBy('beli.tglinv', 'ASC')->orderBy('belidt.belidtpk', 'ASC');

		if (!empty($filterByStatusPembayaran)) {
			if ($filterByStatusPembayaran == 1) {
				// Semua (tidak filter cg)
			} elseif ($filterByStatusPembayaran == 2) {
				$data_cekcash = $data_cekcash->where('beli.abpk', 1)->where('belidt.tglbayar', null);
			} elseif ($filterByStatusPembayaran == 3) {
				$data_cekcash = $data_cekcash->where('beli.abpk', 2)->where('belidt.tglbayar', null);
			} elseif ($filterByStatusPembayaran == 4) {
				$data_cekcash = $data_cekcash->where('belidt.cg', '=', 'C');
			} elseif ($filterByStatusPembayaran == 5) {
				$data_cekcash = $data_cekcash->where('belidt.cg', '=', 'G');
			} 
		}

		if (!empty($searchByInputCekCash)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(sup.supnm, ''),
                COALESCE(user.login, ''),
                COALESCE(beli.noinv, ''),
                COALESCE(belidt.brgnm, '')
            )");

			$data_cekcash = $data_cekcash->where($concatenatedValue, 'like', '%' . $searchByInputCekCash . '%');
		}
		
		// if (!empty($SelectStart)) {
		// 	$SelectStart = Carbon::createFromFormat('d/m/Y', $SelectStart)->format('Y-m-d');
		// 	$data_cekcash = $data_cekcash->whereDate('beli.tglinv', '>=', $SelectStart)->orderBy('beli.tglinv', 'ASC');
		// }

		// if (!empty($SelectFinish)) {
		// 	$SelectFinish = Carbon::createFromFormat('d/m/Y', $SelectFinish)->format('Y-m-d');
		// 	$data_cekcash = $data_cekcash->whereDate('beli.tglinv', '<=', $SelectFinish)->orderBy('beli.tglinv', 'ASC');
		// }

		if (!empty($SelectStart) && !empty($SelectFinish)) {
			$SelectStart = Carbon::createFromFormat('d/m/Y', $SelectStart)->format('Y-m-d');
			$SelectFinish = Carbon::createFromFormat('d/m/Y', $SelectFinish)->format('Y-m-d');

			$data_cekcash = $data_cekcash->where(function ($query) use ($SelectStart, $SelectFinish) {
				$query->whereBetween('beli.tglinv', [$SelectStart, $SelectFinish])
					->orWhereBetween('belidt.tglbayar', [$SelectStart, $SelectFinish]);
			});
		}

		$AllDataPo = $data_cekcash->get();
		$offset = ($page - 1) * $rows;
		$data_cekcash = $data_cekcash->skip($offset)->take($rows)->get();

		$result = array();
		$result['total'] = $AllDataPo->count();
		$result['page'] = $page;
		$result['rows'] = $rows;
		$result['offset'] = $offset;
		$row = array();
		$index = $offset + 1;

		foreach ($data_cekcash as $d) {
			
			if($d->cashgiro == "C"){
				$nama_cg = 'CASH';
			}elseif($d->cashgiro == "G"){
				$nama_cg = 'GIRO';
			}else{
				$nama_cg = '';
			}

	
			if($d->abpk == 1){
				$dt_index = "<p>" . $index . "</p>";
				$dt_supnm = "<p>" . $d->supnm . "</p>";
   				$dt_tanggal = "<p>" .  Carbon::parse($d->tglinv)->format('d-m-Y') . "</p>";
                $dt_noinv = "<p>" . $d->noinv . "</p>";
				$dt_brgnm = "<p>" . $d->brgnm . "</p>";
				$dt_unit = "<p>" . $d->unit . "</p>";
				$dt_hrgbeli = "<p>" . number_format($d->hrgbeli, 0, '.', ',') . "</p>";
				$dt_jmlbeli = "<p>" . $d->jmlbeli . "</p>";
				$dt_jmlhrg = "<p>" . number_format($d->jmlhrg, 2, '.', ',') . "</p>";
				$dt_tglbayar = "<p>" . Carbon::parse($d->tglbayar)->format('d-m-Y') . "</p>";
                $dt_jmlbayar = "<p>" . number_format($d->jmlbayar, 2, '.', ',') . "</p>";
				$dt_cg = "<p>" . $nama_cg . "</p>";
                $dt_login = "<p>" . strtoupper($d->login) . "</p>";
				$dt_nobg = "<p>" . $d->nobg . "</p>";
				$dt_tglaju = "<p>" . $d->tglaju . "</p>";
			}else{
				$dt_index = "<p style='color:red'>" . $index . "</p>";
                $dt_noinv = "<p style='color:red'>" .$d->noinv . "</p>";
				$dt_brgnm = "<p style='color:red'>" .$d->brgnm . "</p>";
				$dt_supnm = "<p style='color:red'>" .$d->supnm . "</p>";
				$dt_tanggal = "<p style='color:red'>" . Carbon::parse($d->tglinv)->format('d-m-Y') . "</p>";
				$dt_unit = "<p style='color:red'>" .$d->unit . "</p>";
				$dt_hrgbeli = "<p style='color:red'>" . number_format($d->hrgbeli, 0, '.', ',') . "</p>";
				$dt_jmlbeli = "<p style='color:red'>" .$d->jmlbeli . "</p>";
				$dt_jmlhrg = "<p style='color:red'>" . number_format($d->jmlhrg, 2, '.', ',') . "</p>";
				$dt_tglbayar = "<p style='color:red'>" . Carbon::parse($d->tglbayar)->format('d-m-Y') . "</p>";
				$dt_jmlbayar = "<p style='color:red'>" . number_format($d->jmlbayar, 2, '.', ',') . "</p>";
				$dt_cg = "<p style='color:red'>" .$nama_cg . "</p>";
                $dt_login = "<p style='color:red'>" . strtoupper($d->login) . "</p>";
				$dt_nobg = "<p style='color:red'>" .$d->nobg . "</p>";
				$dt_tglaju = "<p style='color:red'>" .$d->tglaju  . "</p>";
			}

			$row[] = array(
				'belidtpk' => $d->belidtpk,
                'belipk' => $d->belipk,
                'belidtpk' => $d->belidtpk,
				'index' => $dt_index,
				'noinv' => $dt_noinv,
				'supnm' => $dt_supnm,
				'tanggal' => $dt_tanggal,
				'brgnm' => $dt_brgnm,
				'unit' => $dt_unit,
				'hrgbeli' => $dt_hrgbeli,
				'jmlbeli' => $dt_jmlbeli,
				'jmlhrg' => $dt_jmlhrg,
				'tglbayar' => $dt_tglbayar,
				'jmlbayar' => $dt_jmlbayar,
				'cg' => $dt_cg,
                'user' => $dt_login,
                'nobg' => $dt_nobg,
                'tglaju' => $dt_tglaju,
			);
			$index++;
		}
		$result = array_merge($result, array('rows' => $row));
		return json_encode($result);
	}

	public function PdfCekCashByModal(Request $request)
    {
		ini_set('memory_limit', '512M'); // atau 1024M jika masih error
		set_time_limit(300);

        $filterByStatusPembayaran = $request->input('filterByStatusPembayaran');
        $SelectStart = $request->input('SelectStart');
        $SelectFinish = $request->input('SelectFinish');

		$data_cekcash = DB::table('belidt')
		->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
        ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
        ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
        ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
        ->leftJoin('cur', 'cur.curpk', '=', 'beli.curpk')
        ->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
		->select(
			'belidt.belidtpk',
            'user.userpk',
            'user.guserpk',
            'user.login',
            'sup.suppk',
            'sup.supnm',
            'cur.curpk',
            'cur.curid',
            'ab.abpk',
            'ab.abnm',
            'kel.kelpk',
            'kel.kelnm',
			'beli.belipk',
            'beli.nobukti',
            'beli.noinv',
            'beli.totbeli',
			'belidt.brgnm',
			'belidt.unit',
			'belidt.hrgbeli',
			'belidt.jmlbeli',
			'belidt.jmlhrg',
			'belidt.jmlbayar',
			'belidt.cg',
			'belidt.cg as cashgiro',
			'belidt.nobg',
			'belidt.tglaju',
            'beli.tglinv',
            // DB::raw('DATE_FORMAT(beli.tglinv, "%d %b %Y") as tanggal'),
            DB::raw('DATE_FORMAT(beli.tglinv, "%d-%m-%Y") as tanggal'),
            DB::raw('DATE_FORMAT(belidt.tglbayar, "%d-%m-%Y") as tglbayar'),
		)->orderBy('beli.tglinv', 'ASC')->orderBy('belidt.belidtpk', 'ASC');

		if (!empty($searchByInput)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(sup.supnm, ''),
                COALESCE(user.login, ''),
                COALESCE(beli.noinv, ''),
                COALESCE(belidt.brgnm, '')
            )");

			$data_cekcash = $data_cekcash->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}

		$tanggalLabel = '';
        if (!empty($SelectStart) && !empty($SelectFinish)) {
            // $tglStart = Carbon::parse($SelectStart)->format('m-d-Y');
            // $tglFinish = Carbon::parse($SelectFinish)->format('m-d-Y');
			$tglStart = Carbon::createFromFormat('d/m/Y', $SelectStart)->format('d-m-Y');
			$tglFinish = Carbon::createFromFormat('d/m/Y', $SelectFinish)->format('d-m-Y');

            $tanggalLabel = $SelectStart === $SelectFinish
                ? "TANGGAL : $tglStart"
                : "TANGGAL : $tglStart S/D $tglFinish";
        }
		
		if (!empty($SelectStart)) {
			$SelectStart = Carbon::createFromFormat('d/m/Y', $SelectStart)->format('Y-m-d');
			$data_cekcash = $data_cekcash->whereDate('beli.tglinv', '>=', $SelectStart)->orderBy('beli.tglinv', 'ASC');
		}

		if (!empty($SelectFinish)) {
			$SelectFinish = Carbon::createFromFormat('d/m/Y', $SelectFinish)->format('Y-m-d');
			$data_cekcash = $data_cekcash->whereDate('beli.tglinv', '<=', $SelectFinish)->orderBy('beli.tglinv', 'ASC');
		}

		if (!empty($filterByStatusPembayaran)) {
			if ($filterByStatusPembayaran == 1) {
			} elseif ($filterByStatusPembayaran == 2) {
				$data_cekcash = $data_cekcash->where('beli.abpk', 1)->where('belidt.tglbayar', null);
			} elseif ($filterByStatusPembayaran == 3) {
				$data_cekcash = $data_cekcash->where('beli.abpk', 2)->where('belidt.tglbayar', null);
			} elseif ($filterByStatusPembayaran == 4) {
				$data_cekcash = $data_cekcash->where('belidt.cg', '=', 'C');
			} elseif ($filterByStatusPembayaran == 5) {
				$data_cekcash = $data_cekcash->where('belidt.cg', '=', 'G');
			} 
		}

        // $data_Cash = $data_cekcash->where('beli.abpk', 1)->get();
		$data_cekcash_cash = clone $data_cekcash;
		$data_cekcash_tempo = clone $data_cekcash;


		$data_Cash = $data_cekcash_cash->where('beli.abpk', 1)->get();
		$count_data_Cash = $data_Cash->count();

		$data_Tempo = $data_cekcash_tempo->where('beli.abpk', 2)->get();
		$count_data_Tempo = $data_Tempo->count();

        $formattedData_Cash = [];

        foreach ($data_Cash as $index => $d) {
	
            $formattedData_Cash[] = [
				'belidtpk' => $d->belidtpk,
                'belipk' => $d->belipk,
                'belidtpk' => $d->belidtpk,
				'index' => $index,
				'noinv' => $d->noinv,
				'supnm' => $d->supnm,
				'tanggal' => $d->tanggal,
				'brgnm' => $d->brgnm,
				'unit' => $d->unit,
				'hrgbeli' => number_format($d->hrgbeli, 0, '.', ','),
				'jmlbeli' => number_format($d->jmlbeli, 0, '.', ','),
				'jmlhrg' => number_format($d->jmlhrg, 0, '.', ','),
				'tglbayar' => $d->tglbayar,
				'jmlbayar' => number_format($d->jmlbayar, 0, '.', ','),
            ];
        }
		

        $formattedData_Tempo = [];

        foreach ($data_Tempo as $index => $d) {
	
            $formattedData_Tempo[] = [
				'belidtpk' => $d->belidtpk,
                'belipk' => $d->belipk,
                'belidtpk' => $d->belidtpk,
				'index' => $index,
				'noinv' => $d->noinv,
				'supnm' => $d->supnm,
				'tanggal' => $d->tanggal,
				'brgnm' => $d->brgnm,
				'unit' => $d->unit,
				'hrgbeli' => number_format($d->hrgbeli, 0, '.', ','),
				'jmlbeli' => number_format($d->jmlbeli, 0, '.', ','),
				'jmlhrg' => number_format($d->jmlhrg, 0, '.', ','),
				'tglbayar' => $d->tglbayar,
				'jmlbayar' => number_format($d->jmlbayar, 0, '.', ','),
            ];
        }

		$subTotalCash = $data_Cash->sum('jmlhrg');
		$grandTotalCash = $data_Cash->sum('jmlbayar');

		$subTotalTempo = $data_Tempo->sum('jmlhrg');
		$grandTotalTempo = $data_Tempo->sum('jmlbayar');

		$SubTotalCashTempoJmlHrg = $subTotalCash + $subTotalTempo;
		$TotalGrandCashTempo = $grandTotalTempo + $grandTotalCash;


        $pdf = Pdf::loadView('menu.laporan.bukti.2.modal.pdf-modal-cek-cash', 
		compact('subTotalCash', 'grandTotalCash', 'subTotalTempo', 'grandTotalTempo',  'SubTotalCashTempoJmlHrg', 'TotalGrandCashTempo', 
		'formattedData_Cash', 'formattedData_Tempo', 'tanggalLabel', 'count_data_Cash', 'count_data_Tempo'))->setPaper('a4', 'landscape');
        return $pdf->stream('Laporan Pembayaran Cash-Tempo.pdf');
    }

	public function HitungTotal(Request $request)
	{

		$SelectDate = $request->SelectDate;
        $searchByInput = $request->searchByInput;

		$data_pr = DB::table('belidt')
		->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
        ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
        ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
        ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
        ->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
		->select(
			'belidt.belidtpk',
            'user.userpk',
            'sup.suppk',
			'sup.supnm',
            'ab.abpk',
            'kel.kelpk',
			'beli.belipk',
			'belidt.hrgbeli',
			'belidt.brgnm',
			'belidt.jmlbayar',
			'belidt.tglbayar',
            'beli.tglinv',
			DB::raw('SUM(belidt.jmlbayar) as total_hrgbeli')
		)
		->orderBy('belidt.belidtpk', 'DESC')
		->where('ab.abpk', 1)
		// ->where('belidt.jmlbayar', '>', 0)
		->where('beli.kelpk', '<>', 63)
		->groupBy('sup.suppk');
		
		if (!empty($SelectDate)) {
			$SelectDate = Carbon::createFromFormat('d/m/Y', $SelectDate)->format('Y-m-d');
			$data_pr = $data_pr->whereDate('beli.tglinv', $SelectDate);
		} 

		if(!empty($searchByInput)) {
            $concatenatedValue = DB::raw("CONCAT(
                IFNULL(sup.supnm, ''),
                IFNULL(belidt.brgnm, '')
            )");
            $data_pr = $data_pr->where(DB::raw($concatenatedValue), 'like', '%' . $searchByInput . '%');
        }

		$data_pr = $data_pr->get();

		$result = array();
		$row = array();

		foreach ($data_pr as $d) {

			$barang_list = collect(DB::table('belidt')
			->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
			->where('beli.suppk', $d->suppk)
			->whereDate('beli.tglinv', $SelectDate)
			->select('belidt.brgnm')
			->get());

			$dt_hrgbeli = number_format($d->total_hrgbeli, 0, '.', ',') ;
			
			// $pembulatan = $d->total_hrgbeli; // tanpa pembulatan
			$pembulatan = round($d->total_hrgbeli, -2);
			
			$row[] = array(
				'hrgbeli' => $dt_hrgbeli,
				'pembulatan' => $pembulatan,
			);

		}

		$total_all = array_sum(array_column($row, 'pembulatan'));
		return response()->json([
			// 'total_per_supplier' => $per_supplier,
			'total_all' => $total_all,
		]);
	}

	public function getSuppInDetailBkk(Request $request, $tglInv)
	{

		$data_supp = DB::table('belidt')
			->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
			->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
			->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
			->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
			->select(
				'sup.suppk',
				'sup.supnm',
				DB::raw('TRIM(sup.supnm) as supnm')
			)
			->where('beli.tglinv', $tglInv)
			->where('ab.abpk', 1)
			->where('beli.kelpk', '<>', 63)
			->groupBy('sup.suppk')
			->orderBy('sup.supnm', 'asc')->get();

		return json_encode($data_supp);
	}
}
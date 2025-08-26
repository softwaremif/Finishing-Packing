<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use PDF;

class PembayaranCGController extends Controller
{
    function PagePembayaranCashGiro(){
        return view('menu.laporan.pembayaran.pembayaran-cash-giro');
    }

    function getListPembayaranCashGiro(Request $request)
	{

		$tahun = date("Y");
		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInput = $request->searchByInput;
		$SelectStart = $request->SelectStart;
		$SelectFinish = $request->SelectFinish;
		$filterByStatusPembayaran = $request->filterByStatusPembayaran;

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
			'belidt.jmlhrg',
			'belidt.jmlbayar',
			'belidt.cg',
            'belidt.nobg',
			'belidt.tglbayar',
            'beli.tglinv',
            DB::raw('DATE_FORMAT(beli.tglinv, "%d %b %Y") as tanggal'),
            DB::raw('DATE_FORMAT(belidt.tglbayar, "%d %b %Y") as tglbayar'),
            DB::raw('DATE_FORMAT(belidt.tglaju, "%d %b %Y") as tglaju'),
		)
        // ->orderBy('belidt.belidtpk', 'DESC');
        ->orderBy('beli.tglinv', 'ASC')->orderBy('belidt.belidtpk', 'ASC');

        if (!empty($filterByStatusPembayaran)) {
			if ($filterByStatusPembayaran == 1) {
				// Semua (tidak filter cg)
			} elseif ($filterByStatusPembayaran == 2) {
				$data_pr = $data_pr->where('beli.abpk', 1)->where('belidt.tglbayar', null);
			} elseif ($filterByStatusPembayaran == 3) {
				$data_pr = $data_pr->where('beli.abpk', 2)->where('belidt.tglbayar', null);
			} elseif ($filterByStatusPembayaran == 4) {
				$data_pr = $data_pr->where('belidt.cg', '=', 'C');
			} elseif ($filterByStatusPembayaran == 5) {
				$data_pr = $data_pr->where('belidt.cg', '=', 'G');
			} 
		}

		if (!empty($searchByInput)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(sup.supnm, ''),
                COALESCE(user.login, ''),
                COALESCE(beli.nobukti, ''),
                COALESCE(beli.noinv, ''),
                COALESCE(belidt.brgnm, ''),
                COALESCE(belidt.unit, '')
            )");

			$data_pr = $data_pr->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}

		// if (!empty($SelectStart)) {
        //     $data_pr = $data_pr->whereDate('beli.tglinv', '>=', $SelectStart);
        // }else{
		// 	$data_pr = $data_pr->whereDate('beli.tglinv', Carbon::today());
		// }

        // if (!empty($SelectFinish)) {
        //     $data_pr = $data_pr->whereDate('beli.tglinv', '<=', $SelectFinish);
        // }else{
		// 	$data_pr = $data_pr->whereDate('beli.tglinv', Carbon::today());
		// }


		
		// if (!empty($SelectStart)) {
		// 	$SelectStart = Carbon::createFromFormat('d/m/Y', $SelectStart)->format('Y-m-d');
		// 	$data_pr = $data_pr->whereDate('beli.tglinv', '>=', $SelectStart);
		// } else {
		// 	$data_pr = $data_pr->whereDate('beli.tglinv', Carbon::today());
		// }

		// if (!empty($SelectFinish)) {
		// 	$SelectFinish = Carbon::createFromFormat('d/m/Y', $SelectFinish)->format('Y-m-d');
		// 	$data_pr = $data_pr->whereDate('beli.tglinv', '<=', $SelectFinish);
		// } else {
		// 	$data_pr = $data_pr->whereDate('beli.tglinv', Carbon::today());
		// }

        if (!empty($SelectStart) && !empty($SelectFinish)) {
            $SelectStart = Carbon::createFromFormat('d/m/Y', $SelectStart)->format('Y-m-d');
            $SelectFinish = Carbon::createFromFormat('d/m/Y', $SelectFinish)->format('Y-m-d');

            $data_pr = $data_pr->where(function ($query) use ($SelectStart, $SelectFinish) {
                $query->whereBetween('beli.tglinv', [$SelectStart, $SelectFinish])
                    ->orWhereBetween('belidt.tglbayar', [$SelectStart, $SelectFinish]);
            });
        } else {
            $today = Carbon::now()->format('Y-m-d');

            $data_pr = $data_pr->where(function ($query) use ($today) {
                $query->whereDate('beli.tglinv', $today)
                    ->orWhereDate('belidt.tglbayar', $today);
            });
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

            if($d->cg == "C"){
                $hasil_cg = 'CASH';
            }elseif($d->cg == "G"){
                $hasil_cg = 'GIRO';
            }else{
                $hasil_cg = '';
            }
		
			if($d->cg == "C"){
				$dt_index = "<p>" . $index . "</p>";
				$dt_supnm = "<p>" . $d->supnm . "</p>";
   				$dt_tanggal = "<p>" . $d->tanggal . "</p>";
                $dt_noinv = "<p>" . $d->noinv . "</p>";
				$dt_brgnm = "<p>" . $d->brgnm . "</p>";
				$dt_unit = "<p>" . $d->unit . "</p>";
				$dt_hrgbeli = "<p>" . number_format($d->hrgbeli, 2, '.', ',') . "</p>";
				$dt_jmlbeli = "<p>" . $d->jmlbeli . "</p>";
				$dt_jmlhrg = "<p>" . number_format($d->jmlhrg, 2, '.', ',') . "</p>";
				$dt_tglbayar = "<p>" . $d->tglbayar . "</p>";
				$dt_jmlbayar = "<p>" . number_format($d->jmlbayar, 2, '.', ',') . "</p>";
                $dt_cg = "<p>" . $hasil_cg . "</p>";
                $dt_login = "<p>" . strtoupper($d->login) . "</p>";
                $dt_nobg = "<p>" . $d->nobg . "</p>";
                $dt_tglaju = "<p>" . $d->tglaju . "</p>";
			}else{
				$dt_index = "<p style='color:red'>" . $index . "</p>";
                $dt_noinv = "<p style='color:red'>" . $d->noinv . "</p>";
				$dt_brgnm = "<p style='color:red'>" . $d->brgnm . "</p>";
				$dt_supnm = "<p style='color:red'>" . $d->supnm . "</p>";
				$dt_tanggal = "<p style='color:red'>" . $d->tanggal . "</p>";
				$dt_unit = "<p style='color:red'>" . $d->unit . "</p>";
				$dt_hrgbeli = "<p style='color:red'>" . number_format($d->hrgbeli, 2, '.', ',') . "</p>";
				$dt_jmlbeli = "<p style='color:red'>" . $d->jmlbeli . "</p>";
				$dt_jmlhrg = "<p style='color:red'>" . number_format($d->jmlhrg, 2, '.', ',') . "</p>";
				$dt_tglbayar = "<p style='color:red'>" .$d->tglbayar . "</p>";
				$dt_jmlbayar = "<p style='color:red'>" . number_format($d->jmlbayar, 2, '.', ',') . "</p>";
				$dt_cg = "<p style='color:red'>" . $hasil_cg . "</p>";
                $dt_login = "<p style='color:red'>" . strtoupper($d->login) . "</p>";
				$dt_nobg = "<p style='color:red'>" . $d->nobg . "</p>";
				$dt_tglaju = "<p style='color:red'>" . $d->tglaju . "</p>";
			}

			$row[] = array(
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

	// public function pdfPembayaranCGAwal(Request $request)
    // {
    //     ini_set('memory_limit', '512M'); // atau 1024M jika masih error
	// 	set_time_limit(300);

    //     $validator = Validator::make($request->all(), [
    //         'SelectStart' => 'nullable|date',
    //         'SelectFinish' => 'nullable|date',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()->first()], 400);
    //     }

    //     $SelectStart = $request->input('SelectStart');
    //     $SelectFinish = $request->input('SelectFinish');

    //     // $query = DB::table('invoice')
    //     //     ->leftJoin('spb', 'spb.spbpk', '=', 'invoice.spbpk')
    //     //     ->leftJoin('gud', 'gud.gudpk', '=', 'spb.gudpk')
    //     //     ->leftJoin('invoicedt', 'invoicedt.invpk', '=', 'invoice.invpk')
    //     //     ->select(
    //     //         'invoice.invpk',
    //     //         'invoice.spbpk',
    //     //         'spb.nobukti',
    //     //         'invoice.noinv',
    //     //         'invoice.nokk',
    //     //         'invoice.nofp',
    //     //         'invoicedt.qty',
    //     //         'invoicedt.pricedt',
    //     //         'spb.spbpk',
    //     //         'gud.gudpk',
    //     //         'gud.gudnm',
    //     //         DB::raw('DATE_FORMAT(invoice.tglinv, "%d %b %Y") as tanggal'),
    //     //     );


	// 	$query = DB::table('belidt')
	// 	->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
    //     ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
    //     ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
    //     ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
    //     ->leftJoin('cur', 'cur.curpk', '=', 'beli.curpk')
    //     ->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
	// 	->select(
	// 		'belidt.belidtpk',
    //         'user.userpk',
    //         'user.guserpk',
    //         'user.login',
    //         'sup.suppk',
    //         'sup.supnm',
    //         'cur.curpk',
    //         'cur.curid',
    //         'ab.abpk',
    //         'ab.abnm',
    //         'kel.kelpk',
    //         'kel.kelnm',
	// 		'beli.belipk',
    //         'beli.nobukti',
    //         'beli.noinv',
    //         'beli.totbeli',
	// 		'belidt.brgnm',
	// 		'belidt.unit',
	// 		'belidt.hrgbeli',
	// 		'belidt.jmlbeli',
    //         'belidt.jmlhrg',
	// 		'belidt.jmlbayar',
	// 		'belidt.tglbayar',
	// 		'belidt.cg',
    //         DB::raw('DATE_FORMAT(beli.tglinv, "%d %b %Y") as tanggal'),
	// 	)
    //     ->orderBy('beli.tglinv', 'ASC')->orderBy('belidt.belidtpk', 'DESC');
    //     // ->orderBy('beli.suppk', 'DESC')
    //     // ->orderBy('belidt.belidtpk', 'DESC');

    //     if (!empty($SelectStart) && !empty($SelectFinish)) {
    //         $formattedDateStart = Carbon::parse($SelectStart)->format('Y-m-d');
    //         $formattedDateFinish = Carbon::parse($SelectFinish)->format('Y-m-d');
    //         $query->whereBetween('beli.tglinv', [$formattedDateStart, $formattedDateFinish]);
    //     }

    //     $data = $query->get();
    //     $formattedData = [];

    //     foreach ($data as $index => $d) {
    //         if($d->cg == "C"){
    //             $d_cg = "CASH";
    //         }elseif($d->cg == "G"){
    //             $d_cg = "GIRO";
    //         }else{
    //             $d_cg = "";
    //         }
    //         $formattedData[] = [
    //             'noinv' => $d->noinv,
    //             'tanggal' => $d->tanggal,
    //             'nobukti' => $d->nobukti,
    //             'supnm' => $d->supnm ?? '-',
    //             'brgnm' => $d->brgnm ?? '-',
    //             'unit' => $d->unit?? '-',
    //             'cg' => $d_cg,
    //             'hrgbeli' => number_format($d->hrgbeli, 0, '.', ',') ?? '-',
    //             'jmlbeli' => number_format($d->jmlbeli, 2, '.', ',') ?? '-',
    //             'jmlhrg' => number_format($d->jmlhrg, 0, '.', ',') ?? '-',
    //             'tglbayar' => $d->tglbayar ?? '-',
    //             'jmlbayar' => $d->jmlbayar ?? '-',
    //         ];
    //     }

    //     $current_time = Carbon::now()->format('d-M-Y H-i-s'); // Format dengan spasi
    //     $pdf = Pdf::loadView('menu.laporan.pembayaran.pdf-pembayaran-cg', compact('formattedData'))->setPaper('a4', 'landscape');
    //     return $pdf->stream('List-Payment.pdf');
    // }

    public function pdfPembayaranCG(Request $request)
    {
		ini_set('memory_limit', '512M'); // atau 1024M jika masih error
		set_time_limit(300);

        $searchByInput = $request->input('searchByInput');
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
                COALESCE(beli.noinv, ''),
                COALESCE(belidt.brgnm, '')
            )");

			$data_cekcash = $data_cekcash->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}

		$tanggalLabel = '';
        if (!empty($SelectStart) && !empty($SelectFinish)) {
			$tglStart = Carbon::parse($SelectStart)->format('d-m-Y');
			$tglFinish = Carbon::parse($SelectFinish)->format('d-m-Y');

            $tanggalLabel = $SelectStart === $SelectFinish
                ? "TANGGAL : $tglStart"
                : "TANGGAL : $tglStart S/D $tglFinish";
        }
		
		if (!empty($SelectStart)) {
			$SelectStart = Carbon::parse($SelectStart)->format('Y-m-d');
			$data_cekcash = $data_cekcash->whereDate('beli.tglinv', '>=', $SelectStart)->orderBy('beli.tglinv', 'ASC');
		}

		if (!empty($SelectFinish)) {
			$SelectFinish = Carbon::parse($SelectFinish)->format('Y-m-d');
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


        $pdf = Pdf::loadView('menu.laporan.pembayaran.pdf-pembayaran-cg2', 
		compact('subTotalCash', 'grandTotalCash', 'subTotalTempo', 'grandTotalTempo',  'SubTotalCashTempoJmlHrg', 'TotalGrandCashTempo', 
		'formattedData_Cash', 'formattedData_Tempo', 'tanggalLabel', 'count_data_Cash', 'count_data_Tempo'))->setPaper('a4', 'landscape');
        return $pdf->stream('Laporan Pembayaran Cash-Giro.pdf');
    }
}

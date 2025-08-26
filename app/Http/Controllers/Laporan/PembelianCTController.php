<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use PDF;
use Illuminate\Support\Facades\Session;

class PembelianCTController extends Controller
{
    function PagePembelianCashTempo(){
        return view('menu.laporan.pembelian.cash-tempo');
    }

    function getListPembelianCashTempo(Request $request)
	{

		$tahun = date("Y");
		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInput = $request->searchByInput;
		$SelectStart = $request->SelectStart;
		$SelectFinish = $request->SelectFinish;
        $userpk = Session::get('userpk');

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
			'belidt.jmlhrg',
			'belidt.cg',
			'belidt.tglbayar',
            'belidt.nobg',
            // 'belidt.tglaju',
            'beli.tglinv',
            DB::raw('DATE_FORMAT(beli.tglinv, "%d %b %Y") as tanggal'),
            DB::raw('DATE_FORMAT(belidt.tglaju, "%d %b %Y") as tglaju'),
		)->orderBy('beli.tglinv', 'ASC')->orderBy('belidt.belidtpk', 'ASC')->where('user.userpk', $userpk);

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
		
		if (!empty($SelectStart)) {
			$SelectStart = Carbon::createFromFormat('d/m/Y', $SelectStart)->format('Y-m-d');
			$data_pr = $data_pr->whereDate('beli.tglinv', '>=', $SelectStart);
		} else {
			$data_pr = $data_pr->whereDate('beli.tglinv', Carbon::today());
		}

		if (!empty($SelectFinish)) {
			$SelectFinish = Carbon::createFromFormat('d/m/Y', $SelectFinish)->format('Y-m-d');
			$data_pr = $data_pr->whereDate('beli.tglinv', '<=', $SelectFinish);
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
		
			if($d->cg == "C"){
				$dt_index = "<p>" . $index . "</p>";
				$dt_supnm = "<p>" . $d->supnm . "</p>";
   				$dt_tanggal = "<p>" . $d->tanggal . "</p>";
                $dt_noinv = "<p>" . $d->noinv . "</p>";
				$dt_brgnm = "<p>" . $d->brgnm . "</p>";
				$dt_unit = "<p>" . $d->unit . "</p>";
				$dt_hrgbeli = "<p>" . number_format(floor($d->hrgbeli), 0, '.', ',') . "</p>";
				$dt_jmlbeli = "<p>" . number_format($d->jmlbeli, 2, '.', ',') . "</p>";
                // $dt_jmlbayar = "<p>" . number_format($d->jmlbayar, 2, '.', ',') . "</p>";
                $dt_jmlhrg = "<p>" . number_format($d->jmlhrg, 2, '.', ',') . "</p>";
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
				$dt_hrgbeli = "<p style='color:red'>" . number_format(floor($d->hrgbeli), 0, '.', ',') . "</p>";
				$dt_jmlbeli = "<p style='color:red'>" . number_format($d->jmlbeli, 2, '.', ',') . "</p>";
				// $dt_jmlbayar = "<p style='color:red'>" . number_format($d->jmlbayar, 2, '.', ',') . "</p>";
				$dt_jmlhrg = "<p style='color:red'>" . number_format($d->jmlhrg, 2, '.', ',') . "</p>";
                $dt_login = "<p style='color:red'>" . strtoupper($d->login) . "</p>";
				$dt_nobg = "<p style='color:red'>" .$d->nobg . "</p>";
				$dt_tglaju = "<p style='color:red'>" .$d->tglaju . "</p>";
			}

			$row[] = array(
				'belidtpk' => $d->belidtpk,
                'belipk' => $d->belipk,
				'index' => $dt_index,
				'noinv' => $dt_noinv,
				'supnm' => $dt_supnm,
				'tanggal' => $dt_tanggal,
				'brgnm' => $dt_brgnm,
				'unit' => $dt_unit,
				'hrgbeli' => $dt_hrgbeli,
				'jmlbeli' => $dt_jmlbeli,
				// 'jmlbayar' => $dt_jmlbayar,
				'jmlhrg' => $dt_jmlhrg,
                'user' => $dt_login,
                'nobg' => $dt_nobg,
                'tglaju' => $dt_tglaju,
			);
			$index++;
		}
		$result = array_merge($result, array('rows' => $row));
		return json_encode($result);
	}

    public function pdfPembelianCTAwal(Request $request)
    {
        $userpk = Session::get('userpk');
        $validator = Validator::make($request->all(), [
            'SelectStart' => 'nullable|date',
            'SelectFinish' => 'nullable|date',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 400);
        }

        $SelectStart = $request->input('SelectStart');
        $SelectFinish = $request->input('SelectFinish');

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
			'belidt.tglbayar',
			'belidt.cg',
            DB::raw('DATE_FORMAT(beli.tglinv, "%d/%m/%Y") as tanggal'),
        // )->orderBy('beli.tglinv', 'ASC')->orderBy('belidt.belipk', 'ASC')->orderBy('belidt.belidtpk', 'ASC');
  		)->orderBy('beli.tglinv', 'ASC')->orderBy('belidt.belidtpk', 'ASC')->where('user.userpk', $userpk);
        // ->where('user.userpk', 4);
        // ->orderBy('beli.tglinv', 'DESC');
        // ->orderBy('beli.suppk', 'DESC')
        // ->orderBy('belidt.belidtpk', 'DESC');

        $tanggalLabel = '';
        if (!empty($SelectStart) && !empty($SelectFinish)) {
            $tglStart = Carbon::parse($SelectStart)->format('d-m-Y');
            $tglFinish = Carbon::parse($SelectFinish)->format('d-m-Y');

            $tanggalLabel = $SelectStart === $SelectFinish
                ? "TANGGAL $tglStart"
                : "TANGGAL $tglStart S/D $tglFinish";
        }

        if (!empty($SelectStart) && !empty($SelectFinish)) {
            $formattedDateStart = Carbon::parse($SelectStart)->format('Y-m-d');
            $formattedDateFinish = Carbon::parse($SelectFinish)->format('Y-m-d');
            $query->whereBetween('beli.tglinv', [$formattedDateStart, $formattedDateFinish]);
        }

        $data = $query->get();
        $formattedData = [];

        foreach ($data as $index => $d) {
            if($d->cg == "C"){
                $d_cg = "CASH";
            }elseif($d->cg == "G"){
                $d_cg = "GIRO";
            }else{
                $d_cg = "";
            }
            $formattedData[] = [
                'noinv' => $d->noinv,
                'tanggal' => $d->tanggal,
                'nobukti' => $d->nobukti,
                'supnm' => $d->supnm ?? '-',
                'brgnm' => $d->brgnm ?? '-',
                'unit' => $d->unit?? '-',
                'cg' => $d_cg,
                'hrgbeli' => number_format(floor($d->hrgbeli), 0, '.', ',') ?? '-',
                'jmlbeli' => number_format($d->jmlbeli, 2, '.', ',') ?? '-',
                'jmlhrg' => number_format(floor($d->jmlhrg), 0, '.', ',') ?? '-',
                'tglbayar' => $d->tglbayar ?? '-',
                'jmlbayar' => $d->jmlbayar ?? '-',
            ];
        }

        $pdf = Pdf::loadView('menu.laporan.pembelian.pdf-pembelian-ct', compact('formattedData', 'tanggalLabel'))->setPaper('a4', 'potrait');
        return $pdf->stream('List-Payment.pdf');
    }

    // public function pdfPembelianCT(Request $request)
    // {
    //     $userpk = Session::get('userpk');
    //     $validator = Validator::make($request->all(), [
    //         'SelectStart' => 'nullable|date',
    //         'SelectFinish' => 'nullable|date',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['error' => $validator->errors()->first()], 400);
    //     }

    //     $SelectStart = $request->input('SelectStart');
    //     $SelectFinish = $request->input('SelectFinish');

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
    //         DB::raw('DATE_FORMAT(beli.tglinv, "%d/%m/%Y") as tanggal'),
    //     // )->orderBy('beli.tglinv', 'ASC')->orderBy('belidt.belipk', 'ASC')->orderBy('belidt.belidtpk', 'ASC');
  	// 	)->orderBy('beli.tglinv', 'ASC')->orderBy('belidt.belidtpk', 'ASC')->where('user.userpk', $userpk);
    //     // ->where('user.userpk', 4);
    //     // ->orderBy('beli.tglinv', 'DESC');
    //     // ->orderBy('beli.suppk', 'DESC')
    //     // ->orderBy('belidt.belidtpk', 'DESC');

    //     $tanggalLabel = '';
    //     if (!empty($SelectStart) && !empty($SelectFinish)) {
    //         $tglStart = Carbon::parse($SelectStart)->format('d-m-Y');
    //         $tglFinish = Carbon::parse($SelectFinish)->format('d-m-Y');

    //         $tanggalLabel = $SelectStart === $SelectFinish
    //             ? "TANGGAL $tglStart"
    //             : "TANGGAL $tglStart S/D $tglFinish";
    //     }

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
    //             'hrgbeli' => number_format(floor($d->hrgbeli), 0, '.', ',') ?? '-',
    //             'jmlbeli' => number_format($d->jmlbeli, 2, '.', ',') ?? '-',
    //             'jmlhrg' => number_format(floor($d->jmlhrg), 0, '.', ',') ?? '-',
    //             'tglbayar' => $d->tglbayar ?? '-',
    //             'jmlbayar' => $d->jmlbayar ?? '-',
    //         ];
    //     }

    //     $pdf = Pdf::loadView('menu.laporan.pembelian.pdf-pembelian-ct', compact('formattedData', 'tanggalLabel'))->setPaper('a4', 'potrait');
    //     return $pdf->stream('List-Payment.pdf');
    // }

    public function pdfPembelianCT(Request $request)
    {
		ini_set('memory_limit', '512M'); // atau 1024M jika masih error
		set_time_limit(300);

        $userpk = Session::get('userpk');
        // $searchByInput = $request->input('searchByInput');
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
            DB::raw('DATE_FORMAT(beli.tglinv, "%d-%m-%Y") as tanggal'),
            DB::raw('DATE_FORMAT(belidt.tglbayar, "%d-%m-%Y") as tglbayar'),
		)->orderBy('beli.tglinv', 'ASC')->orderBy('belidt.belidtpk', 'ASC')->where('user.userpk', $userpk);

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


        $pdf = Pdf::loadView('menu.laporan.pembelian.pdf-pembelian-ct2', 
		compact('subTotalCash', 'grandTotalCash', 'subTotalTempo', 'grandTotalTempo',  'SubTotalCashTempoJmlHrg', 'TotalGrandCashTempo', 
		'formattedData_Cash', 'formattedData_Tempo', 'tanggalLabel', 'count_data_Cash', 'count_data_Tempo'))->setPaper('a4', 'potrait');
        return $pdf->stream('Laporan Pembelian Cash-Tempo.pdf');
    }


}

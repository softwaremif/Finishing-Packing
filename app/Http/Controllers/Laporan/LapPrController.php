<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use PDF;
use Illuminate\Support\Facades\Session;

class LapPrController extends Controller
{
    function PageLapPr(){
        return view('menu.laporan.pr.list-pr');
    }

    function getListLapPr(Request $request)
	{

        $getemail = Session::get('username');
        $userpk =  Session::get('userpk');
        // $userpk = DB::table('user')->where('username', '=', $getemail)->value('userpk');

		if (!$userpk) {
			return response()->json(['error' => 'Userpk not found in session.'], 400);
		}

		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInput = $request->searchByInput;
		$SelectStart = $request->SelectStart;
		$SelectFinish = $request->SelectFinish;

		$data_pr = DB::table('prdt')
		->leftJoin('pr', 'pr.prpk', '=', 'prdt.prpk')
        ->leftJoin('user', 'user.userpk', '=', 'pr.userpk')
        ->leftJoin('dep', 'dep.deppk', '=', 'pr.deppk')
		->select(
			'prdt.prdtpk',
			'pr.prpk',
            DB::raw('DATE_FORMAT(pr.tglpr, "%d %b %Y") as tanggal'),
			'pr.nopr',
            'pr.tglpr',
            'pr.posting',
            'user.userpk',
            'user.login',
            'dep.deppk',
            'dep.depnm',
            'prdt.prpk',
            'prdt.brgnm',
			'prdt.unit',
            'prdt.jmlbeli',
		)
        ->where('pr.posting', '=', 1)
        ->where('pr.userpk', '=', $userpk)
        ->orderBy('pr.tglpr', 'DESC');

		if (!empty($searchByInput)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(prdt.nopr, ''),
                COALESCE(prdt.brgnm, '')
            )");
			$data_pr = $data_pr->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}

        if (!empty($SelectStart)) {
			$SelectStart = Carbon::createFromFormat('d/m/Y', $SelectStart)->format('Y-m-d');
			$data_pr = $data_pr->whereDate('pr.tglpr', '>=', $SelectStart);
		} 

		if (!empty($SelectFinish)) {
			$SelectFinish = Carbon::createFromFormat('d/m/Y', $SelectFinish)->format('Y-m-d');
			$data_pr = $data_pr->whereDate('pr.tglpr', '<=', $SelectFinish);
		}

		$AllDataPr = $data_pr->get();
		$offset = ($page - 1) * $rows;
		$data_pr = $data_pr->skip($offset)->take($rows)->get();

		$result = array();
		$result['total'] = $AllDataPr->count();
		$result['page'] = $page;
		$result['rows'] = $rows;
		$result['offset'] = $offset;
		$row = array();
		$index = $offset + 1;

		foreach ($data_pr as $d) {

				$dt_index = "<p>" . $index . "</p>";
   				$dt_tanggal = "<p>" . $d->tanggal . "</p>";
                $dt_nopr = "<p>" . str_pad($d->nopr, 6, '0', STR_PAD_LEFT) . "</p>";
				$dt_brgnm = "<p>" . $d->brgnm . "</p>";
				$dt_unit = "<p>" . $d->unit . "</p>";
				$dt_jmlbeli = "<p>" . $d->jmlbeli . "</p>";
				$dt_pengirim = "<p>" . strtoupper($d->login) . "</p>";
				$dt_penerima = "<p>" . strtoupper($d->depnm) . "</p>";
			
			$row[] = array(
				'prdtpk' => $d->prdtpk,
				'index' => $dt_index,
				'tanggal' => $dt_tanggal,
				'noinv' => $dt_nopr,
				'brgnm' => $dt_brgnm,
				'unit' => $dt_unit,
				'jmlbeli' => $dt_jmlbeli,
                'pengirim' => $dt_pengirim,
                'penerima' => $dt_penerima,
			);
			$index++;
		}
		$result = array_merge($result, array('rows' => $row));
		return json_encode($result);
	}

    public function PdfLapPr(Request $request)
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

		$query = DB::table('prdt')
		->leftJoin('pr', 'pr.prpk', '=', 'prdt.prpk')
        ->leftJoin('user', 'user.userpk', '=', 'pr.userpk')
        ->leftJoin('dep', 'dep.deppk', '=', 'pr.deppk')
		->select(
			'prdt.prdtpk',
			'pr.prpk',
            DB::raw('DATE_FORMAT(pr.tglpr, "%d %b %Y") as tanggal'),
			'pr.nopr',
            'pr.tglpr',
            'pr.posting',
            'user.userpk',
            'user.login',
            'dep.deppk',
            'dep.depnm',
            'prdt.prpk',
            'prdt.brgnm',
			'prdt.unit',
            'prdt.jmlbeli',
		)
        ->where('pr.posting', 1)
        ->where('pr.userpk', '=', $userpk)
        ->orderBy('pr.tglpr', 'DESC');


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
            $query->whereBetween('pr.tglpr', [$formattedDateStart, $formattedDateFinish]);
        }

        $data = $query->get();
        $formattedData = [];

        foreach ($data as $index => $d) {

            $dt_index = ++$index;
            $dt_tanggal = $d->tanggal;
            $dt_nopr = str_pad($d->nopr, 6, '0', STR_PAD_LEFT);
            $dt_brgnm = $d->brgnm;
            $dt_unit = $d->unit;
            $dt_jmlbeli = $d->jmlbeli;
            $dt_pengirim = strtoupper($d->login);
            $dt_penerima = strtoupper($d->depnm);

            $formattedData[] = [
				'index' => $dt_index,
				'tanggal' => $dt_tanggal,
				'nopr' => $dt_nopr,
				'brgnm' => $dt_brgnm,
				'unit' => $dt_unit,
				'jmlbeli' => $dt_jmlbeli,
                'login' => $dt_pengirim,
                'depnm' => $dt_penerima,
            ];
        }

        $pdf = Pdf::loadView('menu.laporan.pr.pdf-list-pr', compact('formattedData', 'tanggalLabel'))->setPaper('a4', 'potrait');
        return $pdf->stream('Laporan Purchase Request.pdf');
    }
}

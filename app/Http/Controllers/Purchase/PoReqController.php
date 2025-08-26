<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use PDF;

class PoReqController extends Controller
{

	// public function __construct()
	// {
	// 	$this->middleware('web');
	// }

	public function GetLastPr()
	{

		$getemail = Session::get('username');
        $userpk = DB::table('user')->where('username', '=', $getemail)->value('userpk');

		// Ambil nopr terakhir (yang paling besar)
		$lastNopr = DB::table('pr')->max('nopr');
		$newNopr = $lastNopr ? $lastNopr + 1 : 1;

		$lastId = DB::table('pr')->insertGetId([
			'tglpr' => Carbon::now(),
			'userpk' => $userpk,
			'nopr' => $newNopr,
		]);

		return response()->json([
			'prpk' => $lastId,
		]);
	}

	public function BackPr(Request $request)
	{
		$prpk = $request->input('prpk');
		$segment3 = $request->input('segment3');

		if ($segment3 == 'add-pr') {
			DB::table('pr')->where('prpk', $prpk)->delete();
			DB::table('prdt')->where('prpk', $prpk)->delete();
		}

		return response()->json([
			'status' => 'success',
			'prpk' => $prpk,
			'segment3' => $segment3,
		]);
	}

	function AddPr($prpk)
	{
		$dt_pr = DB::table('pr')
			->leftJoin('user', 'user.userpk', '=', 'pr.userpk')
			->leftJoin('dep', 'dep.deppk', '=', 'pr.deppk')
			->select(
				'pr.prpk',
				'pr.nopr',
				'user.userpk',
				'user.login',
				'dep.deppk',
				'dep.depnm',
				'pr.tglpr',
				'pr.tglupdt',
				'pr.posting',
				DB::raw('DATE_FORMAT(pr.tglpr, "%d %b %Y") as tanggal'),
			)
			->where('pr.prpk', '=', $prpk)
			->first();

		// return view('menu.purchase-request.detpr', compact('dt_pr'));
		// return view('menu.purchase-request.detpr2', compact('dt_pr'));
		return view('menu.purchase-request.detpr3', compact('dt_pr'));
	}

	public function index()
	{
		if (!Session::get('userpk')) return redirect('/');
		return view('menu.purchase-request.list');
	}

	public function GetPr(Request $request)
	{

        $getemail = Session::get('username');
        // $userpk =  Session::get('userpk');
        $userpk = DB::table('user')->where('username', '=', $getemail)->value('userpk');

		if (!$userpk) {
			return response()->json(['error' => 'Userpk not found in session.'], 400);
		}

		$deppk = DB::table('user')->where('userpk', '=', $userpk)->value('deppk');

		$depreq = DB::table('user')->leftJoin('dep', 'dep.deppk', '=', 'user.deppk')->where('userpk', '=', $userpk)->value('req');
		// dd($depreq);

		$tahun = date("Y");
		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInput = $request->searchByInput;
		// $filterByMonth = $request->filterByMonth;
		$filterByMonth = $request->filterByMonth ?? date('m');
		$filterByYear = $request->filterByYear;
		$sortlistByDate = $request->sortlistByDate;

		if (!empty($filterByYear)) $tahun = $filterByYear;

		$data_pr = DB::table('pr')
		->leftJoin('user', 'user.userpk', '=', 'pr.userpk')
		->leftJoin('dep', 'dep.deppk', '=', 'pr.deppk')
		->select(
			'pr.prpk',
			'pr.nopr',
			'user.userpk as userbuat',
			'user.login',
			'dep.deppk',
			'dep.depnm',
			'pr.tglpr',
			'pr.tglupdt',
			'pr.posting',
			DB::raw('DATE_FORMAT(pr.tglpr, "%d %b %Y") as tanggal')
		)
		->where(function ($query) use ($deppk, $userpk) {
			$query->where(function ($q) use ($deppk) {
				$q->where('pr.deppk', '=', $deppk)
				->where('pr.posting', '=', 1); // hanya yg sudah posting jika sebagai penerima
			})
			->orWhere(function ($q) use ($userpk) {
				$q->where('pr.userpk', '=', $userpk); // semua data yg dibuat sendiri
			});
		});



		if (!empty($searchByInput)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(pr.nopr, ''),
                COALESCE(user.login, ''),
                COALESCE(dep.depnm, '')
            )");

			$data_pr = $data_pr->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}

		// Filter berdasarkan tahun
		if (!empty($filterByYear)) {
			$data_pr = $data_pr->whereYear('pr.tglpr', '=', $filterByYear);
		} else {
			// Jika tidak ada filter tahun, gunakan tahun saat ini
			$data_pr = $data_pr->whereYear('pr.tglpr', '=', $tahun);
		}

		// Filter berdasarkan bulan
		if (!empty($filterByMonth)) {
			$data_pr = $data_pr->whereMonth('pr.tglpr', '=', $filterByMonth);
		}

		// Pengurutan berdasarkan tanggal
		if ($sortlistByDate == 11) {
			$data_pr = $data_pr->orderBy('pr.tglpr', 'desc')->orderBy('pr.nopr', 'desc');
		} elseif ($sortlistByDate == 12) {
			$data_pr = $data_pr->orderBy('pr.tglpr', 'asc')->orderBy('pr.nopr', 'asc');
		} else {
			$data_pr = $data_pr->orderBy('pr.tglpr', 'desc')->orderBy('pr.nopr', 'desc'); // Default pengurutan
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

			// if($d->posting == 1){
			// 	$dt_posting = "<span style='color:#35962a'>Ya</span>";
			// }else{
			// 	$dt_posting = "<span style='color:#FF6B00'>Tidak</span>";
			// }

			// $dt_userbuat = "$d->userbuat";

			$countItem = DB::table('prdt')->where('prpk', '=', $d->prpk)->count();
		

			if($d->posting == null){
				$dt_index = "<b>" . $index . "</b>";
				$dt_userbuat = "<b>" . $d->userbuat . "</b>";
				$dt_tanggal = "<b>" . $d->tanggal . "</b>";
				$dt_nopr = "<b>" . str_pad($d->nopr, 6, '0', STR_PAD_LEFT) . "</b>";
				$dt_login = "<b>" . $d->login . "</b>";
				$dt_depnm = "<b>" . $d->depnm . "</b>";
			}else{
				$dt_index = $index ;
				$dt_userbuat = $d->userbuat ;
				$dt_tanggal = $d->tanggal ;
				$dt_nopr = str_pad($d->nopr, 6, '0', STR_PAD_LEFT);
				$dt_login = $d->login ;
				$dt_depnm = $d->depnm ;
			}

			$row[] = array(
				'prpk' => $d->prpk,
				'index' => $dt_index,
				'userbuat' => $dt_userbuat,
				'tanggal' => $dt_tanggal,
				'nopr' => $dt_nopr ?? '-',
				// 'user' => strtoupper($d->login ?? '-'),
				'user' => strtoupper($dt_login ?? '-'),
				'depnm' => $dt_depnm ?? '-',
				'posting' => $d->posting,
				'totitem' => $countItem,
			);
			$index++;
		}
		$result = array_merge($result, array('rows' => $row));
		return json_encode($result);
	}

	public function ConfirmPosting($prpk)
    {
		$updated = DB::table('pr')
			->where('prpk', $prpk)
			->update([
				'posting' => 1,
				'tglupdt' => Carbon::now(),
			]);

		if ($updated == 0) {
			return response()->json(['success' => false, 'message' => "PR with prpk $prpk not found"]);
		}

        return response()->json([
            'success' => true,
            'message' => 'Purchase Request berhasil diposting',
            'dataPR' => $updated,
            'code' => 202
        ]);
    }

	public function DetailPoReq($prpk)
	{
		// $getemail = Session::get('username');
        // $userpk = DB::table('user')->where('username', '=', $getemail)->value('userpk');
		if (!Session::get('userpk')) return redirect('/');

		$getemail = Session::get('username');
		$getuserpk = DB::table('user')->where('username', $getemail)->value('userpk');

		// // Kirim ke view
		// $view['userpk'] = $userpk;

		$dt_pr = DB::table('pr')
			->leftJoin('user', 'user.userpk', '=', 'pr.userpk')
			->leftJoin('dep', 'dep.deppk', '=', 'pr.deppk')
			->select(
				'pr.prpk',
				'pr.nopr',
				'user.userpk',
				'user.login',
				'dep.deppk',
				'dep.depnm',
				'pr.tglpr',
				'pr.tglupdt',
				'pr.posting',
				DB::raw('DATE_FORMAT(pr.tglpr, "%d %b %Y") as tanggal'),
			)
			->where('pr.prpk', '=', $prpk)
			->first();

		// return view('menu.purchase-request.detpr', compact('dt_pr', 'getuserpk'));
		return view('menu.purchase-request.detpr3', compact('dt_pr', 'getuserpk'));
	}

	public function PrintPr($prpk){
		$dt_pr = DB::table('pr')
		->leftJoin('user', 'user.userpk', '=', 'pr.userpk')
		->leftJoin('dep', 'dep.deppk', '=', 'pr.deppk')
		->select(
			'pr.prpk',
			'pr.nopr',
			'user.userpk',
			'user.login',
			'dep.deppk',
			'dep.depnm',
			'pr.tglpr',
			'pr.tglupdt',
			'pr.posting',
			DB::raw('DATE_FORMAT(pr.tglpr, "%d %b %Y") as tanggal'),
		)
		->where('pr.prpk', '=', $prpk)
		->first();

		$dt_prdt = DB::table('prdt')
		->leftJoin('pr', 'pr.prpk', '=', 'prdt.prpk')
		->select('prdt.prdtpk', 'prdt.prpk', 'prdt.brgnm', 'prdt.unit', 'prdt.jmlbeli')
		->where('prdt.prpk', '=', $prpk)
		->get();

		$dtdatenow = Carbon::now()->translatedFormat('l, d F Y');
		
		$pdf = PDF::loadview('menu.purchase-request.print', compact('dt_pr', 'dt_prdt', 'dtdatenow'));
		return $pdf->stream();
	}

	public function AddBarangToPrdt(Request $request)
    {
        $prpk = $request->input('prpk');
        $selectedBrgpks = $request->input('brgpk');

        if (empty($selectedBrgpks) || !is_array($selectedBrgpks)) {
            return response()->json(['status' => 'error', 'message' => 'No PO detail items selected.']);
        }

        $detailItems = DB::table('brg')
            ->whereIn('brgpk', $selectedBrgpks)
            ->get();

        if ($detailItems->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'Selected PO detail items not found.']);
        }

        foreach ($detailItems as $item) {
            DB::table('prdt')->insert([
                'prpk' => $prpk,
                'brgpk' => $item->brgpk,
                'brgnm' => trim(strtoupper($item->brgnm)),
                'unit' => null,
                'jmlbeli' => trim($item->qtyawal),
            ]);
        }

        return response()->json([
       		'success' => true,
            'message' => 'Data purchase request successfully inserted',
            'inserted_items' => $detailItems->count(),
        ]);
    }
}

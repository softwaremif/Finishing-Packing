<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Pdf;

class PoOrderController extends Controller
{
    public function GetLastPo()
	{

        $getemail = Session::get('username');
        $userpk = DB::table('user')->where('username', '=', $getemail)->value('userpk');

		$lastNopo = DB::table('po')->max('nopo');
		$newNopo = $lastNopo ? $lastNopo + 1 : 1;

		$lastId = DB::table('po')->insertGetId([
			'tglpo' => Carbon::now(),
			'userpk' => $userpk,
			'nopo' => $newNopo,
			'nobukti' => $newNopo,
		]);

        $lastNotran = DB::table('notran')->where('tblnm', '=', 'po')->where('tblket', '=', 'po')->max('no');
		$newNotran = $lastNotran ? $lastNotran + 1 : 1;

        $lastNotran = DB::table('notran')
        ->where('tblnm', '=', 'po')
        ->where('tblket', '=', 'po')
        ->update([
			'no' => $newNotran,
		]);

		return response()->json([
			'popk' => $lastId,
            // 'no' => $newNotran,
		]);
	}

    public function BackPo(Request $request)
	{
		$popk = $request->input('popk');
		$segment3 = $request->input('segment3');

		if ($segment3 == 'add-po') {
            $getprdtpk = DB::table('podt')->where('popk', $popk)->pluck('prdtpk');
    
            $kembalikansts = DB::table('prdt')
                ->whereIn('prdtpk', $getprdtpk)
                ->update(['stspo' => null]);

			$deletepopk = DB::table('po')->where('popk', $popk)->delete();
			$deletepodtpk = DB::table('podt')->where('popk', $popk)->delete();
		}

		return response()->json([
			'status' => 'success',
			'popk' => $popk,
			'segment3' => $segment3,
		]);
	}

    function AddPo($popk)
	{
        if (!Session::get('userpk')) return redirect('/');
        $getemail = Session::get('username');
		$getuserpk = DB::table('user')->where('username', $getemail)->value('userpk');

        $dt_po = DB::table('po')
            ->leftJoin('user', 'user.userpk', '=', 'po.userpk')
            ->leftJoin('ab', 'ab.abpk', '=', 'po.abpk')
            ->leftJoin('sup', 'sup.suppk', '=', 'po.suppk')
            ->leftJoin('cur', 'cur.curpk', '=', 'po.curpk')
            ->leftJoin('kel', 'kel.kelpk', '=', 'po.kelpk')
            ->select(
                'po.popk',
                'po.nobukti',
                'po.nopo',
                'po.tglpo',
                'user.userpk',
                'user.login',
                'sup.suppk',
                'sup.supnm',
                'ab.abpk',
                'ab.abnm',
                'po.totbeli',
                'po.ket',
                'po.ket2',
                'po.posting',
                'cur.curpk',
                'cur.curid',
                DB::raw('DATE_FORMAT(po.tglpo, "%d %b %Y") as tanggal'),
            )
            ->where('po.popk', '=', $popk)
            ->first();

		return view('menu.purchase-order.detpo2', compact('dt_po', 'getuserpk'));
	}

    public function index()
    {
        if (!Session::get('userpk')) return redirect('/');
        return view('menu.purchase-order.list');
    }

    public function GetPo(Request $request)
    {
        // $userpk =  Session::get('userpk');
        $getemail = Session::get('username');
        $userpk = DB::table('user')->where('username', '=', $getemail)->value('userpk');

        if (!$userpk) {
            return response()->json(['error' => 'Userpk not found in session.'], 400);
        }

        // $guserpk = DB::table('user')->where('userpk', '=', $userpk)->value('guserpk');
        // $deppk = DB::table('user')->where('userpk', '=', $userpk)->value('deppk');

        $tahun = date("Y");
        $page = $request->input('page') ?? '1';
        $rows = $request->input('rows') ?? '100';
        $searchByInput = $request->searchByInput;
        $filterByMonth = $request->filterByMonth ?? date('m');
        $filterByYear = $request->filterByYear;
        $sortlistByDate = $request->sortlistByDate;
        if (!empty($filterByYear)) $tahun = $filterByYear;

        $data_po = DB::table('po')
            ->leftJoin('user', 'user.userpk', '=', 'po.userpk')
            ->leftJoin('sup', 'sup.suppk', '=', 'po.suppk')
            ->leftJoin('ab', 'ab.abpk', '=', 'po.abpk')
            ->leftJoin('cur', 'cur.curpk', '=', 'po.curpk')
            ->select(
                'po.popk',
                'po.nobukti',
                'po.nopo',
                'user.userpk',
                'user.guserpk',
                'user.login',
                'sup.suppk',
                'sup.supnm',
                'cur.curpk',
                'cur.curid',
                'ab.abpk',
                'ab.abnm',
                'po.totbeli',
                'po.ket',
                'po.posting',
                'po.ket2',
                'cur.curpk',
                'cur.curnm',
                DB::raw('DATE_FORMAT(po.tglpo, "%d %b %Y") as tanggal'),
            );

        // if ($guserpk == 1) {
        //     $data_po = $data_po;
        // } else {
        //     $data_po = $data_po->where('po.userpk', '=', $userpk);
        // }

        // if (!in_array($deppk, [3, 6])) {
        //     $data_po = $data_po->where('po.userpk', '=', $userpk);
        // }

            if($userpk == 7){
                $data_po->whereIn('po.userpk', ['7', '11']);
            }else{
                $data_po->where('po.userpk', $userpk);
            }


        if (!empty($searchByInput)) {
            $concatenatedValue = DB::raw("CONCAT(
                COALESCE(po.nobukti, ''),
                COALESCE(po.nopo, ''),
                COALESCE(user.login, ''),
                COALESCE(sup.supnm, ''),
                COALESCE(CAST(po.totbeli AS CHAR), ''),
                COALESCE(ab.abnm, ''),
                COALESCE(po.ket, ''),
                COALESCE(po.ket2, ''),
                COALESCE(cur.curnm, '')
            )");

            $data_po = $data_po->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
        }

        // Filter berdasarkan tahun
        if (!empty($filterByYear)) {
            $data_po = $data_po->whereYear('po.tglpo', '=', $filterByYear);
        } else {
            // Jika tidak ada filter tahun, gunakan tahun saat ini
            $data_po = $data_po->whereYear('po.tglpo', '=', $tahun);
        }

        // Filter berdasarkan bulan
        if (!empty($filterByMonth)) {
            $data_po = $data_po->whereMonth('po.tglpo', '=', $filterByMonth);
        }

        // Pengurutan berdasarkan tanggal
        if ($sortlistByDate == 11) {
            $data_po = $data_po->orderBy('po.tglpo', 'desc')->orderBy('po.nobukti', 'desc');
        } elseif ($sortlistByDate == 12) {
            $data_po = $data_po->orderBy('po.tglpo', 'asc')->orderBy('po.nobukti', 'asc');
        } else {
            $data_po = $data_po->orderBy('po.tglpo', 'desc')->orderBy('po.nobukti', 'desc'); // Default pengurutan
        }

        $AllDataPo = $data_po->get();
        $offset = ($page - 1) * $rows;
        $data_po = $data_po->skip($offset)->take($rows)->get();

        $result = array();
        $result['total'] = $AllDataPo->count();
        $result['page'] = $page;
        $result['rows'] = $rows;
        $result['offset'] = $offset;
        $row = array();
        $index = $offset + 1;

        foreach ($data_po as $d) {

            if($d->posting == null){
				$dt_index = "<b>" . $index . "</b>";
				$dt_tanggal = "<b>" . $d->tanggal . "</b>";
				$dt_nopo = "<b>" . str_pad($d->nopo, 6, '0', STR_PAD_LEFT) . "</b>";
				$dt_supnm = "<b>" . $d->supnm . "</b>";
				$dt_curid = "<b>" . $d->curid . "</b>";
				$dt_totbeli = "<b>" . number_format($d->totbeli, 2, ',', '.') . "</b>";
				$dt_term = "<b>" . $d->abnm . "</b>";
				$dt_login = "<b>" . strtoupper($d->login) . "</b>";
			}else{
				$dt_index = $index;
				$dt_tanggal = $d->tanggal;
				$dt_nopo = str_pad($d->nopo, 6, '0', STR_PAD_LEFT);
				$dt_supnm = $d->supnm;
				$dt_curid = $d->curid;
				$dt_totbeli = number_format($d->totbeli, 2, ',', '.');
				$dt_term = $d->abnm;
				$dt_login = strtoupper($d->login);
			}

            $row[] = array(
                'popk' => $d->popk,
                'posting' => $d->posting,
                'index' => $dt_index,
                'tanggal' => $dt_tanggal,
                'nopo' => $dt_nopo,
                'supnm' => $dt_supnm,
                'curid' => $dt_curid,
                'totbeli' => $dt_totbeli,
                'term' => $dt_term,
                'user' => $dt_login,
            );
            $index++;
        }
        $result = array_merge($result, array('rows' => $row));
        return json_encode($result);
    }

    public function ConfirmPosting($popk)
    {
		$updated = DB::table('po')
			->where('popk', $popk)
			->update([
				'posting' => 1,
			]);

		if ($updated == 0) {
			return response()->json(['success' => false, 'message' => "Po with popk $popk not found"]);
		}

        return response()->json([
            'success' => true,
            'message' => 'Purchase Order berhasil diposting',
            'dataPR' => $updated,
            'code' => 202
        ]);
    }

    public function DetailPorder($popk)
    {
        if (!Session::get('userpk')) return redirect('/');
        $getemail = Session::get('username');
		$getuserpk = DB::table('user')->where('username', $getemail)->value('userpk');

        $dt_po = DB::table('po')
            ->leftJoin('user', 'user.userpk', '=', 'po.userpk')
            ->leftJoin('ab', 'ab.abpk', '=', 'po.abpk')
            ->leftJoin('sup', 'sup.suppk', '=', 'po.suppk')
            ->leftJoin('cur', 'cur.curpk', '=', 'po.curpk')
            ->leftJoin('kel', 'kel.kelpk', '=', 'po.kelpk')
            ->select(
                'po.popk',
                'po.nobukti',
                'po.nopo',
                'po.tglpo',
                'user.userpk',
                'user.login',
                'sup.suppk',
                'sup.supnm',
                'ab.abpk',
                'ab.abnm',
                'po.totbeli',
                'po.ket',
                'po.ket2',
                'po.posting',
                'cur.curpk',
                'cur.curid',
                DB::raw('DATE_FORMAT(po.tglpo, "%d %b %Y") as tanggal'),
            )
            ->where('po.popk', '=', $popk)
            ->first();

        return view('menu.purchase-order.detpo2', compact('dt_po', 'getuserpk'));
    }

    public function GetLookUpPr(request $request)
    {
        $getemail = Session::get('username');
        // $userpk =  Session::get('userpk');
        $userpk = DB::table('user')->where('username', '=', $getemail)->value('userpk');

		if (!$userpk) {
			return response()->json(['error' => 'Userpk not found in session.'], 400);
		}

		$deppk = DB::table('user')->where('userpk', '=', $userpk)->value('deppk');
		$depreq = DB::table('user')->leftJoin('dep', 'dep.deppk', '=', 'user.deppk')->where('userpk', '=', $userpk)->value('req');

        $searchByInput = $request->searchByInputOrder;
        $page = $request->input('page') ?? 1;
        $rows = $request->input('rows') ?? 100;

        $dt_pr = DB::table('pr')
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
                )->where(function ($query) use ($deppk, $userpk) {
                    $query->where(function ($q) use ($deppk) {
                        $q->where('pr.deppk', '=', $deppk)
                        ->where('pr.posting', '=', 1); // hanya yg sudah posting jika sebagai penerima
                    })
                    ->orWhere(function ($q) use ($userpk) {
                        $q->where('pr.userpk', '=', $userpk); // semua data yg dibuat sendiri
                    });
                });

        if (!empty($searchByInput)) {
            $concatenatedValue = DB::raw("CONCAT(pr.nopr,'',user.login,'',dep.depnm,'')");
            $dt_pr = $dt_pr->where(DB::raw($concatenatedValue), 'like', '%' . strtolower($searchByInput) . '%');
        }

        $dt_pr = $dt_pr->orderBy('pr.prpk', 'DESC');
        $dt_pr_Total = $dt_pr->get()->count();
        $offset = ($page - 1) * $rows;
        $dt_pr = $dt_pr->skip($offset)->take($rows)->get();
        $result = array();
        $row = array();
        $result['total'] = $dt_pr_Total;
        $index = 0;

        foreach ($dt_pr as $d) {
            $row[] = array(
                'prpk' => $d->prpk,
			    'userbuat' => $d->userbuat,
				'tanggal' => $d->tanggal,
				'nopr' => str_pad($d->nopr, 6, '0', STR_PAD_LEFT),
				'user' => strtoupper($d->login ?? '-'),
				'depnm' => $d->depnm ?? '-',
				'posting' => $d->posting,
            );
            $index++;
        }
        $result = array_merge($result, array('rows' => $row));
        return json_encode($result);
    }

    public function PrintPo($popk){
		$dt_po = DB::table('po')
		->leftJoin('user', 'user.userpk', '=', 'po.userpk')
        ->leftJoin('guser', 'guser.guserpk', '=', 'user.guserpk')
		->leftJoin('sup', 'sup.suppk', '=', 'po.suppk')
		->leftJoin('ab', 'ab.abpk', '=', 'po.abpk')
		->leftJoin('cur', 'cur.curpk', '=', 'po.curpk')
		->select(
			'po.popk',
			'po.nobukti',
			'user.userpk',
			'user.login',
            'user.guserpk',
            'guser.ttdnm',
			'sup.suppk',
			'sup.supnm',
            'ab.abpk',
			'ab.abnm',
			'po.tglpo',
			'po.totbeli',
			'po.ket',
			'po.ket2',
            'cur.curpk',
			'cur.cursign',
			DB::raw('DATE_FORMAT(po.tglpo, "%d %b %Y") as tanggal'),
		)
		->where('po.popk', '=', $popk)
		->first();

		$dt_podt = DB::table('podt')
		->select('podt.podtpk', 'podt.popk', 'podt.brgnm', 'podt.unit', 'podt.hrgbeli', 'podt.jmlbeli')
		->where('podt.popk', '=', $popk)->orderby('podt.podtpk', 'ASC')
		->get();

		$dtdatenow = Carbon::now()->translatedFormat('l, d F Y');
		
		$pdf = PDF::loadview('menu.purchase-order.print', compact('dt_po', 'dt_podt', 'dtdatenow'));
		return $pdf->stream();
	}

    public function GetDetPr(request $request)
    {
        $getemail = Session::get('username');
        $userpk = DB::table('user')->where('username', '=', $getemail)->value('userpk');

		if (!$userpk) {
			return response()->json(['error' => 'Userpk not found in session.'], 400);
		}

		$deppk = DB::table('user')->where('userpk', '=', $userpk)->value('deppk');
		$depreq = DB::table('user')->leftJoin('dep', 'dep.deppk', '=', 'user.deppk')->where('userpk', '=', $userpk)->value('req');

        $searchByInput = $request->searchByInputOrder;
        $tahun = date("Y");
        $filterByMonthPr = $request->filterByMonthPr ?? date('m');
        $filterByYearPr = $request->filterByYearPr;
        if (!empty($filterByYearPr)) $tahun = $filterByYearPr;
        $page = $request->input('page') ?? 1;
        $rows = $request->input('rows') ?? 100;

        $dt_prdt = DB::table('prdt')
                ->leftJoin('pr', 'pr.prpk', '=', 'prdt.prpk')
                ->leftJoin('user', 'user.userpk', '=', 'pr.userpk')
                ->leftJoin('dep', 'dep.deppk', '=', 'pr.deppk')
                ->select(
                    'pr.prpk',
                    'pr.nopr',
                    'user.userpk as userbuat',
                    'user.login',
                    'dep.deppk',
                    'dep.depnm',
                    // 'pr.tglpr',
                    // 'pr.tglupdt',
                    // 'pr.posting',
                    DB::raw('DATE_FORMAT(pr.tglpr, "%d %b %Y") as tanggal'),
                    'prdt.prdtpk',
                    'prdt.prpk',
                    'prdt.brgnm',
                    'prdt.unit',
                    'prdt.jmlbeli',
                    'prdt.stspo',
                )->where(function ($query) use ($deppk, $userpk) {
                    $query->where(function ($q) use ($deppk) {
                        $q->where('pr.deppk', '=', $deppk)
                        ->where('pr.posting', '=', 1); 
                    })
                    ->orWhere(function ($q) use ($userpk) {
                        $q->where('pr.userpk', '=', $userpk); 
                    });
                })->where('prdt.stspo', null);
                // );

        if (!empty($searchByInput)) {
            // $concatenatedValue = DB::raw("CONCAT(prdt.brgnm,'',prdt.nopr,'',user.login,'',dep.depnm,'')");
            $concatenatedValue = DB::raw("CONCAT(prdt.brgnm,'')");
            $dt_prdt = $dt_prdt->where(DB::raw($concatenatedValue), 'like', '%' . strtolower($searchByInput) . '%');
        }

        // Filter berdasarkan tahun
        if (!empty($filterByYearPr)) {
            $dt_prdt = $dt_prdt->whereYear('pr.tglpr', '=', $filterByYearPr);
        } else {
            // Jika tidak ada filter tahun, gunakan tahun saat ini
            $dt_prdt = $dt_prdt->whereYear('pr.tglpr', '=', $tahun);
        }

        // Filter berdasarkan bulan
        if (!empty($filterByMonthPr)) {
            $dt_prdt = $dt_prdt->whereMonth('pr.tglpr', '=', $filterByMonthPr);
        }

        $dt_prdt = $dt_prdt->orderBy('prdt.prdtpk', 'DESC');
        $dt_prdt_Total = $dt_prdt->get()->count();
        $offset = ($page - 1) * $rows;
        $dt_prdt = $dt_prdt->skip($offset)->take($rows)->get();
        $result = array();
        $row = array();
        $result['total'] = $dt_prdt_Total;
        $index = 0;

        foreach ($dt_prdt as $d) {

            if($d->stspo == null){
                $dt_index = $index;
				// $dt_userbuat = $d->userbuat;
				$dt_tanggal = $d->tanggal;
				$dt_nopr = str_pad($d->nopr, 6, '0', STR_PAD_LEFT);
				$dt_login = strtoupper($d->login);
				$dt_depnm = strtoupper($d->depnm ?? '-');
				$dt_brgnm = strtoupper($d->brgnm);
				$dt_unit = $d->unit ?? '-';
				$dt_jmlbeli = $d->jmlbeli ?? '-';
			}else{
				$dt_index = "<span style='color:#919191'>" . $index . "</span>";
				// $dt_userbuat = "<span style='color:#35962a'>" . $d->userbuat . "</span>";
				$dt_tanggal = "<span style='color:#919191'>" . $d->tanggal . "</span>";
				$dt_nopr = "<span style='color:#919191'>" . str_pad($d->nopr, 6, '0', STR_PAD_LEFT) . "</span>";
				$dt_login = "<span style='color:#919191'>" . strtoupper($d->login ?? '-') . "</span>";
				$dt_depnm = "<span style='color:#919191'>" . $d->depnm ?? '-'. "</span>";
				$dt_brgnm = "<span style='color:#919191'>" . strtoupper($d->brgnm) . "</span>";
				$dt_unit = "<span style='color:#919191'>" . $d->unit . "</span>";
				$dt_jmlbeli = "<span style='color:#919191'>" . $d->jmlbeli . "</span>";
			}

            $row[] = array(
			    // 'userbuat' => $d->userbuat,
                // 'posting' => $d->posting,
                'stspo' => $d->stspo,
                'prdtpk' => $d->prdtpk,
                'prpk' => $d->prpk,
				// 'tanggal' => $d->tanggal,
				// 'nopr' => str_pad($d->nopr, 6, '0', STR_PAD_LEFT),
				// 'user' => strtoupper($d->login ?? '-'),
				// 'depnm' => $d->depnm ?? '-',
			    // 'brgnm' => strtoupper($d->brgnm),
				// 'unit' => $d->unit,
				// 'jmlbeli' => $d->jmlbeli,
                'tanggal' => $dt_tanggal,
				'nopr' => $dt_nopr,
				'user' => $dt_login,
				'depnm' => $dt_depnm,
			    'brgnm' => $dt_brgnm,
				'unit' => $dt_unit,
				'jmlbeli' => $dt_jmlbeli,
            );
            $index++;
        }
        $result = array_merge($result, array('rows' => $row));
        return json_encode($result);
    }

    public function AddPrToPo(Request $request)
    {
        $popk = $request->input('popk');
        $selectedPrdtpks = $request->input('prdtpk'); // array of selected prdtpk

        if (empty($selectedPrdtpks) || !is_array($selectedPrdtpks)) {
            return response()->json(['status' => 'error', 'message' => 'No PR detail items selected.']);
        }

        // Ambil detail item berdasarkan prdtpk yang dipilih
        $detailItems = DB::table('prdt')
            ->whereIn('prdtpk', $selectedPrdtpks)
            ->where('stspo', '=', null)
            ->get();

        if ($detailItems->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'Selected Purchase Request detail items not found.']);
        }

        foreach ($detailItems as $item) {
            DB::table('podt')->insert([
                'popk' => $popk,
                'prdtpk' => $item->prdtpk,
                'brgpk' => $item->brgpk,
                'brgnm' => strtoupper($item->brgnm),
                'unit' => strtoupper($item->unit),
                'jmlbeli' => $item->jmlbeli,
            ]);

            // DB::table('prdt')
            // ->where('prdtpk', $item->prdtpk)
            // ->update(['stspo' => 1]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data inserted successfully!',
            'inserted_items' => $detailItems->count(),
        ]);
    }

    public function GetDetPo(request $request)
    {
        $getemail = Session::get('username');
        $userpk = DB::table('user')->where('username', '=', $getemail)->value('userpk');

        if (!$userpk) {
            return response()->json(['error' => 'Userpk not found in session.'], 400);
        }
        // $deppk = DB::table('user')->where('userpk', '=', $userpk)->value('deppk');
        $guserpk = DB::table('user')->where('userpk', '=', $userpk)->value('guserpk');

        $searchByInputOrderPo = $request->searchByInputOrderPo;
        $tahun = date("Y");
        $filterByMonthPo = $request->filterByMonthPo ?? date('m');
        $filterByYearPo = $request->filterByYearPo;
        if (!empty($filterByYearPo)) $tahun = $filterByYearPo;
        $page = $request->input('page') ?? 1;
        $rows = $request->input('rows') ?? 100;

        $dt_podt = DB::table('podt')
                ->leftJoin('po', 'po.popk', '=', 'podt.popk')
                ->leftJoin('user', 'user.userpk', '=', 'po.userpk')
                ->leftJoin('sup', 'sup.suppk', '=', 'po.suppk')
                ->leftJoin('ab', 'ab.abpk', '=', 'po.abpk')
                ->select(
                    'po.popk',
                    'po.nobukti',
                    'po.nopo',
                    'po.totbeli',
                     DB::raw('DATE_FORMAT(po.tglpo, "%d %b %Y") as tanggal'),
                    'user.userpk',
                    'user.login',
                    'sup.suppk',
                    'sup.supnm',
                    'podt.podtpk',
                    'podt.popk as podtpopk',
                    'podt.brgnm',
                    'podt.unit',
                    'podt.hrgbeli',
                    'podt.jmlbeli',
                )->where('podt.hrgbeli', '>', 0);
        
        // if (in_array($deppk, ['1', '3'])) {
        //     $dt_podt = $dt_podt;
        // } else {
        //     $dt_podt = $dt_podt->where('po.userpk', '=', $userpk);
        // }

            if ($guserpk == 1) {
                $dt_podt = $dt_podt;
            } else {
                $dt_podt = $dt_podt->where('po.userpk', '=', $userpk);
            }

            // if ($userpk == 11) {
            //     $dt_podt = $dt_podt;
            // } else {
            //     $dt_podt = $dt_podt->where('po.userpk', '=', $userpk);
            // }

                // ->where(function ($query) use ($deppk, $userpk) {
                //     $query->where(function ($q) use ($deppk) {
                //         $q->where('pr.deppk', '=', $deppk)
                //         ->where('pr.posting', '=', 1); 
                //     })
                //     ->orWhere(function ($q) use ($userpk) {
                //         $q->where('pr.userpk', '=', $userpk); 
                //     });
                // })->where('prdt.stspo', null);

        // if (!empty($searchByInputOrderPo)) {
        //     $concatenatedValue = DB::raw("CONCAT(podt.brgnm,'',po.nopo,'',po.nobukti,'')");
        //     $dt_podt = $dt_podt->where(DB::raw($concatenatedValue), 'like', '%' . strtolower($searchByInputOrderPo) . '%');
        // }

        $search = strtolower($searchByInputOrderPo);
        $dt_podt = $dt_podt->where(function ($query) use ($search) {
            $query->whereRaw('LOWER(podt.brgnm) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(po.nopo) LIKE ?', ["%{$search}%"])
                ->orWhereRaw('LOWER(po.nobukti) LIKE ?', ["%{$search}%"]);
        });

        // Filter berdasarkan tahun
        if (!empty($filterByYearPo)) {
            $dt_podt = $dt_podt->whereYear('po.tglpo', '=', $filterByYearPo);
        } else {
            // Jika tidak ada filter tahun, gunakan tahun saat ini
            $dt_podt = $dt_podt->whereYear('po.tglpo', '=', $tahun);
        }

        // Filter berdasarkan bulan
        if (!empty($filterByMonthPo)) {
            $dt_podt = $dt_podt->whereMonth('po.tglpo', '=', $filterByMonthPo);
        }

        $dt_podt = $dt_podt->orderBy('podt.podtpk', 'DESC');
        $dt_podt_Total = $dt_podt->get()->count();
        $offset = ($page - 1) * $rows;
        $dt_podt = $dt_podt->skip($offset)->take($rows)->get();
        $result = array();
        $row = array();
        $result['total'] = $dt_podt_Total;
        $index = 0;

        foreach ($dt_podt as $d) {

            $dt_index = "<span style='color:#000000'>" . $index . "</span>";
            $dt_tanggal = "<span style='color:#000000'>" . $d->tanggal . "</span>";
            $dt_nopo = "<span style='color:#000000'>" . $d->nopo . "</span>";
            $dt_nobukti = "<span style='color:#000000'>" . $d->nobukti . "</span>";
            $dt_login = "<span style='color:#000000'>" . strtoupper($d->login ?? '-') . "</span>";
            $dt_supnm = "<span style='color:#000000'>" . $d->supnm ?? '-'. "</span>";
            $dt_brgnm = "<span style='color:#000000'>" . strtoupper($d->brgnm) . "</span>";
            $dt_unit = "<span style='color:#000000'>" . $d->unit . "</span>";
            $dt_hrgbeli = "<span style='color:#000000'>" . number_format($d->hrgbeli, 0, '.', ',')  . "</span>";
            $dt_jmlbeli = "<span style='color:#000000'>" . $d->jmlbeli . "</span>";
            $dt_totbeli = "<span style='color:#000000'>" . number_format($d->totbeli, 0, '.', ',') . "</span>";

            $row[] = array(
                'podtpk' => $d->podtpk,
                'popk' => $d->popk,
                'tanggal' => $dt_tanggal,
				'nobukti' => $dt_nobukti,
				'nopo' => $dt_nopo,
				'user' => $dt_login,
				'supnm' => $dt_supnm,
			    'brgnm' => $dt_brgnm,
				'unit' => $dt_unit,
				'hrgbeli' => $dt_hrgbeli,
				'jmlbeli' => $dt_jmlbeli,
				'totbeli' => $dt_totbeli,
            );
            $index++;
        }
        $result = array_merge($result, array('rows' => $row));
        return json_encode($result);
    }

    public function AddPoToPo(Request $request)
    {
        $popk = $request->input('popk');
        $selectedPodtpks = $request->input('podtpk');

        if (empty($selectedPodtpks) || !is_array($selectedPodtpks)) {
            return response()->json(['status' => 'error', 'message' => 'No PO detail items selected.']);
        }

        $detailItems = DB::table('podt')
            ->whereIn('podtpk', $selectedPodtpks)
            ->get();

        if ($detailItems->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'Selected Purchase Order detail items not found.']);
        }

        foreach ($detailItems as $item) {
            DB::table('podt')->insert([
                'popk' => $popk,
                'brgnm' => strtoupper($item->brgnm),
                'unit' => strtoupper($item->unit),
                'jmlbeli' => $item->jmlbeli,
                'hrgbeli' => $item->hrgbeli,
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Data inserted successfully!',
            'inserted_items' => $detailItems->count(),
        ]);
    }
}
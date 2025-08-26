<?php

namespace App\Http\Controllers\Aktifitas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Pdf;

class PembelianCashTempoController extends Controller
{
    public function PagePembelianCT()
    {
        if (!Session::get('userpk')) return redirect('/');
        return view('menu.pembelian-cash-tempo.list');
    }

    public function GetPembelianCT(Request $request){
        $userpk =  Session::get('userpk');
        $guserpk = Session::get('guserpk');

        if (!$userpk) {
            return response()->json(['error' => 'Userpk not found in session.'], 400);
        }

        // $deppk = Session::get('deppk');

        $tahun = date("Y");
        $page = $request->input('page') ?? '1';
        $rows = $request->input('rows') ?? '100';
        $searchByInput = $request->searchByInput;
        $filterByMonth = $request->filterByMonth ?? date('m');
        $filterByYear = $request->filterByYear;
        $filterByTerm = $request->filterByTerm;
        $sortlistByDate = $request->sortlistByDate;
        if (!empty($filterByYear)) $tahun = $filterByYear;

        $dt_beli = DB::table('beli')            
            ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
            ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
            ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
            ->leftJoin('cur', 'cur.curpk', '=', 'beli.curpk')
            ->select([
                'beli.belipk',
                'beli.nobukti',
                'beli.noinv',
                'beli.tglinv',
                'beli.posting',
                'beli.abpk as pkab',
                'user.userpk',
                'user.guserpk',
                'user.login',
                'sup.suppk',
                'sup.supnm',
                'cur.curpk',
                'cur.curid',
                'cur.curnm',
                'ab.abpk',
                'ab.abnm',
                'beli.totbeli',
                'beli.ket',
                DB::raw('DATE_FORMAT(beli.tglinv, "%d %b %Y") as tanggal'),
            ]);

        if ($guserpk == 6) {
            $dt_beli = $dt_beli;
        } else {
            $dt_beli = $dt_beli->where('beli.userpk', '=', $userpk);
        }


        if (!empty($searchByInput)) {
            $concatenatedValue = DB::raw("CONCAT(
                COALESCE(beli.nobukti, ''),
                COALESCE(beli.noinv, ''),
                COALESCE(user.login, ''),
                COALESCE(sup.supnm, ''),
                COALESCE(CAST(beli.totbeli AS CHAR), ''),
                COALESCE(ab.abnm, ''),
                COALESCE(cur.curnm, '')
            )");

            $dt_beli = $dt_beli->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
        }

        // Filter berdasarkan tahun
        if (!empty($filterByYear)) {
            $dt_beli = $dt_beli->whereYear('beli.tglinv', '=', $filterByYear);
        } else {
            // Jika tidak ada filter tahun, gunakan tahun saat ini
            $dt_beli = $dt_beli->whereYear('beli.tglinv', '=', $tahun);
        }

        // Filter berdasarkan bulan
        if (!empty($filterByMonth)) {
            $dt_beli = $dt_beli->whereMonth('beli.tglinv', '=', $filterByMonth);
        }

        if ($filterByTerm) {
        // if (!empty($filterByTerm)) {
            $dt_beli = $dt_beli->where('beli.abpk', $filterByTerm);
        }

        // Pengurutan berdasarkan tanggal
        if ($sortlistByDate == 11) {
            $dt_beli = $dt_beli->orderBy('beli.tglinv', 'desc');
        } elseif ($sortlistByDate == 12) {
            $dt_beli = $dt_beli->orderBy('beli.tglinv', 'asc');
        } else {
            $dt_beli = $dt_beli->orderBy('beli.tglinv', 'desc'); // Default pengurutan
        }

        $AllDataBeli = $dt_beli->get();
        $offset = ($page - 1) * $rows;
        $dt_beli = $dt_beli->skip($offset)->take($rows)->get();

        $result = array();
        $result['total'] = $AllDataBeli->count();
        $result['page'] = $page;
        $result['rows'] = $rows;
        $result['offset'] = $offset;
        $row = array();
        $index = $offset + 1;

        foreach ($dt_beli as $d) {

        $total_jmlbayar = DB::table('belidt')
            ->where('belipk', $d->belipk)
            ->sum('jmlbayar');

        // Ambil semua tglbayar yang terkait, lalu gabungkan jadi string
        $tglbayar_list = DB::table('belidt')
            ->where('belipk', $d->belipk)
            ->pluck(DB::raw('DATE_FORMAT(tglbayar, "%d %b %Y") as tglbayar')) // ambil semua nilai tglbayar dalam bentuk Collection
            ->unique() // hilangkan duplikat tanggal
            ->toArray();


        $cg = DB::table('belidt')
            ->where('belipk', $d->belipk)
            ->pluck('cg')
            ->unique()
            ->toArray();
        $gabung_cg = implode(', ', $cg); 

        $dts_cg = '-';
        if($gabung_cg == 'C'){
            $dts_cg = 'CASH';
        }elseif($gabung_cg == 'G'){
            $dts_cg = 'GIRO';
        }

        $TotCountTglByr = DB::table('belidt')->select('jmlbayar', 'tglbayar')->where('belipk', $d->belipk)->whereNotNull('tglbayar')->get()->count();

        $gabung_tglbayar = implode(', ', $tglbayar_list); 
           
        $dts_tglbayar = $gabung_tglbayar;
        if($gabung_tglbayar == null){
            $dts_tglbayar = '-';
        }



        if ($total_jmlbayar == 0.00) {
            $dts_jmlbayar = '-';
        } else {
            $dts_jmlbayar = number_format((float)$total_jmlbayar, 0, ',', '.');
        }

            if($d->pkab == 2){
				$dt_index = "<p style='color:red;'>" . $index . "</p>";
				$dt_tanggal = "<p style='color:red;'>" . $d->tanggal . "</p>";
				$dt_nobukti = "<p style='color:red;'>" . $d->nobukti . "</p>";
                $dt_noinv = "<p style='color:red;'>" . $d->noinv . "</p>";
				$dt_supnm = "<p style='color:red;'>" . $d->supnm . "</p>";
				$dt_curid = "<p style='color:red;'>" . $d->curid . "</p>";
				$dt_totbeli = "<p style='color:red;'>" . number_format($d->totbeli, 2, ',', '.') . "</p>";
				$dt_term = "<p style='color:red;'>" . $d->abnm . "</p>";
				$dt_tglbayar = "<p style='color:red;'>" . $dts_tglbayar . "</p>";
				$dt_cg = "<p style='color:red;'>" . $dts_cg . "</p>";
				$dt_jmlbayar = "<p style='color:red;'>" . $dts_jmlbayar. "</p>";
				$dt_login = "<p style='color:red;'>" . strtoupper($d->login) . "</p>";
			}else{
				$dt_index = $index;
				$dt_tanggal = $d->tanggal;
				$dt_nobukti = $d->nobukti;
				$dt_noinv = $d->noinv;
				$dt_supnm = $d->supnm;
                $dt_curid = $d->curid;
				$dt_totbeli = number_format($d->totbeli, 2, ',', '.');
				$dt_term = $d->abnm;
                $dt_tglbayar = $dts_tglbayar;
                $dt_cg = $dts_cg;
                $dt_jmlbayar = $dts_jmlbayar;
				$dt_login = strtoupper($d->login);
			}

            $row[] = array(
                'index' => $dt_index,
                'belipk' => $d->belipk,
                'pkab' => $d->pkab,
                'nobukti' => $dt_nobukti,
                'tanggal' => $dt_tanggal,
                'noinv' => $dt_noinv,
                'supnm' => $dt_supnm,
                'curid' => $dt_curid,
                'term' => $dt_term,
                'tglbayar' => $dt_tglbayar,
                'TotCountTglByr' => $TotCountTglByr,
                'cg' => $dt_cg,
                'jmlbayar' => $dt_jmlbayar,
                'totbeli' => $dt_totbeli,
                'user' => $dt_login,
            );
            $index++;
        }
        $result = array_merge($result, array('rows' => $row));
        return json_encode($result);
    }

    public function GetLastPembelianCT()
	{

        $getemail = Session::get('username');
        $userpk = DB::table('user')->where('username', '=', $getemail)->value('userpk');

		$lastNoBukti = DB::table('beli')->max('nobukti');
		$newNoBukti = $lastNoBukti ? $lastNoBukti + 1 : 1;

		$lastId = DB::table('beli')->insertGetId([
			'tglinv' => Carbon::now(),
			'userpk' => $userpk,
			'nobukti' => $newNoBukti,
		]);

        $lastNotran = DB::table('notran')->where('tblnm', '=', 'beli')->where('tblket', '=', 'beli')->max('no');
		$newNotran = $lastNotran ? $lastNotran + 1 : 1;

        $lastNotran = DB::table('notran')
        ->where('tblnm', '=', 'beli')
        ->where('tblket', '=', 'beli')
        ->update([
			'no' => $newNotran,
		]);

		return response()->json([
			'belipk' => $lastId,
		]);
	}

    public function BackPembelian(Request $request)
	{
		$belipk = $request->input('belipk');
		$segment3 = $request->input('segment3');

		if ($segment3 == 'add-pembelian') {
            $getprdtpk = DB::table('belidt')->where('belipk', $belipk)->pluck('prdtpk');
    
            $kembalikansts = DB::table('prdt')->whereIn('prdtpk', $getprdtpk)->update(['stspo' => null]);

			$deletebelipk = DB::table('beli')->where('belipk', $belipk)->delete();
			$deletebelidtpk = DB::table('belidt')->where('belipk', $belipk)->delete();
		}

		return response()->json([
			'status' => 'success',
			'belipk' => $belipk,
			'segment3' => $segment3,
		]);
	}

    function AddPembelian($belipk)
	{
        if (!Session::get('userpk')) return redirect('/');
        $getemail = Session::get('username');
		$getuserpk = DB::table('user')->where('username', $getemail)->value('userpk');

        $dt_beli = DB::table('beli')
            ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
            ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
            ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
            ->leftJoin('cur', 'cur.curpk', '=', 'beli.curpk')
            ->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
            ->leftJoin('mif', 'mif.mifpk', '=', 'beli.mifpk')
            ->select(
                'beli.belipk',
                'beli.nobukti',
                'beli.noinv',
                'beli.tglinv',
                'user.userpk',
                'user.login',
                'sup.suppk',
                'sup.supnm',
                'ab.abpk',
                'ab.abnm',
                'mif.mifpk',
                'mif.mifnm',
                'beli.totbeli',
                'kel.kelpk',
                'kel.kelnm',
                'beli.stok',
                'beli.posting',
                'cur.curpk',
                'cur.curid',
                DB::raw('DATE_FORMAT(beli.tglinv, "%d %b %Y") as tanggal'),
            )
            ->where('beli.belipk', '=', $belipk)
            ->first();

		return view('menu.pembelian-cash-tempo.detail-pembelian', compact('dt_beli', 'getuserpk'));
	}

    public function PageDetailPembelian($belipk)
    {
        if (!Session::get('userpk')) return redirect('/');
        $getemail = Session::get('username');
		$getuserpk = DB::table('user')->where('username', $getemail)->value('userpk');

        $dt_beli = DB::table('beli')
            ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
            ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
            ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
            ->leftJoin('cur', 'cur.curpk', '=', 'beli.curpk')
            ->leftJoin('kel', 'kel.kelpk', '=', 'beli.kelpk')
            ->leftJoin('mif', 'mif.mifpk', '=', 'beli.mifpk')
            ->select(
                'beli.belipk',
                'beli.nobukti',
                'beli.noinv',
                'beli.tglinv',
                'beli.userpk',
                'user.userpk',
                'user.login',
                'beli.suppk',
                'sup.suppk',
                'sup.supnm',
                'beli.abpk',
                'ab.abpk',
                'ab.abnm',
                'beli.totbeli',
                'beli.posting',
                'beli.curpk',
                'cur.curpk',
                'cur.curid',

                'beli.kelpk',
                'kel.kelpk',
                'kel.kelnm',
                'beli.mifpk',
                'mif.mifpk',
                'mif.mifnm',
                DB::raw('DATE_FORMAT(beli.tglinv, "%d %b %Y") as tanggal'),
            )
            ->where('beli.belipk', '=', $belipk)
            ->first();

        return view('menu.pembelian-cash-tempo.detail-pembelian', compact('dt_beli', 'getuserpk'));
    }

    public function ConfirmPostingPembelian($belipk)
    {
		$updated = DB::table('beli')
			->where('belipk', $belipk)
			->update([
				'posting' => 1,
			]);

		if ($updated == 0) {
			return response()->json(['success' => false, 'message' => "Beli with belipk $belipk not found"]);
		}

        return response()->json([
            'success' => true,
            'message' => 'Pembelian berhasil diposting',
            'dataPR' => $updated,
            'code' => 202
        ]);
    }

    public function GetBelidt(Request $request, $belipk)
    {
        $page = $request->input('page') ?? '1';
        $rows = $request->input('rows') ?? '100';

        $data_belidt = DB::table('belidt')
            ->select(
                'belidt.belidtpk',
                'belidt.belipk',
                'belidt.brgnm',
                'belidt.unit',
                'belidt.hrgbeli',
                'belidt.jmlbeli',
            )
            ->where('belidt.belipk', '=', $belipk)->orderBy('belidt.belidtpk', 'ASC');

        $AllDataBelidt = $data_belidt->get();

        $offset = ($page - 1) * $rows;
        $data_belidt = $data_belidt->skip($offset)->take($rows)->get();

        $result = array();
        $result['total'] = $AllDataBelidt->count();
        $result['page'] = $page;
        $result['rows'] = $rows;
        $result['offset'] = $offset;
        $row = array();
        $index = $offset + 1;

        foreach ($data_belidt as $d) {
            $row[] = array(
                'index' => $index,
                'belidtpk' => $d->belidtpk,
                'belipk' => $d->belipk,
                'brgnm' => trim($d->brgnm),
                'unit' => strtoupper($d->unit),
                'hrgbeli' => number_format($d->hrgbeli, 0, '.', ','),
                'jmlbeli' => $d->jmlbeli,
                'total' => number_format($d->jmlbeli * $d->hrgbeli, 0, '.', ','),
            );
            $index++;
        }
        $result = array_merge($result, array('rows' => $row));
        return json_encode($result);
    }

    public function InsertBelidt(Request $request, $belipk)
    {
        $brgnm = $request->input('brgnm');
        $unit = $request->input('unit');
        $hrgbeli = $request->input('hrgbeli');
        $jmlbeli = $request->input('jmlbeli');

        DB::table('belidt')->insert([
            'belipk' => $belipk,
            'brgnm' => strtoupper($brgnm),
            'unit' => strtoupper($unit),
            'hrgbeli' => $hrgbeli,
            'jmlbeli' => $jmlbeli,
        ]);

        return response()->json([
            'belipk' => $belipk,
            'brgnm' => $brgnm,
            'unit' => $unit,
            'hrgbeli' => $hrgbeli,
            'jmlbeli' => $jmlbeli,
        ]);
    }

    public function UpdateBelidt(Request $request)
    {
        $belidtpk = $request->input('belidtpk');
        $brgnm = $request->input('brgnm') ?? '';
        $unit = $request->input('unit') ?? '';
        // $hrgbeli = $request->input('hrgbeli') ?? '';
        $hrgbeli = str_replace(',', '', $request->input('hrgbeli') ?? '');
        $jmlbeli = $request->input('jmlbeli') ?? '';

        DB::table('belidt')
            ->where('belidtpk', $belidtpk)
            ->update([
                'brgnm' => strtoupper($brgnm),
                'unit' => strtoupper($unit),
                'hrgbeli' => $hrgbeli,
                'jmlbeli' => $jmlbeli,
            ]);

        return response()->json([
            'respon' => 'ini sukses',
            'brgnm' => $brgnm,
            'unit' => $unit,
            'hrgbeli' => $hrgbeli,
            'jmlbeli' => $jmlbeli,
        ]);
    }

    public function SaveEditHeaderBelidt(Request $request)
    {
        try {
            $tglinv = $request->input('tglinv') ?? '00/00/0000';
            $tglinv = Carbon::createFromFormat('d/m/Y', $tglinv)->format('Y-m-d');
            $belipk = $request->input('belipk');
            $abpk = $request->input('abpk');
            $suppk = $request->input('suppk');
            $curpk = $request->input('curpk');
            $kelpk = $request->input('kelpk');
            $mifpk = $request->input('mifpk');
            $noinv = $request->input('noinv');


            DB::table('beli')
                ->where('belipk', $belipk)
                ->update([
                    'tglinv' => $tglinv,
                    'abpk' => $abpk,
                    'suppk' => $suppk,
                    'curpk' => $curpk,
                    'kelpk' => $kelpk,
                    'mifpk' => $mifpk,
                    'noinv' => $noinv,
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Data updated successfully!',
                'data' => [
                    'belipk' => $belipk,
                    'abpk' => $abpk,
                    'suppk' => $suppk,
                    'curpk' => $curpk,
                    'kelpk' => $kelpk,
                    'mifpk' => $mifpk,
                    'noinv' => $noinv,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function DeleteBelidt(Request $request)
    {

        $ids = $request->input('ids');

        if (!is_array($ids) || count($ids) === 0) {
            return response()->json([
                'success' => false,
                'msg' => "<div class='text-infomerah' style='width:415px;'>" . $ids . "Invalid data received &nbsp; &#x2573;</div>",
            ]);
        }

        // Proses pembaruan untuk setiap ID
        foreach ($ids as $id) {
            // Ambil data dulu sebelum dihapus
            $row = DB::table('belidt')->where('belidtpk', $id)->first();

            if (!$row) {
                return response()->json([
                    'success' => false,
                    'msg' => "<div class='text-infomerah' style='width:415px;'>Data with ID: $id not found. &#x2573;</div>",
                ]);
            }

            // Hapus baris setelah ambil datanya
            $deleted = DB::table('belidt')->where('belidtpk', $id)->delete();

            // Update ke prdt hanya kalau kolom 'prdt' ada
            if ($row->prdtpk ?? false) {
                DB::table('prdt')->where('prdtpk', $row->prdtpk)->update([
                    'stspo' => null,
                ]);
            }

            if (!$deleted) {
                return response()->json([
                    'success' => false,
                    // 'msg' => "<div class='text-infomerah' style='width:415px;'>Cannot delete data material for ID: $id &nbsp; &#x2573;</div>",
                    'message' => 'Cannot delete data data for ID:'. $id .'&nbsp;',
                ]);
            }
        }

        // Jika semua pembaruan berhasil
        // return response()->json([
        //     'success' => true,
        //     'msg' => "<div class='text-infohijau' style='width:415px;'>Delete successfully. &nbsp; &#10004;</div>",
        // ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Item deleted successfully!',
        ]);
    }

    public function GetDetPrInPembelian(request $request)
    {
        $getemail = Session::get('username');
        $userpk = DB::table('user')->where('username', '=', $getemail)->value('userpk');

		if (!$userpk) {
			return response()->json(['error' => 'Userpk not found in session.'], 400);
		}

		$deppk = DB::table('user')->where('userpk', '=', $userpk)->value('deppk');
		$depreq = DB::table('user')->leftJoin('dep', 'dep.deppk', '=', 'user.deppk')->where('userpk', '=', $userpk)->value('req');

        $searchByInputPr = $request->searchByInputPr;
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
                    DB::raw('DATE_FORMAT(pr.tglpr, "%d %b %Y") as tanggal'),
                    'prdt.prdtpk',
                    'prdt.prpk',
                    'prdt.brgnm',
                    'prdt.unit',
                    'prdt.jmlbeli',
                    'prdt.stspo',
                )
                // ->where('pr.posting', '=', 1)
                // ->where('pr.userpk', $userpk)
                // ->where('prdt.stspo', null);
                ->where(function ($query) use ($deppk, $userpk) {
                    $query->where(function ($q) use ($deppk) {
                        $q->where('pr.deppk', '=', $deppk)
                        ->where('pr.posting', '=', 1); 
                    })
                    ->orWhere(function ($q) use ($userpk) {
                        $q->where('pr.userpk', '=', $userpk); 
                    });
                });

                // })->where('prdt.stspo', null);

        if (!empty($searchByInputPr)) {
            $concatenatedValue = DB::raw("CONCAT(prdt.brgnm,'', pr.nopr,'', user.login,'')");
            $dt_prdt = $dt_prdt->where(DB::raw($concatenatedValue), 'like', '%' . strtolower($searchByInputPr) . '%');
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

            // if($d->stspo == null){
                $dt_index = $index;
				// $dt_userbuat = $d->userbuat;
				$dt_tanggal = $d->tanggal;
				$dt_nopr = str_pad($d->nopr, 6, '0', STR_PAD_LEFT);
				$dt_login = strtoupper($d->login);
				$dt_depnm = strtoupper($d->depnm ?? '-');
				$dt_brgnm = strtoupper($d->brgnm);
				$dt_unit = $d->unit ?? '-';
				$dt_jmlbeli = $d->jmlbeli ?? '-';
			// }else{
			// 	$dt_index = "<span style='color:#919191'>" . $index . "</span>";
			// 	// $dt_userbuat = "<span style='color:#35962a'>" . $d->userbuat . "</span>";
			// 	$dt_tanggal = "<span style='color:#919191'>" . $d->tanggal . "</span>";
			// 	$dt_nopr = "<span style='color:#919191'>" . str_pad($d->nopr, 6, '0', STR_PAD_LEFT) . "</span>";
			// 	$dt_login = "<span style='color:#919191'>" . strtoupper($d->login ?? '-') . "</span>";
			// 	$dt_depnm = "<span style='color:#919191'>" . $d->depnm ?? '-'. "</span>";
			// 	$dt_brgnm = "<span style='color:#919191'>" . strtoupper($d->brgnm) . "</span>";
			// 	$dt_unit = "<span style='color:#919191'>" . $d->unit . "</span>";
			// 	$dt_jmlbeli = "<span style='color:#919191'>" . $d->jmlbeli . "</span>";
			// }

            $row[] = array(
                'stspo' => $d->stspo,
                'prdtpk' => $d->prdtpk,
                'prpk' => $d->prpk,
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

    public function AddPrToPembelian(Request $request)
    {
        $belipk = $request->input('belipk');
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
            DB::table('belidt')->insert([
                'belipk' => $belipk,
                'prdtpk' => $item->prdtpk,
                'brgpk' => strtoupper($item->brgpk),
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
            // 'message' => 'Selected PR detail items successfully inserted to Pembelian detail.',
            'message' => 'Data inserted successfully!',
            'inserted_items' => $detailItems->count(),
        ]);
    }

    public function GetTotPembelian($belipk)
    {
        $gettotal = DB::table('belidt')
            ->select('hrgbeli', 'jmlbeli')
            ->where('belipk', $belipk)
            ->get();

        $total = 0;
        foreach ($gettotal as $row) {
            $total += (float)$row->hrgbeli * (float)$row->jmlbeli;
        }

        return response()->json([
            'success' => true,
            'total_hrg_beli' => number_format($total, 2, '.', ','),
            'data' => $gettotal
        ]);
    }

    public function updateTotBeli(Request $request)
    {
        $request->validate([
            'belipk' => 'required|numeric',
            'totbeli' => 'required|numeric'
        ]);

        $updated = DB::table('beli')
            ->where('belipk', $request->belipk)
            ->update(['totbeli' => $request->totbeli]);

        if ($updated) {
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan atau tidak berubah.']);
        }
    }
}

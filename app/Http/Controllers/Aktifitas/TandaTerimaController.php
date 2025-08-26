<?php

namespace App\Http\Controllers\Aktifitas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use PDF;
use Carbon\Carbon;

class TandaTerimaController extends Controller
{
    function PageTandaTerima(){
        return view('menu.tanda-terima.list-tanda-terima');
    }

    function GetListTandaTerima(Request $request){

        $userpk =  Session::get('userpk');
        // $guserpk = Session::get('guserpk');

        if (!$userpk) {
            return response()->json(['error' => 'Userpk not found in session.'], 400);
        }


        $tahun = date("Y");
        $page = $request->input('page') ?? '1';
        $rows = $request->input('rows') ?? '100';
        $searchByInput = $request->searchByInput;
        $filterByMonth = $request->filterByMonth ?? date('m');
        $filterByYear = $request->filterByYear;
        $sortlistByDate = $request->sortlistByDate;
        if (!empty($filterByYear)) $tahun = $filterByYear;

        $dt_tt = DB::table('tt')            
            ->leftJoin('user', 'user.userpk', '=', 'tt.userpk')
            ->leftJoin('beli', 'beli.belipk', '=', 'tt.belipk')
            ->select([
                'tt.ttpk',
                'tt.nobukti',
                'tt.tgl',
                DB::raw('DATE_FORMAT(tt.tgl, "%d %b %Y") as tanggal'),
                'tt.penerima',
                'tt.ket',
                'user.userpk',
                'tt.belipk',
            ]);

            if($userpk == 11){
                $dt_tt->whereIn('tt.userpk', ['7', '11']);
            }else{
                $dt_tt->where('tt.userpk', $userpk);
            }
           


        if (!empty($searchByInput)) {
            $concatenatedValue = DB::raw("CONCAT(
                COALESCE(tt.nobukti, ''),
                COALESCE(tt.penerima, ''),
                COALESCE(tt.ket, '')
            )");

            $dt_tt = $dt_tt->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
        }

        if (!empty($filterByYear)) {
            $dt_tt = $dt_tt->whereYear('tt.tgl', '=', $filterByYear);
        } else {
            $dt_tt = $dt_tt->whereYear('tt.tgl', '=', $tahun);
        }

        if (!empty($filterByMonth)) {
            $dt_tt = $dt_tt->whereMonth('tt.tgl', '=', $filterByMonth);
        }

        if ($sortlistByDate == 11) {
            $dt_tt = $dt_tt->orderBy('tt.tgl', 'desc');
        } elseif ($sortlistByDate == 12) {
            $dt_tt = $dt_tt->orderBy('tt.tgl', 'asc');
        } else {
            $dt_tt = $dt_tt->orderBy('tt.tgl', 'desc');
        }

        $AllDataTT = $dt_tt->get();
        $offset = ($page - 1) * $rows;
        $dt_tt = $dt_tt->skip($offset)->take($rows)->get();

        $result = array();
        $result['total'] = $AllDataTT->count();
        $result['page'] = $page;
        $result['rows'] = $rows;
        $result['offset'] = $offset;
        $row = array();
        $index = $offset + 1;

        foreach ($dt_tt as $d) {

            $dt_belipk = $d->belipk;
            $dt_index = $index;
            $dt_tanggal = $d->tanggal;
            $dt_nobukti = $d->nobukti;
            $dt_penerima = $d->penerima;
            $dt_ket = $d->ket;
            $dt_login = $d->userpk;

            $row[] = array(
                'ttpk' => $d->ttpk,
                'belipk' => $dt_belipk,
                'index' => $dt_index,
                'nobukti' => $dt_nobukti,
                'tanggal' => $dt_tanggal,
                'penerima' => $dt_penerima,
                'ket' => $dt_ket,
            );
            $index++;
        }
        $result = array_merge($result, array('rows' => $row));
        return json_encode($result);
    }

    function PageDetailTt($ttpk){
         $dt_tt = DB::table('tt')
            ->where('ttpk', '=', $ttpk)
            ->first();

        $dt_ttdt = DB::table('ttdt')
            ->where('ttpk', '=', $ttpk)
            ->get();

        return view('menu.tanda-terima.detail-tt', compact('dt_tt', 'dt_ttdt'));
    }

    public function getDetailTt(Request $request, $ttpk){
        $page = $request->input('page') ?? '1';
        $rows = $request->input('rows') ?? '100';

        $data_ttdt = DB::table('ttdt')->where('ttpk', '=', $ttpk)->orderBy('ttdtpk', 'ASC');

        $AllDataPodt = $data_ttdt->get();

        $offset = ($page - 1) * $rows;
        $data_ttdt = $data_ttdt->skip($offset)->take($rows)->get();

        $result = array();
        $result['total'] = $AllDataPodt->count();
        $result['page'] = $page;
        $result['rows'] = $rows;
        $result['offset'] = $offset;
        $row = array();
        $index = $offset + 1;

        foreach ($data_ttdt as $d) {
            $row[] = array(
                'ttdtpk' => $d->ttdtpk,
                'index' => $index,
                'ttpk' => $d->ttpk,
                'brgnm' => trim($d->brgnm),
                'satuan' => strtoupper($d->satuan),
                'jumlah' => $d->jumlah,
            );
            $index++;
        }
        $result = array_merge($result, array('rows' => $row));
        return json_encode($result);
    }

    public function PrintTandaTerima($ttpk){
        ini_set('memory_limit', '512M'); // atau 1024M jika masih error
		set_time_limit(300);

        $dt_tt = DB::table('tt')->where('ttpk', '=', $ttpk)->first();
		$dt_ttdt = DB::table('ttdt')->where('ttpk', '=', $ttpk)->orderby('ttdtpk', 'ASC')->get();
		
		$pdf = PDF::loadview('menu.tanda-terima.print', compact('dt_tt', 'dt_ttdt'));
		return $pdf->stream();
    }

    public function BackTT(Request $request)
	{
		$ttpk = $request->input('ttpk');
		$segment3 = $request->input('segment3');

		if ($segment3 == 'add-tt') {
			DB::table('tt')->where('ttpk', $ttpk)->delete();
			DB::table('ttdt')->where('ttpk', $ttpk)->delete();
		}

		return response()->json([
			'status' => 'success',
			'ttpk' => $ttpk,
			'segment3' => $segment3,
		]);
	}

    public function GetLastTT()
    {
        $getemail = Session::get('username');
        if (!$getemail) {
            return response()->json(['error' => 'Session expired. Please login again.'], 401);
        }

        $userpk = DB::table('user')->where('username', '=', $getemail)->value('userpk');
        if (!$userpk) {
            return response()->json(['error' => 'User not found.'], 404);
        }

        $lastNoBuktiRaw = DB::table('tt')->whereNotNull('nobukti')->max('nobukti');
        $newNoBuktiInt = $lastNoBuktiRaw ? intval($lastNoBuktiRaw) + 1 : 1;
        $newNoBukti = str_pad($newNoBuktiInt, 6, '0', STR_PAD_LEFT);

        $lastId = DB::table('tt')->insertGetId([
            'userpk' => $userpk,
            'nobukti' => $newNoBukti,
        ]);

        return response()->json(['ttpk' => $lastId]);
    }

    public function PageAddTt($ttpk)
	{
		$dt_tt = DB::table('tt')
			->leftJoin('user', 'user.userpk', '=', 'tt.userpk')
			->leftJoin('beli', 'beli.belipk', '=', 'tt.belipk')
			->leftJoin('keluar', 'keluar.keluarpk', '=', 'tt.keluarpk')
			->select(
				'tt.ttpk',
				'tt.nobukti',
				'user.userpk',
				'keluar.keluarpk',
				'tt.tgl',
				'tt.penerima',
				'tt.ket',
			)
			->where('tt.ttpk', '=', $ttpk)
			->first();

		return view('menu.tanda-terima.det-tt', compact('dt_tt'));
	}

    public function PageDetailTt2($ttpk)
	{
		// $getemail = Session::get('username');
        // $userpk = DB::table('user')->where('username', '=', $getemail)->value('userpk');
		if (!Session::get('userpk')) return redirect('/');

		$getemail = Session::get('username');
		$getuserpk = DB::table('user')->where('username', $getemail)->value('userpk');

		// // Kirim ke view
		// $view['userpk'] = $userpk;

		$dt_tt = DB::table('tt')
			->leftJoin('user', 'user.userpk', '=', 'tt.userpk')
			->leftJoin('beli', 'beli.belipk', '=', 'tt.belipk')
			->leftJoin('keluar', 'keluar.keluarpk', '=', 'tt.keluarpk')
			->select(
				'tt.ttpk',
				'tt.nobukti',
				'user.userpk',
				'keluar.keluarpk',
				'tt.tgl',
				'tt.penerima',
				'tt.ket',
			)
			->where('tt.ttpk', '=', $ttpk)
			->first();

		return view('menu.tanda-terima.det-tt', compact('dt_tt', 'getuserpk'));
	}

    public function GetTtdt(Request $request, $ttpk)
    {
        $page = $request->input('page') ?? '1';
        $rows = $request->input('rows') ?? '100';

        $data_ttdt = DB::table('ttdt')
            ->leftJoin('beli', 'beli.belipk', '=', 'ttdt.belipk')
            ->leftJoin('po', 'po.popk', '=', 'ttdt.popk')
            ->select(
                'ttdt.ttdtpk',
                'ttdt.ttpk',
                'ttdt.brgnm',
                'ttdt.satuan',
                'ttdt.jumlah',
                'beli.belipk',
                'po.popk',
                'beli.noinv as noinvbeli',
                'beli.nobukti as nobuktibeli',
                DB::raw('DATE_FORMAT(beli.tglinv, "%d %b %Y") as tanggalbeli'),
                'po.nopo as noinvpo',
                'po.nobukti as nobuktipo',
                 DB::raw('DATE_FORMAT(po.tglpo, "%d %b %Y") as tanggalpo'),
            )
            ->where('ttdt.ttpk', '=', $ttpk);

        $AllDataTtdt = $data_ttdt->get();

        $offset = ($page - 1) * $rows;
        $data_ttdt = $data_ttdt->skip($offset)->take($rows)->get();

        $result = array();
        $result['total'] = $AllDataTtdt->count();
        $result['page'] = $page;
        $result['rows'] = $rows;
        $result['offset'] = $offset;
        $row = array();
        $index = $offset + 1;

        foreach ($data_ttdt as $d) {
            if($d->belipk != null && $d->popk == null){
                $noinv = trim($d->noinvbeli);
                $nobukti = trim($d->nobuktibeli);
                $tanggal = $d->tanggalbeli;
            }elseif($d->belipk == null && $d->popk != null){
                $noinv = trim($d->noinvpo);
                $nobukti = trim($d->nobuktipo);
                $tanggal = $d->tanggalpo;
            }else{
                $noinv = '-';
                $nobukti = '-';
                $tanggal = '-';
            }

            $row[] = array(
                'index' => $index,
                'ttdtpk' => $d->ttdtpk,
                'ttpk' => $d->ttpk,
                'brgnm' => trim($d->brgnm),
                'satuan' => trim($d->satuan),
                'jumlah' => number_format(trim($d->jumlah), 0, '.', ','),
                // 'noinv' => trim($d->noinv ?? '-'),
                // 'nobukti' => trim($d->nobukti ?? '-'),
                // 'tanggal' => $d->tanggal ?? '-',
                'noinv' => $noinv,
                'nobukti' => $nobukti,
                'tanggal' => $tanggal,
            );
            $index++;
        }
        $result = array_merge($result, array('rows' => $row));
        return json_encode($result);
    }

    public function InsertTtdt(Request $request, $ttpk)
    {
        $brgnm = $request->input('brgnm');
        $satuan = $request->input('satuan');
        $jumlah = $request->input('jumlah');

        DB::table('ttdt')->insert([
            'ttpk' => $ttpk,
            'brgnm' => strtoupper($brgnm),
            'satuan' => strtoupper($satuan),
            'jumlah' => $jumlah,
        ]);

        return response()->json([
            'ttpk' => $ttpk,
            'brgnm' => $brgnm,
            'satuan' => $satuan,
            'jumlah' => $jumlah,
        ]);
    }

    public function UpdateTtdt(Request $request)
    {
        $ttdtpk = $request->input('ttdtpk');
        $brgnm = $request->input('brgnm') ?? '';
        $satuan = $request->input('satuan') ?? '';
        $jumlah = $request->input('jumlah') ?? '';

        DB::table('ttdt')
            ->where('ttdtpk', $ttdtpk)
            ->update([
                'brgnm' => strtoupper($brgnm),
                'satuan' => strtoupper($satuan),
                'jumlah' => $jumlah,
            ]);

        return response()->json([
            'respon' => 'ini sukses',
            'brgnm' => $brgnm,
            'satuan' => $satuan,
            'jumlah' => $jumlah,
        ]);
    }

    public function SaveEditHeaderTtdt(Request $request)
    {
        try {
            $tgl = $request->input('tgl') ?? '00/00/0000';
            $tgl = Carbon::createFromFormat('d/m/Y', $tgl)->format('Y-m-d');
            $ttpk = $request->input('ttpk');
            $penerima = $request->input('penerima');
            $ket = $request->input('ket');


            DB::table('tt')
                ->where('ttpk', $ttpk)
                ->update([
                    'tgl' => $tgl,
                    'penerima' => strtoupper($penerima),
                    'ket' => strtoupper($ket),
                ]);

            // Kembalikan respons JSON
            return response()->json([
                'status' => 'success',
                'message' => 'Data updated successfully!',
                'data' => [
                    'ttpk' => $ttpk,
                    'penerima' => $penerima,
                    'ket' => $ket,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function DeleteTtdt(Request $request)
    {

        $ids = $request->input('ids');

        if (!is_array($ids) || count($ids) === 0) {
            return response()->json([
                'success' => false,
                // 'msg' => "<div class='text-infomerah' style='width:415px;'>" . $ids . "Invalid data received &nbsp; &#x2573;</div>",
                 'message' => 'Cannot delete data data for ID:'. $id .'&nbsp;',
            ]);
        }

        // Proses pembaruan untuk setiap ID
        foreach ($ids as $id) {
            $result = DB::table('ttdt')->where('ttdtpk', '=', $id)->delete();

            if (!$result) {
                return response()->json([
                    'success' => false,
                    'msg' => "<div class='text-infomerah' style='width:415px;'>Cannot process replace data material for ID: $id &nbsp; &#x2573;</div>",
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

    public function GetDetBeliOnTT(request $request)
    {
        $getemail = Session::get('username');
        $userpk = DB::table('user')->where('username', '=', $getemail)->value('userpk');

        if (!$userpk) {
            return response()->json(['error' => 'Userpk not found in session.'], 400);
        }
        $deppk = DB::table('user')->where('userpk', '=', $userpk)->value('deppk');

        $tahun = date("Y");
        $searchByInput = $request->searchByInputOrderPembelian;
        $filterByMonth = $request->filterByMonth ?? date('m');
        $filterByYear = $request->filterByYear;
        if (!empty($filterByYear)) $tahun = $filterByYear;
        $page = $request->input('page') ?? 1;
        $rows = $request->input('rows') ?? 100;

        $dt_podt = DB::table('belidt')
                ->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
                ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
                ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
                ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
                ->select(
                    'beli.belipk',
                    'beli.nobukti',
                    'beli.noinv',
                    'beli.tglinv',
                    'beli.userpk',
                     DB::raw('DATE_FORMAT(beli.tglinv, "%d %b %Y") as tanggal'),
                    'user.userpk',
                    'user.login',
                    'sup.suppk',
                    'sup.supnm',
                    'ab.abpk',
                    'ab.abnm',
                    'beli.totbeli',
                    'belidt.belidtpk',
                    'belidt.belipk',
                    'belidt.brgnm',
                    'belidt.unit',
                    'belidt.hrgbeli',
                    'belidt.jmlbeli',
                    'belidt.jmlhrg',
                    'belidt.tglbayar',
                    'belidt.jmlbayar',
                    'belidt.cg',
                    'belidt.nobg',
                    'belidt.tglaju',
                )->where('belidt.hrgbeli', '>', 0);

        if (in_array($deppk, ['1', '3'])) {
            $dt_podt = $dt_podt;
        } else {
            $dt_podt = $dt_podt->where('beli.userpk', '=', $userpk);
        }

        if (!empty($searchByInput)) {
            $concatenatedValue = DB::raw("CONCAT(belidt.brgnm,'',beli.nobukti,'',beli.noinv,'',user.login,'',sup.supnm,'')");
            $dt_podt = $dt_podt->where(DB::raw($concatenatedValue), 'like', '%' . strtolower($searchByInput) . '%');
        }

        // Filter berdasarkan tahun
        if (!empty($filterByYear)) {
            $dt_podt = $dt_podt->whereYear('beli.tglinv', '=', $filterByYear);
        } else {
            // Jika tidak ada filter tahun, gunakan tahun saat ini
            $dt_podt = $dt_podt->whereYear('beli.tglinv', '=', $tahun);
        }

        // Filter berdasarkan bulan
        if (!empty($filterByMonth)) {
            $dt_podt = $dt_podt->whereMonth('beli.tglinv', '=', $filterByMonth);
        }

        $dt_podt = $dt_podt->orderBy('belidt.belidtpk', 'DESC');
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
            $dt_inv = "<span style='color:#000000'>" . $d->noinv . "</span>";
            $dt_nobukti = "<span style='color:#000000'>" . $d->nobukti . "</span>";
            $dt_userbuat = "<span style='color:#000000'>" . strtoupper($d->login ?? '-') . "</span>";
            $dt_supnm = "<span style='color:#000000'>" . $d->supnm ?? '-'. "</span>";
            $dt_brgnm = "<span style='color:#000000'>" . strtoupper($d->brgnm) . "</span>";
            $dt_unit = "<span style='color:#000000'>" . $d->unit . "</span>";
            $dt_hrgbeli = "<span style='color:#000000'>" . number_format($d->hrgbeli, 0, '.', ',')  . "</span>";
            $dt_jmlbeli = "<span style='color:#000000'>" . number_format($d->jmlbeli, 2, '.', ',') . "</span>";
            $dt_totbeli = "<span style='color:#000000'>" . number_format($d->totbeli, 0, '.', ',') . "</span>";


            $row[] = array(
                'belidtpk' => $d->belidtpk,
                'belipk' => $d->belipk,
                'tanggal' => $dt_tanggal,
				'noinv' => $dt_inv,
				'nobukti' => $dt_nobukti,
				'userbuat' => $dt_userbuat,
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

    public function AddBeliToTt(Request $request)
    {
        $ttpk = $request->input('ttpk');
        $selectedBelidtpks = $request->input('belidtpk');

        if (empty($selectedBelidtpks) || !is_array($selectedBelidtpks)) {
            return response()->json(['status' => 'error', 'message' => 'No Beli detail items selected.']);
        }

        $detailItems = DB::table('belidt')
            ->whereIn('belidtpk', $selectedBelidtpks)
            ->get();

        if ($detailItems->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'Selected Beli detail items not found.']);
        }

        foreach ($detailItems as $item) {
            DB::table('ttdt')->insert([
                'ttpk' => $ttpk,
                'belipk' => $item->belipk,
                'belidtpk' => $item->belidtpk,
                'brgnm' => trim(strtoupper($item->brgnm)),
                'satuan' => trim(strtoupper($item->unit)),
                'jumlah' => trim($item->jmlbeli),
            ]);
        }

        return response()->json([
       		'success' => true,
            'message' => 'Data inserted successfully!',
            'inserted_items' => $detailItems->count(),
        ]);
    }

    public function AddPoToTt(Request $request)
    {
        $ttpk = $request->input('ttpk');
        $selectedPodtpks = $request->input('podtpk');

        if (empty($selectedPodtpks) || !is_array($selectedPodtpks)) {
            return response()->json(['status' => 'error', 'message' => 'No PO detail items selected.']);
        }

        $detailItems = DB::table('podt')
            ->whereIn('podtpk', $selectedPodtpks)
            ->get();

        if ($detailItems->isEmpty()) {
            return response()->json(['status' => 'error', 'message' => 'Selected PO detail items not found.']);
        }

        foreach ($detailItems as $item) {
            DB::table('ttdt')->insert([
                'ttpk' => $ttpk,
                'popk' => $item->popk,
                'podtpk' => $item->podtpk,
                'brgnm' => trim(strtoupper($item->brgnm)),
                'satuan' => trim(strtoupper($item->unit)),
                'jumlah' => trim($item->jmlbeli),
            ]);
        }

        return response()->json([
       		'success' => true,
            'message' => 'Data inserted successfully!',
            'inserted_items' => $detailItems->count(),
        ]);
    }
}

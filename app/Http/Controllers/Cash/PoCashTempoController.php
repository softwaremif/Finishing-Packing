<?php

namespace App\Http\Controllers\Cash;

use App\Http\Controllers\Controller;
use App\Http\Repository\NotranRepo;
use App\Http\Repository\PoCashTempoRepo;
use App\Http\Requests\PoCashTempoStoreRequest;
use App\Models\Notran;
use App\Models\PoCashTempo;
use App\Models\PoCashTempoDetail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PoCashTempoController extends Controller
{
    protected $poCashTempoRepo, $notranRepo;
    public function __construct(PoCashTempoRepo $poCashTempoRepo, NotranRepo $notranRepo)
    {
        $this->poCashTempoRepo = $poCashTempoRepo;
        $this->notranRepo = $notranRepo;
    }

    public function index()
    {
        return view('menu.purchase-cash-tempo.index');
    }

    public function GetBeliAwal(Request $request)
    {
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
        // $filterByTerm = $request->filterByTerm;
        $sortlistByDate = $request->sortlistByDate;
        if (!empty($filterByYear)) $tahun = $filterByYear;

        $result = $this->poCashTempoRepo->getPoCashTempo($guserpk, $userpk, $tahun, $page, $rows, $searchByInput, $filterByMonth, $filterByYear, $sortlistByDate);
        return json_encode($result);
    }


    public function GetBeli2(Request $request){
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
        	// ->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
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

            if($d->pkab == 2){
				$dt_index = "<p style='color:red;'>" . $index . "</p>";
				$dt_tanggal = "<p style='color:red;'>" . $d->tanggal . "</p>";
				$dt_nobukti = "<p style='color:red;'>" . $d->nobukti . "</p>";
                $dt_noinv = "<p style='color:red;'>" . $d->noinv . "</p>";
				$dt_supnm = "<p style='color:red;'>" . $d->supnm . "</p>";
				$dt_curid = "<p style='color:red;'>" . $d->curid . "</p>";
				$dt_totbeli = "<p style='color:red;'>" . number_format($d->totbeli, 2, ',', '.') . "</p>";
				$dt_term = "<p style='color:red;'>" . $d->abnm . "</p>";
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
				$dt_login = strtoupper($d->login);
			}

            $row[] = array(
                // 'popk' => $d->popk,
                // 'posting' => $d->posting,
                // 'index' => $dt_index,
                // 'tanggal' => $dt_tanggal,
                // 'nopo' => $dt_nopo,
                // 'supnm' => $dt_supnm,
                // 'curid' => $dt_curid,
                // 'totbeli' => $dt_totbeli,
                // 'term' => $dt_term,
                // 'user' => $dt_login,

                'index' => $dt_index,
                'belipk' => $d->belipk,
                'pkab' => $d->pkab,
                'nobukti' => $dt_nobukti,
                'tanggal' => $dt_tanggal,
                'noinv' => $dt_noinv,
                'supnm' => $dt_supnm,
                'curid' => $dt_curid,
                'term' => $dt_term,
                'totbeli' => $dt_totbeli,
                'user' => $dt_login,
            );
            $index++;
        }
        $result = array_merge($result, array('rows' => $row));
        return json_encode($result);
    }

    public function GetBeli(Request $request){
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
        	// ->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
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

        // $dt_beli = DB::table('belidt')            
        // 	->leftJoin('beli', 'beli.belipk', '=', 'belidt.belipk')
        //     ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
        //     ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
        //     ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
        //     ->leftJoin('cur', 'cur.curpk', '=', 'beli.curpk')
        //     ->select([
        //         'beli.belipk',
        //         'beli.nobukti',
        //         'beli.noinv',
        //         'beli.tglinv',
        //         'beli.posting',
        //         'beli.abpk as pkab',
        //         'user.userpk',
        //         'user.guserpk',
        //         'user.login',
        //         'sup.suppk',
        //         'sup.supnm',
        //         'cur.curpk',
        //         'cur.curid',
        //         'cur.curnm',
        //         'ab.abpk',
        //         'ab.abnm',
        //         'beli.totbeli',
        //         'beli.ket',
        //         'belidt.jmlbayar',
        //         DB::raw('DATE_FORMAT(beli.tglinv, "%d %b %Y") as tanggal'),
        //         DB::raw('DATE_FORMAT(belidt.tglbayar, "%d %b %Y") as tglbayar'),
        //     ]);

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

    public function PaidTempo(Request $request, $belipk){
        // Validasi input (wajib)
        $request->validate([
            'codepk' => 'required|in:C,G',
            'jadwal' => 'required|date',
        ]);

        // Ambil semua record yang sesuai dengan belipk
        $dataRows = DB::table('belidt')->where('belipk', $belipk)->get();

        if ($dataRows->isEmpty()) {
            return response()->json(['success' => false, 'message' => "Data tidak ditemukan untuk belipk $belipk"], 404);
        }

        foreach ($dataRows as $row) {
            DB::table('belidt')->where('belidtpk', $row->belidtpk)->update([
                'cg' => $request->codepk,
                'tglbayar' => $request->jadwal,
                'jmlbayar' => $row->jmlhrg,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Proses bayar berhasil',
        ]);
    }

    public function UnPaidTempo(Request $request, $belipk){
        $dataRows = DB::table('belidt')->where('belipk', $belipk)->get();

        if ($dataRows->isEmpty()) {
            return response()->json(['success' => false, 'message' => "Data tidak ditemukan untuk belipk $belipk"], 404);
        }

        foreach ($dataRows as $row) {
            DB::table('belidt')->where('belidtpk', $row->belidtpk)->update([
                'cg' => null,
                'tglbayar' => null,
                'jmlbayar' => 0.00,
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Proses unpaid berhasil',
        ]);
    }

    public function create()
    {
        $ab = DB::table('ab')->get();
        $cur = DB::table('cur')->get();
        return view('menu.purchase-cash-tempo.create', compact('ab', 'cur'));
    }

    public function store(PoCashTempoStoreRequest $request)
    {
        try {
            $data = $request->validated();
            $data['userpk'] = Session::get('userpk');
            $result = $this->poCashTempoRepo->store($data);
            return $result;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update(PoCashTempoStoreRequest $request, $id)
    {
        try {
            $data = $request->validated();
            $data['userpk'] = Session::get('userpk');
            $result = $this->poCashTempoRepo->update($id, $data);
            return $result;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function notran()
    {
        try {
            $notran = $this->notranRepo->generateNotran();
            $this->notranRepo->update();
            return response()->json([
                'success' => true,
                'message' => 'Notran Updated',
                'data' => $notran,
            ]);
        } catch (\Throwable $th) {
            return response()->json([
                'success' => false,
                'message' => 'Notran Updated Failed' . $th->getMessage(),
            ], 500);
        }
    }

    public function show($id)
    {
        $ab = DB::table('ab')->get();
        $cur = DB::table('cur')->get();
        $beli = $this->poCashTempoRepo->model()->where('belipk', $id)->with(['user', 'supplier'])->first();
        return view('menu.purchase-cash-tempo.create', compact('beli', 'ab', 'cur'));
    }
}

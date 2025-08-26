<?php

namespace App\Http\Controllers\Laporan;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use PDF;
use Illuminate\Support\Facades\Session;

class LapPoController extends Controller
{
    function PageLapPo(){
        return view('menu.laporan.po.list-po');
    }

    function getListLapPo(Request $request)
	{

        $getemail = Session::get('username');
        $userpk =  Session::get('userpk');
        $deppk = DB::table('user')->where('username', '=', $getemail)->value('deppk');

		if (!$userpk) {
			return response()->json(['error' => 'Userpk not found in session.'], 400);
		}

		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInput = $request->searchByInput;
		$SelectStart = $request->SelectStart;
		$SelectFinish = $request->SelectFinish;
        $tahun = date("Y");
        $filterByMonth = $request->filterByMonth ?? date('m');
        $filterByYear = $request->filterByYear;
        if (!empty($filterByYear)) $tahun = $filterByYear;

		$data_po = DB::table('podt')
		->leftJoin('po', 'po.popk', '=', 'podt.popk')
        ->leftJoin('user', 'user.userpk', '=', 'po.userpk')
        ->leftJoin('sup', 'sup.suppk', '=', 'po.suppk')
        ->leftJoin('ab', 'ab.abpk', '=', 'po.abpk')
        ->leftJoin('kel', 'kel.kelpk', '=', 'po.kelpk')
        ->leftJoin('cur', 'cur.curpk', '=', 'po.curpk')
		->select(
			'podt.podtpk',
			'po.popk',
            DB::raw('DATE_FORMAT(po.tglpo, "%d %b %Y") as tanggal'),
			'po.nobukti',
			'po.nopo',
            'po.posting',
            'user.userpk',
            'user.login',
            'sup.suppk',
            'sup.supnm',
            'ab.abpk',
            'ab.abnm',
            'kel.kelpk',
            'kel.kelnm',
            'cur.curpk',
            'cur.curnm',
            'cur.curid',
            'podt.popk',
            'podt.brgnm',
			'podt.unit',
            'podt.jmlbeli',
            'podt.hrgbeli',
		)
        ->where('po.posting', '=', 1)
        // ->where('po.userpk', '=', $userpk)
        ->orderBy('po.tglpo', 'DESC')->orderBy('po.popk', 'DESC');

        if (!in_array($userpk, ['6', '57', '60'])) {
            $data_po->where('po.userpk', $userpk);
        }

		if (!empty($searchByInput)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(po.nobukti, ''),
                COALESCE(podt.brgnm, ''),
                 COALESCE(user.login, '')
            )");
			$data_po = $data_po->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}

        if (!empty($SelectStart)) {
			$SelectStart = Carbon::createFromFormat('d/m/Y', $SelectStart)->format('Y-m-d');
			$data_po = $data_po->whereDate('po.tglpo', '>=', $SelectStart);
		} 

		if (!empty($SelectFinish)) {
			$SelectFinish = Carbon::createFromFormat('d/m/Y', $SelectFinish)->format('Y-m-d');
			$data_po = $data_po->whereDate('po.tglpo', '<=', $SelectFinish);
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
                $dt_term = "<p>" . 'TEMPO' . "</p>";
                if($d->abpk == 1){
                    $dt_term = "<p>" . 'CASH' . "</p>";
                }

				$dt_index = "<p>" . $index . "</p>";
   				$dt_tanggal = "<p>" . $d->tanggal . "</p>";
                $dt_cur = "<p>" . $d->curid . "</p>";
                $dt_bukti = "<p>" . str_pad($d->nobukti, 6, '0', STR_PAD_LEFT) . "</p>";
                $dt_nopo = "<p>" . str_pad($d->nopo, 6, '0', STR_PAD_LEFT) . "</p>";
				$dt_brgnm = "<p>" . strtoupper($d->brgnm) . "</p>";
				$dt_unit = "<p>" . $d->unit . "</p>";
				$dt_sup = "<p>" . $d->supnm . "</p>";
				$dt_jmlbeli = "<p>" . $d->jmlbeli . "</p>";
				$dt_hrgbeli = "<p>" . number_format($d->hrgbeli, 0, '.', ',') . "</p>";
                $total = $d->jmlbeli * $d->hrgbeli;
                $dt_hrgtotal = "<p>" . number_format($total, 0, '.', ',') . "</p>";
				$dt_user = "<p>" . strtoupper($d->login) . "</p>";
				// $dt_penerima = "<p>" . strtoupper($d->depnm) . "</p>";
			
			$row[] = array(
				'podtpk' => $d->podtpk,
				'index' => $dt_index,
				'tanggal' => $dt_tanggal,
				'nobukti' => $dt_bukti,
				'nopo' => $dt_nopo,
				'curid' => $dt_cur,
				'term' => $dt_term,
				'brgnm' => $dt_brgnm,
				'unit' => $dt_unit,
				'supnm' => $dt_sup,
				'jmlbeli' => $dt_jmlbeli,
				'hrgbeli' => $dt_hrgbeli,
				'hrgtotal' => $dt_hrgtotal,
                'dt_user' => $dt_user,
                // 'penerima' => $dt_penerima,
			);
			$index++;
		}
		$result = array_merge($result, array('rows' => $row));
		return json_encode($result);
    }

    public function PdfLapPoKe2(Request $request)
    {
        ini_set('memory_limit', '512M'); // atau 1024M jika masih error
		set_time_limit(300);

        $userpk = Session::get('userpk');
        // $validator = Validator::make($request->all(), [
        //     'SelectStart' => 'nullable|date',
        //     'SelectFinish' => 'nullable|date',
        // ]);

        // if ($validator->fails()) {
        //     return response()->json(['error' => $validator->errors()->first()], 400);
        // }

        // $SelectStart = $request->input('SelectStart');
        // $SelectFinish = $request->input('SelectFinish');

        $SelectStart = $request->input('SelectStart');
        $filterMonth = $request->input('filterByMonth');
        $filterYear = $request->input('filterByYear');
        $search = $request->input('searchByInput');

		$query= DB::table('podt')
		->leftJoin('po', 'po.popk', '=', 'podt.popk')
        ->leftJoin('user', 'user.userpk', '=', 'po.userpk')
        ->leftJoin('sup', 'sup.suppk', '=', 'po.suppk')
        ->leftJoin('ab', 'ab.abpk', '=', 'po.abpk')
        ->leftJoin('kel', 'kel.kelpk', '=', 'po.kelpk')
        ->leftJoin('cur', 'cur.curpk', '=', 'po.curpk')
		->select(
			'podt.podtpk',
			'po.popk',
            DB::raw('DATE_FORMAT(po.tglpo, "%d %b %Y") as tanggal'),
			'po.nobukti',
			'po.nopo',
            'po.posting',
            'user.userpk',
            'user.login',
            'sup.suppk',
            'sup.supnm',
            'ab.abpk',
            'ab.abnm',
            'kel.kelpk',
            'kel.kelnm',
            'cur.curpk',
            'cur.curnm',
            'cur.curid',
            'podt.popk',
            'podt.brgnm',
			'podt.unit',
            'podt.jmlbeli',
            'podt.hrgbeli',
		)
        ->where('po.posting', '=', 1)
        ->orderBy('po.tglpo', 'DESC')->orderBy('po.popk', 'DESC');

        if (!in_array($userpk, ['6', '57', '60'])) {
            $query->where('po.userpk', $userpk);
        }


        // $tanggalLabel = '';
        // if (!empty($SelectStart) && !empty($SelectFinish)) {
        //     $tglStart = Carbon::parse($SelectStart)->format('d-m-Y');
        //     $tglFinish = Carbon::parse($SelectFinish)->format('d-m-Y');

        //     $tanggalLabel = $SelectStart === $SelectFinish
        //         ? "TANGGAL $tglStart"
        //         : "TANGGAL $tglStart S/D $tglFinish";
        // }

        // if (!empty($SelectStart) && !empty($SelectFinish)) {
        //     $formattedDateStart = Carbon::parse($SelectStart)->format('Y-m-d');
        //     $formattedDateFinish = Carbon::parse($SelectFinish)->format('Y-m-d');
        //     $query->whereBetween('pr.tglpr', [$formattedDateStart, $formattedDateFinish]);
        // }

            if ($filterMonth && $filterYear) {
                $query->whereMonth('po.tglpo', '=', $filterMonth)
                    ->whereYear('po.tglpo', '=', $filterYear);
            }

            if (!empty($search)) {
                $query->where(function ($q) use ($search) {
                    $q->where('podt.brgnm', 'like', "%$search%")
                    ->orWhere('po.nopo', 'like', "%$search%")->orWhere('user.login', 'like', "%$search%");
                });
            }

        $bulanNama = '';
        if ($filterMonth) {
            $bulanNama = Carbon::create()->month($filterMonth)->translatedFormat('F'); // contoh: 'Agustus'
        }

        $data = $query->get();
        $formattedData = [];

        foreach ($data as $index => $d) {

            $dt_index = ++$index;
            $dt_tanggal = $d->tanggal;
            $dt_nobukti = str_pad($d->nobukti, 6, '0', STR_PAD_LEFT);
            $dt_brgnm = strtoupper($d->brgnm);
            $dt_unit = strtoupper($d->unit);
            $dt_jmlbeli = $d->jmlbeli;
            $dt_hrgbeli = number_format($d->hrgbeli, 0, '.', ',');
            $total = $d->jmlbeli * $d->hrgbeli;
            $dt_hrgtotal = number_format($total, 0, '.', ',');
            $dt_user = strtoupper($d->login);

            $formattedData[] = [
				'index' => $dt_index,
				'tanggal' => $dt_tanggal,
				'nobukti' => $dt_nobukti,
				'brgnm' => $dt_brgnm,
				'unit' => $dt_unit,
				'jmlbeli' => $dt_jmlbeli,
				'hrgbeli' => $d->curid.' '.$dt_hrgbeli,
				'totalbeli' => $d->curid.' '.$dt_hrgtotal,
                'user' => $dt_user,
            ];
        }
        
        $grandTotalCash = $data->sum('hrgbeli');
		$subTotal = $grandTotalCash;


        $pdf = Pdf::loadView('menu.laporan.po.pdf-list-po', compact('formattedData', 'subTotal', 'bulanNama', 'filterYear'))->setPaper('a4', 'potrait');
        return $pdf->stream('Laporan Purchase Order.pdf');
    }
}

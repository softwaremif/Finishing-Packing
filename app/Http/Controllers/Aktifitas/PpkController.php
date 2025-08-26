<?php

namespace App\Http\Controllers\Aktifitas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use PDF;
use Illuminate\Support\Facades\Session;

class PpkController extends Controller
{
    function PagePpk(){
        if (!Session::get('userpk')) return redirect('/');
        return view('menu.pengisian-pengembalian-kas.list');
    }

    function getListPkk(Request $request)
	{
        $userpk = Session::get('userpk');
		$tahun = date("Y");
		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInput = $request->searchByInput;
        $filterByMonth = $request->filterByMonth ?? date('m');
        $filterByYear = $request->filterByYear;
        $sortlistByDate = $request->sortlistByDate;
        if (!empty($filterByYear)) $tahun = $filterByYear;

		$data_kms = DB::table('kms')
		->select(
			'kmspk',
			'tgl',
            'ket',
            'jumlah',
            'userpk',
            'sawal',
            // 'tglsawal',
            DB::raw('DATE_FORMAT(tgl, "%d %b %Y") as tanggal'),
            DB::raw('DATE_FORMAT(tglsawal, "%d %b %Y") as tglsawal'),
		// )->where('userpk', $userpk);
        );

		if (!empty($searchByInput)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(ket, ''),
                COALESCE(jumlah, '')
            )");

			$data_kms = $data_kms->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}

            // Filter berdasarkan tahun
        if (!empty($filterByYear)) {
            $data_kms = $data_kms->whereYear('tgl', '=', $filterByYear);
        } else {
            // Jika tidak ada filter tahun, gunakan tahun saat ini
            $data_kms = $data_kms->whereYear('tgl', '=', $tahun);
        }

        // Filter berdasarkan bulan
        if (!empty($filterByMonth)) {
            $data_kms = $data_kms->whereMonth('tgl', '=', $filterByMonth);
        }

        // Pengurutan berdasarkan tanggal
        if ($sortlistByDate == 11) {
            $data_kms = $data_kms->orderBy('tgl', 'desc');
        } elseif ($sortlistByDate == 12) {
            $data_kms = $data_kms->orderBy('tgl', 'asc');
        } else {
            $data_kms = $data_kms->orderBy('tgl', 'desc'); // Default pengurutan
        }

		$AllDataKms = $data_kms->get();
		$offset = ($page - 1) * $rows;
		$data_kms = $data_kms->skip($offset)->take($rows)->get();

		$result = array();
		$result['total'] = $AllDataKms->count();
		$result['page'] = $page;
		$result['rows'] = $rows;
		$result['offset'] = $offset;
		$row = array();
		$index = $offset + 1;

		foreach ($data_kms as $d) {
		
            $dt_index = $index;
            $tanggal = $d->tanggal;

            $tglsawal = $d->tglsawal;
            if($d->tglsawal == null){
                 $tglsawal = '-';
            }
            
            $sawal = $d->sawal;
            $ket = trim($d->ket);
            $jumlah = number_format($d->jumlah, 0, '.', ',');
            $sawal = number_format($d->sawal, 2, '.', ',');

			$row[] = array(
                'kmspk' => $d->kmspk,
				'index' => $dt_index,
				'tanggal' => $tanggal,
				'tglsawal' => $tglsawal,
				'sawal' => $sawal,
				'ket' => $ket,
				'jumlah' => $jumlah,
			);
			$index++;
		}
		$result = array_merge($result, array('rows' => $row));
		return json_encode($result);
	}

    public function DetailPpk($kmspk)
    {
        $dt_ppk = DB::table('kms')->where('kmspk', $kmspk)->first();
        return response()->json([
            'success' => true,
            'message' => 'Data retrieved successfully',
            'data' => $dt_ppk,
        ]);
    }

    public function StorePpk(Request $request)
    {

        $dt_kms =  DB::table('kms')->insert([
            'tgl' => $request->tgl,
            'sawal' => $request->sawal,
            'tglsawal' => $request->tglsawal,
            'ket' => $request->ket,
            'jumlah' => $request->jumlah,
            'userpk' => Session::get('userpk'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data created successfully',
            'data' => $dt_kms,
        ]);
    }

    public function UpdatePpk($kmspk, Request $request)
    {
        $dt_kms =  DB::table('kms')
        ->where('kmspk', $kmspk)
        ->update([
            'tgl' => $request->tgl,
            'tglsawal' => $request->tglsawal,
            'sawal' => $request->sawal,
            'ket' => $request->ket,
            'jumlah' => $request->jumlah,
            'userpk' => Session::get('userpk'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data updated successfully',
            'data' => $dt_kms,
        ]);
    }

    public function pdfPembelianCT(Request $request)
    {
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
        )->orderBy('beli.tglinv', 'ASC')->orderBy('sup.supnm', 'ASC')->where('user.userpk', 4);
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
                'hrgbeli' => number_format($d->hrgbeli, 0, '.', ',') ?? '-',
                'jmlbeli' => number_format($d->jmlbeli, 2, '.', ',') ?? '-',
                'jmlhrg' => number_format($d->jmlhrg, 0, '.', ',') ?? '-',
                'tglbayar' => $d->tglbayar ?? '-',
                'jmlbayar' => $d->jmlbayar ?? '-',
            ];
        }

        $pdf = Pdf::loadView('menu.laporan.pembelian.pdf-pembelian-ct', compact('formattedData', 'tanggalLabel'))->setPaper('a4', 'potrait');
        return $pdf->stream('List-Payment.pdf');
    }
}
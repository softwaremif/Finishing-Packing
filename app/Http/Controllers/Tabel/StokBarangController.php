<?php

namespace App\Http\Controllers\Tabel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;

class StokBarangController extends Controller
{
    function PageStokBarang(){
        if (!Session::get('userpk')) return redirect('/');
        return view('menu.tabel.stok-barang.list');
    }

    function getListStokBarang(Request $request){
        $userpk = Session::get('userpk');
		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInput = $request->searchByInput;
        $sortlistByDate = $request->sortlistByDate;

        if($userpk == 11){
            $getUserpk = 7;
        }else{
            $getUserpk = $userpk;
        }

		$data_barang = DB::table('stok')
        ->leftJoin('jnsbrg', 'jnsbrg.jnsbrgpk', '=', 'stok.jnsbrgpk')
		->select(
			'stok.stokpk',
			'stok.stokid',
            'stok.stoknm',
            // 'stok.merk',
            // 'stok.codebrg',
            'stok.satuan',
            'stok.sawal',
            'stok.masuk',
            'stok.keluar',
            'stok.sakhir',
            'jnsbrg.jnsbrgpk',
            'jnsbrg.jnsbrgnm',
            'stok.userpk',
		)->where('stok.userpk', $getUserpk);


		if (!empty($searchByInput)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(stok.stokid, ''),
                COALESCE(stok.stoknm, ''),
                COALESCE(jnsbrg.jnsbrgnm, '')
            )");

			$data_barang = $data_barang->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}

        // Pengurutan berdasarkan tanggal
        if ($sortlistByDate == 11) {
            $data_barang = $data_barang->orderBy('stok.stokpk', 'desc');
        } elseif ($sortlistByDate == 12) {
            $data_barang = $data_barang->orderBy('stok.stokpk', 'asc');
        } else {
            $data_barang = $data_barang->orderBy('stok.stokpk', 'desc'); // Default pengurutan
        }

		$AllDataKms = $data_barang->get();
		$offset = ($page - 1) * $rows;
		$data_barang = $data_barang->skip($offset)->take($rows)->get();

		$result = array();
		$result['total'] = $AllDataKms->count();
		$result['page'] = $page;
		$result['rows'] = $rows;
		$result['offset'] = $offset;
		$row = array();
		$index = $offset + 1;

		foreach ($data_barang as $d) {
		
            $dt_index = $index;
			$row[] = array(
                'stokpk' => $d->stokpk,
				'index' => $dt_index,
				'stokid' => $d->stokid ?? '-',
                // 'stokid' => !empty($d->stokid) ? $d->stokid : '-',
				'stoknm' => $d->stoknm,
				// 'merk' => $d->merk ?? '-',
				// 'codebrg' => $d->codebrg ?? '-',
				'satuan' => $d->satuan,
                'jnsbrgnm' => $d->jnsbrgnm ?? '-',
				'sawal' => $d->sawal,
				'masuk' => $d->masuk,
				'keluar' => $d->keluar,
				'sakhir' => $d->sakhir,

			);
			$index++;
		}
		$result = array_merge($result, array('rows' => $row));
		return json_encode($result);
    }

    public function DetailBarang($stokpk)
    {
        $data_barang = DB::table('stok')->where('stokpk', $stokpk)->first();
        return response()->json([
            'success' => true,
            'message' => 'Data retrieved successfully',
            'data' => $data_barang,
        ]);
    }

    public function StoreBarang(Request $request)
    {
        $lastStokid = DB::table('stok')->max('stokid');
		$newStokid = $lastStokid ? $lastStokid + 1 : 1;
        $stokid_padded = str_pad($newStokid, 7, '0', STR_PAD_LEFT);

        $data_barang =  DB::table('stok')->insert([
            // 'stokid' => strtoupper($request->stokid),
            'stokid' => $stokid_padded,
            'stoknm' => strtoupper($request->stoknm),
            // 'merk' => $request->merk,
            // 'merk' => $request->merk !== null && $request->merk !== '' ? strtoupper($request->merk) : null,
            // 'codebrg' => $request->codebrg,
            // 'codebrg' => $request->codebrg !== null && $request->codebrg !== '' ? strtoupper($request->codebrg) : null,
            'satuan' => strtoupper($request->satuan),
            'sawal' => $request->sawal,
            'masuk' => $request->masuk,
            'keluar' => $request->keluar,
            'sakhir' => $request->sakhir,
            'jnsbrgpk' => $request->jnsbrgpk,
            'userpk' => Session::get('userpk'),
            'tgldibuat' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data created successfully',
            'data' => $data_barang,
        ]);
    }

    public function UpdateBarang($stokpk, Request $request)
    {
        $data_barang =  DB::table('stok')
        ->where('stokpk', $stokpk)
        ->update([
            // 'stokid' => strtoupper($request->stokid),
            'stoknm' => strtoupper($request->stoknm),
            // 'merk' => $request->merk !== null && $request->merk !== '' ? strtoupper($request->merk) : null,
            // 'codebrg' => $request->codebrg !== null && $request->codebrg !== '' ? strtoupper($request->codebrg) : null,
            'satuan' => strtoupper($request->satuan),
            'sawal' => $request->sawal,
            'masuk' => $request->masuk,
            'keluar' => $request->keluar,
            'sakhir' => $request->sakhir,
            'jnsbrgpk' => $request->jnsbrgpk,
            // 'userpk' => Session::get('userpk'),
            'tgldiedit' => Carbon::now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data updated successfully',
            'data' => $data_barang,
        ]);
    }
}

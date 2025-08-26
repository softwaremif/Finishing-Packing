<?php

namespace App\Http\Controllers\Aktifitas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use App\Imports\BarangImport;
use Maatwebsite\Excel\Facades\Excel;

class BarangController extends Controller
{
    function PageBarang(){
        if (!Session::get('userpk')) return redirect('/');
        return view('menu.list-barang.list');
    }

    function getListBarang(Request $request)
	{

		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInputBarang = $request->searchByInputBarang;
        $sortlistByDate = $request->sortlistByDate;
        $uriSegment = request()->segment(4); // ambil popk dari URL


		$data_brg = DB::table('brg')
        // ->leftJoin('podt', 'podt.brgpk', '=', 'podt.brgpk')
		->select(
			'brg.brgpk',
			'brg.brgnm',
            'brg.noseri',
            'brg.merknm',
            'brg.qtyawal',
            DB::raw('DATE_FORMAT(brg.lastupdate, "%d %b %Y %H:%i:%s") as lastupdate'),
		);

		if (!empty($searchByInputBarang)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(brg.brgnm, ''),
                COALESCE(brg.noseri, ''),
                COALESCE(brg.merknm, '')
            )");

			$data_brg = $data_brg->where($concatenatedValue, 'like', '%' . $searchByInputBarang . '%');
		}

        // Pengurutan berdasarkan brgpk
        if ($sortlistByDate == 11) {
            $data_brg = $data_brg->orderBy('brg.brgpk', 'desc');
        } elseif ($sortlistByDate == 12) {
            $data_brg = $data_brg->orderBy('brg.brgpk', 'asc');
        } else {
            $data_brg = $data_brg->orderBy('brg.brgpk', 'desc'); 
        }

		$AllDataKms = $data_brg->get();
		$offset = ($page - 1) * $rows;
		$data_brg = $data_brg->skip($offset)->take($rows)->get();

		$result = array();
		$result['total'] = $AllDataKms->count();
		$result['page'] = $page;
		$result['rows'] = $rows;
		$result['offset'] = $offset;
		$row = array();
		$index = $offset + 1;

		foreach ($data_brg as $d) {

            $MasukByPodt = DB::table('podt')
            ->where('brgpk', $d->brgpk)
            ->sum('jmlbeli');

            $MasukByBelidt = DB::table('belidt')
            ->where('brgpk', $d->brgpk)
            ->sum('jmlbeli');

            $QtyMasuk = $MasukByPodt + $MasukByBelidt;

            $GetPodt = DB::table('podt')
                ->where('brgpk', $d->brgpk)
                ->pluck('podtpk'); // Ambil semua podtpk untuk brgpk tersebut

            $GetBelidt = DB::table('belidt')
                ->where('brgpk', $d->brgpk)
                ->pluck('belidtpk');

            $KeluarByPodt = DB::table('ttdt')
                ->whereIn('podtpk', $GetPodt) // Gunakan whereIn untuk semua podtpk yang ditemukan
                ->sum('jumlah');

            $KeluarByBelidt = DB::table('ttdt')
                ->whereIn('belidtpk', $GetBelidt) // Gunakan whereIn untuk semua podtpk yang ditemukan
                ->sum('jumlah');
            
            $QtyKeluar = $KeluarByPodt + $KeluarByBelidt;
		
			$row[] = array(
                'brgpk' => $d->brgpk,
				'index' => $index,
				'brgnm' => trim($d->brgnm),
                'noseri' => !empty($d->noseri) ? $d->noseri : '-',
				'merknm' => !empty($d->merknm) ? $d->merknm : '-',
                'qtyawal' => !empty($d->qtyawal) ? $d->qtyawal : '-',
				'lastupdate' => !empty($d->lastupdate) ? $d->lastupdate : '-',
                'qtymasuk' => $QtyMasuk,
                'qtykeluar' => $QtyKeluar,
                'qtyakhir' => $d->qtyawal + $QtyMasuk - $QtyKeluar,
			);
			$index++;
		}
		$result = array_merge($result, array('rows' => $row));
		return json_encode($result);
	}

    public function upload_excel(Request $request){

        if (!$request->hasFile('uploadbarang')) {
            return response()->json(['message' => 'No file uploaded'], 400);
        }

        $file = $request->file('uploadbarang');

        $originalName = $file->getClientOriginalName();
        $timestamp = now()->format('Ymd-His'); // Gunakan - bukan :
        $nama_file = $timestamp . '-' . $originalName;

        $tujuan_upload = public_path('upload/barang');
        $file->move($tujuan_upload, $nama_file);

        // Simpan data pakai import
        Excel::import(new BarangImport(), $tujuan_upload . '/' . $nama_file);

        // Hapus record dengan qtyawal NULL atau 0
        DB::table('brg')->where('qtyawal', 0)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Barang successfully saved.',
        ], 200);
	}

    public function AddBarangToPodt(Request $request)
    {
        $popk = $request->input('popk');
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
            DB::table('podt')->insert([
                'popk' => $popk,
                'brgpk' => $item->brgpk,
                'brgnm' => trim(strtoupper($item->brgnm)),
                // 'satuan' => trim(strtoupper($item->unit)),
                'unit' => null,
                'jmlbeli' => trim($item->qtyawal),
            ]);
        }

        return response()->json([
       		'success' => true,
            'message' => 'Data inserted successfully!',
            'inserted_items' => $detailItems->count(),
        ]);
    }
}

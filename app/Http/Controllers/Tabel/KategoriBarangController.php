<?php

namespace App\Http\Controllers\Tabel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\QueryException;

class KategoriBarangController extends Controller
{
    function getListKtb(Request $request){

		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInput = $request->searchByInput;
        $sortlistByDate = $request->sortlistByDate;

		$data_ktb = DB::table('kel')
		->select(
			'kelpk',
			'kelid',
            'kelnm',
            'nour',
		);

		if (!empty($searchByInput)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(kelid, ''),
                COALESCE(kelnm, '')
            )");

			$data_ktb = $data_ktb->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}

        // Pengurutan berdasarkan tanggal
        if ($sortlistByDate == 11) {
            $data_ktb = $data_ktb->orderBy('kelnm', 'asc');
        } elseif ($sortlistByDate == 12) {
            $data_ktb = $data_ktb->orderBy('kelnm', 'desc');
        } else {
            $data_ktb = $data_ktb->orderBy('kelnm', 'asc'); // Default pengurutan
        }

		$AllDataKtb = $data_ktb->get();
		$offset = ($page - 1) * $rows;
		$data_ktb = $data_ktb->skip($offset)->take($rows)->get();

		$result = array();
		$result['total'] = $AllDataKtb->count();
		$result['page'] = $page;
		$result['rows'] = $rows;
		$result['offset'] = $offset;
		$row = array();
		$index = $offset + 1;

		foreach ($data_ktb as $d) {
		
            $dt_index = $index;
			$row[] = array(
                'kelpk' => $d->kelpk,
				'index' => $dt_index,
				'kelid' => $d->kelid,
				'kelnm' => $d->kelnm,
			);
			$index++;
		}
		$result = array_merge($result, array('rows' => $row));
		return json_encode($result);
    }

    public function index()
    {
        if (!Session::get('userpk')) return redirect('/');
        return view('menu.tabel.kategori-barang.list');
    }

    public function store(Request $request)
    {
        try {
            $data_ktb = DB::table('kel')->insert([
                'kelid' => strtoupper($request->kelid),
                'kelnm' => strtoupper($request->kelnm),
                'nour' => 0,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data Kategori Barang berhasil disimpan.',
                'data' => $data_ktb,
            ], 200);

        } catch (QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                return response()->json([
                    'success' => false,
                    'message' => 'Kode Kategori Barang sudah digunakan.',
                ], 422);
            }

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error' => $e->getMessage(), // Opsional: hapus di production
            ], 500);
        }
    }

    public function show($kelpk)
    {
        $data_ktb = DB::table('kel')->where('kelpk', $kelpk)->first();
        return response()->json([
            'success' => true,
            'message' => 'Data retrieved successfully',
            'data' => $data_ktb,
        ]);
    }

    public function update(Request $request, $kelpk)
    {
        try {
                // Cek apakah supid baru sudah dipakai kategori lain
                $existing = DB::table('kel')
                    ->where('kelid', strtoupper($request->kelid))
                    ->where('kelpk', '!=', $kelpk)
                    ->first();

                if ($existing) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Kode Kategori Barang sudah digunakan oleh Kategori lain.',
                    ], 422);
                }

                $data_ktb = DB::table('kel')
                    ->where('kelpk', $kelpk)
                    ->update([
                        'kelid' => strtoupper($request->kelid),
                        'kelnm' => strtoupper($request->kelnm),
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Data Kategori Barang berhasil diperbarui.',
                    'data' => $data_ktb,
                ], 200);

            } catch (QueryException $e) {
                if ($e->errorInfo[1] == 1062) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Kode Kategori Barang sudah digunakan.',
                    ], 422);
                }

                return response()->json([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memperbarui data.',
                    'error' => $e->getMessage(), // Opsional
                ], 500);
        }
    }


}

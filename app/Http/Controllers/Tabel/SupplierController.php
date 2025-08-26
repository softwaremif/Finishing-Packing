<?php

namespace App\Http\Controllers\Tabel;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Database\QueryException;

class SupplierController extends Controller
{
    function getListSupplier(Request $request){

		$page = $request->input('page') ?? '1';
		$rows = $request->input('rows') ?? '100';
		$searchByInput = $request->searchByInput;
        $sortlistByDate = $request->sortlistByDate;

		$data_supp = DB::table('sup')
		->select(
			'suppk',
			'supid',
            'supnm',
            'kontak',
            'alamat',
            'kota',
            'telepon',
            'fax',
            'email',
            'nour',
		);

		if (!empty($searchByInput)) {
			$concatenatedValue = DB::raw("CONCAT(
                COALESCE(supid, ''),
                COALESCE(supnm, '')
            )");

			$data_supp = $data_supp->where($concatenatedValue, 'like', '%' . $searchByInput . '%');
		}

        // Pengurutan berdasarkan tanggal
        if ($sortlistByDate == 11) {
            $data_supp = $data_supp->orderBy('supnm', 'asc');
        } elseif ($sortlistByDate == 12) {
            $data_supp = $data_supp->orderBy('supnm', 'desc');
        } else {
            $data_supp = $data_supp->orderBy('supnm', 'asc'); // Default pengurutan
        }

		$AllDataSupp = $data_supp->get();
		$offset = ($page - 1) * $rows;
		$data_supp = $data_supp->skip($offset)->take($rows)->get();

		$result = array();
		$result['total'] = $AllDataSupp->count();
		$result['page'] = $page;
		$result['rows'] = $rows;
		$result['offset'] = $offset;
		$row = array();
		$index = $offset + 1;

		foreach ($data_supp as $d) {
		
            $dt_index = $index;
			$row[] = array(
                'suppk' => $d->suppk,
				'index' => $dt_index,
				'supid' => $d->supid ?? '-',
				'supnm' => $d->supnm,
				'kontak' => $d->kontak ?? '-',
				'alamat' => $d->alamat ?? '-',
				'kota' => $d->kota,
                'telepon' => $d->telepon,
				'fax' => $d->fax,
				'email' => $d->email,
			);
			$index++;
		}
		$result = array_merge($result, array('rows' => $row));
		return json_encode($result);
    }

    public function index()
    {
        if (!Session::get('userpk')) return redirect('/');
        return view('menu.tabel.supplier.list');
    }

    public function store(Request $request)
    {
        try {
            $data_supp = DB::table('sup')->insert([
                'supid' => strtoupper($request->supid),
                'supnm' => strtoupper($request->supnm),
                'kontak' => $request->kontak !== null && $request->kontak !== '' ? strtoupper($request->kontak) : null,
                'alamat' => $request->alamat !== null && $request->alamat !== '' ? strtoupper($request->alamat) : null,
                'kota' => $request->kota !== null && $request->kota !== '' ? strtoupper($request->kota) : null,
                'telepon' => $request->telepon,
                'fax' => $request->fax,
                'email' => $request->email,
                'nour' => $request->nour,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Data supplier berhasil disimpan.',
                'data' => $data_supp,
            ], 200);

        } catch (QueryException $e) {
            if ($e->errorInfo[1] == 1062) {
                // Duplicate entry
                return response()->json([
                    'success' => false,
                    'message' => 'Kode Supplier sudah digunakan.',
                ], 422);
            }

            // Error lain
            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.',
                'error' => $e->getMessage(), // Opsional: hapus di production
            ], 500);
        }
    }

    public function show($suppk)
    {
        $data_supp = DB::table('sup')->select(  
            DB::raw('TRIM(suppk) as suppk'),
            DB::raw('TRIM(supid) as supid'),
            DB::raw('TRIM(supnm) as supnm'),
            DB::raw('TRIM(kontak) as kontak'), 
            DB::raw('TRIM(alamat) as alamat'), 
            DB::raw('TRIM(kota) as kota'), 
            DB::raw('TRIM(telepon) as telepon'), 
            DB::raw('TRIM(fax) as fax'), 
            DB::raw('TRIM(email) as email'), 'nour')->where('suppk', $suppk)->first();
        return response()->json([
            'success' => true,
            'message' => 'Data retrieved successfully',
            'data' => $data_supp,
        ]);
    }

    public function update(Request $request, $suppk)
    {
        try {
                // Cek apakah supid baru sudah dipakai supplier lain
                $existing = DB::table('sup')
                    ->where('supid', strtoupper($request->supid))
                    ->where('suppk', '!=', $suppk)
                    ->first();

                if ($existing) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Kode Supplier sudah digunakan oleh supplier lain.',
                    ], 422);
                }

                $data_supp = DB::table('sup')
                    ->where('suppk', $suppk)
                    ->update([
                        'supid' => strtoupper($request->supid),
                        'supnm' => strtoupper($request->supnm),
                        'kontak' => $request->kontak !== null && $request->kontak !== '' ? strtoupper($request->kontak) : null,
                        'alamat' => $request->alamat !== null && $request->alamat !== '' ? strtoupper($request->alamat) : null,
                        'kota' => $request->kota !== null && $request->kota !== '' ? strtoupper($request->kota) : null,
                        'telepon' => $request->telepon,
                        'fax' => $request->fax,
                        'email' => $request->email,
                        'nour' => $request->nour,
                    ]);

                return response()->json([
                    'success' => true,
                    'message' => 'Data supplier berhasil diperbarui.',
                    'data' => $data_supp,
                ], 200);

            } catch (QueryException $e) {
                if ($e->errorInfo[1] == 1062) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Kode Supplier sudah digunakan.',
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

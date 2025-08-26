<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PoCashTempoDetail;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;

class ApiPoController extends Controller
{

    public function GetNotran()
    {
        $detail = DB::table('notran')
            ->select(
                'tblnm',
                'tblket',
                'no',
            )
            ->where('tblnm', '=', 'po')
            ->where('tblket', '=', 'po')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $detail
        ]);
    }

    public function GetSpbDetail($spbpk)
    {
        $DtSpbDetail = DB::table('spb')
            ->leftJoin('invoice', 'invoice.spbpk', '=', 'spb.spbpk')
            ->select(
                'spb.spbpk',
                'spb.nobukti',
                'spb.tgl',
                'spb.totsatuan',
                'invoice.noinv',
                'invoice.priceppn'
            )
            ->where('spb.spbpk', '=', $spbpk)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $DtSpbDetail
        ]);
    }

    public function GetTotHrgBeli($popk)
    {
        $gettotal = DB::table('podt')
            ->select('hrgbeli', 'jmlbeli')
            ->where('popk', $popk)
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
            'popk' => 'required|numeric',
            'totbeli' => 'required|numeric'
        ]);

        $updated = DB::table('po')
            ->where('popk', $request->popk)
            ->update(['totbeli' => $request->totbeli]);

        if ($updated) {
            return response()->json(['success' => true]);
        } else {
            return response()->json(['success' => false, 'message' => 'Data tidak ditemukan atau tidak berubah.']);
        }
    }

    public function GetPodt(Request $request, $popk)
    {
        $page = $request->input('page') ?? '1';
        $rows = $request->input('rows') ?? '100';

        $data_podt = DB::table('podt')
            ->select(
                'podt.podtpk',
                'podt.popk',
                'podt.brgnm',
                'podt.unit',
                'podt.hrgbeli',
                'podt.jmlbeli',
            )
            ->where('popk', '=', $popk)->orderBy('podt.podtpk', 'ASC');

        $AllDataPodt = $data_podt->get();

        $offset = ($page - 1) * $rows;
        $data_podt = $data_podt->skip($offset)->take($rows)->get();

        $result = array();
        $result['total'] = $AllDataPodt->count();
        $result['page'] = $page;
        $result['rows'] = $rows;
        $result['offset'] = $offset;
        $row = array();
        $index = $offset + 1;

        foreach ($data_podt as $d) {
            $row[] = array(
                'index' => $index,
                'podtpk' => $d->podtpk,
                'popk' => $d->popk,
                'brgnm' => trim($d->brgnm),
                // 'unit' => trim($d->unit),
                'unit' => strtoupper($d->unit),
                'hrgbeli' => number_format($d->hrgbeli, 0, '.', ','),
                // 'hrgbeli' => $d->hrgbeli,
                'jmlbeli' => $d->jmlbeli,
                'total' => number_format($d->jmlbeli * $d->hrgbeli, 0, '.', ','),
            );
            $index++;
        }
        $result = array_merge($result, array('rows' => $row));
        return json_encode($result);
    }

    public function InsertPodt(Request $request, $popk)
    {

        $brgnm = $request->input('brgnm');
        $unit = $request->input('unit');
        $hrgbeli = $request->input('hrgbeli');
        $jmlbeli = $request->input('jmlbeli');

        DB::table('podt')->insert([
            'popk' => $popk,
            'brgnm' => strtoupper($brgnm),
            'unit' => strtoupper($unit),
            'hrgbeli' => $hrgbeli,
            'jmlbeli' => $jmlbeli,
        ]);

        return response()->json([
            'popk' => $popk,
            'brgnm' => $brgnm,
            'unit' => $unit,
            'hrgbeli' => $hrgbeli,
            'jmlbeli' => $jmlbeli,
        ]);
    }

    public function UpdatePodt(Request $request)
    {
        $podtpk = $request->input('podtpk');
        $brgnm = $request->input('brgnm') ?? '';
        $unit = $request->input('unit') ?? '';
        // $hrgbeli = $request->input('hrgbeli') ?? '';
        $hrgbeli = str_replace(',', '', $request->input('hrgbeli') ?? '');
        $jmlbeli = $request->input('jmlbeli') ?? '';

        DB::table('podt')
            ->where('podtpk', $podtpk)
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

    public function SaveEditHeaderPodt(Request $request)
    {
        try {
            $tglpo = $request->input('tglpo') ?? '00/00/0000';
            $tglpo = Carbon::createFromFormat('d/m/Y', $tglpo)->format('Y-m-d');
            $popk = $request->input('popk');
            $abpk = $request->input('abpk');
            $suppk = $request->input('suppk');
            $curpk = $request->input('curpk');
            $ket = $request->input('ket');
            $ket2 = $request->input('ket2');

            DB::table('po')
                ->where('popk', $popk)
                ->update([
                    'tglpo' => $tglpo,
                    'abpk' => $abpk,
                    'suppk' => $suppk,
                    'curpk' => $curpk,
                    'ket' => $ket,
                    'ket2' => $ket2,
                ]);

            $dt_pdrt = DB::table('podt')->where('popk', $popk)->whereNotNull('prdtpk')->get();

            foreach ($dt_pdrt as $item) {
                DB::table('prdt')
                ->where('prdtpk', $item->prdtpk)
                ->update(['stspo' => 1]);
            }


            // Kembalikan respons JSON
            return response()->json([
                'status' => 'success',
                'message' => 'Data updated successfully!',
                'data' => [
                    'popk' => $popk,
                    'abpk' => $abpk,
                    'suppk' => $suppk,
                    'curpk' => $curpk,
                    'ket' => $ket,
                    'ket2' => $ket2,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function DeletePodt(Request $request)
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
            $row = DB::table('podt')->where('podtpk', $id)->first();

            if (!$row) {
                return response()->json([
                    'success' => false,
                    'msg' => "<div class='text-infomerah' style='width:415px;'>Data with ID: $id not found. &#x2573;</div>",
                ]);
            }

            // Hapus baris setelah ambil datanya
            $deleted = DB::table('podt')->where('podtpk', $id)->delete();

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
                     'message' => 'Cannot delete data material for ID:'. $id .'&nbsp;',
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

    public function GetBelidt($id)
    {
        // Define the query without fetching the results yet
        $query = PoCashTempoDetail::where('belipk', $id);

        // Get pagination parameters
        $page = request()->input('page') ?? '1';
        $perPage = request()->input('rows') ?? '100';
        $total = $query->count();
        $offset = ($page - 1) * $perPage;

        // Apply pagination and fetch the results
        $data = $query->skip($offset)
            ->take($perPage)
            ->get();
        $rows = $data->map(function ($item, $index) use ($offset) {
            return [
                'index' => $offset + $index + 1,
                'jmlbeli' => $item->jmlbeli,
                'belidtpk' => $item->belidtpk,
                'brgnm' => $item->brgnm,
                'unit' => $item->unit,
                'hrgbeli' => $item->hrgbeli,
                'jmlhrg' => $item->jmlhrg,
            ];
        })->toArray();

        return [
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'offset' => $offset,
            'rows' => $rows,
        ];
    }
}

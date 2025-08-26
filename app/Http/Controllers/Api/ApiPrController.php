<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use DB;
use Illuminate\suport\Facades\Session;
use Carbon\Carbon;

class ApiPrController extends Controller
{
    public function GetPrdt(Request $request, $prpk)
    {
        $page = $request->input('page') ?? '1';
        $rows = $request->input('rows') ?? '100';

        $data_prdt = DB::table('prdt')
            ->select(
                'prdt.prdtpk',
                'prdt.prpk',
                'prdt.brgnm',
                'prdt.unit',
                'prdt.jmlbeli',
            )
            ->where('prpk', '=', $prpk);

        $AllDataPrdt = $data_prdt->get();

        $offset = ($page - 1) * $rows;
        $data_prdt = $data_prdt->skip($offset)->take($rows)->get();

        $result = array();
        $result['total'] = $AllDataPrdt->count();
        $result['page'] = $page;
        $result['rows'] = $rows;
        $result['offset'] = $offset;
        $row = array();
        $index = $offset + 1;

        foreach ($data_prdt as $d) {
            $row[] = array(
                'index' => $index,
                'prdtpk' => $d->prdtpk,
                'prpk' => $d->prpk,
                'brgnm' => $d->brgnm,
                'unit' => $d->unit,
                'jmlbeli' => $d->jmlbeli,
            );
            $index++;
        }
        $result = array_merge($result, array('rows' => $row));
        return json_encode($result);
    }

    public function InsertPrdt(Request $request, $prpk)
    {

        $brgnm = $request->input('brgnm');
        $unit = $request->input('unit');
        $jmlbeli = $request->input('jmlbeli');

        DB::table('prdt')->insert([
            'prpk' => $prpk,
            'brgnm' => strtoupper($brgnm),
            'unit' => strtoupper($unit),
            'jmlbeli' => $jmlbeli,
        ]);

        return response()->json([
            'prpk' => $prpk,
            'brgnm' => $brgnm,
            'unit' => $unit,
            'jmlbeli' => $jmlbeli,
        ]);
    }

    public function UpdatePrdt(Request $request)
    {
        $prdtpk = $request->input('prdtpk');
        $brgnm = $request->input('brgnm') ?? '';
        $unit = $request->input('unit') ?? '';
        $jmlbeli = $request->input('jmlbeli') ?? '';

        DB::table('prdt')
            ->where('prdtpk', $prdtpk)
            ->update([
                'brgnm' => strtoupper($brgnm),
                'unit' => strtoupper($unit),
                'jmlbeli' => $jmlbeli,
            ]);

        return response()->json([
            'respon' => 'ini sukses',
            'brgnm' => $brgnm,
            'unit' => $unit,
            'jmlbeli' => $jmlbeli,
        ]);
    }

    public function SaveEditHeaderPrdt(Request $request)
    {
        try {
            $tglpr = $request->input('tglpr') ?? '00/00/0000';
            $tglpr = Carbon::createFromFormat('d/m/Y', $tglpr)->format('Y-m-d');
            $prpk = $request->input('prpk'); 
            $deppk = $request->input('deppk');

            DB::table('pr')
                ->where('prpk', $prpk)
                ->update([
                    'tglpr' => $tglpr,
                    'deppk' => $deppk,
                    'tglupdt' => Carbon::now(),
                ]);

            return response()->json([
                'status' => 'success',
                'message' => 'Data updated successfully!',
                'data' => [
                    'prpk' => $prpk,
                    'deppk' => $deppk,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function DeletePrdt(Request $request)
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
            $result = DB::table('prdt')->where('prdtpk', '=', $id)->delete();

            if (!$result) {
                return response()->json([
                    'success' => false,
                    // 'msg' => "<div class='text-infomerah' style='width:415px;'>Cannot process replace data material for ID: $id &nbsp; &#x2573;</div>",
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
}

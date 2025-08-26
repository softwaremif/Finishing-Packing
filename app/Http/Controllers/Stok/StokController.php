<?php

namespace App\Http\Controllers\Stok;

use App\Http\Controllers\Controller;
use App\Imports\StokImport;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class StokController extends Controller
{
    public function showUploadForm()
    {
        return view('menu.purchase-cash-tempo.excel');
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|mimes:xlsx,xls',
        ]);

        try {
            return Excel::import(new StokImport, $request->file('file'));
            return redirect()->back()->with('success', 'Data imported successfully!');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Error importing data: ' . $e->getMessage());
        }
    }
}

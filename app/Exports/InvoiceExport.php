<?php

namespace App\Exports;

// use App\Models\Downtime;
// use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
// use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// class DowntimeExport implements FromCollection
class InvoiceExport implements FromView
{
    // public function collection()
    // {
    //     return Downtime::all();
    // }
    protected $tahun, $bulan;

    public function __construct($tahun, $bulan)
    {
        $this->tahun = $tahun;
        $this->bulan = $bulan;
    }

    public function view(): View
    {

        $dtinvs = DB::table('invsdt')
        ->leftJoin('invs', 'invs.invspk', '=', 'invsdt.invspk')
        ->select(
            DB::raw("DATE_FORMAT(invs.sendate, '%d/%m/%Y') as sendate"),
            'invs.brand',
            'invs.noinvs',
            'invsdt.style',
            'invsdt.pono',
            'invsdt.qty',
            'invsdt.price',
            'invsdt.ordpk',
            'invsdt.itemnm',
            'invsdt.nos',
            'invsdt.ordpk',
        )
        ->whereYear('invs.tglinvs', $this->tahun)
        ->whereMonth('invs.tglinvs', $this->bulan);
        if (Session::get('fupkgis')>0) {
            $dtinvs = $dtinvs->where('invs.funm', 'LIKE', '%' . Session::get('login') . '%');
        }
        $dtinvs = $dtinvs->orderBy('invs.brand', 'asc')
        ->orderBy('invs.noinvs', 'asc')
        ->get();

     
        return view('menu.invoice.invoice_excel', compact('dtinvs'));
    }
}

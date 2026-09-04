<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportListBkk implements FromView
{
    protected $data;
    protected $totalAll;
    protected $SelectDate;

    public function __construct($data, $totalAll, $SelectDate)
    {
        $this->data = $data;
        $this->totalAll = $totalAll;
        $this->SelectDate = $SelectDate;
    }

    public function view(): View
    {
        return view('menu.laporan.bukti.2.export-excel-list-bkk2', [
            'formattedData' => $this->data,
            'totalAll'      => $this->totalAll,
            'SelectDate'    => $this->SelectDate,
        ]);
    }
}

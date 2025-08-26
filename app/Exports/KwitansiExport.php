<?php

namespace App\Exports;

// use App\Models\Downtime;
// use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
// use Maatwebsite\Excel\Concerns\WithColumnFormatting;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
// use PhpOffice\PhpSpreadsheet\Style\NumberFormat;

// class DowntimeExport implements FromCollection
class KwitansiExport implements FromView, WithStyles, WithHeadings
{
    // public function collection()
    // {
    //     return Downtime::all();
    // }
    protected $data;
    protected $gudnm;

    public function __construct($data, $gudnm)
    {
        $this->data = $data;
        $this->gudnm = $gudnm;
    }

    public function view(): View
    {
        return view('menu.finance.export', [
            'gudnm' => $this->gudnm,
            'dt_kwitansi' => $this->data
        ]);
    }

    public function styles(Worksheet $sheet)
    {
        // return [
        //     2    => ['font' => ['bold' => true]],
        //     'A1:R1' => [
        //         'borders' => [
        //             'outline' => [
        //                 'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THICK,
        //             ],
        //         ],
        //     ],
        // ];
    }

    public function headings(): array
    {
        return [
            'KONTRAK KERJA',
            'INVOICE NO.',
            'STYLE',
            'QUANTITY (PCS)',
            'UNIT PRICE (USD)',
            'AMOUNT (USD)',
        ];
    }
}

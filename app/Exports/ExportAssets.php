<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithHeadings;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ExportAssets implements FromView, WithStyles, WithHeadings
{

    protected $data;

    public function __construct($data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        return view('menu.data-aset.export', [
            'dt_assets' => $this->data
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
            'Nama Line/Lokasi',
            'Jenis Aset',
            'Nama Aset',
            'Aset ID',
            'Tahun',
            'Pemakai',
            'Status',
            'MIF',
        ];
    }
}

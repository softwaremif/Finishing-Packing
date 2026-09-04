<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;

class ExportListCashTempo implements FromView
{
    protected $subTotalCash;
    protected $grandTotalCash;
    protected $subTotalTempo;
    protected $grandTotalTempo;
    protected $SubTotalCashTempoJmlHrg;
    protected $grandTotalAll;
    protected $formattedData_Cash;
    protected $formattedData_Tempo;
    protected $tanggalLabel;
    protected $countCash;
    protected $countTempo;

    public function __construct(
        $subTotalCash,
        $grandTotalCash,
        $subTotalTempo,
        $grandTotalTempo,
        $SubTotalCashTempoJmlHrg,
        $grandTotalAll,
        $formattedData_Cash,
        $formattedData_Tempo,
        $tanggalLabel,
        $countCash,
        $countTempo
    ) {
        $this->subTotalCash   = $subTotalCash;
        $this->grandTotalCash = $grandTotalCash;
        $this->subTotalTempo  = $subTotalTempo;
        $this->grandTotalTempo= $grandTotalTempo;
        $this->SubTotalCashTempoJmlHrg    = $SubTotalCashTempoJmlHrg;
        $this->grandTotalAll  = $grandTotalAll;
        $this->formattedData_Cash       = $formattedData_Cash;
        $this->formattedData_Tempo      = $formattedData_Tempo;
        $this->tanggalLabel   = $tanggalLabel;
        $this->countCash      = $countCash;
        $this->countTempo     = $countTempo;
    }

    public function view(): View
    {
        return view('menu.laporan.pembelian.excel-cash-tempo', [
            'formattedData_Cash'       => $this->formattedData_Cash,
            'formattedData_Tempo'      => $this->formattedData_Tempo,
            'subTotalCash'   => $this->subTotalCash,
            'grandTotalCash' => $this->grandTotalCash,
            'subTotalTempo'  => $this->subTotalTempo,
            'grandTotalTempo'=> $this->grandTotalTempo,
            'SubTotalCashTempoJmlHrg'    => $this->SubTotalCashTempoJmlHrg,
            'grandTotalAll'  => $this->grandTotalAll,
            'tanggalLabel'   => $this->tanggalLabel,
            'countCash'      => $this->countCash,
            'countTempo'     => $this->countTempo,
        ]);
    }
}

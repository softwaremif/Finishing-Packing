<?php

namespace App\View\Components;

use Illuminate\View\Component;
use Closure;
use Illuminate\Contracts\View\View;

class TableDefault extends Component
{
    public string $id;
    public ?string $title;

    public bool $search;
    public string $searchName;
    public string $searchPlaceholder;
    public int $searchWidth;

    public bool $buyer;
    public ?string $buyerUrl;
    public string $buyerName;
    public string $buyerValueField;
    public string $buyerTextField;
    public string $buyerMode;
    public int $buyerWidth;

    public bool $year, $sort;
    public string $yearName;
    public int $yearsBack;
    public string $yearAllLabel;
    public int $yearWidth;

    public bool $exfactory;
    public string $exfactoryName;
    public int $exfactoryWidth;

    // FIX UTAMA: property ini SEBELUMNYA tidak ada -- attribute yang
    // dikirim lewat <x-table-default sort-dropdown sort-asc-label="..."
    // sort-desc-label="..." sort-default="..."> diam-diam DIABAIKAN
    // Laravel karena class-based component WAJIB deklarasi eksplisit
    // setiap property yang mau diterima dari attribute Blade.
    public bool $sortDropdown;
    public string $sortAscLabel;
    public string $sortDescLabel;
    public string $sortDefault;

    public function __construct(
        ?string $id = null,
        ?string $title = null,

        bool $search = true,
        string $searchName = 'search',
        string $searchPlaceholder = 'Search...',
        int $searchWidth = 260,

        bool $buyer = true,
        ?string $buyerUrl = null,
        string $buyerName = 'buyer',
        string $buyerValueField = 'buyer',
        string $buyerTextField = 'buyer_name',
        string $buyerMode = 'remote',
        int $buyerWidth = 200,

        bool $year = true,
        bool $sort = true,
        string $yearName = 'year',
        int $yearsBack = 5,
        string $yearAllLabel = 'All Year',
        int $yearWidth = 120,

        bool $exfactory = false,
        string $exfactoryName = 'ex_factory',
        int $exfactoryWidth = 170,

        // BARU
        bool $sortDropdown = false,
        string $sortAscLabel = 'Terlama',
        string $sortDescLabel = 'Terbaru',
        string $sortDefault = 'desc'
    ) {
        $this->id = $id ?? 'dgDatagrid';
        $this->title = $title;

        $this->search = $search;
        $this->searchName = $searchName;
        $this->searchPlaceholder = $searchPlaceholder;
        $this->searchWidth = $searchWidth;

        $this->buyer = $buyer;
        $this->buyerUrl = $buyerUrl;
        $this->buyerName = $buyerName;
        $this->buyerValueField = $buyerValueField;
        $this->buyerTextField = $buyerTextField;
        $this->buyerMode = $buyerMode;
        $this->buyerWidth = $buyerWidth;

        $this->year = $year;
        $this->sort = $sort;
        $this->yearName = $yearName;
        $this->yearsBack = $yearsBack;
        $this->yearAllLabel = $yearAllLabel;
        $this->yearWidth = $yearWidth;

        $this->exfactory = $exfactory;
        $this->exfactoryName = $exfactoryName;
        $this->exfactoryWidth = $exfactoryWidth;

        // BARU
        $this->sortDropdown = $sortDropdown;
        $this->sortAscLabel = $sortAscLabel;
        $this->sortDescLabel = $sortDescLabel;
        $this->sortDefault = $sortDefault;
    }

    public function render()
    {
        return view('components.table-default');
    }
}
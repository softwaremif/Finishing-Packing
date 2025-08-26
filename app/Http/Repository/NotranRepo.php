<?php

namespace App\Http\Repository;

use App\Models\Notran;
use Exception;
use Illuminate\Support\Facades\DB;

class NotranRepo extends BaseRepo
{
    protected $model;

    public function __construct(Notran $model)
    {
        $this->model = $model;
    }

    public function generateNotran()
    {
        $notran = $this->model->where('tblnm', 'beli')->first();
        return $notran->no;
    }

    public function update()
    {
        return DB::transaction(function () {
            // Lock and retrieve the record
            $sj = $this->model->where('tblnm', 'beli')->lockForUpdate()->first();

            if (!$sj) {
                throw new Exception('Data notran tidak ditemukan');
            }

            // Increment the 'no' field
            $newValue = $sj->no + 1;

            // Update the record with the new value
            $this->model->where('tblnm', 'beli')->update(['no' => $newValue]);

            return $newValue;
        });
    }
}

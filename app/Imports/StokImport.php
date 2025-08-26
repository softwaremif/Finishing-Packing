<?php

namespace App\Imports;

use App\Models\NeedleStock;
use App\Models\Stok;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StokImport implements ToModel, WithHeadingRow
{
    public function model(array $row)
    {
        // Check if sawal exists, is numeric, and is not zero
        if (isset($row['sawal']) && is_numeric($row['sawal']) && $row['sawal'] > 0) {
            // dump([
            //     'stoknm' => $row['stoknm'] ?? null,
            //     'merk' => $row['merk'] ?? null,
            //     'codebrg' => $row['codebrg'] ?? null,
            //     'satuan' => "PCS",
            //     'sawal' => (int) $row['sawal'],
            //     'jnsbrgpk' => $row['jnsbrgpk'] ?? null,
            //     'userpk' => 7,
            //     'tgldibuat' => now()->format('Y-m-d')
            // ]);
            return new Stok([
                'stoknm' => $row['stoknm'] ?? null,
                'merk' => $row['merk'] ?? null,
                'codebrg' => $row['codebrg'] ?? null,
                'satuan' => "PCS",
                'sawal' => (int) $row['sawal'],
                'jnsbrgpk' => $row['jnsbrgpk'] ?? null,
                'userpk' => 7,
                'tgldibuat' => now()->format('Y-m-d')
            ]);
        }

        // Return null to skip rows where sawal is empty or zero
        return null;
    }
}

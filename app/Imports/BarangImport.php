<?php

namespace App\Imports;

use Maatwebsite\Excel\Concerns\ToModel;
use App\Models\Brg;
use Maatwebsite\Excel\Concerns\WithStartRow;

// use Maatwebsite\Excel\Concerns\ToCollection;
// use Illuminate\Support\Collection;

class BarangImport implements ToModel, WithStartRow
{
     public function startRow(): int
    {
        return 2;
    }

    // public function model(array $row)
    // {
    //     return new Brg([
    //         'brgnm'      => trim($row[0]),
    //         'noseri'     => trim($row[1]),
    //         'merknm'     => trim($row[2]),
    //         'qtyawal'    => is_numeric($row[3]) ? $row[3] : null,
    //         'lastupdate' => now(),
    //     ]);
    // }

    // public function model(array $row)
    // {
    //     return Brg::updateOrCreate(
    //         ['brgnm' => trim($row[0])], // Kolom yang harus unik
    //         [
    //             'noseri'     => trim($row[1]),
    //             'merknm'     => trim($row[2]),
    //             'qtyawal'    => is_numeric($row[3]) ? $row[3] : null,
    //             'lastupdate' => now(),
    //         ]
    //     );
    // }

    // public function model(array $row)
    // {
    //     // Cek berdasarkan kombinasi brgnm + noseri
    //     $existing = Brg::where('brgnm', trim($row[0]))
    //                 ->where('noseri', trim($row[1]))
    //                 ->first();

    //     if ($existing) {
    //         // Update jika sudah ada dengan noseri yang sama
    //         $existing->update([
    //             'merknm'     => trim($row[2]),
    //             'qtyawal'    => is_numeric($row[3]) ? $row[3] : null,
    //             'lastupdate' => now(),
    //         ]);
    //         return null;
    //     }
        
    //     // Buat baru jika tidak ada
    //     return new Brg([
    //         'brgnm'      => trim($row[0]),
    //         'noseri'     => trim($row[1]),
    //         'merknm'     => trim($row[2]),
    //         'qtyawal'    => is_numeric($row[3]) ? $row[3] : null,
    //         'lastupdate' => now(),
    //     ]);
    // }

    public function model(array $row)
    {
        $brgnm  = trim($row[0]);
        $noseri = trim($row[1]);
        $merknm = trim($row[2]);
        $qtyawal = is_numeric($row[3]) ? $row[3] : null;

        // Cek apakah ada barang dengan brgnm yg sama
        $sameBrg = Brg::where('brgnm', $brgnm)->first();

        if ($sameBrg) {
            // Jika noseri-nya berbeda → ubah nama brgnm (misal: "NEEDLE BAR-SA9353001")
            if ($sameBrg->noseri !== $noseri) {
                $brgnm = $brgnm . '-' . $noseri;
            } else {
                // Jika kombinasi brgnm + noseri sama, update saja
                $sameBrg->update([
                    'merknm'     => $merknm,
                    'qtyawal'    => $qtyawal,
                    'lastupdate' => now(),
                ]);
                return null;
            }
        }

        // Insert data baru
        return new Brg([
            'brgnm'      => $brgnm,
            'noseri'     => $noseri,
            'merknm'     => $merknm,
            'qtyawal'    => $qtyawal,
            'lastupdate' => now(),
        ]);
}

}

// class BarangImport implements ToCollection, WithStartRow
// {
//     public function startRow(): int
//     {
//         return 2;
//     }

//     public function collection(Collection $rows)
//     {
//         foreach ($rows as $row) {
//             $brgnm  = trim($row[0]);
//             $noseri = trim($row[1]);
//             $merknm = trim($row[2]);
//             $qtyawal = is_numeric($row[3]) ? $row[3] : null;

//             if ($brgnm === '' || $noseri === '' || $merknm === '') {
//                 continue; // Skip jika ada data penting yang kosong
//             }

//             $existing = Brg::where('brgnm', $brgnm)
//                         ->where('noseri', $noseri)
//                         ->where('merknm', $merknm)
//                         ->first();

//             if ($existing) {
//                 // Jika qtyawal berbeda, update
//                 if ($existing->qtyawal != $qtyawal) {
//                     $existing->qtyawal = $qtyawal;
//                     $existing->lastupdate = now();
//                     $existing->save();
//                 }
//             } else {
//                 // Insert baru
//                 Brg::create([
//                     'brgnm' => $brgnm,
//                     'noseri' => $noseri,
//                     'merknm' => $merknm,
//                     'qtyawal' => $qtyawal,
//                     'lastupdate' => now(),
//                 ]);
//             }
//         }
//     }
// }

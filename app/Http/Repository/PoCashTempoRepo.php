<?php

namespace App\Http\Repository;

use App\Models\PoCashTempo;
use App\Models\PoCashTempoDetail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class PoCashTempoRepo extends BaseRepo
{
    protected $model;

    public function __construct(PoCashTempo $model)
    {
        $this->model = $model;
    }

    public function getPoCashTempo(
        $guserPk,
        $userPk,
        $defaultYear,
        $page,
        $perPage,
        $search = null,
        $month = null,
        // $tempo = null,
        $year = null,
        $sortByDate = 11
    ): array {
        $query = $this->buildBaseQuery();

        $query = $this->applyFilters(
            $query,
            $guserPk,
            $userPk,
            $search,
            $month,
            // $tempo,
            $year ?? $defaultYear
        );

        $query = $this->applySorting($query, $sortByDate);

        return $this->getPaginatedResults($query, $page, $perPage);
    }

    private function buildBaseQuery(): Builder
    {
        return $this->model
            ->leftJoin('user', 'user.userpk', '=', 'beli.userpk')
            ->leftJoin('sup', 'sup.suppk', '=', 'beli.suppk')
            ->leftJoin('ab', 'ab.abpk', '=', 'beli.abpk')
            ->leftJoin('cur', 'cur.curpk', '=', 'beli.curpk')
            ->select([
                'beli.belipk',
                'beli.nobukti',
                'beli.noinv',
                'beli.tglinv',
                'user.userpk',
                'user.guserpk',
                'user.login',
                'sup.suppk',
                'sup.supnm',
                'cur.curpk',
                'cur.curid',
                'cur.curnm',
                'ab.abpk',
                'ab.abnm',
                'beli.totbeli',
                'beli.ket',
                DB::raw('DATE_FORMAT(beli.tglinv, "%d %b %Y") as tanggal'),
            ]);
    }

    private function applyFilters(
        Builder $query,
        $guserPk,
        $userPk,
        $search,
        $month,
        // $tempo,
        $year
    ): Builder {
        if ($guserPk != 6) {
            $query->where('beli.userpk', $userPk);
        }

        // if ($guserPk == 6) {
        //     $query;
        // }else{
        //     $query->where('beli.userpk', $userPk);
        // }

        // Apply search filter
        if ($search) {
            $searchableFields = DB::raw("CONCAT(
                COALESCE(beli.nobukti, ''),
                COALESCE(beli.noinv, ''),
                COALESCE(user.login, ''),
                COALESCE(sup.supnm, ''),
                COALESCE(CAST(beli.totbeli AS CHAR), ''),
                COALESCE(ab.abnm, ''),
                COALESCE(cur.curnm, '')
            )");
            $query->where($searchableFields, 'like', "%{$search}%");
        }

        // Apply date filters
        $query->whereYear('beli.tglinv', $year);

        if ($month) {
            $query->whereMonth('beli.tglinv', $month);
        }

        // if ($tempo) {
        //     $query->where('beli.abpk', $tempo);
        // }

        return $query;
    }

    private function applySorting(Builder $query, $sortByDate): Builder
    {
        if ($sortByDate) {
            $direction = $sortByDate === 12 ? 'asc' : 'desc';
            return $query->orderBy('beli.tglinv', $direction);
        } else {
            return $query->orderBy('beli.nobukti', 'desc');
        }
    }

    private function getPaginatedResults(Builder $query, int $page, int $perPage): array
    {
        $total = $query->count();
        $offset = ($page - 1) * $perPage;

        $data = $query->skip($offset)
            ->take($perPage)
            ->get();

        $rows = $data->map(function ($item, $index) use ($offset) {
            return [
                'index' => $offset + $index + 1,
                'belipk' => $item->belipk,
                'nobukti' => $item->nobukti,
                'tanggal' => $item->tglinv,
                'noinv' => $item->noinv,
                'supnm' => $item->supnm,
                'curid' => $item->curid,
                'term' => $item->abnm,
                'totbeli' => number_format($item->totbeli, 2, ',', '.'),
                'user' => strtoupper($item->login),
            ];
        })->toArray();

        return [
            'total' => $total,
            'page' => $page,
            'perPage' => $perPage,
            'offset' => $offset,
            'rows' => $rows,
        ];
    }

    public function store($data)
    {
        try {
            DB::beginTransaction();
            $details = $data['details'];
            $filteredData = collect($data)->except('details')->all();
            $beli = $this->model->create($filteredData);
            // Simpan detail
            foreach ($details as $detail) {
                $beli->details()->create([
                    'brgnm' => $detail['brgnm'],
                    'jmlbeli' => $detail['jmlbeli'],
                    'unit' => $detail['unit'],
                    'hrgbeli' => $detail['hrgbeli'],
                    'jmlhrg' => $detail['jmlhrg'],
                    'cg' => $beli->abpk == 1 ? 'c' : null,
                    'tglbayar' => $beli->abpk == 1 ? $filteredData['tglinv'] : null,
                    'jmlbayar' => $beli->abpk == 1 ? $detail['jmlhrg'] : null,
                ]);
            }

            // Commit transaksi
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil disimpan!'
            ]);
        } catch (\Exception $e) {
            // Rollback jika gagal
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menyimpan data: ' . $e->getMessage()
            ], 500);
        }
    }

    public function update($id, $data)
    {
        try {
            DB::beginTransaction();

            $details = $data['details'];
            $filteredData = collect($data)->except('details')->all();

            // Ambil data utama
            $beli = $this->model->findOrFail($id);

            // Update data utama
            $beli->update($filteredData);

            // Hapus semua detail lama
            $beli->details()->delete();

            // Tambahkan kembali detail baru
            foreach ($details as $key => $detail) {
                $beli->details()->create([
                    'brgnm' => $detail['brgnm'],
                    'jmlbeli' => $detail['jmlbeli'],
                    'unit' => $detail['unit'],
                    'hrgbeli' => $detail['hrgbeli'],
                    'jmlhrg' => $detail['jmlhrg'],
                    'nour' => $key + 1,
                    'cg' => $beli->abpk == 1 ? 'C' : "G",
                    'tglbayar' => $beli->abpk == 1 ? $filteredData['tglinv'] : null,
                    'jmlbayar' => $beli->abpk == 1 ? $detail['jmlhrg'] : null,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Data berhasil diupdate!'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengupdate data: ' . $e->getMessage()
            ], 500);
        }
    }
}

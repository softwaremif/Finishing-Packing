<?php

namespace App\Services;

use App\Repositories\ShipRepository;
use Illuminate\Http\Request;

class ShipService
{
    protected ShipRepository $shipRepo;

    public function __construct(ShipRepository $shipRepo)
    {
        $this->shipRepo = $shipRepo;
    }

    public function getListShip(
        Request $request
    ): array {

        $page   = (int) ($request->page ?? 1);
        $rows   = (int) ($request->rows ?? 50);
        $offset = ($page - 1) * $rows;

        $connection = session('pos') == 2
            ? 'mysql'
            : 'mysql_andon';

        $result = $this->shipRepo->getShip(
            $connection,
            $request,
        );

        foreach ($result['data'] as $i => $row) {
            $row->no = $offset + $i + 1;
        }

        return [
            'total' => $result['total'],
            'rows'  => $result['data'],
        ];
    }

    // service
    public function buildDetailShiping($dt, $customer){
        $build = $this->shipRepo->buildDetailShiping($dt, $customer);

        foreach ($build['groups'] as $g) {
            $size = null;
            for ($i = 1; $i <= 40; $i++) {
                if (($g->{"qtyp{$i}"} ?? 0) > 0) $size = $dt->{"size{$i}"} ?? null;
            }

            $cartons = $build['allCartons']->get($g->urut . '|' . $g->pcsp, collect());

            $cartonRows = [];
            foreach ($cartons as $c) {
                $label = !empty($c->keterangan)
                    ? "{$c->carton} ({$c->keterangan})"
                    : $c->carton;
                $width = !empty($c->keterangan) ? '15%' : '7%';

                // $c, $g->pcsp, $c->pcs
                $style = "width:{$width};";
                if ($c->status == 4) {
                    if ($g->pcsp == $c->pcs)
                        $style = "width:{$width}; background-color:#8FBC8F; color:#000;";
                    elseif ($c->pcs < $g->pcsp && $c->pcs > 0)
                        $style = "width:{$width}; background-color:#DC143C; color:#000;";
                } elseif ($c->status == 5) {
                    $style = "width:{$width}; background-color:#8FBC8F; color:#000;";
                }
                $cartonRows[] = ['label' => $label, 'style' => $style];
            }

            $result[] = [
                'size'       => $size,
                'ctn'        => $g->ctn,
                'pcsp'       => $g->pcsp,
                'cartonRows' => $cartonRows,
            ];

            return $result;
        }
    }
}
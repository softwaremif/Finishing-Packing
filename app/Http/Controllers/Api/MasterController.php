<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
// use Illuminate\Http\Request;
use DB;

class MasterController extends Controller
{


    public function get_ab()
    {
        $data = DB::table('ab')->orderBy('abnm', 'asc')->get()->toArray();
        $data = array_map(function ($item) {
            $item->abnm = trim($item->abnm);
            return $item;
        }, $data);

        return json_encode($data);
    }

    public function get_sup()
    {
        $data = DB::table('sup')->orderBy('supnm', 'asc')->get()->toArray();
        $data = array_map(function ($item) {
            $item->supnm = trim($item->supnm);
            return $item;
        }, $data);

        return json_encode($data);
    }

    public function get_cur()
    {
        $data = DB::table('cur')->orderBy('curid', 'asc')->get()->toArray();
        $data = array_map(function ($item) {
            $item->curid = trim($item->curid); // Menghapus spasi di awal & akhir
            return $item;
        }, $data);

        return json_encode($data);
    }

    public function get_user()
    {
        $data = DB::table('user')->orderBy('login', 'asc')->get()->toArray();
        $data = array_map(function ($item) {
            $item->login = trim($item->login);
            return $item;
        }, $data);

        return json_encode($data);
    }

    public function get_dep()
    {
        $data = DB::table('dep')->orderBy('depnm', 'asc')->get()->toArray();
        $data = array_map(function ($item) {
            $item->depnm = trim($item->depnm);
            return $item;
        }, $data);

        return json_encode($data);
    }

    public function get_penerima()
    {
        $data = DB::table('dep')->where('req', 1)->orderBy('depnm', 'asc')->get()->toArray();
        $data = array_map(function ($item) {
            $item->depnm = trim($item->depnm);
            return $item;
        }, $data);

        return json_encode($data);
    }

    public function get_jenis()
    {
        $data = DB::table('kel')->orderBy('kelnm', 'asc')->get()->toArray();
        $data = array_map(function ($item) {
            $item->kelnm = trim($item->kelnm);
            return $item;
        }, $data);

        return json_encode($data);
    }

    public function get_mif()
    {
        $data = DB::table('mif')->orderBy('mifnm', 'asc')->get()->toArray();
        $data = array_map(function ($item) {
            $item->mifnm = trim($item->mifnm);
            return $item;
        }, $data);

        return json_encode($data);
    }

    public function get_jnsbrg()
    {
        $data = DB::table('jnsbrg')->orderBy('jnsbrgpk', 'asc')->get();

        return json_encode($data);
    }
}

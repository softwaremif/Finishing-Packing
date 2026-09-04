<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function index()
    {
        Session::flush();
        Session::regenerate();

        return view('pages.login');
    }

    // 17: QA, FCA
    // 23: Adm Finishing
    // 34: Support
    // 35: Stuffing
    // 36: LO, Gudang LO
    // 38: Fingoods, Adm Fingoods

    // login Mas Riyan
    public function login(Request $request)
    {
        $user = $request->input('username');
        $pass = $request->input('password');
        $url = null;
        $hari = Carbon::now();

        // $akses = DB::connection('mysql_akses')->table('user')->where('username', $user)
        //     ->where('password', $pass)->first();

        $akses = DB::connection('mysql_akses')->table('user')
            ->leftJoin('userdt', 'userdt.userpk', '=', 'user.userpk')
            ->leftJoin('guser', 'guser.guserpk', '=', 'user.guserpk')
            ->select(
                'user.userpk',
                'user.guserpk',
                'user.username',
                'user.password',
                'user.login',
                'userdt.webpk',
                'user.datechange',
                'user.pos',
                'guser.gusernm',
            )
            ->where('user.username', $user)
            ->where('user.password', $pass)
            ->where('userdt.webpk', 19)
            ->whereIn('user.guserpk', [17, 23, 35, 36, 34, 37, 38])
            ->first();

        if (!$akses) {
            return response()->json([
                'success' => false,
                'message' => 'Username / Password Salah',
                'route' => $url
            ], 422);
        }

        // $akses = DB::table('user')->where('username', $akses->username)->first();

        if (!empty($akses->userpk)) {
            DB::connection('mysql_akses')->table('user')
                ->where('user.userpk', $akses->userpk)
                ->update([
                    'datelogin' => now()
                ]);
        }

        if (empty($akses)) return response()->json(['success' => false, 'message' => "Informasi salah, silahkan coba lagi", 'route' => null], 400);
        if (!empty($akses->userpk)) Session::put('userpk', $akses->userpk);
        if (!empty($akses->gusernm)) Session::put('gusernm', $akses->gusernm);
        if (!empty($akses->guserpk)) Session::put('guserpk', $akses->guserpk);
        if (!empty($akses->login)) Session::put('login', $akses->login);
        if (!empty($akses->username)) Session::put('username', $akses->username);
        if (!empty($akses->pos)) Session::put('pos', $akses->pos);
        if ($akses->password) Session::put('password', $akses->password);

        if ($akses->datechange < $hari) {
            $url = "password";
        } else {
            if ($akses->guserpk == 35) {
                $url = "finish-good-stuffing";
            } else if ($akses->guserpk == 36) {
                $url = "sisa-produksi";
            } else if ($akses->guserpk == 37) {
                $url = "packing";
            }else if ($akses->guserpk == 17) {
                $url = "inspection";
            } else if ($akses->guserpk == 38) {
                $url = "finish-good-stuffing";
            }else {
                $url = "tf-finishing";
            }
        }

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $akses,
            'route' => $url
        ], 200);
    }

    public function logout()
    {
        Session::flush();
        Session::regenerate();

        return redirect('/');
    }

    public function password()
    {
        return view('pages.password');
    }

    public function updtpswdb(Request $request)
    {
        $user = $request->userpk;
        $pswd = $request->pswd;
        $cnfrm = $request->cnfrm;
        $emailp = Session::get('username');
        $date = date("Y-m-d");
        $next = date("Y-m-d", strtotime("+6 month", strtotime($date)));

        if (!empty($emailp)) {

            DB::table('user')
                ->where('username', $emailp)
                ->update([
                    'password' => $cnfrm,
                ]);

            $akses = DB::connection('mysql_akses')->table('user')
                // ->where('userpk', $user)
                ->where('username', $emailp)
                ->update([
                    'password' => $cnfrm,
                    'datechange' => $next,
                    'dateupdate' => now(),
                    'datelogin' => now()
                ]);
        }

        return response()->json(['success' => true, 'message' => 'Data saved successfully']);
    }
}

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

    // public function login(Request $request)
    // {
    //     // $nrk = $request->input('nrk');
    //     $user = $request->input('username');
    //     $pass = $request->input('password');
    //     $url = null;

    //     $users = DB::connection('mysql_akses')->table('user')
    //         ->leftJoin('userdt', 'userdt.userpk', '=', 'user.userpk')
    //         ->leftJoin('guser', 'guser.guserpk', '=', 'user.guserpk')
    //         ->where(['user.username' => $user])
    //         ->where(['user.password' => $pass])
    //         ->where('userdt.webpk', 13)
    //         ->first();

    //     if (empty($users)) return response()->json(['success' => false, 'message' => "Informasi salah, silahkan coba lagi", 'route' => null], 400);

    //     if (!empty($users->userpk)) Session::put('userpk', $users->userpk);
    //     if (!empty($users->guserpk)) Session::put('guserpk', $users->guserpk);
    //     if (!empty($users->guserid)) Session::put('guserid', $users->guserid);
    //     if (!empty($users->fupkgis)) Session::put('fupkgis', $users->fupkgis);
    //     if (!empty($users->webpk)) Session::put('webpk', $users->webpk);
    //     if (!empty($users->stsakses)) Session::put('stsakses', $users->stsakses);
    //     if (!empty($users->login)) Session::put('login', $users->login);
    //     if (!empty($users->username)) Session::put('username', $users->username);
    //     if (!empty($users->pos)) Session::put('pos', $users->pos);
    //     if (!empty($users->pos)) Session::put('mif', $users->pos);
    //     if ($users->password) Session::put('password', $users->password);

    //     if ($users->stsakses == 1) {
    //         if ($users->fupkgis > 0) $url = "list-invoice";
    //         elseif ($users->userpk == 23) $url = "list-invoice";
    //         else $url = "surat/index";

    //         DB::connection('mysql_akses')->table('user')
    //             ->where('userpk', $users->userpk)
    //             ->update([
    //                 'datelogin' => NOW()
    //             ]);
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => '',
    //         'data' => $users,
    //         'route' => $url
    //     ], 200);
    // }

    // public function loginAsli(Request $request)
    // {
    //     $user = $request->input('username');
    //     $pass = $request->input('password');
    //     $url = null;

    //     $users = DB::table('user')
    //         // ->leftJoin('userdt', 'userdt.userpk', '=', 'user.userpk')
    //         ->leftJoin('guser', 'guser.guserpk', '=', 'user.guserpk')
    //         // ->where(['user.login' => $user])
    //         ->where(['user.username' => $user])
    //         ->where(['user.password' => $pass])
    //         ->first();

    //     // $users = DB::connection('mysql_akses')->table('user')
    //     //     ->leftJoin('userdt', 'userdt.userpk', '=', 'user.userpk')
    //     //     ->leftJoin('guser', 'guser.guserpk', '=', 'user.guserpk')
    //     //     ->where(['user.username' => $user])
    //     //     ->where(['user.password' => $pass])
    //     //     ->where('userdt.webpk', 13)
    //     //     ->first();

    //     if (empty($users)) return response()->json(['success' => false, 'message' => "Informasi salah, silahkan coba lagi", 'route' => null], 400);

    //     if (!empty($users->userpk)) Session::put('userpk', $users->userpk);
    //     if (!empty($users->guserpk)) Session::put('guserpk', $users->guserpk);
    //     if (!empty($users->deppk)) Session::put('deppk', $users->deppk);
    //     if (!empty($users->login)) Session::put('login', $users->login);
    //     if (!empty($users->username)) Session::put('username', $users->username);
    //     if ($users->password) Session::put('password', $users->password);

    //     // if ($users->stsakses == 1) {
    //     //     if ($users->fupkgis > 0) $url = "list-invoice";
    //     //     elseif ($users->userpk == 23) $url = "list-invoice";
    //     //     else 
    //         $url = "purchase-request";

    //         // DB::connection('mysql_akses')->table('user')
    //         //     ->where('userpk', $users->userpk)
    //         //     ->update([
    //         //         'datelogin' => NOW()
    //         //     ]);
    //     // }

    //     return response()->json([
    //         'success' => true,
    //         'message' => '',
    //         'data' => $users,
    //         'route' => $url
    //     ], 200);
    // }


    // Login mas Sandro
    // public function login(Request $request)
    // {
    //     $user = $request->input('username');
    //     $pass = $request->input('password');
    //     $url = null;
    //     $hari = Carbon::now();

    //     $users = DB::connection('mysql_akses')->table('user')
    //         ->leftJoin('userdt', 'userdt.userpk', '=', 'user.userpk')
    //         ->leftJoin('guser', 'guser.guserpk', '=', 'user.guserpk')
    //         ->where(['user.username' => $user])
    //         ->where(['user.password' => $pass])
    //         // ->where('userdt.webpk', 13)
    //         ->first();

    //     // $users = DB::table('user')
    //     //     ->leftJoin('guser', 'guser.guserpk', '=', 'user.guserpk')
    //     //     ->where(['user.username' => $user])
    //     //     ->where(['user.password' => $pass])
    //     //     ->first();

    //     if (!empty($users->userpk)) {
    //         DB::connection('mysql_akses')->table('user')
    //             ->where('user.userpk', $users->userpk)
    //             ->update([
    //                 'datelogin' => now()
    //             ]);
    //     }
    //     // dd($users->username);

    //     if (empty($users)) return response()->json(['success' => false, 'message' => "Informasi salah, silahkan coba lagi", 'route' => null], 400);
    //     if (!empty($users->userpk)) Session::put('userpk', $users->userpk);
    //     if (!empty($users->guserpk)) Session::put('guserpk', $users->guserpk);
    //     if (!empty($users->deppk)) Session::put('deppk', $users->deppk);
    //     if (!empty($users->login)) Session::put('login', $users->login);
    //     if (!empty($users->username)) Session::put('username', $users->username);
    //     if ($users->password) Session::put('password', $users->password);

    //     if ($users->datechange < $hari) {
    //         $url = "password";
    //     } else {
    //         $url = "purchase-request";
    //     }

    //     return response()->json([
    //         'success' => true,
    //         'message' => '',
    //         'data' => $users,
    //         'route' => $url
    //     ], 200);
    // }

    // login Mas Riyan
    public function login(Request $request)
    {
        $user = $request->input('username');
        $pass = $request->input('password');
        $url = null;
        $hari = Carbon::now();

        $akses = DB::connection('mysql_akses')->table('user')->where('username', $user)
            ->where('password', $pass)->first();
        if (!$akses) {
            return response()->json([
                'success' => false,
                'message' => 'Username / Password Salah',
                'route' => $url
            ], 422);
        }

        $users = DB::table('user')->where('username', $akses->username)->first();

        if (!empty($users->userpk)) {
            DB::connection('mysql_akses')->table('user')
                ->where('user.userpk', $users->userpk)
                ->update([
                    'datelogin' => now()
                ]);
        }

        if (empty($users)) return response()->json(['success' => false, 'message' => "Informasi salah, silahkan coba lagi", 'route' => null], 400);
        if (!empty($users->userpk)) Session::put('userpk', $users->userpk);
        if (!empty($users->guserpk)) Session::put('guserpk', $users->guserpk);
        if (!empty($users->deppk)) Session::put('deppk', $users->deppk);
        if (!empty($users->login)) Session::put('login', $users->login);
        if (!empty($users->username)) Session::put('username', $users->username);
        if ($users->password) Session::put('password', $users->password);

        if ($akses->datechange < $hari) {
            $url = "password";
        } else {
            // $url = "purchase-cash-tempo";
            $url = "purchase-request";
        }

        return response()->json([
            'success' => true,
            'message' => '',
            'data' => $users,
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

			$users = DB::connection('mysql_akses')->table('user')
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

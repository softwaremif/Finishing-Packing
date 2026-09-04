<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;

class SimpanController extends Controller
{
	public function index()
	{
		return view('login');
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
			->where('userpk', $user)
			->update([
				'password' => $cnfrm,
				'datechange' => $next,
				'dateupdate' => now(),
				'datelogin' => now()
			]);
		}

		return response()->json(['success' => true, 'message' => 'Data saved successfully']);
	}

	// public function updtpswd2b(Request $request)
	// {
	// 	$user = $request->input('userpk');
	// 	$pswdl = $request->input('pswdl');
	// 	$pswdb = $request->input('pswdb');
	// 	$cnfrm = $request->input('cnfrm');
	// 	$date = date("Y-m-d");
	// 	$next = date("Y-m-d", strtotime("+6 month", strtotime($date)));

	// 	DB::table('user')
	// 		->where('userpk', $user)
	// 		->update([
	// 			'password' => $cnfrm,
	// 			'datechange' => $next
	// 		]);
	// 	return response()->json(['success' => true, 'message' => 'Data saved successfully']);
	// }

}

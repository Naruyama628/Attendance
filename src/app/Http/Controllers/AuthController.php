<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\AdminAuthRequest;

class AuthController extends Controller
{
    //
    public function register() {
        return view('auth.register');
    }

    public function login() {
        return view('auth.login');
    }

    public function adminShowLogin() {
        return view('admin.auth.login');
    }

    public function adminLogin(AdminAuthRequest $request) {
        $credentials = $request->only('email', 'password');

        // ログイン試行
        if (!Auth::attempt($credentials)) {
            return back()->withErrors([ 'email' => 'ログイン情報が登録されていません', ]);
        }

        if(Auth::user()->role !== 'admin') {
            Auth::logout();
            return back()->withErrors([ 'email' => '管理者アカウントではありません', ]);
        }

        // セッション再生成
        $request->session()->regenerate();
        return redirect('/admin/attendance/list');
    }
}

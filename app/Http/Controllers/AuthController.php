<?php

namespace App\Http\Controllers;

use App\Constants\UserConst;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function login()
    {
        return view('_admin.auth.login');
    }

    public function doLogin(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials)) {
            $request->session()->regenerate();

            return $this->redirectByRole(Auth::user());
        }

        return back()->withErrors([
            'login_error' => 'Email atau Password tidak sesuai, periksa kembali',
        ])->onlyInput('email');
    }

    private function redirectByRole($user)
    {
        switch ($user->role) {
            case UserConst::ROLE_SUPERADMIN:
            case UserConst::ROLE_OJEK_ADMIN:
                return redirect()->route('admin.dashboard');
            case UserConst::ROLE_UMKM:
                return redirect()->route('app.umkm.profile.edit');
            case UserConst::ROLE_PENGGUNA:
                return redirect()->to('/app');
            case UserConst::ROLE_DRIVER:
                return redirect()->to('/driver/orders');
            default:
                if ($user->access_type === UserConst::SUPERADMIN) {
                    return redirect()->route('admin.dashboard');
                }
                return redirect()->intended(route('admin.dashboard'));
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showLogin()
    {
        if (Auth::check()) {
            $user = auth()->user();
            if ($user->isDepartemen()) {
                return redirect()->route('departemen.dashboard');
            }

            if ($user->isFAT() || $user->isSuperAdmin()) {
                return redirect()->route('fat.departments.index');
            }

            return redirect()->route('dashboard.index');
        }

        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (Auth::attempt($credentials, true)) {
            $request->session()->regenerate();

            $user = auth()->user();
            if ($user->isDepartemen()) {
                return redirect()->route('departemen.dashboard');
            }

            if ($user->isFAT() || $user->isSuperAdmin()) {
                return redirect()->route('fat.departments.index');
            }

            return redirect()->route('dashboard.index');
        }

        return back()->withErrors([
            'email' => 'Email atau password tidak valid.',
        ])->onlyInput('email');
    }

    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}

<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function loginForm()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate(['email' => ['required', 'email'], 'password' => ['required']]);
        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Email atau kata sandi tidak cocok.']);
        }
        if (! Auth::user()->canVerifyPayments()) {
            Auth::logout();
            throw ValidationException::withMessages(['email' => 'Login hanya tersedia untuk administrator dan tim keuangan.']);
        }
        $request->session()->regenerate();

        if (Auth::user()->isFinance()) {
            return redirect()->route('admin.payments');
        }

        return redirect()->intended(route('admin.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}

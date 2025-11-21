<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Tampilkan halaman login
     */
    public function showLogin()
    {
        // Jika sudah login, redirect berdasarkan role
        if (Auth::check()) {
            return $this->redirectByRole();
        }

        return view('auth.login');
    }

    /**
     * Proses login
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ], [
            'email.required' => 'Email harus diisi',
            'email.email' => 'Format email tidak valid',
            'password.required' => 'Password harus diisi',
        ]);

        $remember = $request->boolean('remember');

        // Cek kredensial dan user aktif
        if (Auth::attempt(array_merge($credentials, ['is_active' => true]), $remember)) {
            $request->session()->regenerate();

            // Redirect berdasarkan role
            return $this->redirectByRole();
        }

        throw ValidationException::withMessages([
            'email' => 'Email atau password salah, atau akun Anda tidak aktif.',
        ]);
    }

    /**
     * Logout
     */
    public function logout(Request $request)
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'Anda berhasil logout.');
    }

    /**
     * Redirect berdasarkan role user
     */
    protected function redirectByRole()
    {
        $user = Auth::user();

        // Admin & Manager → Admin Dashboard
        if ($user->hasRole(['admin', 'manager'])) {
            return redirect()->intended(route('admin.dashboard'));
        }

        // Kasir → POS Dashboard
        if ($user->hasRole('kasir')) {
            return redirect()->intended(route('pos.dashboard'));
        }

        // Kitchen → Kitchen Display
        if ($user->hasRole('kitchen')) {
            return redirect()->intended(route('pos.kitchen.index'));
        }

        // Default ke admin dashboard
        return redirect()->route('admin.dashboard');
    }
}


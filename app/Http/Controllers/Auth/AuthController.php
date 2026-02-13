<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_DECAY_SECONDS = 900; // 15 menit

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
        $throttleKey = $this->throttleKey($request);

        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = max(1, (int) ceil($seconds / 60));

            throw ValidationException::withMessages([
                'email' => "Terlalu banyak percobaan login. Coba lagi dalam {$minutes} menit.",
            ]);
        }

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

            // Cek status outlet (jika user terikat dengan outlet)
            $user = Auth::user();
            if ($user->outlet_id && $user->outlet && !$user->outlet->is_active) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                RateLimiter::hit($throttleKey, self::LOGIN_DECAY_SECONDS);

                throw ValidationException::withMessages([
                    'email' => 'Outlet Anda sedang dinonaktifkan. Silakan hubungi admin.',
                ]);
            }

            if (Auth::user()?->hasRole('karyawan_outlet')) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                RateLimiter::hit($throttleKey, self::LOGIN_DECAY_SECONDS);

                throw ValidationException::withMessages([
                    'email' => 'Akun karyawan outlet tidak memiliki akses login.',
                ]);
            }

            RateLimiter::clear($throttleKey);

            // Redirect berdasarkan role
            return $this->redirectByRole();
        }

        RateLimiter::hit($throttleKey, self::LOGIN_DECAY_SECONDS);

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

        if ($user->hasRole('karyawan_outlet')) {
            Auth::logout();
            if (request()->hasSession()) {
                request()->session()->invalidate();
                request()->session()->regenerateToken();
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Akun karyawan outlet tidak memiliki akses login.',
            ]);
        }

        Auth::logout();
        if (request()->hasSession()) {
            request()->session()->invalidate();
            request()->session()->regenerateToken();
        }

        return redirect()->route('login')->withErrors([
            'email' => 'Role akun tidak dikenali. Hubungi admin.',
        ]);
    }

    /**
     * Kunci rate limiter per kombinasi email + IP.
     */
    private function throttleKey(Request $request): string
    {
        $email = mb_strtolower((string) $request->input('email', ''));

        return 'login:' . $email . '|' . $request->ip();
    }
}

<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOGIN_DECAY_SECONDS = 900; // 15 menit
    private const SINGLE_DEVICE_ROLES = ['kasir', 'kitchen'];

    /**
     * Tampilkan halaman login
     */
    public function showLogin(Request $request)
    {
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

        if (Auth::attempt(array_merge($credentials, ['is_active' => true]), $remember)) {
            $request->session()->regenerate();

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

            $user = Auth::user();
            $this->clearStaleActivePosDevice($user);
            $device = $this->resolvePosDevice($request);
            if ($user && $device && $user->outlet_id && (int) $device->outlet_id !== (int) $user->outlet_id) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                RateLimiter::hit($throttleKey, self::LOGIN_DECAY_SECONDS);

                throw ValidationException::withMessages([
                    'email' => 'Perangkat browser ini terdaftar untuk outlet lain. Silakan lakukan pairing ulang untuk outlet Anda.',
                ]);
            }

            if ($user && $device && $user->hasRole(self::SINGLE_DEVICE_ROLES)
                && (int) $user->active_pos_device_id !== (int) $device->id) {
                $user->forceFill(['active_pos_device_id' => $device->id])->save();
            }

            $this->rememberDeviceUserLogin($request);

            RateLimiter::clear($throttleKey);

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
        $user = Auth::user();
        $isPosRole = $user && $user->hasRole(['kasir', 'kitchen']);
        $device = $this->resolvePosDevice($request);
        $redirectToPin = $isPosRole && $device !== null;

        if ($user && $device && $isPosRole && (int) $user->active_pos_device_id === (int) $device->id) {
            $user->forceFill(['active_pos_device_id' => null])->save();
        }

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($redirectToPin) {
            return redirect()->route('pos.pin.show')->with('success', 'Anda berhasil logout.');
        }

        return redirect()->route('login', ['email' => 1])->with('success', 'Anda berhasil logout.');
    }

    /**
     * Redirect berdasarkan role user.
     */
    protected function redirectByRole()
    {
        $user = Auth::user();

        if ($user->hasRole('superadmin')) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if ($user->hasRole('admin')) {
            $landingRoute = $this->resolveAdminLandingRoute($user);
            if ($landingRoute !== null) {
                return redirect()->intended(route($landingRoute));
            }

            Auth::logout();
            if (request()->hasSession()) {
                request()->session()->invalidate();
                request()->session()->regenerateToken();
            }

            return redirect()->route('login')->withErrors([
                'email' => 'Akun admin belum memiliki hak akses back office. Hubungi superadmin.',
            ]);
        }

        if ($user->hasRole('kasir')) {
            return redirect()->intended(route('pos.dashboard'));
        }

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
     * Tentukan landing page admin berdasarkan permission pertama yang dimiliki.
     */
    private function resolveAdminLandingRoute(User $user): ?string
    {
        $routePermissions = [
            'admin.dashboard' => 'dashboard.view',
            'admin.products.index' => 'products.view',
            'admin.outlets.index' => 'outlets.view',
            'admin.suppliers.index' => 'suppliers.view',
            'admin.payment-methods.index' => 'payment-methods.view',
            'admin.customers.index' => 'customers.view',
            'admin.cash-accounts.index' => 'cash-accounts.view',
            'admin.coa-accounts.index' => 'coa-accounts.view',
            'admin.stocks.index' => 'stocks.view',
            'admin.boms.index' => 'boms.view',
            'admin.purchases.index' => 'purchases.view',
            'admin.cash-transactions.index' => 'cash-transactions.view',
            'admin.promo-vouchers.index' => 'promo-vouchers.view',
            'admin.reports.index' => 'reports.view',
            'admin.pos-devices.index' => 'pos-devices.view',
            'admin.void-tokens.index' => 'void-tokens.view',
        ];

        foreach ($routePermissions as $routeName => $permissionKey) {
            if ($user->hasPermission($permissionKey)) {
                return $routeName;
            }
        }

        return null;
    }

    /**
     * Kunci rate limiter per kombinasi email + IP.
     */
    private function throttleKey(Request $request): string
    {
        $email = mb_strtolower((string) $request->input('email', ''));

        return 'login:' . $email . '|' . $request->ip();
    }

    /**
     * Bersihkan penandaan device aktif yang sudah tidak valid
     * (mis. device lama/revoked atau outlet device tidak sama dengan outlet user saat ini).
     */
    private function clearStaleActivePosDevice(?User $user): void
    {
        if (!$user || !$user->hasRole(self::SINGLE_DEVICE_ROLES) || !$user->active_pos_device_id) {
            return;
        }

        $activeDevice = PosDevice::query()
            ->select(['id', 'outlet_id', 'is_active', 'revoked_at'])
            ->find($user->active_pos_device_id);

        $isStale = !$activeDevice
            || !$activeDevice->is_active
            || $activeDevice->revoked_at !== null
            || ($user->outlet_id && (int) $activeDevice->outlet_id !== (int) $user->outlet_id);

        if ($isStale) {
            $user->forceFill(['active_pos_device_id' => null])->save();
            $user->refresh();
        }
    }

    private function resolvePosDevice(Request $request): ?PosDevice
    {
        $token = $request->cookie(config('pos.device_cookie', 'pos_device_token'));
        if (!$token) {
            return null;
        }

        return PosDevice::query()
            ->where('token_hash', hash('sha256', $token))
            ->where('is_active', true)
            ->whereNotNull('paired_at')
            ->whereNull('revoked_at')
            ->first();
    }

    private function rememberDeviceUserLogin(Request $request): void
    {
        $user = Auth::user();
        if (!$user || !$user->hasRole(['kasir', 'admin', 'kitchen'])) {
            return;
        }

        $device = $this->resolvePosDevice($request);
        if (!$device || !$user->outlet_id || $device->outlet_id !== $user->outlet_id) {
            return;
        }

        DB::table('pos_device_user_logins')->upsert([
            [
                'pos_device_id' => $device->id,
                'user_id' => $user->id,
                'last_login_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['pos_device_id', 'user_id'], ['last_login_at', 'updated_at']);
    }
}

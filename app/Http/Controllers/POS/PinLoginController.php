<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PinLoginController extends Controller
{
    private const MAX_ATTEMPTS = 5;
    private const DECAY_SECONDS = 900;
    private const ALLOWED_ROLES = ['kasir', 'admin', 'kitchen'];
    private const SINGLE_DEVICE_ROLES = ['kasir', 'kitchen'];

    public function show(Request $request): View|RedirectResponse
    {
        if (Auth::check()) {
            return $this->redirectByRole();
        }

        $device = $this->resolveDevice($request);
        if (!$device) {
            return redirect()->route('login', ['email' => 1])->withErrors([
                'email' => 'Perangkat belum terdaftar. Silakan login dengan email.',
            ]);
        }

        $users = DB::table('pos_device_user_logins as device_users')
            ->join('users', 'users.id', '=', 'device_users.user_id')
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('device_users.pos_device_id', $device->id)
            ->where('users.is_active', true)
            ->whereNotNull('users.attendance_pin')
            ->where('users.outlet_id', $device->outlet_id)
            ->whereIn('roles.name', self::ALLOWED_ROLES)
            ->orderByDesc('device_users.last_login_at')
            ->limit(8)
            ->get([
                'users.id',
                'users.name',
                'roles.display_name as role_name',
            ]);

        return view('auth.pin-login', [
            'users' => $users,
        ]);
    }

    public function login(Request $request): RedirectResponse
    {
        $device = $this->resolveDevice($request);
        if (!$device) {
            return redirect()->route('login', ['email' => 1])->withErrors([
                'email' => 'Perangkat belum terdaftar. Silakan login dengan email.',
            ]);
        }

        $validated = $request->validate([
            'user_id' => 'required|integer',
            'pin' => ['required', 'digits:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'user_id.required' => 'Pilih user terlebih dahulu.',
            'pin.required' => 'PIN wajib diisi.',
            'pin.digits' => 'PIN harus 6 digit.',
            'pin.regex' => 'PIN harus 6 digit angka.',
        ]);

        $throttleKey = $this->throttleKey($request, $device->id, (int) $validated['user_id']);
        if (RateLimiter::tooManyAttempts($throttleKey, self::MAX_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            $minutes = max(1, (int) ceil($seconds / 60));

            throw ValidationException::withMessages([
                'pin' => "Terlalu banyak percobaan PIN. Coba lagi dalam {$minutes} menit.",
            ]);
        }

        $user = User::query()
            ->with(['role', 'outlet'])
            ->join('pos_device_user_logins as device_users', function ($join) use ($device) {
                $join->on('users.id', '=', 'device_users.user_id')
                    ->where('device_users.pos_device_id', '=', $device->id);
            })
            ->join('roles', 'roles.id', '=', 'users.role_id')
            ->where('users.id', $validated['user_id'])
            ->where('users.is_active', true)
            ->whereNotNull('users.attendance_pin')
            ->where('users.outlet_id', $device->outlet_id)
            ->whereIn('roles.name', self::ALLOWED_ROLES)
            ->select('users.*')
            ->first();

        if (!$user || !Hash::check($validated['pin'], $user->attendance_pin)) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'pin' => 'PIN tidak valid atau user belum terdaftar di perangkat ini.',
            ]);
        }

        if ($user->outlet && !$user->outlet->is_active) {
            RateLimiter::hit($throttleKey, self::DECAY_SECONDS);

            throw ValidationException::withMessages([
                'pin' => 'Outlet user sedang dinonaktifkan. Hubungi admin.',
            ]);
        }

        if ($user->hasRole(self::SINGLE_DEVICE_ROLES)
            && (int) $user->active_pos_device_id !== (int) $device->id) {
            // Device baru langsung mengambil alih sesi aktif user.
            $user->forceFill(['active_pos_device_id' => $device->id])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        DB::table('pos_device_user_logins')->upsert([
            [
                'pos_device_id' => $device->id,
                'user_id' => $user->id,
                'last_login_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ], ['pos_device_id', 'user_id'], ['last_login_at', 'updated_at']);

        RateLimiter::clear($throttleKey);

        return $this->redirectByRole();
    }

    private function resolveDevice(Request $request): ?PosDevice
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

    private function throttleKey(Request $request, int $deviceId, int $userId): string
    {
        return "pin-login:{$deviceId}:{$userId}:" . $request->ip();
    }

    private function redirectByRole(): RedirectResponse
    {
        $user = Auth::user();

        if (!$user) {
            return redirect()->route('login');
        }

        if ($user->hasRole(['admin', 'manager', 'superadmin'])) {
            return redirect()->intended(route('admin.dashboard'));
        }

        if ($user->hasRole('kasir')) {
            return redirect()->intended(route('pos.dashboard'));
        }

        if ($user->hasRole('kitchen')) {
            return redirect()->intended(route('pos.kitchen.index'));
        }

        Auth::logout();

        return redirect()->route('login')->withErrors([
            'email' => 'Role akun tidak dikenali. Hubungi admin.',
        ]);
    }
}

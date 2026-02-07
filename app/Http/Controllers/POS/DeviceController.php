<?php

namespace App\Http\Controllers\POS;

use App\Http\Controllers\Controller;
use App\Models\PosDevice;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DeviceController extends Controller
{
    public function showRegister(): View
    {
        return view('pos.device.register');
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'pairing_code' => 'required|string|max:12',
            'device_name' => 'nullable|string|max:100',
        ]);

        $code = preg_replace('/\s+/', '', $data['pairing_code']);

        $device = PosDevice::query()
            ->where('pairing_code', $code)
            ->where('pairing_expires_at', '>=', now())
            ->where('is_active', true)
            ->first();

        if (!$device) {
            return back()->withErrors([
                'pairing_code' => 'Kode pairing tidak valid atau sudah kedaluwarsa.',
            ]);
        }

        if ($device->token_hash) {
            return back()->withErrors([
                'pairing_code' => 'Kode pairing sudah digunakan.',
            ]);
        }

        $user = $request->user();
        if ($user && $user->outlet_id && $device->outlet_id !== $user->outlet_id) {
            return back()->withErrors([
                'pairing_code' => 'Kode pairing ini bukan untuk outlet Anda.',
            ]);
        }

        $token = Str::random(64);

        $device->forceFill([
            'token_hash' => hash('sha256', $token),
            'paired_at' => now(),
            'last_seen_at' => now(),
            'pairing_code' => null,
            'pairing_expires_at' => null,
        ]);

        if (!empty($data['device_name']) && empty($device->name)) {
            $device->name = $data['device_name'];
        }

        $device->save();

        $minutes = (int) config('pos.token_ttl_days', 365) * 24 * 60;
        $cookie = cookie(
            config('pos.device_cookie', 'pos_device_token'),
            $token,
            $minutes,
            null,
            null,
            (bool) config('session.secure', false),
            true,
            false,
            config('session.same_site', 'lax')
        );

        return redirect()
            ->route('pos.dashboard')
            ->with('success', 'Perangkat berhasil didaftarkan.')
            ->withCookie($cookie);
    }
}

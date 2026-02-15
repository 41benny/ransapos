<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\PosDevice;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosDeviceController extends Controller
{
    private const DEVICE_TYPES = ['kasir', 'kitchen', 'lainnya'];

    public function index(): View
    {
        $outlets = Outlet::query()->active()->orderBy('name')->get();
        $devices = PosDevice::query()
            ->with(['outlet', 'creator'])
            ->orderByDesc('created_at')
            ->get();
        $deviceEnforced = Setting::getBool('pos_device_enforce', config('pos.device_enforce', false));
        $deviceTypes = self::DEVICE_TYPES;

        return view('admin.pos-devices.index', compact('outlets', 'devices', 'deviceEnforced', 'deviceTypes'));
    }

    public function storePairing(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'name' => 'nullable|string|max:100',
            'device_type' => 'required|string|in:' . implode(',', self::DEVICE_TYPES),
        ]);

        $code = $this->generatePairingCode();

        $pairingTtlMinutes = (int) config('pos.pairing_ttl_minutes', 15);
        if ($pairingTtlMinutes <= 0) {
            $pairingTtlMinutes = 15;
        }

        $device = PosDevice::create([
            'outlet_id' => $data['outlet_id'],
            'name' => $data['name'] ?: null,
            'device_type' => $data['device_type'],
            'pairing_code' => $code,
            'pairing_expires_at' => now()->addMinutes($pairingTtlMinutes),
            'created_by' => $request->user()?->id,
            'is_active' => true,
        ]);

        return redirect()
            ->route('admin.pos-devices.index')
            ->with('success', 'Kode pairing berhasil dibuat.')
            ->with('pairing_code', $code)
            ->with('pairing_device_id', $device->id);
    }

    public function revoke(PosDevice $posDevice): RedirectResponse
    {
        User::query()
            ->where('active_pos_device_id', $posDevice->id)
            ->update(['active_pos_device_id' => null]);

        $posDevice->forceFill([
            'is_active' => false,
            'revoked_at' => now(),
            'pairing_code' => null,
            'pairing_expires_at' => null,
        ])->save();

        return redirect()
            ->route('admin.pos-devices.index')
            ->with('success', 'Perangkat berhasil dinonaktifkan.');
    }

    public function destroy(PosDevice $posDevice): RedirectResponse
    {
        if ($posDevice->is_active) {
            return redirect()
                ->route('admin.pos-devices.index')
                ->with('error', 'Perangkat aktif tidak bisa dihapus. Nonaktifkan dulu perangkat ini.');
        }

        User::query()
            ->where('active_pos_device_id', $posDevice->id)
            ->update(['active_pos_device_id' => null]);

        $posDevice->delete();

        return redirect()
            ->route('admin.pos-devices.index')
            ->with('success', 'Perangkat nonaktif berhasil dihapus.');
    }

    public function updateEnforcement(Request $request): RedirectResponse
    {
        $enabled = $request->boolean('enabled');

        Setting::setValue('pos_device_enforce', $enabled);

        return redirect()
            ->route('admin.pos-devices.index')
            ->with('success', $enabled ? 'Fitur perangkat POS diaktifkan.' : 'Fitur perangkat POS dinonaktifkan.');
    }

    protected function generatePairingCode(): string
    {
        $attempts = 0;
        do {
            $attempts++;
            $code = (string) random_int(100000, 999999);
            $exists = PosDevice::query()
                ->where('pairing_code', $code)
                ->where('pairing_expires_at', '>=', now())
                ->exists();
        } while ($exists && $attempts < 5);

        return $code;
    }
}

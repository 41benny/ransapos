<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Outlet;
use App\Models\PosDevice;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PosDeviceController extends Controller
{
    public function index(): View
    {
        $outlets = Outlet::query()->active()->orderBy('name')->get();
        $devices = PosDevice::query()
            ->with(['outlet', 'creator'])
            ->orderByDesc('created_at')
            ->get();
        $deviceEnforced = Setting::getBool('pos_device_enforce', config('pos.device_enforce', false));

        return view('admin.pos-devices.index', compact('outlets', 'devices', 'deviceEnforced'));
    }

    public function storePairing(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'outlet_id' => 'required|exists:outlets,id',
            'name' => 'nullable|string|max:100',
        ]);

        $code = $this->generatePairingCode();

        $pairingTtlMinutes = (int) config('pos.pairing_ttl_minutes', 15);
        if ($pairingTtlMinutes <= 0) {
            $pairingTtlMinutes = 15;
        }

        $device = PosDevice::create([
            'outlet_id' => $data['outlet_id'],
            'name' => $data['name'] ?: null,
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

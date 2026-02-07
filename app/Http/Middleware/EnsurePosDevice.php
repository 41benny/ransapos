<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\PosDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePosDevice
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $enforced = Setting::getBool('pos_device_enforce', config('pos.device_enforce', false));

        if (!$enforced) {
            return $next($request);
        }

        $token = $request->cookie(config('pos.device_cookie', 'pos_device_token'));

        if (!$token) {
            return $this->reject($request);
        }

        $hash = hash('sha256', $token);

        $device = PosDevice::query()
            ->where('token_hash', $hash)
            ->where('is_active', true)
            ->whereNotNull('paired_at')
            ->first();

        if (!$device || $device->revoked_at) {
            return $this->reject($request);
        }

        $user = $request->user();
        if ($user && $user->outlet_id && $device->outlet_id !== $user->outlet_id) {
            return $this->reject($request, 'Perangkat ini tidak terdaftar untuk outlet Anda.');
        }

        if (!$device->last_seen_at || $device->last_seen_at->lt(now()->subMinutes(5))) {
            $device->forceFill(['last_seen_at' => now()])->save();
        }

        $request->attributes->set('pos_device', $device);

        return $next($request);
    }

    protected function reject(Request $request, string $message = 'Perangkat POS belum terdaftar.'): Response
    {
        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 403);
        }

        return redirect()->route('pos.device.register')->withErrors([
            'device' => $message,
        ]);
    }
}

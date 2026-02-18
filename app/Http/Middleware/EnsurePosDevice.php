<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use App\Models\PosDevice;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePosDevice
{
    private const SINGLE_DEVICE_ROLES = ['kasir', 'kitchen'];

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $enforced = Setting::getBool('pos_device_enforce', config('pos.device_enforce', false));
        $token = $request->cookie(config('pos.device_cookie', 'pos_device_token'));

        if (!$enforced) {
            if ($token) {
                $device = PosDevice::query()
                    ->where('token_hash', hash('sha256', $token))
                    ->where('is_active', true)
                    ->whereNotNull('paired_at')
                    ->whereNull('revoked_at')
                    ->first();

                if ($device) {
                    if ($response = $this->enforceSingleDeviceSession($request, $device)) {
                        return $response;
                    }

                    $this->syncDeviceTelemetry($device, $request);
                    $request->attributes->set('pos_device', $device);
                }
            }

            return $next($request);
        }

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

        if ($response = $this->enforceSingleDeviceSession($request, $device)) {
            return $response;
        }

        $this->syncDeviceTelemetry($device, $request);

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

    protected function syncDeviceTelemetry(PosDevice $device, Request $request): void
    {
        $detectedMeta = $this->extractDeviceMeta($request);
        $existingMeta = is_array($device->device_meta) ? $device->device_meta : [];
        $mergedMeta = array_filter(array_merge($existingMeta, $detectedMeta), fn ($value) => $value !== null && $value !== '');

        $shouldUpdateMeta = empty($existingMeta);
        if (!$shouldUpdateMeta) {
            foreach (['browser', 'platform', 'user_agent', 'language', 'ip'] as $key) {
                if (empty($existingMeta[$key]) && !empty($detectedMeta[$key])) {
                    $shouldUpdateMeta = true;
                    break;
                }
            }
        }

        $shouldUpdateSeen = !$device->last_seen_at || $device->last_seen_at->lt(now()->subMinutes(5));

        if ($shouldUpdateSeen || $shouldUpdateMeta || empty($device->fingerprint_hash)) {
            $updatePayload = [];

            if ($shouldUpdateSeen) {
                $updatePayload['last_seen_at'] = now();
            }

            if ($shouldUpdateMeta) {
                $updatePayload['device_meta'] = $mergedMeta ?: null;
            }

            if (empty($device->fingerprint_hash)) {
                $fingerprintSource = implode('|', [
                    $mergedMeta['platform'] ?? '',
                    $mergedMeta['browser'] ?? '',
                    $mergedMeta['user_agent'] ?? '',
                    $mergedMeta['language'] ?? '',
                ]);
                if ($fingerprintSource !== '') {
                    $updatePayload['fingerprint_hash'] = hash('sha256', $fingerprintSource);
                }
            }

            if (!empty($updatePayload)) {
                $device->forceFill($updatePayload)->save();
            }
        }
    }

    protected function enforceSingleDeviceSession(Request $request, PosDevice $device): ?Response
    {
        $user = $request->user();
        if (!$user || !$user->hasRole(self::SINGLE_DEVICE_ROLES)) {
            return null;
        }

        if (!$user->active_pos_device_id) {
            $user->forceFill(['active_pos_device_id' => $device->id])->save();

            return null;
        }

        if ((int) $user->active_pos_device_id === (int) $device->id) {
            return null;
        }

        Auth::logout();
        if ($request->hasSession()) {
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }

        $message = 'Sesi Anda telah dipindahkan ke perangkat lain. Silakan login kembali.';

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => $message,
            ], 401);
        }

        return redirect()->route('pos.pin.show')->withErrors([
            'email' => $message,
        ]);
    }

    protected function extractDeviceMeta(Request $request): array
    {
        $userAgent = (string) ($request->userAgent() ?? '');

        return [
            'browser' => $this->detectBrowser($userAgent),
            'platform' => $this->detectPlatform($userAgent),
            'user_agent' => $userAgent,
            'language' => (string) ($request->getPreferredLanguage() ?? ''),
            'ip' => (string) ($request->ip() ?? ''),
        ];
    }

    protected function detectBrowser(string $userAgent): string
    {
        if (preg_match('/Edg\//i', $userAgent)) {
            return 'Edge';
        }

        if (preg_match('/OPR\//i', $userAgent)) {
            return 'Opera';
        }

        if (preg_match('/Chrome\//i', $userAgent)) {
            return 'Chrome';
        }

        if (preg_match('/Safari\//i', $userAgent) && !preg_match('/Chrome\//i', $userAgent)) {
            return 'Safari';
        }

        if (preg_match('/Firefox\//i', $userAgent)) {
            return 'Firefox';
        }

        return 'Unknown';
    }

    protected function detectPlatform(string $userAgent): string
    {
        if (preg_match('/Android/i', $userAgent)) {
            return 'Android';
        }

        if (preg_match('/iPhone|iPad|iPod/i', $userAgent)) {
            return 'iOS';
        }

        if (preg_match('/Windows/i', $userAgent)) {
            return 'Windows';
        }

        if (preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
            return 'macOS';
        }

        if (preg_match('/Linux/i', $userAgent)) {
            return 'Linux';
        }

        return 'Unknown';
    }
}

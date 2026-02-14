<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Hindari redirect loop guest->login saat user sebenarnya masih login.
        RedirectIfAuthenticated::redirectUsing(function (Request $request): string {
            $user = Auth::user();

            if (!$user) {
                return route('login');
            }

            $lastUrl = $request->hasSession() ? $request->session()->get('auth.last_url') : null;
            if (is_string($lastUrl) && $lastUrl !== '') {
                $currentHost = parse_url($request->fullUrl(), PHP_URL_HOST);
                $lastHost = parse_url($lastUrl, PHP_URL_HOST);
                $lastPath = parse_url($lastUrl, PHP_URL_PATH) ?: '/';

                $isSameHost = !$lastHost || $lastHost === $currentHost;
                $isSafePath = !in_array($lastPath, ['/', '/login'], true);

                if ($isSameHost && $isSafePath) {
                    return $lastUrl;
                }
            }

            if ($user->hasRole(['admin', 'manager'])) {
                return route('admin.dashboard');
            }

            if ($user->hasRole('kasir')) {
                return route('pos.dashboard');
            }

            if ($user->hasRole('kitchen')) {
                return route('pos.kitchen.index');
            }

            Auth::logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return route('login');
        });
    }
}

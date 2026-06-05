<?php

namespace App\Providers;

use App\Observers\ActivityLogObserver;
use App\Support\ActivityLogger;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
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

            if ($user->hasRole(['admin', 'manager', 'superadmin'])) {
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

        $this->bootActivityLog();
    }

    /**
     * Audit trail: rekam perubahan data penting + login/logout.
     */
    private function bootActivityLog(): void
    {
        // Lewati pencatatan saat seeding/migrasi/command artisan agar tidak banjir log.
        if ($this->app->runningInConsole()) {
            return;
        }

        foreach (array_keys(ActivityLogger::auditedModels()) as $model) {
            if (class_exists($model)) {
                $model::observe(ActivityLogObserver::class);
            }
        }

        Event::listen(Login::class, function (Login $event): void {
            ActivityLogger::log('login', 'Login ke sistem', $event->user instanceof \Illuminate\Database\Eloquent\Model ? $event->user : null);
        });

        Event::listen(Logout::class, function (Logout $event): void {
            ActivityLogger::log('logout', 'Logout dari sistem', $event->user instanceof \Illuminate\Database\Eloquent\Model ? $event->user : null);
        });

        Event::listen(Failed::class, function (Failed $event): void {
            $identifier = $event->credentials['email'] ?? $event->credentials['username'] ?? '-';
            ActivityLogger::log('login_failed', "Percobaan login gagal ({$identifier})");
        });
    }
}

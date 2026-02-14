<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreLastVisitedPage
{
    /**
     * Simpan halaman terakhir yang valid untuk user login.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (
            $request->isMethod('GET')
            && !$request->expectsJson()
            && !$request->ajax()
            && $request->user()
            && $this->routeUsesAuthMiddleware($request)
            && !$request->routeIs('login', 'login.post', 'logout')
        ) {
            $request->session()->put('auth.last_url', $request->fullUrl());
        }

        return $next($request);
    }

    private function routeUsesAuthMiddleware(Request $request): bool
    {
        $route = $request->route();
        if (!$route) {
            return false;
        }

        foreach ($route->gatherMiddleware() as $middleware) {
            if ($middleware === 'auth' || str_starts_with($middleware, 'auth:')) {
                return true;
            }
        }

        return false;
    }
}


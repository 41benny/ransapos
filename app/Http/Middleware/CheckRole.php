<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();

        // Cek apakah user punya salah satu role yang diizinkan
        if (!$user->hasRole($roles)) {
            Log::warning('Role check failed', [
                'user_id' => $user->id,
                'user_name' => $user->name,
                'user_role' => $user->role?->name,
                'required_roles' => $roles,
                'method' => $request->method(),
                'path' => $request->path(),
                'route_name' => optional($request->route())->getName(),
            ]);

            abort(403, 'Anda tidak memiliki akses ke halaman ini.');
        }

        return $next($request);
    }
}

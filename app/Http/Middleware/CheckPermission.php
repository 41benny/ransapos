<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $parsedPermissions = [];
        foreach ($permissions as $p) {
            foreach (explode('|', $p) as $split) {
                if (trim($split) !== '') {
                    $parsedPermissions[] = trim($split);
                }
            }
        }

        $user = auth()->user();
        if (!$user || !$user->hasPermission($parsedPermissions)) {
            abort(403, 'Anda tidak memiliki permission untuk aksi ini.');
        }

        return $next($request);
    }
}

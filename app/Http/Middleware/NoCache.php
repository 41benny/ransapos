<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Cegah browser/PWA menyimpan halaman di cache.
 *
 * Dipakai pada halaman login & PIN agar token CSRF selalu segar — menghindari
 * error "Page Expired" (419) saat user menutup lalu membuka kembali tab dan
 * browser menampilkan halaman login lama (token sudah kedaluwarsa).
 */
class NoCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $response->headers->set('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0');
        $response->headers->set('Pragma', 'no-cache');
        $response->headers->set('Expires', '0');

        return $response;
    }
}

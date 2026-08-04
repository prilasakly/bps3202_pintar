<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware RBAC sederhana: pastikan user yang sedang login (via Sanctum) punya
 * salah satu role yang disyaratkan route ini. Guest (tidak login) otomatis ditolak
 * karena middleware ini selalu dipasang SETELAH 'auth:sanctum'.
 *
 * Pemakaian di routes: ->middleware(['auth:sanctum', EnsureHasRole::class.':ipds'])
 * Bisa lebih dari satu role: EnsureHasRole::class.':ipds,umum'
 */
class EnsureHasRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasRole($roles)) {
            abort(403, 'Anda tidak memiliki akses untuk melakukan aksi ini. Dibutuhkan role: '.implode(', ', $roles).'.');
        }

        return $next($request);
    }
}

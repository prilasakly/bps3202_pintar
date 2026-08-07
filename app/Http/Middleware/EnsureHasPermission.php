<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware RBAC berbasis PERMISSION (bukan nama role langsung seperti EnsureHasRole).
 * Cek "apakah user punya hak akses X" dibaca dari tabel permissions/permission_role
 * di database -- jadi siapa yang boleh melakukan apa bisa diubah lewat halaman
 * "Kelola Hak Akses" tanpa perlu ubah kode/redeploy.
 *
 * Pemakaian di routes: ->middleware(['auth:sanctum', EnsureHasPermission::class.':data.manage'])
 * Bisa lebih dari satu permission (OR, salah satu cukup): EnsureHasPermission::class.':data.manage,data.upload'
 *
 * Superadmin selalu lolos (lihat User::hasPermission()), supaya superadmin tidak pernah
 * bisa mengunci dirinya sendiri keluar dari fitur apa pun lewat kesalahan konfigurasi.
 */
class EnsureHasPermission
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasPermission($permissions)) {
            abort(403, 'Anda tidak memiliki hak akses untuk melakukan aksi ini.');
        }

        return $next($request);
    }
}

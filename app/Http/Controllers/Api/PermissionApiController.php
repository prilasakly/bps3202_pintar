<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;

/**
 * Halaman "Kelola Hak Akses": tempat superadmin mengatur role apa saja yang punya
 * suatu permission -- inilah yang bikin "halaman/tombol X hanya untuk role Y" bisa
 * diubah dari web tanpa sentuh kode sama sekali (lihat User::hasPermission(),
 * EnsureHasPermission, dan Alpine store "auth" di layouts/app.blade.php).
 *
 * SELURUH endpoint di controller ini KHUSUS role "superadmin" (lihat routes/api.php) --
 * sengaja pakai EnsureHasRole (role tetap, hardcoded), BUKAN EnsureHasPermission,
 * supaya halaman yang mengatur kunci ini sendiri tidak bisa diubah lewat dirinya
 * sendiri (menghindari risiko superadmin "mengunci diri sendiri" tanpa sadar).
 */
class PermissionApiController extends Controller
{
    /** Daftar semua permission + role apa saja yang sedang memegangnya, dan daftar semua role yang bisa dipilih. */
    public function index()
    {
        $permissions = Permission::with('roles:id,nama,slug')
            ->orderBy('grup')
            ->orderBy('nama')
            ->get()
            ->map(fn (Permission $p) => $this->formatPermission($p));

        $roles = Role::orderBy('nama')->get(['id', 'nama', 'slug']);

        return response()->json([
            'permissions' => $permissions,
            'roles' => $roles,
        ]);
    }

    /**
     * Ganti daftar role pemegang satu permission (replace semua, bukan tambah/hapus satu-satu)
     * -- cocok dengan UI checkbox "role mana yang dicek" di halaman Kelola Hak Akses.
     */
    public function update(Request $request, Permission $permission)
    {
        $data = $request->validate([
            'role_ids' => ['required', 'array'],
            'role_ids.*' => ['integer', 'exists:roles,id'],
        ]);

        $permission->roles()->sync($data['role_ids']);

        return response()->json([
            'message' => "Hak akses \"{$permission->nama}\" berhasil diperbarui.",
            'permission' => $this->formatPermission($permission->fresh()->load('roles:id,nama,slug')),
        ]);
    }

    private function formatPermission(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'nama' => $permission->nama,
            'slug' => $permission->slug,
            'deskripsi' => $permission->deskripsi,
            'grup' => $permission->grup,
            'role_ids' => $permission->roles->pluck('id')->values(),
        ];
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Role;

class UserController extends Controller
{
    /**
     * Halaman "Kelola User". View ini cuma shell -- daftar user diambil lewat
     * GET /api/users setelah halaman dimuat (mirip pola halaman /upload), supaya
     * konsisten dengan RBAC yang dicek di client lewat Alpine store "auth".
     *
     * Daftar role dikirim langsung dari sini (bukan lewat API terpisah) karena
     * datanya statis & kecil, dipakai untuk isi pilihan checkbox role di form
     * tambah/ubah user.
     */
    public function index()
    {
        $roles = Role::orderBy('nama')->get(['id', 'nama', 'slug']);

        return view('user.index', compact('roles'));
    }
}

<?php

namespace App\Http\Controllers;

class PermissionController extends Controller
{
    /**
     * Halaman "Kelola Hak Akses". View ini cuma shell -- daftar permission & role diambil
     * lewat GET /api/permissions setelah halaman dimuat. Halaman ini sendiri KHUSUS
     * superadmin (dicek di client lewat $store.auth.isSuperadmin, bukan lewat sistem
     * permission yang justru diatur dari sini -- lihat catatan di PermissionApiController).
     */
    public function index()
    {
        return view('permission.index');
    }
}

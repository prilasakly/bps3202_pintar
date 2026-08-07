<?php

namespace App\Http\Controllers;

use App\Models\Sidebar;

class DataController extends Controller
{
    /**
     * Halaman "Kelola Data". View ini cuma shell -- daftar kategori (subsidebar) &
     * indikator diambil lewat GET /api/subsidebars & GET /api/indikators setelah halaman
     * dimuat (mirip pola halaman /kelola-user & /upload), supaya konsisten dengan RBAC
     * yang dicek di client lewat Alpine store "auth" (method can()).
     *
     * Daftar sidebar dikirim langsung dari sini (bukan lewat API terpisah) karena
     * dipakai untuk isi pilihan dropdown "menu induk" saat menambah kategori baru.
     */
    public function index()
    {
        $sidebars = Sidebar::orderBy('urutan')->get(['id', 'nama']);

        return view('data.index', compact('sidebars'));
    }
}

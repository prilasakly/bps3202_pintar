<?php

namespace App\Http\Controllers;

class PageController extends Controller
{
    /**
     * Halaman Buku Tamu. Masih placeholder — belum ada tabel/isi datanya sendiri,
     * disiapkan supaya menu sidebar "Buku Tamu" punya tujuan yang valid.
     */
    public function bukuTamu()
    {
        return view('pages.coming-soon', [
            'judul' => 'Buku Tamu',
            'deskripsi' => 'Halaman untuk pengunjung mengisi buku tamu akan tersedia di sini.',
        ]);
    }

    /**
     * Halaman Tautan Penting. Placeholder sama seperti Buku Tamu.
     */
    public function tautanPenting()
    {
        return view('pages.coming-soon', [
            'judul' => 'Tautan Penting',
            'deskripsi' => 'Kumpulan tautan penting (link) terkait BPS Kabupaten Sukabumi akan tersedia di sini.',
        ]);
    }
}

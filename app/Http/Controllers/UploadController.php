<?php

namespace App\Http\Controllers;

use App\Models\Indikator;
use App\Models\Sidebar;
use Illuminate\Http\Request;

class UploadController extends Controller
{
    public function create(Request $request)
    {
        // Dikelompokkan Sidebar > Subsidebar > Indikator supaya form upload tidak lagi
        // menampilkan satu dropdown raksasa berisi semua indikator sekaligus.
        $sidebars = Sidebar::with(['subsidebars' => function ($q) {
            $q->orderBy('urutan');
        }, 'subsidebars.indikators' => function ($q) {
            $q->orderBy('nama_judul');
        }])->orderBy('urutan')->get();

        // Kalau datang dari tombol "Upload data" di halaman sebuah indikator,
        // indikator itu langsung dipilihkan di form (masih bisa diganti manual).
        $indikatorTerpilih = Indikator::with('subsidebar')
            ->where('slug', $request->query('indikator'))
            ->first();

        // Kalau datang dari tombol "Upload data" di halaman kategori, subsidebar-nya
        // langsung dibuka di form supaya user tinggal pilih indikatornya saja.
        $subsidebarTerpilih = $indikatorTerpilih?->subsidebar_id
            ?? ($request->filled('kategori') ? $request->integer('kategori') : null);

        return view('upload.create', compact('sidebars', 'indikatorTerpilih', 'subsidebarTerpilih'));
    }

    // Method store() sudah dipindah ke App\Http\Controllers\Api\UploadApiController::store()
    // (route POST /api/upload) supaya submit upload dari web & mobile lewat jalur RBAC yang sama.
}

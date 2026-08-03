<?php

namespace App\Http\Controllers;

use App\Http\Requests\UploadIndikatorRequest;
use App\Models\Indikator;
use App\Models\Sidebar;
use App\Services\IndikatorExcelImporter;
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
        $indikatorTerpilih = null;
        if ($request->filled('indikator')) {
            $indikatorTerpilih = Indikator::with('subsidebar')
                ->where('slug', $request->query('indikator'))
                ->first();
        }

        // Kalau datang dari tombol "Upload data" di halaman kategori, subsidebar-nya
        // langsung dibuka di form supaya user tinggal pilih indikatornya saja.
        $subsidebarTerpilih = $indikatorTerpilih?->subsidebar_id
            ?? ($request->filled('kategori') ? $request->integer('kategori') : null);

        return view('upload.create', compact('sidebars', 'indikatorTerpilih', 'subsidebarTerpilih'));
    }

    public function store(UploadIndikatorRequest $request, IndikatorExcelImporter $importer)
    {
        $indikator = Indikator::findOrFail($request->integer('indikator_id'));
        $file = $request->file('file');

        $hasil = $importer->import(
            indikator: $indikator,
            filePath: $file->getRealPath(),
            tahun: $request->integer('tahun'),
            triwulan: $request->filled('triwulan') ? $request->integer('triwulan') : null,
            namaFileAsli: $file->getClientOriginalName(),
            namaSheet: $request->input('sheet'),
            force: $request->boolean('force'),
        );

        return back()->with('hasil_import', $hasil);
    }
}

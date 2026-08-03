<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Indikator;
use App\Models\IndikatorNilai;
use App\Models\Sidebar;
use Illuminate\Http\Request;

class IndikatorApiController extends Controller
{
    /** Menu lengkap sidebar > subsidebar > indikator, untuk navigasi frontend. */
    public function menu()
    {
        $sidebars = Sidebar::with('subsidebars.indikators:id,subsidebar_id,nama_judul,slug,satuan')
            ->orderBy('urutan')
            ->get(['id', 'nama', 'slug', 'urutan']);

        return response()->json($sidebars);
    }

    /** Daftar tahun/triwulan yang tersedia untuk satu indikator (dipakai untuk isi pilihan tahun di frontend). */
    public function periode(Indikator $indikator)
    {
        $periode = $indikator->periode()
            ->orderBy('tahun')
            ->orderBy('triwulan')
            ->get(['id', 'tahun', 'triwulan']);

        return response()->json($periode);
    }

    /**
     * Data tabel/time-series untuk satu indikator, difilter tahun tertentu.
     * Contoh: GET /api/indikators/jumlah-guru-ma/data?tahun[]=2023&tahun[]=2024&tahun[]=2025
     */
    public function data(Indikator $indikator, Request $request)
    {
        $tahunDipilih = array_map('intval', (array) $request->query('tahun', []));

        $kolom = $indikator->kolom()->get(['id', 'kolom_key', 'kolom_label', 'induk_label']);
        $baris = $indikator->baris()->orderBy('urutan')->get(['id', 'baris_label', 'kecamatan_id']);

        $periodeQuery = $indikator->periode();
        if (! empty($tahunDipilih)) {
            $periodeQuery->whereIn('tahun', $tahunDipilih);
        }
        $periode = $periodeQuery->orderBy('tahun')->orderBy('triwulan')->get(['id', 'tahun', 'triwulan']);

        $nilai = IndikatorNilai::whereIn('periode_id', $periode->pluck('id'))
            ->get(['baris_id', 'periode_id', 'kolom_id', 'nilai', 'nilai_numerik']);

        // Bentuk output flat, biar fleksibel dipivot di sisi frontend sesuai kebutuhan (tabel atau chart)
        $data = $nilai->map(function ($n) use ($periode, $baris, $kolom) {
            return [
                'baris' => $baris->firstWhere('id', $n->baris_id)?->baris_label,
                'tahun' => $periode->firstWhere('id', $n->periode_id)?->tahun,
                'triwulan' => $periode->firstWhere('id', $n->periode_id)?->triwulan,
                'kolom' => $kolom->firstWhere('id', $n->kolom_id)?->kolom_label,
                'nilai' => $n->nilai,
                'nilai_numerik' => $n->nilai_numerik,
            ];
        });

        return response()->json([
            'indikator' => [
                'nama_judul' => $indikator->nama_judul,
                'satuan' => $indikator->satuan,
            ],
            'kolom' => $kolom,
            'baris' => $baris,
            'periode' => $periode,
            'data' => $data,
        ]);
    }
}

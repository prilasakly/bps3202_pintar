<?php

namespace App\Http\Controllers;

use App\Models\Indikator;
use App\Models\IndikatorPeriode;
use App\Models\Sidebar;
use App\Models\Subsidebar;
use Illuminate\Http\Request;

class IndikatorController extends Controller
{
    public function index()
    {
        $sidebars = Sidebar::with(['subsidebars' => function ($q) {
            $q->orderBy('urutan')->withCount('indikators');
        }])->orderBy('urutan')->get();

        $stats = [
            'total_sidebar' => $sidebars->count(),
            'total_subsidebar' => $sidebars->sum(fn ($s) => $s->subsidebars->count()),
            'total_indikator' => $sidebars->sum(fn ($s) => $s->subsidebars->sum('indikators_count')),
            'total_dataset' => IndikatorPeriode::count(),
        ];

        // Beberapa indikator dengan data yang paling baru diupload, buat highlight di beranda.
        $indikatorTerbaru = Indikator::with('subsidebar')
            ->whereHas('periode')
            ->withMax('periode', 'diupload_pada')
            ->orderByDesc('periode_max_diupload_pada')
            ->limit(6)
            ->get();

        return view('indikator.index', compact('sidebars', 'stats', 'indikatorTerbaru'));
    }

    public function bySubsidebar(Subsidebar $subsidebar)
    {
        $subsidebar->load('sidebar');
        $indikators = $subsidebar->indikators()->withCount('periode')->get();

        return view('indikator.subsidebar', compact('subsidebar', 'indikators'));
    }

    public function show(Indikator $indikator, Request $request)
    {
        $indikator->load(['kolom', 'baris' => fn ($q) => $q->orderBy('urutan'), 'subsidebar.sidebar']);
        $tahunTersedia = $indikator->periode()->orderBy('tahun')->pluck('tahun', 'id');
        $tahunTersediaUnik = $tahunTersedia->unique()->values();

        // Default: tampilkan 3 tahun terakhir yang tersedia kalau user belum pilih apa-apa
        $tahunDipilih = $request->query('tahun', $tahunTersediaUnik->slice(-3)->values()->all());
        $tahunDipilih = array_map('intval', is_array($tahunDipilih) ? $tahunDipilih : [$tahunDipilih]);

        $periodeDipilih = $indikator->periode()
            ->whereIn('tahun', $tahunDipilih)
            ->orderBy('tahun')
            ->orderBy('triwulan')
            ->get();

        // Ambil semua nilai untuk periode yang dipilih, disusun jadi map untuk lookup cepat di view:
        // $nilaiMap[baris_id][periode_id][kolom_id] = nilai (string asli)
        $nilaiMap = [];
        if ($periodeDipilih->isNotEmpty()) {
            $rows = \App\Models\IndikatorNilai::whereIn('periode_id', $periodeDipilih->pluck('id'))
                ->get(['baris_id', 'periode_id', 'kolom_id', 'nilai']);

            foreach ($rows as $r) {
                $nilaiMap[$r->baris_id][$r->periode_id][$r->kolom_id] = $r->nilai;
            }
        }

        return view('indikator.show', [
            'indikator' => $indikator,
            'tahunTersediaUnik' => $tahunTersediaUnik,
            'tahunDipilih' => $tahunDipilih,
            'periodeDipilih' => $periodeDipilih,
            'nilaiMap' => $nilaiMap,
        ]);
    }
}

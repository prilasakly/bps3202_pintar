<?php

namespace App\Http\Controllers;

use App\Models\Indikator;
use App\Models\IndikatorNilai;
use App\Models\IndikatorPeriode;
use App\Models\Sidebar;
use App\Models\Subsidebar;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

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

        [$periodeDipilih, $nilaiMap] = $this->ambilDataPivot($indikator, $tahunDipilih);

        // Daftar semua periode (tahun/triwulan) yang sudah pernah diupload untuk indikator ini,
        // dipakai di panel "Kelola Data per Periode" (export & hapus per periode).
        $semuaPeriode = $indikator->periode()->orderBy('tahun')->orderBy('triwulan')->get();

        return view('indikator.show', [
            'indikator' => $indikator,
            'tahunTersediaUnik' => $tahunTersediaUnik,
            'tahunDipilih' => $tahunDipilih,
            'periodeDipilih' => $periodeDipilih,
            'nilaiMap' => $nilaiMap,
            'semuaPeriode' => $semuaPeriode,
        ]);
    }

    /**
     * Unduh tabel yang sedang ditampilkan (sesuai filter tahun yang aktif) sebagai file Excel.
     * Sengaja memakai method ambilDataPivot() yang sama dengan show(), supaya isi file yang
     * diunduh selalu identik dengan apa yang sedang dilihat user di halaman.
     */
    public function export(Indikator $indikator, Request $request)
    {
        $indikator->load(['kolom', 'baris' => fn ($q) => $q->orderBy('urutan')]);

        $tahunDipilih = array_map('intval', (array) $request->query('tahun', []));
        if (empty($tahunDipilih)) {
            $tahunDipilih = $indikator->periode()->pluck('tahun')->unique()->values()->all();
        }

        [$periodeDipilih, $nilaiMap] = $this->ambilDataPivot($indikator, $tahunDipilih);

        $kolomList = $indikator->kolom;
        $satuKolom = $kolomList->count() <= 1;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $namaSheetBersih = trim(preg_replace('/[\\\\\/\?\*\[\]:]/', ' ', $indikator->nama_judul));
        $sheet->setTitle(Str::limit($namaSheetBersih, 31, '') ?: 'Data');

        // Helper lokal: ubah (kolom ke-, baris ke-) jadi koordinat string seperti "B3".
        $koordinat = fn (int $col, int $row): string => Coordinate::stringFromColumnIndex($col).$row;

        // Header: kolom A = label baris (kecamatan/dsb), kolom berikutnya = kombinasi tahun x kolom nilai.
        $sheet->setCellValue($koordinat(1, 1), 'Kecamatan');
        $petaKolom = [];
        $colIndex = 2;
        foreach ($periodeDipilih as $p) {
            $labelTahun = $p->tahun.($p->triwulan ? ' TW'.$p->triwulan : '');
            foreach ($kolomList as $k) {
                $header = $satuKolom ? $labelTahun : $labelTahun.' - '.$k->kolom_label;
                $sheet->setCellValue($koordinat($colIndex, 1), $header);
                $petaKolom[$p->id][$k->id] = $colIndex;
                $colIndex++;
            }
        }
        $kolomTerakhir = Coordinate::stringFromColumnIndex(max($colIndex - 1, 1));
        $sheet->getStyle("A1:{$kolomTerakhir}1")->getFont()->setBold(true);
        $sheet->getStyle("A1:{$kolomTerakhir}1")->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle("A1:{$kolomTerakhir}1")->getFill()
            ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('0B7A3B');

        // Isi baris data
        $rowIndex = 2;
        foreach ($indikator->baris as $baris) {
            $sheet->setCellValue($koordinat(1, $rowIndex), $baris->baris_label);
            foreach ($periodeDipilih as $p) {
                foreach ($kolomList as $k) {
                    $col = $petaKolom[$p->id][$k->id];
                    $sheet->setCellValue($koordinat($col, $rowIndex), $nilaiMap[$baris->id][$p->id][$k->id] ?? null);
                }
            }
            $rowIndex++;
        }

        for ($c = 1; $c < $colIndex; $c++) {
            $sheet->getColumnDimension(Coordinate::stringFromColumnIndex($c))->setAutoSize(true);
        }

        $filename = Str::slug($indikator->nama_judul).'-'.now()->format('Ymd_His').'.xlsx';

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    /**
     * Ambil periode + peta nilai untuk daftar tahun tertentu. Dipakai bersama oleh show() dan
     * export() supaya isi file yang diunduh selalu sama dengan yang sedang ditampilkan di layar.
     *
     * @param  array<int>  $tahunList
     * @return array{0: \Illuminate\Database\Eloquent\Collection, 1: array}
     */
    private function ambilDataPivot(Indikator $indikator, array $tahunList): array
    {
        $periodeDipilih = $indikator->periode()
            ->whereIn('tahun', $tahunList)
            ->orderBy('tahun')
            ->orderBy('triwulan')
            ->get();

        $nilaiMap = [];
        if ($periodeDipilih->isNotEmpty()) {
            $rows = IndikatorNilai::whereIn('periode_id', $periodeDipilih->pluck('id'))
                ->get(['baris_id', 'periode_id', 'kolom_id', 'nilai']);

            foreach ($rows as $r) {
                $nilaiMap[$r->baris_id][$r->periode_id][$r->kolom_id] = $r->nilai;
            }
        }

        return [$periodeDipilih, $nilaiMap];
    }
}

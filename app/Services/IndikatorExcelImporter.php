<?php

namespace App\Services;

use App\Models\Indikator;
use App\Models\IndikatorBaris;
use App\Models\IndikatorKolom;
use App\Models\IndikatorNilai;
use App\Models\IndikatorPeriode;
use App\Models\Kecamatan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class IndikatorExcelImporter
{
    /** Label baris yang dianggap baris total/agregat, bukan data per-kecamatan, dan diabaikan. */
    private const LABEL_TOTAL = ['JUMLAH', 'TOTAL', 'KAB SUKABUMI', 'KABUPATEN SUKABUMI'];

    /** Maksimum kolom yang dipindai sebelum menyerah mencari batas kolom nilai. */
    private const MAX_KOLOM_SCAN = 25;

    /**
     * @return array{status: string, pesan: string, periode?: IndikatorPeriode, jumlah_baris?: int, jumlah_kolom?: int}
     */
    public function import(
        Indikator $indikator,
        string $filePath,
        int $tahun,
        ?int $triwulan = null,
        ?string $namaFileAsli = null,
        ?string $namaSheet = null,
        bool $force = false
    ): array {
        // 1. Cek duplikat: kalau periode (indikator, tahun, triwulan) sudah ada dan tidak dipaksa, abaikan.
        $periodeLama = IndikatorPeriode::where('indikator_id', $indikator->id)
            ->where('tahun', $tahun)
            ->where('triwulan', $triwulan)
            ->first();

        if ($periodeLama && ! $force) {
            return [
                'status' => 'diabaikan',
                'pesan' => "Data tahun {$tahun}".($triwulan ? " triwulan {$triwulan}" : '')." untuk indikator ini sudah ada, upload diabaikan.",
            ];
        }

        $spreadsheet = IOFactory::load($filePath);
        $sheet = $namaSheet ? $spreadsheet->getSheetByName($namaSheet) : $spreadsheet->getSheet(0);

        if (! $sheet) {
            return ['status' => 'gagal', 'pesan' => "Sheet '{$namaSheet}' tidak ditemukan di file."];
        }

        $this->terapkanMergedCells($sheet);

        $kolomKolom = $this->deteksiKolomNilai($sheet);

        if (empty($kolomKolom)) {
            return ['status' => 'gagal', 'pesan' => 'Tidak ada kolom nilai yang terdeteksi pada sheet ini.'];
        }

        return DB::transaction(function () use (
            $indikator, $sheet, $kolomKolom, $tahun, $triwulan, $namaFileAsli, $filePath, $periodeLama, $force
        ) {
            // 2. Upsert definisi kolom (sekali dibuat, dipakai lagi tahun-tahun berikutnya)
            $kolomModel = [];
            foreach ($kolomKolom as $i => $k) {
                $kolomModel[$k['index']] = IndikatorKolom::updateOrCreate(
                    ['indikator_id' => $indikator->id, 'kolom_key' => $k['kolom_key']],
                    [
                        'kolom_label' => $k['kolom_label'],
                        'induk_label' => $k['induk_label'],
                        'urutan' => $i,
                    ]
                );
            }

            // 3. Upsert periode (tahun/triwulan)
            if ($periodeLama && $force) {
                // Import ulang: hapus nilai lama pada periode ini supaya tidak dobel
                $periodeLama->nilai()->delete();
                $periode = $periodeLama;
                $periode->update([
                    'file_asal' => $namaFileAsli ?? basename($filePath),
                    'file_hash' => hash_file('sha256', $filePath),
                    'diupload_pada' => now(),
                ]);
            } else {
                $periode = IndikatorPeriode::create([
                    'indikator_id' => $indikator->id,
                    'tahun' => $tahun,
                    'triwulan' => $triwulan,
                    'file_asal' => $namaFileAsli ?? basename($filePath),
                    'file_hash' => hash_file('sha256', $filePath),
                    'diupload_pada' => now(),
                ]);
            }

            // 4. Baca baris data, mulai baris ke-3 (setelah 2 baris header)
            $jumlahBaris = 0;
            $jumlahNilai = 0;
            $highestRow = $sheet->getHighestDataRow();
            $urutanBaris = 0;

            for ($row = 3; $row <= $highestRow; $row++) {
                $labelBaris = trim((string) $sheet->getCell([1, $row])->getValue());

                if ($labelBaris === '') {
                    break; // baris kosong = akhir data
                }

                if (in_array(mb_strtoupper($labelBaris), self::LABEL_TOTAL, true)) {
                    continue; // baris total/agregat, bukan data per baris
                }

                $urutanBaris++;
                $barisKey = Str::slug($labelBaris, '_');

                $kecamatanId = null;
                if ($indikator->tipe_baris === 'kecamatan') {
                    $kecamatanId = Kecamatan::whereRaw('UPPER(nama) = ?', [mb_strtoupper($labelBaris)])
                        ->value('id');
                }

                $baris = IndikatorBaris::updateOrCreate(
                    ['indikator_id' => $indikator->id, 'baris_key' => $barisKey],
                    [
                        'baris_label' => $labelBaris,
                        'kecamatan_id' => $kecamatanId,
                        'urutan' => $urutanBaris,
                    ]
                );
                $jumlahBaris++;

                foreach ($kolomKolom as $k) {
                    $cell = $sheet->getCell([$k['index'], $row]);

                    // 1. Ambil nilai mentah/asli dari Excel
                    $rawValue = $cell->getValue();

                    // 2. Jika sel berupa rumus/formula, hitung nilainya terlebih dahulu
                    if (is_string($rawValue) && str_starts_with($rawValue, '=')) {
                        $rawValue = $cell->getCalculatedValue();
                    }

                    // 3. Logika penyesuaian angka desimal dan pembersihan zero-padding di depan
                    if (is_numeric($rawValue)) {
                        $formatted = (string) $cell->getFormattedValue();
                        $formattedClean = ltrim($formatted, '0'); // Buang '0' tambahan di depan

                        // Hitung jumlah angka di belakang koma/titik dari format asli
                        if (preg_match('/[.,](\d+)/', $formattedClean, $matches)) {
                            $decimals = strlen($matches[1]);
                            $rawValue = number_format((float) $rawValue, $decimals, '.', '');
                        } else {
                            $rawValue = (string) $rawValue;
                        }
                    } else {
                        $rawValue = (string) $rawValue;
                    }

                    // Gunakan updateOrCreate berdasarkan unique composite key
                    $nilai = IndikatorNilai::firstOrNew([
                        'periode_id' => $periode->id,
                        'baris_id'   => $baris->id,
                        'kolom_id'   => $kolomModel[$k['index']]->id,
                    ]);

                    $nilai->setNilaiMentah($rawValue);
                    $nilai->save();
                    $jumlahNilai++;
                }
            }

            return [
                'status' => 'sukses',
                'pesan' => "Import berhasil: {$jumlahBaris} baris, ".count($kolomKolom)." kolom, {$jumlahNilai} sel nilai.",
                'periode' => $periode,
                'jumlah_baris' => $jumlahBaris,
                'jumlah_kolom' => count($kolomKolom),
            ];
        });
    }

    /**
     * Salin nilai merged cell ke semua sel dalam range-nya, khusus untuk 2 baris header teratas,
     * supaya header gabungan (misal "Jumlah Guru MA" yang di-merge di atas kolom Negeri & Swasta)
     * terbaca di setiap kolom turunannya, bukan cuma sel kiri-atas.
     */
    private function terapkanMergedCells(Worksheet $sheet): void
    {
        foreach ($sheet->getMergeCells() as $range) {
            [$start, $end] = explode(':', $range);
            $startCoord = Coordinate::coordinateFromString($start);

            if ((int) $startCoord[1] > 2) {
                continue; // hanya urus 2 baris header teratas
            }

            $nilaiAsal = $sheet->getCell($start)->getValue();
            foreach (Coordinate::extractAllCellReferencesInRange($range) as $coord) {
                if ($coord !== $start) {
                    $sheet->getCell($coord)->setValue($nilaiAsal);
                }
            }
        }
    }

    /**
     * Deteksi kolom nilai (mulai kolom B) berdasarkan 2 baris header:
     * - baris 1 = induk_label (header gabungan, opsional)
     * - baris 2 = kolom_label (breakdown, atau bisa juga cuma angka tahun untuk indikator flat 1 kolom)
     * Berhenti begitu ketemu kolom kosong (header kosong DAN tidak ada data di 3 baris pertama).
     *
     * @return array<int, array{index: int, kolom_key: string, kolom_label: string, induk_label: ?string}>
     */
    private function deteksiKolomNilai(Worksheet $sheet): array
    {
        $hasil = [];

        for ($col = 2; $col <= self::MAX_KOLOM_SCAN; $col++) {
            $indukLabel = trim((string) $sheet->getCell([$col, 1])->getValue());
            $kolomLabel = trim((string) $sheet->getCell([$col, 2])->getValue());

            $adaData = false;
            for ($r = 3; $r <= 5; $r++) {
                if (trim((string) $sheet->getCell([$col, $r])->getValue()) !== '') {
                    $adaData = true;
                    break;
                }
            }

            if ($indukLabel === '' && $kolomLabel === '' && ! $adaData) {
                break; // sudah masuk area kosong / legend, berhenti
            }

            // Kalau baris ke-2 cuma angka tahun (mis. "2025"), ini bukan breakdown sungguhan,
            // melainkan indikator flat 1 kolom -- pakai induk_label sebagai nama kolomnya.
            if ($kolomLabel !== '' && preg_match('/^\d{4}$/', $kolomLabel)) {
                $labelDipakai = $indukLabel !== '' ? $indukLabel : $kolomLabel;
                $hasil[] = [
                    'index' => $col,
                    'kolom_key' => Str::slug($labelDipakai, '_') ?: 'nilai',
                    'kolom_label' => $labelDipakai,
                    'induk_label' => null,
                ];
                break; // indikator flat cuma 1 kolom nilai
            }

            if ($kolomLabel === '' && $indukLabel === '') {
                continue; // kolom data tanpa header jelas, lewati (jarang terjadi)
            }

            $labelDipakai = $kolomLabel !== '' ? $kolomLabel : $indukLabel;

            $hasil[] = [
                'index' => $col,
                'kolom_key' => Str::slug($labelDipakai, '_') ?: 'kolom_'.$col,
                'kolom_label' => $labelDipakai,
                'induk_label' => $indukLabel !== '' && $indukLabel !== $labelDipakai ? $indukLabel : null,
            ];
        }

        return $hasil;
    }
}
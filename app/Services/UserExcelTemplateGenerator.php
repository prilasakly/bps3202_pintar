<?php

namespace App\Services;

use App\Models\Role;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class UserExcelTemplateGenerator
{
    /**
     * Header kolom sheet "Template User". Urutan & jumlahnya HARUS sama persis
     * dengan konstanta KOLOM_* di UserExcelImporter -- kalau salah satu diubah,
     * yang lain wajib ikut disesuaikan.
     */
    private const HEADERS = [
        'Nama', 'Email', 'Password', 'NIP Lama', 'NIP Baru', 'Golongan', 'Jabatan', 'Role (pisahkan koma)',
    ];

    private const WARNA_HEADER = '16A34A'; // senada dengan bg-bps-green-600 di halaman Kelola User

    public function generate(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();

        $this->buatSheetTemplate($spreadsheet);
        $this->buatSheetDaftarRole($spreadsheet);

        // Sheet aktif pertama kali dibuka = sheet template, bukan sheet referensi role.
        $spreadsheet->setActiveSheetIndex(0);

        return $spreadsheet;
    }

    private function buatSheetTemplate(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Template User');

        foreach (self::HEADERS as $i => $label) {
            $sheet->getCell([$i + 1, 1])->setValue($label);
        }

        $kolomTerakhir = chr(ord('A') + count(self::HEADERS) - 1); // 8 kolom -> 'H'
        $rangeHeader = "A1:{$kolomTerakhir}1";

        $sheet->getStyle($rangeHeader)->getFont()->setBold(true);
        $sheet->getStyle($rangeHeader)->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle($rangeHeader)->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle($rangeHeader)->getFill()->getStartColor()->setRGB(self::WARNA_HEADER);
        $sheet->getStyle($rangeHeader)->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);

        // Baris ke-2: contoh data. Boleh ditimpa/dihapus pengguna, hanya panduan format.
        $contoh = [
            'Contoh Nama Lengkap',
            'contoh.user@bps.go.id',
            'password123',
            '196001011990031001',
            '199001012015031001',
            'III/c',
            'Statistisi Ahli Muda',
            'ipds,sosial',
        ];
        foreach ($contoh as $i => $nilai) {
            $sheet->getCell([$i + 1, 2])->setValue($nilai);
        }
        $rangeContoh = "A2:{$kolomTerakhir}2";
        $sheet->getStyle($rangeContoh)->getFont()->setItalic(true);
        $sheet->getStyle($rangeContoh)->getFont()->getColor()->setRGB('94A3B8');

        foreach (range('A', $kolomTerakhir) as $kolom) {
            $sheet->getColumnDimension($kolom)->setAutoSize(true);
        }

        // Baris header dikunci supaya tetap terlihat saat scroll ke bawah mengisi data.
        $sheet->freezePane('A2');
    }

    /**
     * Sheet referensi berisi slug role yang valid untuk diisi di kolom "Role" pada
     * sheet template -- diambil langsung dari tabel roles supaya selalu sinkron
     * dengan role yang benar-benar ada di aplikasi (termasuk "superadmin").
     */
    private function buatSheetDaftarRole(Spreadsheet $spreadsheet): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Daftar Role');

        $sheet->getCell([1, 1])->setValue('Slug (isi di kolom Role)');
        $sheet->getCell([2, 1])->setValue('Nama Role');

        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getStyle('A1:B1')->getFont()->getColor()->setRGB('FFFFFF');
        $sheet->getStyle('A1:B1')->getFill()->setFillType(Fill::FILL_SOLID);
        $sheet->getStyle('A1:B1')->getFill()->getStartColor()->setRGB(self::WARNA_HEADER);

        $roles = Role::orderBy('nama')->get(['nama', 'slug']);
        foreach ($roles as $i => $role) {
            $baris = $i + 2;
            $sheet->getCell([1, $baris])->setValue($role->slug);
            $sheet->getCell([2, $baris])->setValue($role->nama);
        }

        $sheet->getColumnDimension('A')->setAutoSize(true);
        $sheet->getColumnDimension('B')->setAutoSize(true);
    }
}
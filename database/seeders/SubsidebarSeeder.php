<?php

namespace Database\Seeders;

use App\Models\Sidebar;
use App\Models\Subsidebar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SubsidebarSeeder extends Seeder
{
    /**
     * Daftar subsidebar per sidebar. Urutan sesuai urutan tampil di sidebar,
     * mengikuti pengelompokan kategori data BPS Kabupaten Sukabumi.
     */
    public function run(): void
    {
        $data = [
            'Data Makro' => [
                'KEPENDUDUKAN DAN MIGRASI',
                'KESEHATAN',
                'KONSUMSI DAN PENDAPATAN',
                'SOSIAL BUDAYA',
                'TENAGA KERJA',
                'PENDIDIKAN',
                'INDEKS PEMBANGUNAN MANUSIA',
                'KEMISKINAN',
                'PEMERINTAHAN',
                'PEMUKIMAN DAN PERUMAHAN',
                'POLITIK DAN KEAMANAN',
                'ENERGI',
                'GEOGRAFI',
                'IKLIM ATAU LINGKUNGAN',
                'KEUANGAN',
                'NERACA SOSIAL EKONOMI',
                'PARIWISATA',
                'TRANSPORTASI',
                'INDUSTRI',
                'KOMUNIKASI',
                'PERIKANAN',
                'TANAMAN PANGAN',
                'HOLTIKULTURA',
                'PERKEBUNAN',
                'PETERNAKAN',
            ],
        ];

        foreach ($data as $namaSidebar => $subsidebarList) {
            $sidebar = Sidebar::where('slug', Str::slug($namaSidebar))->first();

            if (! $sidebar) {
                continue;
            }

            foreach ($subsidebarList as $urutan => $nama) {
                Subsidebar::updateOrCreate(
                    ['sidebar_id' => $sidebar->id, 'slug' => Str::slug($nama)],
                    ['nama' => $nama, 'urutan' => $urutan + 1]
                );
            }
        }
    }
}

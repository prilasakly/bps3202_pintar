<?php

namespace Database\Seeders;

use App\Models\Sidebar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SidebarSeeder extends Seeder
{
    /**
     * Daftar menu sidebar utama. Urutan array = urutan tampil di sidebar.
     *
     * type "link"     : menu langsung menuju satu halaman (pakai route_name atau url).
     * type "dropdown" : menu yang saat dibuka menampilkan daftar subsidebar/kategori
     *                    (khusus "Data Makro", subsidebar-nya diisi SubsidebarSeeder).
     */
    public function run(): void
    {
        $sidebar = [
            ['nama' => 'Beranda', 'icon' => 'home', 'type' => 'link', 'route_name' => 'indikator.index'],
            ['nama' => 'Buku Tamu', 'icon' => 'book-open', 'type' => 'link', 'route_name' => 'buku-tamu.index'],
            ['nama' => 'Data Makro', 'icon' => 'database', 'type' => 'dropdown', 'route_name' => null],
            ['nama' => 'Tautan Penting', 'icon' => 'link', 'type' => 'link', 'route_name' => 'tautan-penting.index'],
        ];

        foreach ($sidebar as $urutan => $item) {
            Sidebar::updateOrCreate(
                ['slug' => Str::slug($item['nama'])],
                [
                    'nama' => $item['nama'],
                    'icon' => $item['icon'],
                    'type' => $item['type'],
                    'route_name' => $item['route_name'],
                    'urutan' => $urutan + 1,
                ]
            );
        }
    }
}

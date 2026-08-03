<?php

namespace Database\Seeders;

use App\Models\Sidebar;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class SidebarSeeder extends Seeder
{
    /**
     * Daftar menu sidebar utama. Urutan sesuai urutan tampil di sidebar.
     */
    public function run(): void
    {
        $sidebar = [
            'Data Makro',
        ];

        foreach ($sidebar as $urutan => $nama) {
            Sidebar::updateOrCreate(
                ['slug' => Str::slug($nama)],
                ['nama' => $nama, 'urutan' => $urutan + 1]
            );
        }
    }
}

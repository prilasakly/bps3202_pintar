<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Daftar tim/seksi di lingkungan BPS Kabupaten Sukabumi yang bisa dipilih sebagai role user.
     * "guest" TIDAK dimasukkan di sini karena guest bukan role di database -- guest = belum login sama sekali.
     */
    public function run(): void
    {
        $roles = [
            'ipds' => 'IPDS',
            'sosial' => 'Statistik Sosial',
            'distribusi' => 'Statistik Distribusi',
            'industri' => 'Statistik Industri',
            'umum' => 'Umum',
            'nerwilis' => 'Neraca Wilayah dan Analisis Statistik',
            'produksi' => 'Statistik Produksi',
        ];

        foreach ($roles as $slug => $nama) {
            Role::updateOrCreate(['slug' => $slug], ['nama' => $nama]);
        }
    }
}

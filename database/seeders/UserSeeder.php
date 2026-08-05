<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Bikin satu akun demo per role supaya login & RBAC langsung bisa dicoba tanpa
     * harus insert manual. Password semuanya sama: "password" -- WAJIB diganti sebelum
     * dipakai di server production sungguhan.
     *
     * Akun "admin@bps.go.id" sengaja dibuat dengan SEMUA role sekaligus (contoh nyata
     * "1 user bisa banyak tim").
     */
    public function run(): void
    {
        $roles = Role::all()->keyBy('slug');

        $akunPerRole = [
            'ipds' => 'ipds@bps.go.id',
            'sosial' => 'sosial@bps.go.id',
            'distribusi' => 'distribusi@bps.go.id',
            'industri' => 'industri@bps.go.id',
            'umum' => 'umum@bps.go.id',
            'nerwilis' => 'nerwilis@bps.go.id',
            'produksi' => 'produksi@bps.go.id',
        ];

        foreach ($akunPerRole as $slug => $email) {
            $user = User::updateOrCreate(
                ['email' => $email],
                ['name' => 'Staf '.$roles[$slug]->nama, 'password' => Hash::make('password')]
            );
            $user->roles()->syncWithoutDetaching([$roles[$slug]->id]);
        }

        // Contoh user lintas tim: IPDS sekaligus Nerwilis.
        $admin = User::updateOrCreate(
            ['email' => 'admin@bps.go.id'],
            ['name' => 'Administrator PINTAR', 'password' => Hash::make('password')]
        );
        $admin->roles()->sync($roles->pluck('id')->all());

        // Akun superadmin: satu-satunya role yang boleh kelola (tambah/ubah/hapus) akun user
        // lain lewat menu "Kelola User". Dipisah dari akun "admin" di atas supaya jelas beda
        // peran -- admin lintas tim untuk urusan data, superadmin khusus urusan akun.
        $superadmin = User::updateOrCreate(
            ['email' => 'superadmin@bps.go.id'],
            [
                'name' => 'Super Admin PINTAR',
                'password' => Hash::make('password'),
                'nip_lama' => null,
                'nip_baru' => '196001011990031001',
                'golongan' => 'IV/a',
                'jabatan' => 'Kepala BPS Kabupaten Sukabumi',
            ]
        );
        $superadmin->roles()->syncWithoutDetaching([$roles['superadmin']->id]);
    }
}

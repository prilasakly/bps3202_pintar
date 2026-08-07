<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    /**
     * Daftar permission (hak akses) beserta role pemegangnya SAAT PERTAMA KALI di-seed.
     * Setelah ini, "siapa boleh apa" TIDAK lagi diatur di sini -- melainkan lewat halaman
     * web "Kelola Hak Akses" (superadmin), yang meng-update tabel permission_role langsung.
     *
     * Karena pakai updateOrCreate + sync-jika-belum-ada (lihat di bawah), menjalankan seeder
     * ini ulang TIDAK akan menimpa perubahan yang sudah dilakukan lewat halaman web --
     * role yang sudah di-attach/detach manual akan tetap dipertahankan.
     */
    public function run(): void
    {
        $permissions = [
            [
                'slug' => 'users.manage',
                'nama' => 'Kelola User',
                'deskripsi' => 'Tambah, ubah, hapus akun user, dan import user massal via Excel.',
                'grup' => 'Kelola User',
                'roles_default' => ['superadmin'],
            ],
            [
                'slug' => 'data.manage',
                'nama' => 'Kelola Data',
                'deskripsi' => 'Tambah, ubah, hapus kategori (subsidebar) dan indikator di halaman Kelola Data.',
                'grup' => 'Kelola Data',
                'roles_default' => ['ipds', 'admin', 'superadmin'],
            ],
            [
                'slug' => 'data.upload',
                'nama' => 'Upload Data Excel',
                'deskripsi' => 'Upload data (nilai per periode) ke sebuah indikator lewat file Excel.',
                'grup' => 'Kelola Data',
                'roles_default' => ['ipds'],
            ],
        ];

        foreach ($permissions as $item) {
            $permission = Permission::updateOrCreate(
                ['slug' => $item['slug']],
                ['nama' => $item['nama'], 'deskripsi' => $item['deskripsi'], 'grup' => $item['grup']]
            );

            // Hanya isi role default kalau permission ini BARU dibuat (belum punya role
            // sama sekali) -- supaya seeder aman dijalankan berulang tanpa menimpa
            // pengaturan yang sudah diubah manual lewat halaman "Kelola Hak Akses".
            if ($permission->roles()->count() === 0) {
                $roleIds = Role::whereIn('slug', $item['roles_default'])->pluck('id');
                $permission->roles()->sync($roleIds);
            }
        }
    }
}

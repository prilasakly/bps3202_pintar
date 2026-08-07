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
     *
     * grup : judul seksi/header tempat menu ini dikelompokkan di sidebar (General,
     *        Statistik, Internal, dst). Bebas ditambah seksi baru kapan saja -- tinggal
     *        ganti nilai "grup" di sini, tidak perlu migration baru.
     */
    public function run(): void
    {
        $sidebar = [
            ['nama' => 'Beranda', 'grup' => 'General', 'icon' => 'home', 'type' => 'link', 'route_name' => 'indikator.index'],
            ['nama' => 'Buku Tamu', 'grup' => 'General', 'icon' => 'book-open', 'type' => 'link', 'route_name' => 'buku-tamu.index'],

            ['nama' => 'Data Makro', 'grup' => 'Statistik', 'icon' => 'database', 'type' => 'dropdown', 'route_name' => null],
            // Menu "Kelola Data": tempat CRUD kategori (subsidebar) & indikator. Disembunyikan
            // dari guest (belum login) -- lihat $wajibLogin di partials.sidebar. Semua role yang
            // login boleh MELIHAT halamannya, tapi tambah/ubah/hapus dibatasi permission
            // "data.manage" (lihat PermissionSeeder & EnsureHasPermission di routes/api.php).
            ['nama' => 'Kelola Data', 'grup' => 'Statistik', 'icon' => 'edit', 'type' => 'link', 'route_name' => 'kelola-data.index'],

            ['nama' => 'Tautan Penting', 'grup' => 'Internal', 'icon' => 'link', 'type' => 'link', 'route_name' => 'tautan-penting.index'],
            // Menu "Kelola User": disembunyikan dari guest (belum login) -- lihat pengecekan
            // $wajibLogin di partials.sidebar. Semua role yang sudah login boleh MELIHAT daftar
            // user, tapi tambah/ubah/hapus dibatasi permission "users.manage" (lihat
            // PermissionSeeder & tombol aksi di resources/views/user/index.blade.php).
            ['nama' => 'Kelola User', 'grup' => 'Internal', 'icon' => 'users', 'type' => 'link', 'route_name' => 'users.index'],
            // Menu "Kelola Hak Akses": tempat mengatur permission per role (lihat
            // PermissionController & resources/views/permission/index.blade.php).
            // KHUSUS superadmin -- sengaja tidak lewat sistem permission itu sendiri, supaya
            // tidak ada risiko "kunci diri sendiri" dari halaman yang justru mengatur kunci itu.
            ['nama' => 'Kelola Hak Akses', 'grup' => 'Internal', 'icon' => 'shield', 'type' => 'link', 'route_name' => 'kelola-akses.index'],
        ];

        foreach ($sidebar as $urutan => $item) {
            Sidebar::updateOrCreate(
                ['slug' => Str::slug($item['nama'])],
                [
                    'nama' => $item['nama'],
                    'grup' => $item['grup'],
                    'icon' => $item['icon'],
                    'type' => $item['type'],
                    'route_name' => $item['route_name'],
                    'urutan' => $urutan + 1,
                ]
            );
        }
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel master "hak akses" (permission/capability) di aplikasi, contoh:
     * "users.manage" (tambah/ubah/hapus user), "data.manage" (kelola indikator & kategori),
     * "data.upload" (upload data excel).
     *
     * Berbeda dengan Role (tim/seksi), Permission ini murni "boleh melakukan apa" -- dan
     * SENGAJA dipisah dari kode (EnsureHasRole) supaya siapa yang boleh apa bisa diatur
     * dari halaman "Kelola Hak Akses" tanpa perlu ubah kode/redeploy. Lihat migration
     * create_permission_role_table.php untuk pemetaannya ke Role.
     */
    public function up(): void
    {
        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('nama'); // contoh: "Kelola User"
            $table->string('slug')->unique(); // contoh: "users.manage" -- dipakai di kode (middleware, cek di frontend)
            $table->string('deskripsi')->nullable();
            $table->string('grup')->default('Umum'); // pengelompokan tampilan di halaman Kelola Hak Akses
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permissions');
    }
};

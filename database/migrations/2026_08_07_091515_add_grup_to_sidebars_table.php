<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom "grup" ke sidebars supaya menu utama bisa dikelompokkan jadi
     * beberapa seksi berjudul di sidebar, contoh:
     *   General   -> Beranda, Buku Tamu
     *   Statistik -> Data Makro, Kelola Data
     *   Internal  -> Tautan Penting
     *
     * File migration TERPISAH (bukan menambah ke create_sidebars_table.php) dengan alasan
     * yang sama seperti add_icon_and_type_to_sidebars_table.php: supaya migrate aman
     * dijalankan di database yang sudah berisi data tanpa perlu migrate:fresh.
     */
    public function up(): void
    {
        Schema::table('sidebars', function (Blueprint $table) {
            if (! Schema::hasColumn('sidebars', 'grup')) {
                // Judul seksi/header tempat menu ini ditampilkan di sidebar. Bebas diisi
                // teks apa saja (bukan enum) supaya nanti mudah ditambah seksi baru tanpa migration.
                $table->string('grup')->default('General')->after('nama');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sidebars', function (Blueprint $table) {
            $table->dropColumn('grup');
        });
    }
};

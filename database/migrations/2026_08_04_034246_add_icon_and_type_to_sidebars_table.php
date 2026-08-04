<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Menambahkan kolom supaya menu sidebar utama (Beranda, Buku Tamu, Data Makro,
     * Tautan Penting, dst) bisa 100% diatur dari database: ikon apa yang dipakai,
     * apakah dia link biasa atau dropdown kategori (khusus "Data Makro"), dan
     * kemana dia mengarah (nama route Laravel atau URL bebas).
     *
     * PENTING: migration ini file TERPISAH dari create_sidebars_table.php supaya
     * `php artisan migrate` tetap menjalankannya walau tabel `sidebars` sudah ada
     * sebelumnya di database kamu — tidak perlu migrate:fresh (yang akan menghapus
     * semua data indikator yang sudah diupload).
     */
    public function up(): void
    {
        Schema::table('sidebars', function (Blueprint $table) {
            if (! Schema::hasColumn('sidebars', 'icon')) {
                // Kunci ikon (bukan HTML mentah), dicocokkan ke SVG lewat partials.icon. Contoh: "home", "book-open".
                $table->string('icon')->default('circle')->after('nama');
            }

            if (! Schema::hasColumn('sidebars', 'type')) {
                // "link"     => menu langsung menuju satu halaman (Beranda, Buku Tamu, Tautan Penting).
                // "dropdown" => menu yang saat dibuka menampilkan daftar subsidebar/kategori (Data Makro).
                $table->enum('type', ['link', 'dropdown'])->default('link')->after('icon');
            }

            if (! Schema::hasColumn('sidebars', 'route_name')) {
                // Nama route Laravel (dipakai untuk resolve href + status aktif). Kosong kalau pakai kolom "url".
                $table->string('route_name')->nullable()->after('type');
            }

            if (! Schema::hasColumn('sidebars', 'url')) {
                // URL bebas (dipakai kalau menu tidak punya route Laravel, misal tautan eksternal).
                $table->string('url')->nullable()->after('route_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sidebars', function (Blueprint $table) {
            $table->dropColumn(['icon', 'type', 'route_name', 'url']);
        });
    }
};

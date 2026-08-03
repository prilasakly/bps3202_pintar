<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indikators', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subsidebar_id')->constrained('subsidebars')->cascadeOnDelete();
            $table->string('nama_judul'); // contoh: "Jumlah Guru MA Menurut Kecamatan" (tanpa tahun)
            $table->string('slug')->unique();
            $table->string('satuan')->nullable(); // orang, ha, km, dst
            // tipe_baris menentukan apa yang jadi baris tabel: kecamatan (paling umum),
            // kelompok_umur (untuk file penduduk per umur), atau custom (kombinasi/lainnya)
            $table->enum('tipe_baris', ['kecamatan', 'kelompok_umur', 'custom'])->default('kecamatan');
            $table->string('nama_file_asli')->nullable(); // untuk auto-match saat upload, contoh: "Jumlah Guru MA Menurut Kecamatan 2025.xls"
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indikators');
    }
};

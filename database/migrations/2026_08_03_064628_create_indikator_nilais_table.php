<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('indikator_nilais', function (Blueprint $table) {
            $table->id();
            $table->foreignId('periode_id')->constrained('indikator_periodes')->cascadeOnDelete();
            $table->foreignId('baris_id')->constrained('indikator_baris')->cascadeOnDelete();
            $table->foreignId('kolom_id')->constrained('indikator_koloms')->cascadeOnDelete();

            // PENTING: nilai disimpan sebagai string, verbatim sama seperti sel excel aslinya
            // (contoh: "225.182", "55,025", "-", "52"). Ini mencegah salah interpretasi
            // pemisah ribuan/desimal (titik vs koma) yang bisa mengubah nilai kalau langsung
            // di-cast ke tipe numerik saat import.
            $table->string('nilai')->nullable();

            // Hasil parsing angka dari `nilai`, opsional, dipakai untuk sorting/chart/agregasi.
            // Null kalau raw value tidak bisa diparse jadi angka (misal "-" atau kosong).
            // Ini bukan sumber kebenaran -- kalau butuh tampilan asli, selalu ambil dari `nilai`.
            $table->decimal('nilai_numerik', 20, 4)->nullable();

            $table->timestamps();

            $table->unique(['periode_id', 'baris_id', 'kolom_id'], 'uniq_indikator_nilai_cell');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indikator_nilais');
    }
};

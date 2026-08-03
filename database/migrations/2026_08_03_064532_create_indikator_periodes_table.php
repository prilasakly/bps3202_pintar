<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Satu baris = satu kali "rilis data" untuk indikator tertentu (tahun, atau tahun+triwulan).
        // file_hash dipakai untuk deteksi upload ulang: kalau kombinasi (indikator, tahun, triwulan)
        // sudah ada, proses import di service layer akan mengabaikannya (skip), sesuai kebutuhan
        // "upload baru cuma nambah tahun baru, kalau sama diabaikan".
        Schema::create('indikator_periodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indikator_id')->constrained('indikators')->cascadeOnDelete();
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('triwulan')->nullable(); // 1-4, null jika data tahunan
            $table->string('file_asal')->nullable(); // nama file excel yang diupload
            $table->string('file_hash', 64)->nullable(); // sha256 file, untuk cek duplikat persis
            $table->timestamp('diupload_pada')->nullable();
            $table->timestamps();

            $table->unique(['indikator_id', 'tahun', 'triwulan']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indikator_periodes');
    }
};

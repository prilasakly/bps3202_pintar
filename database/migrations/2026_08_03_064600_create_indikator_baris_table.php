<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Baris entitas per indikator (misal daftar kecamatan, atau daftar kelompok umur).
        // kecamatan_id diisi kalau baris ini bisa dipetakan ke master kecamatan (untuk join lintas indikator),
        // dibiarkan null untuk tipe_baris lain seperti kelompok_umur.
        Schema::create('indikator_baris', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indikator_id')->constrained('indikators')->cascadeOnDelete();
            $table->foreignId('kecamatan_id')->nullable()->constrained('kecamatans')->nullOnDelete();
            $table->string('baris_key'); // key ternormalisasi, contoh: "ciemas", "0-4"
            $table->string('baris_label'); // label tampil asli, contoh: "CIEMAS", "0-4"
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['indikator_id', 'baris_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indikator_baris');
    }
};

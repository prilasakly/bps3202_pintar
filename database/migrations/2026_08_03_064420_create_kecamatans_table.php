<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kecamatans', function (Blueprint $table) {
            $table->id();
            $table->string('nama')->unique(); // contoh: "CIEMAS", "CIRACAP"
            $table->string('kode_bps')->nullable();
            $table->unsignedInteger('urutan')->default(0); // urutan tampil sesuai urutan BPS
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kecamatans');
    }
};

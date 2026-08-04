<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabel master "tim"/role. Bukan role tunggal per user (lihat role_user pivot),
     * karena satu user (misal staf lintas seksi) bisa tergabung di lebih dari satu tim.
     *
     * slug dipakai di kode (middleware, pengecekan hasRole), nama dipakai untuk tampilan.
     */
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('nama');
            $table->string('slug')->unique();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};

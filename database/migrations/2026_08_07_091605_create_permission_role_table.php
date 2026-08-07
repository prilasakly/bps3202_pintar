<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pivot many-to-many permission <-> role. Satu permission bisa diberikan ke banyak
     * role, dan satu role bisa punya banyak permission. Inilah yang diubah lewat halaman
     * "Kelola Hak Akses" (checkbox role per permission) -- jadi "siapa boleh apa" berubah
     * cukup lewat UPDATE baris di tabel ini, tanpa ubah kode.
     */
    public function up(): void
    {
        Schema::create('permission_role', function (Blueprint $table) {
            $table->id();
            $table->foreignId('permission_id')->constrained('permissions')->cascadeOnDelete();
            $table->foreignId('role_id')->constrained('roles')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['permission_id', 'role_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('permission_role');
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subsidebars', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sidebar_id')->constrained('sidebars')->cascadeOnDelete();
            $table->string('nama'); // contoh: "KEPENDUDUKAN DAN MIGRASI", "PENDIDIKAN"
            $table->string('slug');
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['sidebar_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subsidebars');
    }
};

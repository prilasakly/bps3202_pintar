<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Mendefinisikan "bentuk kolom" tiap indikator, karena setiap file punya
        // breakdown kolom yang berbeda (negeri/swasta, laki-laki/perempuan, islam/protestan/dst).
        // Sekali didefinisikan saat upload pertama, dipakai lagi di tahun-tahun berikutnya.
        Schema::create('indikator_koloms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('indikator_id')->constrained('indikators')->cascadeOnDelete();
            $table->string('kolom_key'); // contoh: negeri, swasta, laki_laki, islam
            $table->string('kolom_label'); // contoh: "Negeri", "Laki-laki", "Islam"
            $table->string('induk_label')->nullable(); // header gabungan di atasnya, contoh: "Jumlah Guru MA"
            $table->unsignedInteger('urutan')->default(0);
            $table->timestamps();

            $table->unique(['indikator_id', 'kolom_key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('indikator_koloms');
    }
};

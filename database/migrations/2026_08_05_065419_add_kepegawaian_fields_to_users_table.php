<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom data kepegawaian tambahan untuk fitur "Kelola User" (khusus superadmin).
     * File TERPISAH dari migration users bawaan Laravel supaya `php artisan migrate`
     * tetap aman dijalankan di atas tabel users yang sudah ada datanya.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'nip_lama')) {
                $table->string('nip_lama')->nullable()->after('name');
            }

            if (! Schema::hasColumn('users', 'nip_baru')) {
                $table->string('nip_baru')->nullable()->after('nip_lama');
            }

            if (! Schema::hasColumn('users', 'golongan')) {
                $table->string('golongan')->nullable()->after('nip_baru');
            }

            if (! Schema::hasColumn('users', 'jabatan')) {
                $table->string('jabatan')->nullable()->after('golongan');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nip_lama', 'nip_baru', 'golongan', 'jabatan']);
        });
    }
};

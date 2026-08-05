<?php

use App\Http\Controllers\IndikatorController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\UploadController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IndikatorController::class, 'index'])->name('indikator.index');
Route::get('/buku-tamu', [PageController::class, 'bukuTamu'])->name('buku-tamu.index');
Route::get('/tautan-penting', [PageController::class, 'tautanPenting'])->name('tautan-penting.index');
Route::get('/kategori/{subsidebar}', [IndikatorController::class, 'bySubsidebar'])->name('indikator.subsidebar');
Route::get('/indikator/{indikator:slug}', [IndikatorController::class, 'show'])->name('indikator.show');
Route::get('/indikator/{indikator:slug}/export', [IndikatorController::class, 'export'])->name('indikator.export');

// Catatan: hapus periode & submit upload SUDAH PINDAH ke routes/api.php (DELETE
// /api/indikators/{slug}/periode/{id} & POST /api/upload) supaya RBAC-nya konsisten
// dengan mobile app. Route web di bawah ini cuma nampilin HALAMAN form-nya saja.
Route::get('/upload', [UploadController::class, 'create'])->name('upload.create');

// Halaman "Kelola User" cuma nampilin shell/UI-nya di sini (mirip pola /upload di atas).
// Data user, dan proses tambah/ubah/hapus, SEMUA lewat routes/api.php supaya RBAC-nya
// (lihat/tidak vs boleh ubah) konsisten dan bisa dipakai juga oleh mobile app nanti.
Route::get('/kelola-user', [UserController::class, 'index'])->name('users.index');

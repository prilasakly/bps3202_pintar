<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\IndikatorApiController;
use App\Http\Controllers\Api\UploadApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Middleware\EnsureHasRole;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth
|--------------------------------------------------------------------------
| Login dipakai bersama oleh modal login di web (fetch dari JS) dan mobile app.
| Tidak pakai session/CSRF -- murni token (Sanctum personal access token) yang
| dikirim lewat header "Authorization: Bearer <token>".
*/
Route::post('/login', [AuthApiController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthApiController::class, 'logout']);
    Route::get('/me', [AuthApiController::class, 'me']);
});

/*
|--------------------------------------------------------------------------
| Baca data (lihat)
|--------------------------------------------------------------------------
| Sengaja TANPA middleware auth -- guest (belum login) pun boleh lihat menu &
| data indikator. Ini yang dimaksud "semua bisa lihat" di RBAC.
*/
Route::get('/menu', [IndikatorApiController::class, 'menu']);
Route::get('/indikators/{indikator:slug}/periode', [IndikatorApiController::class, 'periode']);
Route::get('/indikators/{indikator:slug}/data', [IndikatorApiController::class, 'data']);

/*
|--------------------------------------------------------------------------
| Tulis/hapus data (upload, update, hapus)
|--------------------------------------------------------------------------
| WAJIB login DAN berperan "ipds". Role lain (sosial, distribusi, industri, umum,
| nerwilis, produksi) serta guest tetap read-only lewat grup route di atas.
*/
Route::middleware(['auth:sanctum', EnsureHasRole::class.':ipds'])->group(function () {
    Route::post('/upload', [UploadApiController::class, 'store']);
    Route::put('/indikators/{indikator:slug}', [IndikatorApiController::class, 'update']);
    Route::patch('/indikators/{indikator:slug}', [IndikatorApiController::class, 'update']);
    Route::delete('/indikators/{indikator:slug}', [IndikatorApiController::class, 'destroy']);
    Route::delete('/indikators/{indikator:slug}/periode/{periode}', [IndikatorApiController::class, 'hapusPeriode']);
});

/*
|--------------------------------------------------------------------------
| Kelola User
|--------------------------------------------------------------------------
| Lihat daftar user: WAJIB login, tapi role apa saja boleh (bukan cuma ipds/
| superadmin). Guest tetap ditolak karena tetap dibungkus auth:sanctum.
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserApiController::class, 'index']);
});

/*
| Tambah/ubah/hapus user: WAJIB login DAN berperan "superadmin". Role lain
| (termasuk ipds) tetap read-only untuk data user -- lihat grup route di atas.
*/
Route::middleware(['auth:sanctum', EnsureHasRole::class.':superadmin'])->group(function () {
    Route::post('/users', [UserApiController::class, 'store']);
    Route::put('/users/{user}', [UserApiController::class, 'update']);
    Route::patch('/users/{user}', [UserApiController::class, 'update']);
    Route::delete('/users/{user}', [UserApiController::class, 'destroy']);

    // Upload user batch pakai Excel + tombol download templatenya.
    Route::get('/users/template', [UserApiController::class, 'template']);
    Route::post('/users/import', [UserApiController::class, 'import']);
});
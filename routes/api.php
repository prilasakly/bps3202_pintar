<?php

use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\IndikatorApiController;
use App\Http\Controllers\Api\PermissionApiController;
use App\Http\Controllers\Api\SubsidebarApiController;
use App\Http\Controllers\Api\UploadApiController;
use App\Http\Controllers\Api\UserApiController;
use App\Http\Middleware\EnsureHasPermission;
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
| Kelola Data (kategori/subsidebar + indikator)
|--------------------------------------------------------------------------
| Lihat daftar (index): WAJIB login, role apa saja boleh -- dipakai halaman
| "Kelola Data" untuk menampilkan tabel sebelum user menekan tombol ubah.
|
| Tambah/ubah/hapus kategori & indikator, serta hapus periode: WAJIB login DAN
| punya permission "data.manage". Permission ini DIATUR DARI DATABASE lewat
| halaman "Kelola Hak Akses" (super admin), bukan hardcoded seperti role biasa --
| jadi role mana saja yang boleh (default: ipds, admin, superadmin) bisa diubah
| kapan saja tanpa ubah kode. Lihat App\Http\Middleware\EnsureHasPermission.
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/indikators', [IndikatorApiController::class, 'index']);
    Route::get('/subsidebars', [SubsidebarApiController::class, 'index']);
});

Route::middleware(['auth:sanctum', EnsureHasPermission::class.':data.manage'])->group(function () {
    Route::post('/indikators', [IndikatorApiController::class, 'store']);
    Route::put('/indikators/{indikator:slug}', [IndikatorApiController::class, 'update']);
    Route::patch('/indikators/{indikator:slug}', [IndikatorApiController::class, 'update']);
    Route::delete('/indikators/{indikator:slug}', [IndikatorApiController::class, 'destroy']);
    Route::delete('/indikators/{indikator:slug}/periode/{periode}', [IndikatorApiController::class, 'hapusPeriode']);

    Route::post('/subsidebars', [SubsidebarApiController::class, 'store']);
    Route::put('/subsidebars/{subsidebar}', [SubsidebarApiController::class, 'update']);
    Route::patch('/subsidebars/{subsidebar}', [SubsidebarApiController::class, 'update']);
    Route::delete('/subsidebars/{subsidebar}', [SubsidebarApiController::class, 'destroy']);
});

/*
|--------------------------------------------------------------------------
| Upload data (excel)
|--------------------------------------------------------------------------
| WAJIB login DAN punya permission "data.upload" (default: role ipds). Sama seperti
| "data.manage" di atas, ini juga diatur dari halaman "Kelola Hak Akses", bukan
| hardcoded di kode -- jadi siapa yang boleh upload bisa diubah dari web.
*/
Route::middleware(['auth:sanctum', EnsureHasPermission::class.':data.upload'])->group(function () {
    Route::post('/upload', [UploadApiController::class, 'store']);
});

/*
|--------------------------------------------------------------------------
| Kelola User
|--------------------------------------------------------------------------
| Lihat daftar user: WAJIB login, tapi role apa saja boleh (bukan cuma yang punya
| permission tertentu). Guest tetap ditolak karena tetap dibungkus auth:sanctum.
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/users', [UserApiController::class, 'index']);
});

/*
| Tambah/ubah/hapus user: WAJIB login DAN punya permission "users.manage" (default:
| role superadmin). Diatur dari halaman "Kelola Hak Akses" -- lihat catatan di atas.
*/
Route::middleware(['auth:sanctum', EnsureHasPermission::class.':users.manage'])->group(function () {
    Route::post('/users', [UserApiController::class, 'store']);
    Route::put('/users/{user}', [UserApiController::class, 'update']);
    Route::patch('/users/{user}', [UserApiController::class, 'update']);
    Route::delete('/users/{user}', [UserApiController::class, 'destroy']);

    // Upload user batch pakai Excel + tombol download templatenya.
    Route::get('/users/template', [UserApiController::class, 'template']);
    Route::post('/users/import', [UserApiController::class, 'import']);
});

/*
|--------------------------------------------------------------------------
| Kelola Hak Akses
|--------------------------------------------------------------------------
| SELURUH endpoint di sini KHUSUS role "superadmin" -- sengaja pakai EnsureHasRole
| (role tetap/hardcoded), BUKAN EnsureHasPermission, supaya halaman yang mengatur
| permission itu sendiri tidak bisa "mengunci diri sendiri" lewat kesalahan
| konfigurasi yang dibuat lewat halaman ini juga. Lihat PermissionApiController.
*/
Route::middleware(['auth:sanctum', EnsureHasRole::class.':superadmin'])->group(function () {
    Route::get('/permissions', [PermissionApiController::class, 'index']);
    Route::put('/permissions/{permission}', [PermissionApiController::class, 'update']);
});

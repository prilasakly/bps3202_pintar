<?php

use App\Http\Controllers\IndikatorController;
use App\Http\Controllers\UploadController;
use Illuminate\Support\Facades\Route;

Route::get('/', [IndikatorController::class, 'index'])->name('indikator.index');
Route::get('/kategori/{subsidebar}', [IndikatorController::class, 'bySubsidebar'])->name('indikator.subsidebar');
Route::get('/indikator/{indikator:slug}', [IndikatorController::class, 'show'])->name('indikator.show');

Route::get('/upload', [UploadController::class, 'create'])->name('upload.create');
Route::post('/upload', [UploadController::class, 'store'])->name('upload.store');

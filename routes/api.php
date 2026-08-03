<?php

use App\Http\Controllers\Api\IndikatorApiController;
use App\Http\Controllers\Api\UploadApiController;
use Illuminate\Support\Facades\Route;

Route::get('/menu', [IndikatorApiController::class, 'menu']);
Route::get('/indikators/{indikator:slug}/periode', [IndikatorApiController::class, 'periode']);
Route::get('/indikators/{indikator:slug}/data', [IndikatorApiController::class, 'data']);

Route::post('/upload', [UploadApiController::class, 'store']);

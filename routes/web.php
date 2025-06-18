<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\FileController;

Route::get('/', [FileController::class, 'dashboard'])->name('dashboard');
Route::get('/export', [FileController::class, 'index'])->name('upload.form');
Route::post('/upload', [FileController::class, 'upload'])->name('upload');
Route::get('/download/{filename}', [FileController::class, 'download'])->name('download');
Route::get('/generate-report/{filename}', [FileController::class, 'generateReport'])->name('generate.report');
Route::get('/show-report/{filename}', [FileController::class, 'showReport'])->name('show.report');


<?php

use App\Http\Controllers\FileController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::post('files/upload', [FileController::class, 'upload'])->name('file.upload');
    Route::delete('files/{id}', [FileController::class, 'remove'])->name('file.remove');
    Route::get('files/{id}', [FileController::class, 'show'])->name('file.show');
});

<?php

use App\Http\Controllers\ClassroomController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
    Route::post('classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');
    Route::put('classrooms/{classroom}', [ClassroomController::class, 'update'])->name('classrooms.update');
    Route::delete('classrooms/{classroom}', [ClassroomController::class, 'destroy'])->name('classrooms.destroy');
    Route::post('classrooms/{classroom}/publish', [ClassroomController::class, 'publish'])->name('classrooms.publish');
    Route::post('classrooms/{classroom}/unpublish', [ClassroomController::class, 'unpublish'])->name('classrooms.unpublish');
});

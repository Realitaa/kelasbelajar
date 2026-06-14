<?php

use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\ClassroomDiscoveryController;
use App\Http\Controllers\ClassroomDiscussionController;
use App\Http\Controllers\ClassroomLearningContentController;
use App\Http\Controllers\ClassroomModuleController;
use App\Http\Controllers\ClassroomQuizController;
use App\Http\Controllers\ModuleObjectController;
use App\Http\Controllers\StudentQuizController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->group(function () {
    Route::get('discovery', [ClassroomDiscoveryController::class, 'index'])->name('classrooms.discovery');
    Route::post('classrooms/{classroom:slug}/enroll', [ClassroomDiscoveryController::class, 'enroll'])->name('classrooms.enroll');

    Route::get('classrooms', [ClassroomController::class, 'index'])->name('classrooms.index');
    Route::get('classrooms/{classroom:slug}', [ClassroomController::class, 'show'])->name('classrooms.show');

    // Discussion Forum
    Route::get('classrooms/{classroom:slug}/discussion', [ClassroomDiscussionController::class, 'index'])->name('classrooms.discussion.index');
    Route::post('classrooms/{classroom:slug}/discussion/comments', [ClassroomDiscussionController::class, 'storeComment'])->name('classrooms.discussion.comments.store');
    Route::put('classrooms/{classroom:slug}/discussion/comments/{comment}', [ClassroomDiscussionController::class, 'updateComment'])->name('classrooms.discussion.comments.update');
    Route::delete('classrooms/{classroom:slug}/discussion/comments/{comment}', [ClassroomDiscussionController::class, 'destroyComment'])->name('classrooms.discussion.comments.destroy');

    Route::post('classrooms', [ClassroomController::class, 'store'])->name('classrooms.store');
    Route::put('classrooms/{classroom}', [ClassroomController::class, 'update'])->name('classrooms.update');
    Route::delete('classrooms/{classroom}', [ClassroomController::class, 'destroy'])->name('classrooms.destroy');
    Route::post('classrooms/{classroom}/publish', [ClassroomController::class, 'publish'])->name('classrooms.publish');
    Route::post('classrooms/{classroom}/unpublish', [ClassroomController::class, 'unpublish'])->name('classrooms.unpublish');
    Route::get('classrooms/{classroom:slug}/manage', [ClassroomController::class, 'manage'])->name('classrooms.manage');
    Route::get('classrooms/{classroom:slug}/students', [ClassroomController::class, 'students'])->name('classrooms.students');

    Route::post('classrooms/{classroom:slug}/modules', [ClassroomModuleController::class, 'store'])->name('classrooms.modules.store');
    Route::put('classrooms/{classroom:slug}/modules/{module}', [ClassroomModuleController::class, 'update'])->name('classrooms.modules.update');
    Route::delete('classrooms/{classroom:slug}/modules/{module}', [ClassroomModuleController::class, 'destroy'])->name('classrooms.modules.destroy');
    Route::post('classrooms/{classroom:slug}/modules/reorder', [ClassroomModuleController::class, 'reorder'])->name('classrooms.modules.reorder');
    Route::post('classrooms/{classroom:slug}/objects/reorder', [ClassroomModuleController::class, 'reorderObjects'])->name('classrooms.objects.reorder');

    Route::post('classrooms/{classroom:slug}/manage/modules/{module}/objects', [ModuleObjectController::class, 'store'])->name('classrooms.objects.store');
    Route::put('classrooms/{classroom:slug}/manage/objects/{object}', [ModuleObjectController::class, 'update'])->name('classrooms.objects.update');
    Route::delete('classrooms/{classroom:slug}/manage/objects/{object}', [ModuleObjectController::class, 'destroy'])->name('classrooms.objects.destroy');

    Route::get('classrooms/{classroom:slug}/learning-contents/{learningContent}', [ClassroomLearningContentController::class, 'show'])->name('classrooms.learning-contents.show');
    Route::put('classrooms/{classroom:slug}/learning-contents/{learningContent}/content', [ClassroomLearningContentController::class, 'updateContent'])->name('classrooms.learning-contents.update-content');

    Route::get('classrooms/{classroom:slug}/quizzes/{quiz}', [ClassroomQuizController::class, 'show'])->name('classrooms.quizzes.show');
    Route::put('classrooms/{classroom:slug}/quizzes/{quiz}/questions', [ClassroomQuizController::class, 'updateQuestions'])->name('classrooms.quizzes.update-questions');

    // Quiz Taking
    Route::post('classrooms/{classroom:slug}/quizzes/{quiz}/start', [StudentQuizController::class, 'start'])->name('classrooms.quizzes.start');
    Route::get('quizzes/{session}/take', [StudentQuizController::class, 'take'])->name('quizzes.take');
    Route::get('quizzes/{session}/questions/{index}', [StudentQuizController::class, 'getQuestion'])->name('quizzes.question');
    Route::post('quizzes/{session}/answer', [StudentQuizController::class, 'saveAnswer'])->name('quizzes.answer');
    Route::post('quizzes/{session}/submit', [StudentQuizController::class, 'submit'])->name('quizzes.submit');
});

<?php

use App\Http\Controllers\SubpartLessonController;

/**
 * Add these alongside the existing module.subparts.* routes in routes/web.php.
 * Follows the exact same naming/prefix convention already used there.
 */

// --- Teacher (manage lessons within a sub-part) ---
Route::get('/subparts/{subpart}/lessons', [SubpartLessonController::class, 'index'])
    ->name('subpart.lessons.index');

Route::post('/subparts/{subpart}/lessons', [SubpartLessonController::class, 'store'])
    ->name('subpart.lessons.store');

Route::put('/lessons/{lesson}', [SubpartLessonController::class, 'update'])
    ->name('subpart.lessons.update');

Route::post('/subparts/{subpart}/lessons/reorder', [SubpartLessonController::class, 'reorder'])
    ->name('subpart.lessons.reorder');

Route::delete('/lessons/{lesson}', [SubpartLessonController::class, 'destroy'])
    ->name('subpart.lessons.destroy');

// --- Student (view lessons + track progress) ---
Route::get('/subparts/{subpart}/lessons/student', [SubpartLessonController::class, 'studentIndex'])
    ->name('subpart.lessons.student.index');

Route::post('/lessons/{lesson}/progress', [SubpartLessonController::class, 'updateProgress'])
    ->name('subpart.lessons.progress.update');

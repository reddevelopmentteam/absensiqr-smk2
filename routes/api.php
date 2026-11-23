<?php

use App\Http\Controllers\Api\V1\Library\LibraryClassController;
use App\Http\Controllers\Api\V1\Library\LibraryStudentController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| SKENDA Library Integration API
|--------------------------------------------------------------------------
| Read-only API untuk SKENDA Library. Semua endpoint hanya menerima GET
| dan wajib memakai token Sanctum dengan ability "library-read".
|
*/

Route::prefix('v1/library')
    ->name('api.v1.library.')
    ->middleware(['auth:sanctum', 'abilities:library-read', 'throttle:library-api'])
    ->group(function () {
        // Rute spesifik harus didaftarkan sebelum {student} agar tidak tertangkap.
        Route::get('/students/changes', [LibraryStudentController::class, 'changes'])
            ->name('students.changes');
        Route::get('/students/by-nis/{nis}', [LibraryStudentController::class, 'byNis'])
            ->name('students.by-nis');
        Route::get('/students/by-qr/{token}', [LibraryStudentController::class, 'byQr'])
            ->name('students.by-qr');

        Route::get('/students/{student}', [LibraryStudentController::class, 'show'])
            ->name('students.show');
        Route::get('/students', [LibraryStudentController::class, 'index'])
            ->name('students.index');

        Route::get('/classes', [LibraryClassController::class, 'index'])
            ->name('classes.index');
    });

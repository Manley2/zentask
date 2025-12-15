<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use App\Http\Controllers\CalendarController;

Route::get('/', function () {
    // arahkan ke form register biasa, tanpa pakai nama route
    return redirect('/register');
});

/* =========================================================
 | [A] ROUTE YANG BUTUH LOGIN + VERIFIED
 ========================================================= */
Route::middleware(['auth', 'verified'])->group(function () {

    /* -------------------------
     | [A1] DASHBOARD
     ------------------------- */
    Route::get('/dashboard', [TaskController::class, 'index'])
        ->name('dashboard');

    /* -------------------------
     | [A2] TASKS (CRUD)
     ------------------------- */
    Route::get('/tasks', [TaskController::class, 'index'])->name('tasks.index');
    Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');

    // Edit task
    Route::get('/tasks/{task}/edit', [TaskController::class, 'edit'])->name('tasks.edit');

    // Update task
    Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');

    // Delete task
    Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');

    /* -------------------------
     | [A3] CALENDAR (TERINTEGRASI)
     ------------------------- */
    Route::get('/calendar', [CalendarController::class, 'index'])
        ->name('calendar.index');
});

/* =========================================================
 | [B] ROUTE PROFILE (default Breeze)
 ========================================================= */
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

/* =========================================================
 | [C] ROUTE AUTH (login, register, dll)
 ========================================================= */
require __DIR__ . '/auth.php';

<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\TaskController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // arahkan ke form register biasa, tanpa pakai nama route
    return redirect('/register');
});

// ROUTE YANG BUTUH LOGIN
Route::middleware(['auth', 'verified'])->group(function () {
    // Dashboard pakai TaskController (kalau belum ada, sementara bisa view biasa)
    Route::get('/dashboard', [TaskController::class, 'index'])
        ->name('dashboard');
});

// ROUTE PROFILE (default Breeze)
Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ROUTE AUTH (login, register, dll)

Route::post('/tasks', [TaskController::class, 'store'])->name('tasks.store');

Route::put('/tasks/{task}', [TaskController::class, 'update'])->name('tasks.update');

Route::delete('/tasks/{task}', [TaskController::class, 'destroy'])->name('tasks.destroy');
require __DIR__.'/auth.php';

<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HistoryController;
use App\Http\Controllers\MaterialController;
use App\Http\Controllers\TrioLabController;
use Illuminate\Support\Facades\Route;

// Halaman utama
Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

// Login dan Register
Route::middleware('guest')->group(function () {

    Route::get('/login', [AuthController::class, 'showLogin'])
        ->name('login');

    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:10,1')
        ->name('login.process');

    Route::get('/register', [AuthController::class, 'showRegister'])
        ->name('register');

    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:5,1')
        ->name('register.process');

});

// Halaman yang membutuhkan login
Route::middleware('auth')->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    Route::get('/riwayat', [HistoryController::class, 'index'])
        ->name('riwayat');

    Route::get('/akun', [HistoryController::class, 'akun'])
        ->name('akun');

    Route::get('/lab', [TrioLabController::class, 'index'])
        ->name('lab.index');

    Route::post('/lab/run', [TrioLabController::class, 'run'])
        ->name('lab.run');

    Route::post('/lab/trio', [TrioLabController::class, 'trio'])
        ->name('lab.trio');

    Route::post('/lab', [TrioLabController::class, 'proses'])
        ->name('lab.proses');

    Route::post('/logout', [AuthController::class, 'logout'])
        ->name('logout');

});

// Halaman Materi Kriptografi
Route::get('/materials', [MaterialController::class, 'index'])
    ->name('materials.index');

Route::get('/materials/kategori/{category}', [MaterialController::class, 'category'])
    ->name('materials.category');

Route::get('/materials/{slug}', [MaterialController::class, 'show'])
    ->name('materials.show');

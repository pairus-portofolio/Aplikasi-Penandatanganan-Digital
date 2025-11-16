<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\Tu\DocumentController;

// Halaman utama diarahkan ke login
Route::get('/', function () {
    return view('auth.login');
});

// Login routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Google Login
Route::get('/auth/google/redirect', [GoogleLoginController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleLoginController::class, 'handleGoogleCallback'])->name('google.callback');

// Dashboard
Route::get('/dashboard', function () {return view('dashboard.index');})->middleware('auth')->name('dashboard');

// TU
Route::middleware(['auth'])->group(function () {
    // Halaman upload surat TU
    Route::get('/Tu/upload', [DocumentController::class, 'create'])
        ->name('tu.upload.create');

    // Proses upload surat
    Route::post('/Tu/upload', [DocumentController::class, 'store'])
        ->name('tu.upload.store');
});

<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\KaprodiController; 

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
Route::get('/dashboard', function () {
    return view('dashboard.index');
})->middleware('auth')->name('dashboard');


// semua rute role di sini
Route::middleware('auth')->group(function () {

    Route::controller(KaprodiController::class)->group(function () {
        Route::get('/review-surat', 'showReviewSurat')->name('kaprodi.review');
        Route::get('/paraf-surat', 'showParafSurat')->name('kaprodi.paraf');
    });
});
<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\Tu\DocumentController;
use App\Http\Controllers\Kaprodi\ReviewController; 
use App\Http\Controllers\Kaprodi\ParafController;
use App\Http\Controllers\Kajur_Sekjur\TandatanganController;

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

// TU
Route::middleware(['auth'])->group(function () {
    // Halaman upload surat TU
    Route::get('/tu/upload', [DocumentController::class, 'create'])->name('tu.upload.create');

    // Proses upload surat
    Route::post('/tu/upload', [DocumentController::class, 'store'])->name('tu.upload.store');
});

// Kaprodi D3 & D4
Route::middleware('auth')->group(function () {
    
    // Route untuk Review Surat
    Route::get('/review-surat', [ReviewController::class, 'index'])->name('kaprodi.review');

    // Route untuk Paraf Surat
    Route::get('/paraf-surat', [ParafController::class, 'index'])->name('kaprodi.paraf');

});

// Kajur & Sekjur
Route::middleware('auth')->group(function () {
    
    // Route Tanda Tangan
    Route::get('/tandatangan-surat', [TandatanganController::class, 'index'])->name('kajur.tandatangan'); 
});
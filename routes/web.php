<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\Dashboard\CardsController;
use App\Http\Controllers\Dashboard\TableController;
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
Route::get('/dashboard', [CardsController::class, 'index'])->middleware('auth')->name('dashboard');
Route::get('/dashboard/table', [TableController::class, 'index'])->middleware('auth')->name('dashboard.table');

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
    
    // --- REVIEW SURAT ---
    // 1. Menampilkan Tabel Daftar Surat (Fitur)
    Route::get('/review-surat', [ReviewController::class, 'index'])->name('kaprodi.review');
    
    // 2. Menampilkan Halaman Proses Review (Detail)
    Route::get('/review-surat/{id}', [ReviewController::class, 'show'])->name('kaprodi.review.show');


    // --- PARAF SURAT  ---
    // 1. Tabel Daftar
    Route::get('/paraf-surat', [ParafController::class, 'index'])->name('kaprodi.paraf');

    // 2. Halaman Detail (Tambahkan parameter {id})
    Route::get('/paraf-surat/{id}', [ParafController::class, 'show'])->name('kaprodi.paraf.show');
});

// Kajur & Sekjur
Route::middleware('auth')->group(function () {
    
    // Route Tanda Tangan
    Route::get('/tandatangan-surat', [TandatanganController::class, 'index'])->name('kajur.tandatangan'); 
});
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
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');        // ← WAJIB DITAMBAHKAN
Route::get('/login', [AuthController::class, 'showLogin'])->name('auth.login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Google Login
Route::get('/auth/google/redirect', [GoogleLoginController::class, 'redirectToGoogle'])->name('google.redirect');
Route::get('/auth/google/callback', [GoogleLoginController::class, 'handleGoogleCallback'])->name('google.callback');

// Dashboard
Route::get('/dashboard', [CardsController::class, 'index'])->middleware('auth')->name('dashboard');
Route::get('/dashboard/table', [TableController::class, 'index'])->middleware('auth')->name('dashboard.table');

// TU
Route::middleware(['auth'])->group(function () {
    Route::get('/tu/upload', [DocumentController::class, 'create'])->name('tu.upload.create');
    Route::post('/tu/upload', [DocumentController::class, 'store'])->name('tu.upload.store');
});

// Kaprodi
Route::middleware('auth')->group(function () {
    Route::get('/review-surat', [ReviewController::class, 'index'])->name('kaprodi.review');
    Route::get('/review-surat/{id}', [ReviewController::class, 'show'])->name('kaprodi.review.show');

    Route::get('/paraf-surat', [ParafController::class, 'index'])->name('kaprodi.paraf');
    Route::get('/paraf-surat/{id}', [ParafController::class, 'show'])->name('kaprodi.paraf.show');
   
    // 1. Route Download Document
    Route::get('/document/download/{document}', [DocumentController::class, 'download'])->name('document.download');

    // 2. Route Submit Paraf
    Route::post('/paraf-surat/{id}/submit', [ParafController::class, 'submit'])->name('kaprodi.paraf.submit');

    // 3. Route untuk Upload Paraf via AJAX
    Route::post('/kaprodi/paraf/upload', [ParafController::class, 'uploadParaf'])->name('kaprodi.paraf.upload');

    // 4. Route Hapus 
    Route::post('/kaprodi/paraf/delete', [ParafController::class, 'deleteParaf'])->name('kaprodi.paraf.delete');
});

// Kajur & Sekjur
Route::middleware('auth')->group(function () {

    Route::get('/tandatangan-surat', [TandatanganController::class, 'index'])->name('kajur.tandatangan');
    Route::get('/tandatangan-surat/{id}', [TandatanganController::class, 'show'])->name('kajur.tandatangan.show');

    Route::post('/tandatangan-surat/{id}/submit', [TandatanganController::class, 'submit'])
        ->name('kajur.tandatangan.submit');
});

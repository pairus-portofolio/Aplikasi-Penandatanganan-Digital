<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Storage;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\GoogleLoginController;
use App\Http\Controllers\Dashboard\CardsController;
use App\Http\Controllers\Dashboard\TableController;
use App\Http\Controllers\Tu\DocumentController;
use App\Http\Controllers\Tu\FinalisasiController;
use App\Http\Controllers\Kaprodi\ReviewController;
use App\Http\Controllers\Kaprodi\ParafController;
use App\Http\Controllers\Kajur_Sekjur\TandatanganController;

// Halaman utama diarahkan ke login
Route::get('/', function () {
    return view('auth.login');
});

// Login routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
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

    // Upload
    Route::get('/tu/upload', [DocumentController::class, 'create'])->name('tu.upload.create');
    Route::post('/tu/upload', [DocumentController::class, 'store'])->name('tu.upload.store');
    Route::put('/tu/document/{id}/revisi', [DocumentController::class, 'updateRevision'])->name('tu.document.revisi');

    // Finalisasi TU
    Route::prefix('tu/finalisasi')->name('tu.finalisasi.')->group(function() {

        // daftar finalisasi
        Route::get('/', [FinalisasiController::class, 'index'])->name('index');

        // detail finalisasi (show)
        Route::get('/{id}', [FinalisasiController::class, 'show'])->name('show');

        // submit finalisasi (post)
        Route::post('/{id}', [FinalisasiController::class, 'store'])->name('store');

        // preview PDF (private) - gunakan model binding Document
        Route::get('/{id}/preview', [DocumentController::class, 'preview']) // Ganti {document} ke {id}
            ->name('preview');

        // download PDF (private)
        Route::get('/{document}/download', [DocumentController::class, 'download'])
            ->name('download');
    });

});

// Kaprodi
Route::middleware('auth')->group(function () {

    // REVIEW
    Route::get('/review-surat', [ReviewController::class, 'index'])
        ->name('kaprodi.review.index');
    Route::get('/review-surat/{id}', [ReviewController::class, 'show'])
        ->name('kaprodi.review.show');
    Route::post('/review-surat/{id}/revise', [ReviewController::class, 'revise'])
        ->name('kaprodi.review.revise');

    // PARAF
    Route::get('/paraf-surat', [ParafController::class, 'index'])
        ->name('kaprodi.paraf.index');
    Route::get('/paraf-surat/{id}', [ParafController::class, 'show'])
        ->name('kaprodi.paraf.show');

    // SAVE PARAF
    Route::post('/paraf-surat/{id}/save-paraf', [ParafController::class, 'saveParaf'])
        ->name('kaprodi.paraf.save');

    Route::post('/paraf-surat/{id}/submit', [ParafController::class, 'submit'])
        ->name('kaprodi.paraf.submit');

    // Upload / Delete Paraf
    Route::post('/kaprodi/paraf/upload', [ParafController::class, 'uploadParaf'])
        ->name('kaprodi.paraf.upload');
    Route::post('/kaprodi/paraf/delete', [ParafController::class, 'deleteParaf'])
        ->name('kaprodi.paraf.delete');

    // DOWNLOAD
    Route::get('/document/download/{document}', [DocumentController::class, 'download'])
        ->name('document.download');
});


// Kajur & Sekjur
Route::middleware('auth')->group(function () {

    Route::get('/tandatangan-surat', [TandatanganController::class, 'index'])
        ->name('kajur.tandatangan.index');

    Route::get('/tandatangan-surat/{id}', [TandatanganController::class, 'show'])
        ->name('kajur.tandatangan.show');

    Route::post('/tandatangan-surat/{id}/submit', [TandatanganController::class, 'submit'])
        ->name('kajur.tandatangan.submit');

    // SAVE TTD POSITION
    Route::post('/tandatangan-surat/{id}/save', [TandatanganController::class, 'saveTandatangan'])
        ->name('kajur.tandatangan.save');

    // Upload / Delete Tanda Tangan
    Route::post('/kajur/tandatangan/upload', [TandatanganController::class, 'uploadTandatangan'])
        ->name('kajur.tandatangan.upload');
    Route::post('/kajur/tandatangan/delete', [TandatanganController::class, 'deleteTandatangan'])
        ->name('kajur.tandatangan.delete');
});


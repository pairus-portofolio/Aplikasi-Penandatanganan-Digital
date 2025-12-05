<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ApiAuthController;
use App\Http\Controllers\Api\ApiDocumentController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
| URL Base: http://127.0.0.1:8000/api/...
*/

// 1. Login
Route::post('/login', [ApiAuthController::class, 'login']);

// 2. Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // Logout
    Route::post('/logout', [ApiAuthController::class, 'logout']);

    Route::get('/user', [ApiAuthController::class, 'me']);

    // Dashboard - List Semua Surat
    Route::get('/documents', [ApiDocumentController::class, 'index']);

    // Upload-surat
    Route::post('/upload-surat', [ApiDocumentController::class, 'store']);

    // Arsip Surat (List yang sudah selesai)
    Route::get('/documents/archive', [ApiDocumentController::class, 'archive']);

    Route::get('/documents/{id}/status', [ApiDocumentController::class, 'checkStatus']);
});
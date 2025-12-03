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

// 1. Login (Langsung di root api)
// URL JADI: POST /api/login
Route::post('/login', [ApiAuthController::class, 'login']);

// 2. Protected Routes
Route::middleware('auth:sanctum')->group(function () {
    
    // URL JADI: POST /api/logout
    Route::post('/logout', [ApiAuthController::class, 'logout']);

    // URL JADI: GET /api/user
    Route::get('/user', [ApiAuthController::class, 'me']);

    // URL JADI: POST /api/upload-surat
    Route::post('/upload-surat', [ApiDocumentController::class, 'store']);

    // URL JADI: GET /api/documents/{id}/status
    Route::get('/documents/{id}/status', [ApiDocumentController::class, 'checkStatus']);
});
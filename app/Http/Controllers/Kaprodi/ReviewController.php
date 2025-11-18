<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    // Menampilkan halaman review surat untuk Kaprodi
    public function index()
    {
        return view('kaprodi.review-surat');
    }
}
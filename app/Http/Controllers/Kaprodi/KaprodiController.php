<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class KaprodiController extends Controller
{
    // Menampilkan halaman Review Surat
    public function showReviewSurat()
    {
        return view('kaprodi.review-surat');
    }

    // Menampilkan halaman Paraf Surat
    public function showParafSurat()
    {
        return view('kaprodi.paraf-surat');
    }
}

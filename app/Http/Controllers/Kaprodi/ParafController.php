<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ParafController extends Controller
{
    // Menampilkan halaman paraf surat untuk Kaprodi
    public function index()
    {
        return view('kaprodi.paraf-surat');
    }
}
<?php

namespace App\Http\Controllers\Kajur_Sekjur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TandatanganController extends Controller
{
    // Menampilkan halaman tanda tangan surat untuk Kajur/Sekjur
    public function index()
    {
        return view('kajur_sekjur.tandatangan-surat');
    }
}

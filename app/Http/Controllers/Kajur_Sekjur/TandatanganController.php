<?php

namespace App\Http\Controllers\Kajur_Sekjur;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class TandatanganController extends Controller
{
    public function index()
    {
        // Di sini nanti Anda bisa mengambil data surat dari DB
        return view('kajur_sekjur.tandatangan-surat');
    }
}
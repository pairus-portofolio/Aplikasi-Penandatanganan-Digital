<?php

namespace App\Http\Controllers\Kaprodi;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ParafController extends Controller
{
    public function index()
    {
        return view('kaprodi.paraf-surat');
    }
}
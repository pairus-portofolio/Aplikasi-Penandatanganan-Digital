<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Carbon;

class GoogleLoginController extends Controller
{
    // Mengarahkan user ke halaman login Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // Menangani data yang dikirim Google setelah login
    public function handleGoogleCallback()
    {
        try {
            // Ambil data user dari akun Google
            $googleUser = Socialite::driver('google')->user();

            // Cari user di database berdasarkan email
            $user = User::where('email', $googleUser->getEmail())->first();

            // Jika user ditemukan
            if ($user) {
                // Simpan ID Google ke kolom google_id
                $user->google_id = $googleUser->getId();

                // Jika email belum diverifikasi, isi tanggal verifikasinya
                if (empty($user->email_verified_at)) {
                    $user->email_verified_at = Carbon::now();
                }

                $user->save();

                // Login-kan user dan arahkan ke dashboard
                Auth::login($user);
                return redirect()->intended('/dashboard');
            } else {
                // Jika user tidak terdaftar
                return redirect('/')->with('error', 'Akun Google Anda tidak terdaftar di sistem kami.');
            }

        } catch (Exception $e) {
            // Jika terjadi error saat login
            return redirect('/')->with('error', 'Gagal login dengan Google. Coba lagi.');
        }
    }
}

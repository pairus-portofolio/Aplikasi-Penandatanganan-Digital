<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Carbon;
use App\Enums\RoleEnum;

class GoogleLoginController extends Controller
{
    // Mengarahkan user ke halaman login Google
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    // Menangani callback setelah user login melalui Google
    public function handleGoogleCallback()
    {
        try {
            // Mengambil data pengguna dari Google
            $googleUser = Socialite::driver('google')->user();

            // Mencari user berdasarkan email yang diberikan Google
            $user = User::where('email', $googleUser->getEmail())->first();

            // Login jika user ditemukan
            if ($user) {

                // Simpan google_id untuk menyambungkan akun
                $user->google_id = $googleUser->getId();

                // Verifikasi email jika belum diverifikasi
                if (empty($user->email_verified_at)) {
                    $user->email_verified_at = Carbon::now();
                }

                // Simpan perubahan user
                $user->save();

                // Login user ke sistem
                Auth::login($user);
                if ($user->role_id == RoleEnum::ID_ADMIN) {
                    return redirect()->intended(route('admin.users.index')); // Redirect ke Manajemen Pengguna
                }
                return redirect()->intended('/dashboard');
            } else {

                // Email Google tidak terdaftar di sistem
                return redirect('/')->with('error', 'Email tidak valid');
            }

        } catch (Exception $e) {

            // Menangani error ketika proses login Google gagal
            return redirect('/')->with('error', 'Gagal login dengan Google. Coba lagi.');
        }
    }
}

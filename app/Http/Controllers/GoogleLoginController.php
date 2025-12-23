<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Exception;
use Illuminate\Support\Carbon;
use App\Enums\RoleEnum;

/**
 * Controller untuk mengelola autentikasi menggunakan Google OAuth.
 *
 * Menangani redirect ke Google, callback setelah login, dan verifikasi email otomatis.
 *
 * @package App\Http\Controllers
 */
class GoogleLoginController extends Controller
{
    /**
     * Redirect user ke halaman login Google.
     *
     * @return \Symfony\Component\HttpFoundation\RedirectResponse
     */
    public function redirectToGoogle()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Tangani callback setelah user login melalui Google.
     *
     * Proses:
     * 1. Ambil data user dari Google
     * 2. Cari user berdasarkan email
     * 3. Simpan google_id dan verifikasi email
     * 4. Login user ke sistem
     *
     * @return \Illuminate\Http\RedirectResponse
     */
    public function handleGoogleCallback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();

            $user = User::where('email', $googleUser->getEmail())->first();

            if ($user) {

                $user->google_id = $googleUser->getId();

                if (empty($user->email_verified_at)) {
                    $user->email_verified_at = Carbon::now();
                }

                $user->save();

                Auth::login($user);
                if ($user->role_id == RoleEnum::ID_ADMIN) {
                    return redirect()->intended(route('admin.users.index')); // Redirect ke Manajemen Pengguna
                }
                return redirect()->intended('/dashboard');
            } else {

                return redirect('/')->with('error', 'Email tidak valid');
            }

        } catch (Exception $e) {

            return redirect('/')->with('error', 'Gagal login dengan Google. Coba lagi.');
        }
    }
}

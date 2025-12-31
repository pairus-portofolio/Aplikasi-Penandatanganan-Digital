<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use App\Enums\RoleEnum;

/**
 * Controller untuk mengelola autentikasi user.
 *
 * Menangani login, logout, dan migrasi otomatis password plaintext ke hash.
 *
 * @package App\Http\Controllers
 */
class AuthController extends Controller
{
    /**
     * Tampilkan halaman login.
     *
     * @return \Illuminate\View\View
     */
    public function showLogin()
    {
        return view('auth.login');
    }
    /**
     * Proses login pengguna dengan kredensial email dan password.
     *
     * Mendukung migrasi otomatis password plaintext ke hash.
     *
     * @param Request $request HTTP request dengan email dan password
     * @return \Illuminate\Http\RedirectResponse
     */
    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'string'],
            'password' => ['required'],
        ]);

        $loginField = 'email';

        $attempt = Auth::attempt(
            ['email' => $credentials['email'], 'password' => $credentials['password']],
            $request->filled('remember')
        );

        if ($attempt) {
            $request->session()->regenerate();
            if (Auth::user()->role_id == RoleEnum::ID_ADMIN) {
                return redirect()->intended(route('admin.users.index')); 
            }
            return redirect()->intended(route('dashboard'));
        }
        $user = User::where('email', $credentials['email'])->first();

        if ($user && $user->password === $credentials['password']) {
            $user->password = $credentials['password'];
            $user->save();

            $attempt = Auth::attempt(
                ['email' => $credentials['email'], 'password' => $credentials['password']],
                $request->filled('remember')
            );

            if ($attempt) {
                $request->session()->regenerate();
                if (Auth::user()->role_id == RoleEnum::ID_ADMIN) {
                    return redirect()->intended(route('admin.users.index')); 
                }
                return redirect()->intended(route('dashboard'));
            }
        }
        if (!$user) {
            $error = 'Email tidak terdaftar';
        } else {
            $error = 'Password yang Anda masukkan salah.';
        }

        return back()
            ->withInput($request->only('email'))
            ->with('error', $error);
    }

    /**
     * Logout pengguna dan mengakhiri sesi.
     *
     * @param Request $request HTTP request
     * @return \\Illuminate\\Http\\RedirectResponse
     */
    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('auth.login');
    }
}
